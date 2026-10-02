<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Content-Type: application/json");

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);

    exit;
}

if (!isset($_POST['submit'])) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid request"
    ]);

    exit;
}

$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$email = $_POST['email'];
$password = $_POST['password'];
$account_type = $_POST['account_type'];

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users
        (email, password_hash, first_name, last_name, account_type)
        VALUES
        (:email, :password_hash, :first_name, :last_name, :account_type)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':email' => $email,
    ':password_hash' => $password_hash,
    ':first_name' => $firstName,
    ':last_name' => $lastName,
    ':account_type' => $account_type
]);

echo json_encode([
    "success" => true,
    "message" => "Registration successful"
]);