<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = $_SESSION['client_id'];
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT telephone FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    $telephone = $client['telephone'];
    $uploadDir = UPLOAD_DIR . $telephone . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo_profil']['tmp_name'];
        $fileName = $_FILES['photo_profil']['name'];
        $fileSize = $_FILES['photo_profil']['size'];
        $fileType = $_FILES['photo_profil']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = $uploadDir . $newFileName;

        if(move_uploaded_file($fileTmpPath, $dest_path)) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE clients SET photo_profil = ? WHERE id = ?");
            $stmt->execute([$newFileName, $client_id]);

            header('Location: carnet.php');
            exit;
        } else {
            echo "Il y a eu une erreur, veuillez réessayer.";
        }
    } else {
        echo "Erreur dans le téléchargement du fichier.";
    }
}
?>
