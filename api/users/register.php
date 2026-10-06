<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

session_start();

require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed"
    ]);

    exit;
}

$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$account_type = $_POST['account_type'] ?? '';

if (
    empty($firstName) ||
    empty($lastName) ||
    empty($email) ||
    empty($password) ||
    empty($account_type)
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "All fields are required"
    ]);

    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {

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

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['account_type'] = $account_type;

    echo json_encode([
        "success" => true,
        "message" => "Registration successful"
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Registration failed"
    ]);
}