<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");

require_once "../config/database.php";

try {

    // NEW: /products/index.php?id=1 returns a single product
    if (isset($_GET["id"])) {

        $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);

        if ($id === false) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid product id"
            ]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(["id" => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Product not found"
            ]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "data" => $product
        ]);
        exit;
    }

    // Existing behaviour: list all products
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