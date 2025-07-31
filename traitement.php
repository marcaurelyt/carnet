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

// Initialisation des variables
$error = null;
$fileNames = [];

// Traitement du formulaire d'ajout de séance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_seance = isset($_POST['numero_seance']) ? secure($_POST['numero_seance']) : '';
    $date_seance = isset($_POST['date_seance']) ? secure($_POST['date_seance']) : '';
    $soin = isset($_POST['soin']) ? secure($_POST['soin']) : '';
    $microneedle = isset($_POST['microneedle']);
    $steamer = isset($_POST['steamer']);
    $bain_huile = isset($_POST['bain_huile']);
    $bain_medical = isset($_POST['bain_medical']);
    $defrisage = isset($_POST['defrisage']);
    $coloration = isset($_POST['coloration']);
    $gommage = isset($_POST['gommage']);
    $stimulation = isset($_POST['stimulation']);
    $autres = isset($_POST['autres']) ? secure($_POST['autres']) : '';
    $executant = isset($_POST['executant']) ? secure($_POST['executant']) : '';

    for ($i = 1; $i <= 3; $i++) {
        if (isset($_FILES['photo'.$i]) && $_FILES['photo'.$i]['error'] == UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['photo'.$i]['name']);
            $targetFilePath = $uploadDir . $fileName;
            $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
            $allowTypes = array('jpg', 'png', 'jpeg', 'gif');

            if (in_array($fileType, $allowTypes)) {
                if (!move_uploaded_file($_FILES['photo'.$i]['tmp_name'], $targetFilePath)) {
                    $error = "Désolé, il y a eu une erreur lors de l'upload de votre fichier.";
                } else {
                    $fileNames[] = $fileName;
                }
            } else {
                $error = "Désolé, seulement les fichiers JPG, JPEG, PNG, et GIF sont autorisés.";
            }
        }
    }

    if (!isset($error)) {
        try {
            $stmt = $db->prepare("INSERT INTO seances (client_id, numero_seance, date_seance, soin, microneedle, steamer, bain_huile, bain_medical, defrisage, coloration, gommage, stimulation, autres, executant, nom_fichier1, nom_fichier2, nom_fichier3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $client_id,
                $numero_seance,
                $date_seance,
                $soin,
                $microneedle,
                $steamer,
                $bain_huile,
                $bain_medical,
                $defrisage,
                $coloration,
                $gommage,
                $stimulation,
                $autres,
                $executant,
                $fileNames[0] ?? null,
                $fileNames[1] ?? null,
                $fileNames[2] ?? null
            ]);
            header('Location: carnet.php');
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de la séance: " . $e->getMessage();
            error_log($error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Traitement</title>
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
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #357abd;
        }
        .error {
            color: red;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.9);
        }
        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
        }
        .modal-content img {
            width: 100%;
            height: auto;
        }
        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }
        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-images {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .modal-images img {
            width: 30%;
            cursor: pointer;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Traitement</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="numero_seance">Numéro de séance :</label>
                <input type="number" id="numero_seance" name="numero_seance" required>
            </div>
            <div class="form-group">
                <label for="date_seance">Date de la séance :</label>
                <input type="date" id="date_seance" name="date_seance" required>
            </div>
            <div class="form-group">
                <label for="soin">Soin :</label>
                <input type="text" id="soin" name="soin">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="microneedle"> Microneedle
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="steamer"> Steamer
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="bain_huile"> Bain d'huile
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="bain_medical"> Bain médical
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="defrisage"> Défrisage
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="coloration"> Coloration
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="gommage"> Gommage
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="stimulation"> Stimulation
                </label>
            </div>
            <div class="form-group">
                <label for="autres">Autres :</label>
                <input type="text" id="autres" name="autres">
            </div>
            <div class="form-group">
                <label for="photo1">Photo 1 :</label>
                <input type="file" id="photo1" name="photo1">
            </div>
            <div class="form-group">
                <label for="photo2">Photo 2 :</label>
                <input type="file" id="photo2" name="photo2">
            </div>
            <div class="form-group">
                <label for="photo3">Photo 3 :</label>
                <input type="file" id="photo3" name="photo3">
            </div>
            <div class="form-group">
                <label for="executant">Exécutant :</label>
                <input type="text" id="executant" name="executant">
            </div>
            <button type="submit">Ajouter la séance</button>
        </form>
        <div class="links">
            <a href="carnet.php">Retour au carnet</a>
        </div>
    </div>
    <!-- Modal pour afficher les photos -->
    <div id="myModal" class="modal">
        <span class="close">&times;</span>
        <div class="modal-images">
            <img class="modal-content" id="img1" style="display:none;">
            <img class="modal-content" id="img2" style="display:none;">
            <img class="modal-content" id="img3" style="display:none;">
        </div>
    </div>
    <script>
        // Récupérer le modal
        var modal = document.getElementById("myModal");
        // Récupérer les images et insérer dans le modal
        var modalImg1 = document.getElementById("img1");
        var modalImg2 = document.getElementById("img2");
        var modalImg3 = document.getElementById("img3");
        // Récupérer le bouton de fermeture
        var span = document.getElementsByClassName("close")[0];
        // Lorsque l'utilisateur clique sur une image, ouvrir le modal
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(event) {
                        var imgId = 'img' + e.target.id.replace('photo', '');
                        document.getElementById(imgId).src = event.target.result;
                        document.getElementById(imgId).style.display = 'block';
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
        // Lorsque l'utilisateur clique sur <span> (x), fermer le modal
        span.onclick = function() {
            modal.style.display = "none";
        }
        // Lorsque l'utilisateur clique n'importe où en dehors du modal, le fermer
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        // Fonction pour ouvrir le modal
        function openModal() {
            modal.style.display = "block";
        }
        // Ajouter un événement pour ouvrir le modal lorsque les images sont chargées
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', openModal);
        });
    </script>
</body>
</html>
