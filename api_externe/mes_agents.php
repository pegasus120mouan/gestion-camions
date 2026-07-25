<?php

declare(strict_types=1);

/**
 * API: mes_agents.php
 * Agents rattachés au chef d'équipe connecté.
 * GET ?token=BAEB3101&search=nom&page=1
 * GET ?id_chef=12&search=nom&page=1
 * GET ?hors_pgf=1&search=nom&page=1  → tous les agents hors groupe/chef PGF
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function jsonOut(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizeChef(array $row): array
{
    $nom = trim((string) ($row['nom'] ?? ''));
    $prenoms = trim((string) ($row['prenoms'] ?? ''));

    return [
        'id_chef' => (int) ($row['id_chef'] ?? 0),
        'nom' => $nom,
        'prenoms' => $prenoms,
        'nom_complet' => trim($nom . ' ' . $prenoms),
        'token' => (string) ($row['token'] ?? ''),
        'login' => (string) ($row['login'] ?? ''),
    ];
}

function normalizeAgent(array $row): array
{
    $nom = trim((string) ($row['nom'] ?? ''));
    $prenom = trim((string) ($row['prenom'] ?? ''));

    return [
        'id_agent' => (int) ($row['id_agent'] ?? 0),
        'numero_agent' => (string) ($row['numero_agent'] ?? ''),
        'nom' => $nom,
        'prenom' => $prenom,
        'nom_complet' => trim($nom . ' ' . $prenom),
        'contact' => (string) ($row['contact'] ?? ''),
        'date_ajout' => $row['date_ajout'] ?? null,
        'id_chef' => (int) ($row['id_chef'] ?? 0),
        'chef_equipe' => normalizeChef([
            'id_chef' => $row['id_chef'] ?? 0,
            'nom' => $row['chef_nom'] ?? '',
            'prenoms' => $row['chef_prenoms'] ?? '',
            'token' => $row['chef_token'] ?? '',
            'login' => $row['chef_login'] ?? '',
        ]),
    ];
}

function isTruthy(mixed $value): bool
{
    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
}

try {
    require __DIR__ . '/connexion.php';

    if (!isset($conn) || !($conn instanceof PDO)) {
        jsonOut(500, ['success' => false, 'error' => 'Connexion PDO indisponible.']);
    }

    $token = trim((string) ($_GET['token'] ?? ''));
    $idChef = (int) ($_GET['id_chef'] ?? 0);
    $search = trim((string) ($_GET['search'] ?? ''));
    $horsPgf = isTruthy($_GET['hors_pgf'] ?? '');
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 15)));

    if (! $horsPgf && $token === '' && $idChef <= 0) {
        jsonOut(422, [
            'success' => false,
            'error' => 'Le paramètre token ou id_chef est requis.',
        ]);
    }

    $where = ['a.date_suppression IS NULL'];
    $bindings = [];

    if ($horsPgf) {
        // Exclure le groupe / chef PGF (login pgf ou nom contenant PGF).
        $where[] = "(LOWER(TRIM(COALESCE(ce.login, ''))) <> 'pgf'
            AND CONCAT(COALESCE(ce.nom, ''), ' ', COALESCE(ce.prenoms, '')) NOT LIKE :hors_pgf_nom)";
        $bindings['hors_pgf_nom'] = '%PGF%';

        if ($idChef > 0) {
            $where[] = 'a.id_chef = :id_chef';
            $bindings['id_chef'] = $idChef;
        }
    } elseif ($token !== '') {
        $where[] = 'ce.token = :token';
        $bindings['token'] = $token;
    } else {
        $where[] = 'a.id_chef = :id_chef';
        $bindings['id_chef'] = $idChef;
    }

    if ($search !== '') {
        $where[] = '(a.nom LIKE :search OR a.prenom LIKE :search2 OR a.numero_agent LIKE :search3 OR CONCAT(a.nom, \' \', a.prenom) LIKE :search4)';
        $bindings['search'] = '%' . $search . '%';
        $bindings['search2'] = '%' . $search . '%';
        $bindings['search3'] = '%' . $search . '%';
        $bindings['search4'] = '%' . $search . '%';
    }

    $whereSql = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) AS total
        FROM agents a
        INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
        WHERE {$whereSql}";

    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($bindings);
    $total = (int) ($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $lastPage = max(1, (int) ceil($total / $perPage));
    $page = min($page, $lastPage);
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT
            a.id_agent,
            a.numero_agent,
            a.nom,
            a.prenom,
            a.contact,
            a.date_ajout,
            a.id_chef,
            ce.nom AS chef_nom,
            ce.prenoms AS chef_prenoms,
            ce.token AS chef_token,
            ce.login AS chef_login
        FROM agents a
        INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
        WHERE {$whereSql}
        ORDER BY a.nom ASC, a.prenom ASC, a.id_agent DESC
        LIMIT {$perPage} OFFSET {$offset}";

    $stmt = $conn->prepare($sql);
    $stmt->execute($bindings);
    $agents = array_map('normalizeAgent', $stmt->fetchAll(PDO::FETCH_ASSOC));

    $chefs = [];
    $chef = null;

    if ($horsPgf) {
        $chefsStmt = $conn->query(
            "SELECT id_chef, nom, prenoms, token, login
            FROM chef_equipe
            WHERE LOWER(TRIM(COALESCE(login, ''))) <> 'pgf'
              AND CONCAT(COALESCE(nom, ''), ' ', COALESCE(prenoms, '')) NOT LIKE '%PGF%'
            ORDER BY nom ASC, prenoms ASC"
        );
        $chefs = array_map('normalizeChef', $chefsStmt ? $chefsStmt->fetchAll(PDO::FETCH_ASSOC) : []);
    } elseif ($token !== '') {
        $chefStmt = $conn->prepare('SELECT id_chef, nom, prenoms, token, login FROM chef_equipe WHERE token = :token LIMIT 1');
        $chefStmt->execute(['token' => $token]);
        $chefRow = $chefStmt->fetch(PDO::FETCH_ASSOC);
        if ($chefRow) {
            $chef = normalizeChef($chefRow);
            $chefs = [$chef];
        }
    } elseif ($idChef > 0) {
        $chefStmt = $conn->prepare('SELECT id_chef, nom, prenoms, token, login FROM chef_equipe WHERE id_chef = :id_chef LIMIT 1');
        $chefStmt->execute(['id_chef' => $idChef]);
        $chefRow = $chefStmt->fetch(PDO::FETCH_ASSOC);
        if ($chefRow) {
            $chef = normalizeChef($chefRow);
            $chefs = [$chef];
        }
    }

    jsonOut(200, [
        'success' => true,
        'chef' => $chef,
        'agents' => $agents,
        'chefs' => $chefs,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ],
    ]);
} catch (Throwable $e) {
    $debug = (string) ($_GET['debug'] ?? '') === '1';
    if ($debug) {
        jsonOut(500, ['success' => false, 'error' => 'Erreur serveur.', 'detail' => $e->getMessage()]);
    }
    jsonOut(500, ['success' => false, 'error' => 'Erreur serveur.']);
}
