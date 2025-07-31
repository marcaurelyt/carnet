<?php
require 'config.php';
session_start();

$input = file_get_contents('php://input');
$subscription = json_decode($input, true);

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id && $subscription) {
    $stmt = $pdo->prepare("REPLACE INTO push_subscriptions (user_id, subscription) VALUES (?, ?)");
    $stmt->execute([$user_id, json_encode($subscription)]);
}
?>