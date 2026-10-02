<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in"
    ]);

    exit;
}

if ($_SESSION['account_type'] !== 'admin') {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Admin access required"
    ]);

    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Admin access granted"
]);