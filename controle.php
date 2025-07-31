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

// Traitement du formulaire d'ajout de contrôle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_controle = secure($_POST['date_controle']);
    $reparation_rapide_fibre = isset($_POST['reparation_rapide_fibre']);
    $reparation_lente_fibre = isset($_POST['reparation_lente_fibre']);
    $repousse_cheveux = isset($_POST['repousse_cheveux']);
    $densite_cheveux = isset($_POST['densite_cheveux']);
    $elasticite_cheveux = isset($_POST['elasticite_cheveux']);
    $force_cheveux = isset($_POST['force_cheveux']);
    $executant = secure($_POST['executant']);
    $observations = secure($_POST['observations']);

    $photoNames = [];
    for ($i = 1; $i <= 3; $i++) {
        $fileKey = 'photo' . $i;
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
            $fileName = uniqid() . '_' . basename($_FILES[$fileKey]['name']);
            $targetPath = $uploadDir . $fileName;
            $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                    $photoNames[$i] = $fileName;
                } else {
                    $photoNames[$i] = null;
                }
            } else {
                $photoNames[$i] = null;
            }
        } else {
            $photoNames[$i] = null;
        }
    }

    if (!isset($error)) {
        try {
            $stmt = $db->prepare("INSERT INTO controles (client_id, date_controle, reparation_rapide_fibre, reparation_lente_fibre, repousse_cheveux, densite_cheveux, elasticite_cheveux, force_cheveux, executant, observations, nom_fichier1, nom_fichier2, nom_fichier3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $client_id, $date_controle, $reparation_rapide_fibre, $reparation_lente_fibre, $repousse_cheveux, $densite_cheveux, $elasticite_cheveux, $force_cheveux, $executant, $observations,
                $photoNames[1], $photoNames[2], $photoNames[3]
            ]);
            header('Location: carnet.php');
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du contrôle.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Contrôle</title>
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
        input[type="checkbox"] {
            margin-right: 10px;
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
        <h1>Contrôle</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="date_controle">Date du contrôle :</label>
                <input type="date" id="date_controle" name="date_controle" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="reparation_rapide_fibre"> Réparation rapide de la fibre capillaire
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="reparation_lente_fibre"> Réparation lente de la fibre capillaire
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="repousse_cheveux"> Repousse des cheveux
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="densite_cheveux"> Densité des cheveux
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="elasticite_cheveux"> Élasticité des cheveux
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="force_cheveux"> Force des cheveux
                </label>
            </div>
            <div class="form-group">
                <label for="executant">Exécutant :</label>
                <input type="text" id="executant" name="executant">
            </div>
            <div class="form-group">
                <label>Photo 1 :</label>
                <input type="file" name="photo1" accept="image/*">
            </div>
            <div class="form-group">
                <label>Photo 2 :</label>
                <input type="file" name="photo2" accept="image/*">
            </div>
            <div class="form-group">
                <label>Photo 3 :</label>
                <input type="file" name="photo3" accept="image/*">
            </div>
            <div class="form-group">
                <label for="observations">Observations :</label>
                <textarea id="observations" name="observations"></textarea>
            </div>
            <button type="submit" class="btn">Ajouter le contrôle</button>
        </form>
        <div class="links">
            <a href="carnet.php">Retour au carnet</a>
        </div>
    </div>
</body>
</html>
