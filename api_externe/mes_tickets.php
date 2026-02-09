<?php

declare(strict_types=1);

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

try {
    require __DIR__ . '/connexion.php';

    if (!isset($conn) || !($conn instanceof PDO)) {
        jsonOut(500, ['error' => 'Connexion PDO indisponible.']);
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    // Compter le total
    $countStmt = $conn->prepare('SELECT COUNT(*) as total FROM tickets');
    $countStmt->execute();
    $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->prepare(
        'SELECT
            t.id_ticket,
            t.id_usine,
            t.date_ticket,
            t.id_agent,
            t.numero_ticket,
            t.vehicule_id,
            t.poids,
            t.id_utilisateur,
            t.prix_unitaire,
            t.date_validation_boss,
            t.montant_paie,
            t.montant_payer,
            t.montant_reste,
            t.date_paie,
            t.created_at,
            t.updated_at,
            t.statut_ticket,
            t.numero_bordereau,
            v.matricule_vehicule,
            v.type_vehicule,
            v.id_proprietaire,
            a.nom AS agent_nom,
            a.prenom AS agent_prenom,
            a.numero_agent,
            u.nom_usine
         FROM tickets t
         LEFT JOIN vehicules v ON v.vehicules_id = t.vehicule_id
         LEFT JOIN agents a ON a.id_agent = t.id_agent
         LEFT JOIN usines u ON u.id_usine = t.id_usine
         ORDER BY t.id_ticket DESC
         LIMIT :limit OFFSET :offset'
    );

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lastPage = (int)ceil($total / $perPage);

    jsonOut(200, [
        'success' => true,
        'tickets' => $tickets,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ],
    ]);
} catch (Throwable $e) {
    $debug = (string)($_GET['debug'] ?? '') === '1';
    if ($debug) {
        jsonOut(500, ['error' => 'Erreur serveur.', 'detail' => $e->getMessage()]);
    }
    jsonOut(500, ['error' => 'Erreur serveur.']);
}
