<?php

require_once '../config/admin.php';
require_once '../config/database.php';

adminHeaders();
handleOptionsRequest();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed']);
    exit;
}

try {
    $userColumns = tableColumns($pdo, 'users');
    $joinedAtColumn = firstExistingColumn($userColumns, ['created_at', 'registered_at', 'joined_at']);
    $joinedAtSelect = $joinedAtColumn !== null ? "`{$joinedAtColumn}` AS joined_at" : 'NULL AS joined_at';

    $statement = $pdo->query("
        SELECT id, first_name, last_name, email, account_type, {$joinedAtSelect}
        FROM users
        ORDER BY id DESC
    ");

    echo json_encode([
        'success' => true,
        'data' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load accounts']);
}
