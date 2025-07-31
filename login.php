<?php
require_once 'config.php';

// Traitement du formulaire de connexion admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = secure($_POST['nom_utilisateur']);
    $mot_de_passe = secure($_POST['mot_de_passe']);

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ?");
    $stmt->execute([$nom_utilisateur]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['admin_id'] = $utilisateur['id'];
        $_SESSION['admin_nom_utilisateur'] = $utilisateur['nom_utilisateur'];
        $_SESSION['admin_nom_complet'] = $utilisateur['nom_complet'];
        $_SESSION['admin_role'] = $utilisateur['role'];

        // Afficher l'écran de chargement personnalisé
        echo '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Connexion en cours...</title>
            <style>
                body {
                    background: #fff;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .loader-logo {
                    max-width: 200px;
                    margin-bottom: 30px;
                    animation: pulse 1.5s infinite;
                }
                @keyframes pulse {
                    0% { transform: scale(1);}
                    50% { transform: scale(1.05);}
                    100% { transform: scale(1);}
                }
                .webattou {
                    font-size: 1.2em;
                    color: #333;
                    font-family: Arial, sans-serif;
                    margin-top: 10px;
                    letter-spacing: 1px;
                }
            </style>
            <script>
                setTimeout(function() {
                    window.location.href = "dashboard.php";
                }, 2500);
            </script>
        </head>
        <body>
            <img src="https://images.wakelet.com/resize?id=3M6-YKxOz4Q-aLIlv0iUz&w=1600&h=actual&q=85" alt="Logo" class="loader-logo">
            <div class="webattou">Logiciel Conçu par WEBATTOU</div>
        </body>
        </html>';
        exit;
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Connexion Administrateur</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            background-image:url("https://images.wakelet.com/resize?id=UuJYQCzjzp4lcgyJMJbg-&w=1600&h=actual&q=85")
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 150px;
        }
        .copyright {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
        .form-group input {
            transition: all 0.3s ease;
        }
        .form-group input:focus {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://centreflorence.com/images/LOGO_BLANC1.png" alt="Logo">
        </div>
        <div class="login-container animate__animated animate__fadeIn">
            <h2 class="text-center mb-4">Connexion Administrateur</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="nom_utilisateur">Nom d'utilisateur :</label>
                    <input type="text" id="nom_utilisateur" name="nom_utilisateur" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe :</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
            </form>
            <div class="copyright mt-3">
                <p>&copy; <?php echo date("Y"); ?> Centre Florence Santé du Cheveu. Tous droits réservés.</p>
                <p>Conçu par <a href="https://www.webattou.com">WEBATTOU</a></p>

            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
