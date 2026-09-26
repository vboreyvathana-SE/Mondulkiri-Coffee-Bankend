<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");

require_once "../config/database.php";

try {

    $sql = "SELECT * FROM products";

    $stmt = $pdo->query($sql);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $products
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve products"
    ]);
}