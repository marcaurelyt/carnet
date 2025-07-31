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

// Récupérer les diagnostics du client
$diagnostics = $db->prepare("SELECT * FROM diagnostics WHERE client_id = ? ORDER BY date_diagnostic DESC");
$diagnostics->execute([$client_id]);
$diagnostics = $diagnostics->fetchAll();

// Récupérer les séances de traitement du client
$seances = $db->prepare("SELECT * FROM seances WHERE client_id = ? ORDER BY date_seance DESC");
$seances->execute([$client_id]);
$seances = $seances->fetchAll();

// Récupérer les contrôles du client
$controles = $db->prepare("SELECT * FROM controles WHERE client_id = ? ORDER BY date_controle DESC");
$controles->execute([$client_id]);
$controles = $controles->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Détails du client</title>
    <style>
        /* Ajoutez vos styles CSS ici */
    </style>
</head>
<body>
    <div class="container">
        <h1>Détails du client</h1>

        <h2>Informations personnelles</h2>
        <p><strong>Nom :</strong> <?php echo htmlspecialchars($client['nom']); ?></p>
        <p><strong>Prénoms :</strong> <?php echo htmlspecialchars($client['prenoms']); ?></p>
        <p><strong>Email :</strong> <?php echo htmlspecialchars($client['email']); ?></p>
        <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($client['telephone']); ?></p>

        <h2>Diagnostics</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Cheveux abîmés</th>
                <th>Cheveux faibles</th>
                <!-- Ajoutez d'autres colonnes selon vos besoins -->
            </tr>
            <?php foreach ($diagnostics as $diagnostic): ?>
            <tr>
                <td><?php echo formatDate($diagnostic['date_diagnostic']); ?></td>
                <td><?php echo $diagnostic['cheveux_abimes'] ? 'Oui' : 'Non'; ?></td>
                <td><?php echo $diagnostic['cheveux_faibles'] ? 'Oui' : 'Non'; ?></td>
                <!-- Ajoutez d'autres cellules selon vos besoins -->
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>Séances de traitement</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Séance</th>
                <th>Soin</th>
                <!-- Ajoutez d'autres colonnes selon vos besoins -->
            </tr>
            <?php foreach ($seances as $seance): ?>
            <tr>
                <td><?php echo formatDate($seance['date_seance']); ?></td>
                <td><?php echo $seance['numero_seance']; ?></td>
                <td><?php echo htmlspecialchars($seance['soin']); ?></td>
                <!-- Ajoutez d'autres cellules selon vos besoins -->
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>Contrôles</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Réparation rapide de la fibre capillaire</th>
                <th>Réparation lente de la fibre capillaire</th>
                <!-- Ajoutez d'autres colonnes selon vos besoins -->
            </tr>
            <?php foreach ($controles as $controle): ?>
            <tr>
                <td><?php echo formatDate($controle['date_controle']); ?></td>
                <td><?php echo $controle['reparation_rapide_fibre'] ? 'Oui' : 'Non'; ?></td>
                <td><?php echo $controle['reparation_lente_fibre'] ? 'Oui' : 'Non'; ?></td>
                <!-- Ajoutez d'autres cellules selon vos besoins -->
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="links">
            <a href="admin.php">Retour à la liste des clients</a>
        </div>
    </div>
</body>
</html>
