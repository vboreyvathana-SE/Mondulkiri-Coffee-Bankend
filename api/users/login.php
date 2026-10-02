<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);

    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);

    exit;
}

$sql = "SELECT id, email, password_hash, first_name, last_name, account_type
        FROM users
        WHERE email = :email
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':email' => $email
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password"
    ]);

    exit;
}

session_start();

$_SESSION['user_id'] = $user['id'];
$_SESSION['account_type'] = $user['account_type'];

unset($user['password_hash']);

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "user" => $user
]);