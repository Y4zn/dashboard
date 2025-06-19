<?php
require 'vendor/autoload.php';
require 'config.php'; // Ensure this file contains the $pdo database connection

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure no additional output interferes with JSON response
ob_start();
header('Content-Type: application/json');

if (!isset($_GET['client_id']) || !is_numeric($_GET['client_id'])) {
    echo json_encode(['error' => 'Invalid client ID']);
    exit;
}

$clientId = (int) $_GET['client_id'];
$stmt = $pdo->prepare("SELECT id, name FROM projects WHERE client_id = ?");
$stmt->execute([$clientId]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_end_clean();
echo json_encode($projects);
