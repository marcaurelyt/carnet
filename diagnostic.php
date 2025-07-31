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
$uploadDir = (defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads/') . $telephone . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Traitement du formulaire d'ajout de diagnostic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_diagnostic = secure($_POST['date_diagnostic']);
    $cheveux_abimes = isset($_POST['cheveux_abimes']);
    $cheveux_faibles = isset($_POST['cheveux_faibles']);
    $cheveux_perte_densite = isset($_POST['cheveux_perte_densite']);
    $cheveux_trop_gras = isset($_POST['cheveux_trop_gras']);
    $alopecie_androgenique = isset($_POST['alopecie_androgenique']);
    $alopecie_androgenique_niveau = isset($_POST['alopecie_androgenique']) ? secure($_POST['alopecie_androgenique_niveau']) : null;
    $alopecie_traction = isset($_POST['alopecie_traction']);
    $pelade = isset($_POST['pelade']);
    $psoriasis = isset($_POST['psoriasis']);
    $teigne = isset($_POST['teigne']);
    $texture_naturels = isset($_POST['texture_naturels']);
    $texture_defrises = isset($_POST['texture_defrises']);
    $texture_demeles = isset($_POST['texture_demeles']);
    $texture_colores = isset($_POST['texture_colores']);
    $executant = secure($_POST['executant']);

    // Gestion upload photos
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

    try {
        $stmt = $db->prepare("INSERT INTO diagnostics (client_id, date_diagnostic, cheveux_abimes, cheveux_faibles, cheveux_perte_densite, cheveux_trop_gras, alopecie_androgenique, alopecie_androgenique_niveau, alopecie_traction, pelade, psoriasis, teigne, texture_naturels, texture_defrises, texture_demeles, texture_colores, executant, nom_fichier1, nom_fichier2, nom_fichier3) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $client_id, $date_diagnostic, $cheveux_abimes, $cheveux_faibles, $cheveux_perte_densite, $cheveux_trop_gras,
            $alopecie_androgenique, $alopecie_androgenique_niveau, $alopecie_traction, $pelade, $psoriasis, $teigne,
            $texture_naturels, $texture_defrises, $texture_demeles, $texture_colores, $executant,
            $photoNames[1], $photoNames[2], $photoNames[3]
        ]);
        header('Location: carnet.php');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout du diagnostic.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Ajouter un diagnostic</title>
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
        <h1>Ajouter un diagnostic</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="date_diagnostic">Date du diagnostic :</label>
                <input type="date" id="date_diagnostic" name="date_diagnostic" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="cheveux_abimes"> Cheveux abîmés
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="cheveux_faibles"> Cheveux faibles
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="cheveux_perte_densite"> Cheveux en perte de densité
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="cheveux_trop_gras"> Cheveux trop gras
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="alopecie_androgenique"> Alopecie androgénétique
                </label>
                <select id="alopecie_androgenique_niveau" name="alopecie_androgenique_niveau">
                    <option value="1">Niveau 1</option>
                    <option value="2">Niveau 2</option>
                    <option value="3">Niveau 3</option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="alopecie_traction"> Alopecie de traction
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="pelade"> Pelade
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="psoriasis"> Psoriasis
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="teigne"> Teigne
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="texture_naturels"> Naturels
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="texture_defrises"> Défrisés
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="texture_demeles"> Démêlés
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="texture_colores"> Colorés
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
            <button type="submit" class="btn">Ajouter le diagnostic</button>
        </form>
        <div class="links">
            <a href="carnet.php">Retour au carnet</a>
        </div>
    </div>
</body>
</html>
