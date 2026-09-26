<?php

require_once "config/database.php";

echo "PHP is working!<br>";

$stmt = $pdo->query("SELECT * FROM products");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($products);
echo "</pre>";