<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);

    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $_SESSION['user_id'],
        "account_type" => $_SESSION['account_type']
    ]
]);