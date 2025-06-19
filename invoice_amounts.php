<?php
// API endpoint to get invoice subtotal and total (with tax) for a given invoice id
$pdo = new PDO("mysql:host=localhost;dbname=dashboard", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'No invoice id']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($items as $item) {
    $discount = isset($item['discount']) ? $item['discount'] : 0;
    $line_total = $item['quantity'] * $item['unit_price'] * (1 - $discount / 100);
    $subtotal += $line_total;
}
$btw = $subtotal * 0.21;
$total = $subtotal + $btw;

header('Content-Type: application/json');
echo json_encode([
    'subtotal' => $subtotal,
    'total' => $total
]);
