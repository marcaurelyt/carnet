<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté en tant qu'admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$client_id = secure($_GET['id']);

// Récupérer les informations du client
$stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: admin.php');
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = secure($_POST['nom']);
    $prenoms = secure($_POST['prenoms']);
    $email = secure($_POST['email']);
    $telephone = secure($_POST['telephone']);

    try {
        $stmt = $db->prepare("UPDATE clients SET nom = ?, prenoms = ?, email = ?, telephone = ? WHERE id = ?");
        $stmt->execute([$nom, $prenoms, $email, $telephone, $client_id]);

        header('Location: client_detail.php?id=' . $client_id);
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la modification du client.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Modifier le client</title>
    <style>
        /* Ajoutez vos styles CSS ici */
    </style>
</head>
<body>
    <div class="container">
        <h1>Modifier le client</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($client['nom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="prenoms">Prénoms :</label>
                <input type="text" id="prenoms" name="prenoms" value="<?php echo htmlspecialchars($client['prenoms']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone :</label>
                <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($client['telephone']); ?>">
            </div>

            <button type="submit" class="btn">Modifier</button>
        </form>

        <div class="links">
            <a href="client_detail.php?id=<?php echo $client_id; ?>">Retour aux détails du client</a>
        </div>
    </div>
</body>
</html>
