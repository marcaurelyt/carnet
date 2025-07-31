<?php
require_once 'config.php';
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$client_id = $_SESSION['client_id'];
$stmt = $db->prepare("SELECT telephone FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();
$telephone = $client['telephone'];
$uploadDir = UPLOAD_DIR . $telephone . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Traitement du formulaire d'ajout de photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photos'])) {
    $type_photo = secure($_POST['type_photo']);
    $date_photo = secure($_POST['date_photo']);
    $commentaires = secure($_POST['commentaires']);

    $totalFiles = count($_FILES['photos']['name']);
    for ($i = 0; $i < $totalFiles; $i++) {
        $fileName = basename($_FILES['photos']['name'][$i]);
        $targetFilePath = $uploadDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');

        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $targetFilePath)) {
                try {
                    $stmt = $db->prepare("INSERT INTO photos (client_id, type_photo, date_photo, nom_fichier, commentaires) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$client_id, $type_photo, $date_photo, $fileName, $commentaires]);
                    $success = "Les photos ont été uploadées avec succès.";
                } catch (PDOException $e) {
                    $error = "Erreur lors de l'ajout des photos: " . $e->getMessage();
                }
            } else {
                $error = "Désolé, il y a eu une erreur lors de l'upload du fichier $fileName.";
            }
        } else {
            $error = "Désolé, seulement les fichiers JPG, JPEG, PNG, et GIF sont autorisés pour $fileName.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Gérer les photos</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #74ba4268;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            padding-bottom: 80px;
        }
        .card {
            position: relative;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-direction: column;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: 30px;
        }
        .container {
            background: rgb(255 255 255 / 50%);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        .photo img {
            max-width: 200px;
            height: auto;
            display: block;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="date"], input[type="file"], textarea, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4a90e2;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
        }
        button:hover {
            background-color: #357abd;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .btn {
            background-color: #4a90e2;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #357abd;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Gérer les photos</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="type_photo">Type de photo :</label>
                <input type="text" id="type_photo" name="type_photo" required>
            </div>
            <div class="form-group">
                <label for="date_photo">Date de la photo :</label>
                <input type="date" id="date_photo" name="date_photo" required>
            </div>
            <div class="form-group">
                <label for="photos">Photos :</label>
                <input type="file" id="photos" name="photos[]" multiple required>
            </div>
            <div class="form-group">
                <label for="commentaires">Commentaires :</label>
                <textarea id="commentaires" name="commentaires"></textarea>
            </div>
            <button type="submit" class="btn">Ajouter les photos</button>
        </form>
        <div class="links">
            <a href="carnet.php">Retour au carnet</a>
        </div>
    </div>
</body>
</html>
