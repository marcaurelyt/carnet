<?php
require 'vendor/autoload.php';
require 'config.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

session_start();
// Optionnel : vérifie ici que l'utilisateur est bien administrateur

$input = json_decode(file_get_contents('php://input'), true);
$title = $input['title'] ?? 'Notification';
$body = $input['body'] ?? '';

$auth = [
    'VAPID' => [
        'subject' => 'mailto:ton@email.com', // Mets ton email ici
        'publicKey' => 'TA_CLE_PUBLIQUE_VAPID_ICI', // Mets ta clé publique VAPID
        'privateKey' => 'TA_CLE_PRIVEE_VAPID_ICI', // Mets ta clé privée VAPID
    ],
];

$webPush = new WebPush($auth);

$stmt = $pdo->query("SELECT subscription FROM push_subscriptions");
while ($row = $stmt->fetch()) {
    $subscription = Subscription::create(json_decode($row['subscription'], true));
    $webPush->queueNotification(
        $subscription,
        json_encode(['title' => $title, 'body' => $body])
    );
}

foreach ($webPush->flush() as $report) {
    // Tu peux gérer les retours ici si besoin
}
?>