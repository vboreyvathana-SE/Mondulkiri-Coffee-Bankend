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
    $sales = $pdo->query('
        SELECT
            COUNT(*) AS purchase_count,
            COALESCE(SUM(quantity), 0) AS items_sold,
            COALESCE(SUM(quantity * price), 0) AS revenue
        FROM sale_products
    ')->fetch(PDO::FETCH_ASSOC);

    $accountCount = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $productCount = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'revenue' => (float) $sales['revenue'],
            'purchase_count' => (int) $sales['purchase_count'],
            'items_sold' => (int) $sales['items_sold'],
            'account_count' => (int) $accountCount,
            'product_count' => (int) $productCount
        ]
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load dashboard data']);
}
