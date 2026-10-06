<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once "../config/database.php";

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);

    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in"
    ]);

    exit;
}

$userId = $_SESSION['user_id'];

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Cart is empty"
    ]);

    exit;
}

try {

    $pdo->beginTransaction();

    $insertSale = $pdo->prepare("
        INSERT INTO sale_products
        (user_id, product_id, quantity, price)
        VALUES
        (:user_id, :product_id, :quantity, :price)
    ");

    $updateStock = $pdo->prepare("
        UPDATE products
        SET stock = stock - :quantity
        WHERE id = :product_id
    ");

    $sales = [];

    foreach ($input['items'] as $item) {

        $productId = filter_var(
            $item['product_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $quantity = filter_var(
            $item['quantity'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$productId || !$quantity || $quantity < 1) {
            throw new Exception("Invalid cart item");
        }

        // Get the real product from the database.
        $stmt = $pdo->prepare("
            SELECT id, price, stock
            FROM products
            WHERE id = :product_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':product_id' => $productId
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found");
        }

        if ($product['stock'] < $quantity) {
            throw new Exception(
                "Not enough stock for product ID " . $productId
            );
        }

        // Save the database price, not the price sent by React.
        $insertSale->execute([
            ':user_id' => $userId,
            ':product_id' => $productId,
            ':quantity' => $quantity,
            ':price' => $product['price']
        ]);

        $sales[] = [
            "product_id" => $productId,
            "quantity" => $quantity,
            "price" => $product['price']
        ];

        $updateStock->execute([
            ':quantity' => $quantity,
            ':product_id' => $productId
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Purchase recorded successfully",
        "sales" => $sales
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
