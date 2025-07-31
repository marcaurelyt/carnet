<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Conseils Pratiques</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Conseils Pratiques</h1>

        <div class="list-group">
            <a href="#" class="list-group-item list-group-item-action">
                Faire trois défrisages par an
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                Ne porter pas la perruque à répétition
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                Avoir une alimentation saine
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                Éviter le séchage trop chaud à répétition
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                Éviter les tresses trop serrées
            </a>
            <a href="#" class="list-group-item list-group-item-action">
                Éviter les tresses après un défrisage
            </a>
        </div>

        <div class="text-center mt-4">
            <a href="carnet.php" class="btn btn-secondary">Retour au carnet</a>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
