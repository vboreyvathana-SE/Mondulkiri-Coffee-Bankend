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
    $saleColumns = tableColumns($pdo, 'sale_products');
    $saleIdColumn = firstExistingColumn($saleColumns, ['id', 'sale_id']);
    $purchasedAtColumn = firstExistingColumn($saleColumns, ['created_at', 'purchased_at', 'ordered_at']);

    $saleIdSelect = $saleIdColumn !== null ? "sp.`{$saleIdColumn}` AS sale_id" : 'NULL AS sale_id';
    $purchasedAtSelect = $purchasedAtColumn !== null ? "sp.`{$purchasedAtColumn}` AS purchased_at" : 'NULL AS purchased_at';
    $orderBy = $purchasedAtColumn !== null
        ? "sp.`{$purchasedAtColumn}` DESC"
        : ($saleIdColumn !== null ? "sp.`{$saleIdColumn}` DESC" : 'sp.product_id DESC');

    $statement = $pdo->query("
        SELECT
            {$saleIdSelect},
            {$purchasedAtSelect},
            sp.product_id,
            sp.quantity,
            sp.price,
            (sp.quantity * sp.price) AS line_total,
            p.name AS product_name,
            p.product_code,
            u.id AS user_id,
            u.first_name,
            u.last_name,
            u.email
        FROM sale_products AS sp
        INNER JOIN products AS p ON p.id = sp.product_id
        INNER JOIN users AS u ON u.id = sp.user_id
        ORDER BY {$orderBy}
    ");

    echo json_encode([
        'success' => true,
        'data' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load purchase records']);
}
