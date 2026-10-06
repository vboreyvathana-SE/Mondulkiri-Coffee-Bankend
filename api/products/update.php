<?php

require_once '../config/admin.php';
require_once '../config/database.php';

adminHeaders('POST, OPTIONS');
handleOptionsRequest();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed']);
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
$stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);

$textFields = [
    'product_code', 'product_type', 'name', 'description',
    'roast_profile', 'flavor_notes', 'grind', 'weight'
];

if (!$id || $price === false || $price < 0 || $stock === false || $stock < 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Provide a valid product ID, price, and stock amount']);
    exit;
}

$product = [];
foreach ($textFields as $field) {
    $product[$field] = trim($_POST[$field] ?? '');
    if ($product[$field] === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => "{$field} is required"]);
        exit;
    }
}

try {
    $existingStatement = $pdo->prepare('SELECT image FROM products WHERE id = :id LIMIT 1');
    $existingStatement->execute([':id' => $id]);
    $existing = $existingStatement->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    $imagePath = $existing['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed');
        }

        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Image must be 5 MB or smaller');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($extensions[$mimeType])) {
            throw new RuntimeException('Use a JPG, PNG, or WebP image');
        }

        $filename = 'product-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mimeType];
        $destination = __DIR__ . '/../Images/' . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            throw new RuntimeException('Unable to save the uploaded image');
        }

        $imagePath = 'Images/' . $filename;
    }

    $statement = $pdo->prepare('
        UPDATE products
        SET product_code = :product_code,
            product_type = :product_type,
            name = :name,
            description = :description,
            price = :price,
            roast_profile = :roast_profile,
            flavor_notes = :flavor_notes,
            grind = :grind,
            weight = :weight,
            stock = :stock,
            image = :image
        WHERE id = :id
    ');
    $statement->execute([
        ':id' => $id,
        ':product_code' => $product['product_code'],
        ':product_type' => $product['product_type'],
        ':name' => $product['name'],
        ':description' => $product['description'],
        ':price' => $price,
        ':roast_profile' => $product['roast_profile'],
        ':flavor_notes' => $product['flavor_notes'],
        ':grind' => $product['grind'],
        ':weight' => $product['weight'],
        ':stock' => $stock,
        ':image' => $imagePath
    ]);

    $updatedStatement = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $updatedStatement->execute([':id' => $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'data' => $updatedStatement->fetch(PDO::FETCH_ASSOC)
    ]);
} catch (RuntimeException $exception) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update product']);
}
