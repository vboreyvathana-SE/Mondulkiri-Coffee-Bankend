<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed"
    ]);
    exit;
}

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to view your profile"
    ]);
    exit;
}

require_once "../config/database.php";

/**
 * Returns the columns available in a table. A few installations of this
 * project do not have timestamp columns yet, so the endpoint treats them as
 * optional instead of failing to load the entire profile.
 */
function tableColumns(PDO $pdo, string $table): array
{
    $statement = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

function firstExistingColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

try {
    $userId = (int) $_SESSION['user_id'];
    $userColumns = tableColumns($pdo, 'users');
    $saleColumns = tableColumns($pdo, 'sale_products');

    $joinedAtColumn = firstExistingColumn($userColumns, ['created_at', 'registered_at', 'joined_at']);
    $saleIdColumn = firstExistingColumn($saleColumns, ['id', 'sale_id']);
    $purchasedAtColumn = firstExistingColumn($saleColumns, ['created_at', 'purchased_at', 'ordered_at']);

    $userSelect = "id, email, first_name, last_name, account_type";
    if ($joinedAtColumn !== null) {
        $userSelect .= ", `{$joinedAtColumn}` AS joined_at";
    } else {
        $userSelect .= ", NULL AS joined_at";
    }

    $userStatement = $pdo->prepare("SELECT {$userSelect} FROM users WHERE id = :id LIMIT 1");
    $userStatement->execute([':id' => $userId]);
    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "User account not found"
        ]);
        exit;
    }

    $saleIdSelect = $saleIdColumn !== null
        ? "sp.`{$saleIdColumn}` AS sale_id"
        : "NULL AS sale_id";
    $purchasedAtSelect = $purchasedAtColumn !== null
        ? "sp.`{$purchasedAtColumn}` AS purchased_at"
        : "NULL AS purchased_at";

    $orderBy = $purchasedAtColumn !== null
        ? "sp.`{$purchasedAtColumn}` DESC"
        : ($saleIdColumn !== null ? "sp.`{$saleIdColumn}` DESC" : "sp.product_id DESC");

    $purchaseStatement = $pdo->prepare("
        SELECT
            {$saleIdSelect},
            {$purchasedAtSelect},
            sp.product_id,
            sp.quantity,
            sp.price,
            p.name AS product_name,
            p.image AS product_image,
            p.product_code,
            p.weight AS product_weight,
            p.flavor_notes,
            p.roast_profile
        FROM sale_products AS sp
        INNER JOIN products AS p ON p.id = sp.product_id
        WHERE sp.user_id = :user_id
        ORDER BY {$orderBy}
    ");
    $purchaseStatement->execute([':user_id' => $userId]);
    $purchases = $purchaseStatement->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "user" => $user,
            "purchases" => $purchases
        ]
    ]);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to load profile data"
    ]);
}
