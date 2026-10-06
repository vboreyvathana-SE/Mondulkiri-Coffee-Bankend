<?php

function adminHeaders(string $methods = 'GET, OPTIONS'): void
{
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Credentials: true');
    header("Access-Control-Allow-Methods: {$methods}");
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json');
}

function handleOptionsRequest(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function requireAdmin(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in'
        ]);
        exit;
    }

    if (($_SESSION['account_type'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required'
        ]);
        exit;
    }
}

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
