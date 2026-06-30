<?php

declare(strict_types=1);

/**
 * API: mes_agents.php
 * Liste des agents rattachés à un chef d'équipe.
 * GET ?token=BAEB3101
 * GET ?id_chef=1
 * GET ?search=nom&page=1&per_page=15
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
        ]),
    ];
}

try {
    require __DIR__ . '/connexion.php';

    if (!isset($conn) || !($conn instanceof PDO)) {
        jsonOut(500, ['success' => false, 'error' => 'Connexion PDO indisponible.']);
    }

    $token = trim((string) ($_GET['token'] ?? ''));
    $idChef = (int) ($_GET['id_chef'] ?? 0);
    $search = trim((string) ($_GET['search'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 15)));

    $where = ['a.date_suppression IS NULL'];
    $bindings = [];

    if ($token !== '') {
        $where[] = 'ce.token = :token';
        $bindings['token'] = $token;
    } elseif ($idChef > 0) {
        $where[] = 'a.id_chef = :id_chef';
        $bindings['id_chef'] = $idChef;
    }

    if ($search !== '') {
        $where[] = '(a.nom LIKE :search OR a.prenom LIKE :search OR a.numero_agent LIKE :search OR CONCAT(a.nom, \' \', a.prenom) LIKE :search)';
        $bindings['search'] = '%' . $search . '%';
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
            ce.token AS chef_token
        FROM agents a
        INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
        WHERE {$whereSql}
        ORDER BY a.date_ajout DESC, a.id_agent DESC
        LIMIT {$perPage} OFFSET {$offset}";

    $stmt = $conn->prepare($sql);
    $stmt->execute($bindings);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $agents = array_map('normalizeAgent', $rows);

    $chefs = [];
    if ($token !== '') {
        $chefStmt = $conn->prepare('SELECT id_chef, nom, prenoms, token FROM chef_equipe WHERE token = :token LIMIT 1');
        $chefStmt->execute(['token' => $token]);
        $chefRow = $chefStmt->fetch(PDO::FETCH_ASSOC);
        if ($chefRow) {
            $chefs[] = normalizeChef($chefRow);
        }
    } else {
        $chefStmt = $conn->query('SELECT id_chef, nom, prenoms, token FROM chef_equipe ORDER BY nom, prenoms');
        $chefs = array_map('normalizeChef', $chefStmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    jsonOut(200, [
        'success' => true,
        'agents' => $agents,
        'chefs' => $chefs,
        'pagination' => [
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ],
    ]);
} catch (Throwable $e) {
    $debug = (string) ($_GET['debug'] ?? '') === '1';
    if ($debug) {
        jsonOut(500, ['success' => false, 'error' => 'Erreur serveur.', 'detail' => $e->getMessage()]);
    }
    jsonOut(500, ['success' => false, 'error' => 'Erreur serveur.']);
}
