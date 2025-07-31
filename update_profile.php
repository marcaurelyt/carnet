<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = $_SESSION['client_id'];
    $nom = $_POST['nom'];
    $prenoms = $_POST['prenoms'];
    $genre = $_POST['genre'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $ville = $_POST['ville'];

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE clients SET nom = ?, prenoms = ?, genre = ?, email = ?, telephone = ?, ville = ? WHERE id = ?");
    $stmt->execute([$nom, $prenoms, $genre, $email, $telephone, $ville, $client_id]);

    header('Location: carnet.php');
    exit;
}
?>
