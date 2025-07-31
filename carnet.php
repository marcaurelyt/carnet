<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'carnet_cheveu');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration générale
define('SITE_NAME', 'Centre Florence Santé du Cheveu');
define('UPLOAD_DIR', 'uploads/');

// Classe de connexion à la base de données
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Fonction utilitaire pour sécuriser les données
function secure($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction pour formater les dates
function formatDate($date) {
    if (empty($date)) return '';
    $dateObj = new DateTime($date);
    return $dateObj->format('d/m/Y');
}

// Démarrage de la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}

// Connexion à la base de données
$db = Database::getInstance()->getConnection();

// Récupérer les informations du client
$client_id = $_SESSION['client_id'];
$stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

if (!$client) {
    die("Client not found.");
}

// Mettre à jour automatiquement les RDV expirés
$updateExpiredRdv = $db->prepare("
    UPDATE rdv
    SET statut = 'annule'
    WHERE client_id = ?
    AND statut = 'planifie'
    AND CONCAT(date_rdv, ' ', heure_rdv) < NOW()
");
$updateExpiredRdv->execute([$client_id]);

// Récupérer les RDV du client qui ne sont pas terminés
$rdvStmt = $db->prepare("
    SELECT * FROM rdv
    WHERE client_id = ?
    AND statut != 'termine'
    ORDER BY date_rdv DESC, heure_rdv DESC
");
$rdvStmt->execute([$client_id]);
$rdvs = $rdvStmt->fetchAll() ?: [];

// Récupérer les diagnostics du client
$diagnosticsStmt = $db->prepare("
    SELECT * FROM diagnostics
    WHERE client_id = ?
    ORDER BY date_diagnostic DESC
");
$diagnosticsStmt->execute([$client_id]);
$diagnostics = $diagnosticsStmt->fetchAll() ?: [];

// Récupérer les séances de traitement du client
$seancesStmt = $db->prepare("
    SELECT * FROM seances
    WHERE client_id = ?
    ORDER BY date_seance DESC
");
$seancesStmt->execute([$client_id]);
$seances = $seancesStmt->fetchAll() ?: [];

// Récupérer les contrôles du client
$controlesStmt = $db->prepare("
    SELECT * FROM controles
    WHERE client_id = ?
    ORDER BY date_controle DESC
");
$controlesStmt->execute([$client_id]);
$controles = $controlesStmt->fetchAll() ?: [];

// Récupérer les photos du client
$photosStmt = $db->prepare("
    SELECT * FROM photos
    WHERE client_id = ?
    ORDER BY date_photo DESC
");
$photosStmt->execute([$client_id]);
$photos = $photosStmt->fetchAll() ?: [];

// Récupérer les 5 derniers conseils
$conseilsStmt = $db->prepare("
    SELECT * FROM conseils_pratiques
    ORDER BY date_creation DESC
    LIMIT 5
");
$conseilsStmt->execute();
$conseils = $conseilsStmt->fetchAll() ?: [];

// Fonction pour confirmer un RDV
if (isset($_POST['confirm_rdv'])) {
    $rdv_id = $_POST['rdv_id'];
    $confirmStmt = $db->prepare("
        UPDATE rdv
        SET statut = 'confirme'
        WHERE id = ? AND client_id = ?
    ");
    $confirmStmt->execute([$rdv_id, $client_id]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fonction pour annuler un RDV
if (isset($_POST['cancel_rdv'])) {
    $rdv_id = $_POST['rdv_id'];
    $cancelStmt = $db->prepare("
        UPDATE rdv
        SET statut = 'annule'
        WHERE id = ? AND client_id = ?
    ");
    $cancelStmt->execute([$rdv_id, $client_id]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fonction pour réinitialiser le PIN
if (isset($_POST['reset_pin'])) {
    $ancien_pin = $_POST['ancien_pin'];
    $nouveau_pin = $_POST['nouveau_pin'];
    $confirmer_pin = $_POST['confirmer_pin'];
    if ($client['pin'] === $ancien_pin && $nouveau_pin === $confirmer_pin) {
        $resetPinStmt = $db->prepare("
            UPDATE clients
            SET pin = ?
            WHERE id = ?
        ");
        $resetPinStmt->execute([$nouveau_pin, $client_id]);
        $pin_message = "Votre code PIN a été réinitialisé avec succès.";
    } else {
        $pin_error = "L'ancien code PIN est incorrect ou les nouveaux codes PIN ne correspondent pas.";
    }
}

// Traitement de l'upload de photo de profil
if (isset($_FILES['photo_profil'])) {
    $client_id = $_SESSION['client_id'];
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT telephone FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    $telephone = $client['telephone'];
    $uploadDir = UPLOAD_DIR . $telephone . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $file = $_FILES['photo_profil'];
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    // Vérifier si le fichier est une image
    $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $updatePhotoStmt = $db->prepare("UPDATE clients SET photo_profil = ? WHERE id = ?");
            $updatePhotoStmt->execute([$fileName, $client_id]);
            header('Location: ' . $_SERVER['PHP_SELF'] . '#profil');
            exit;
        }
    }
}

// Mise à jour du profil
if (isset($_POST['update_profile'])) {
    $nom = secure($_POST['nom']);
    $prenoms = secure($_POST['prenoms']);
    $genre = secure($_POST['genre']);
    $email = secure($_POST['email']);
    $telephone = secure($_POST['telephone']);
    $ville = secure($_POST['ville']);
    $updateStmt = $db->prepare("
        UPDATE clients
        SET nom = ?, prenoms = ?, genre = ?, email = ?, telephone = ?, ville = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$nom, $prenoms, $genre, $email, $telephone, $ville, $client_id]);
    // Rafraîchir les données du client
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    $profile_message = "Votre profil a été mis à jour avec succès.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Mon Carnet</title>
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
            background: rgb(255 255 255 / 0%);
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
        .floating-button {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            text-align: center;
            line-height: 60px;
            font-size: 30px;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: transform 0.3s;
        }
        .floating-button.rotate {
            transform: rotate(45deg);
        }
        .button-column {
            position: fixed;
            bottom: 160px;
            right: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            z-index: 1000;
        }
        .button-column .btn {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
            position: absolute;
            right: 0;
        }
        .button-column .btn.show {
            position: relative;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .hidden {
            display: none;
        }
        .header-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-bubble {
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #e9ecef;
            border-radius: 20px;
            padding: 5px 15px;
            border-style: solid;
        }
        .profile-bubble img {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            padding: 0;
        }
        .bottom-nav .nav {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0;
        }
        .bottom-nav .nav-item {
            flex: 1;
            text-align: center;
        }
        .bottom-nav .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #6c757d;
            text-decoration: none;
            padding: 5px;
            font-size: 12px;
            border: none;
            background: none;
            transition: color 0.3s;
        }
        .bottom-nav .nav-link.active {
            color: #007bff;
        }
        .bottom-nav .nav-link:hover {
            color: #007bff;
            text-decoration: none;
        }
        .bottom-nav .nav-link i {
            font-size: 20px;
            margin-bottom: 2px;
        }
        .nav-tabs {
            display: none;
        }
        .rdv-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .rdv-card.planifie {
            border-left-color: #ffc107;
        }
        .rdv-card.confirme {
            border-left-color: #28a745;
        }
        .rdv-card.annule {
            border-left-color: #dc3545;
        }
        .rdv-card.termine {
            border-left-color: #6c757d;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 0.25em 0.6em;
        }
        .status-badge.planifie {
            background-color: #ffc107;
            color: #000;
        }
        .status-badge.confirme {
            background-color: #28a745;
            color: #fff;
        }
        .status-badge.annule {
            background-color: #dc3545;
            color: #fff;
        }
        .status-badge.termine {
            background-color: #6c757d;
            color: #fff;
        }
        .mb-3, .my-3 {
            margin-bottom: 1rem !important;
            margin-top: 10px;
        }
        .icon-yes {
            color: #28a745;
            font-size: 18px;
        }
        .icon-no {
            color: #dc3545;
            font-size: 18px;
        }
        .card-text:last-child {
            margin-bottom: 0;
            font-weight: bold;
        }
        .btn:not(:disabled):not(.disabled) {
            cursor: pointer;
            border-radius: 40px;
        }
        @media (max-width: 768px) {
            .bottom-nav .nav-link {
                font-size: 10px;
            }
            .bottom-nav .nav-link i {
                font-size: 18px;
            }
        }
        .footer {
            text-align: center;
            padding: 20px;
            background-color: rgba(248, 249, 250, 0);
            margin-top: 20px;
            border-radius: 40px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .social-icons a {
            margin: 0 10px;
            color: #007bff;
            font-size: 24px;
        }
        .info-button {
            background-color: transparent;
            border: none;
            color: red;
            font-size: 16px;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
        }
        .info-button.hidden {
            display: none;
        }
        /* Style pour le modal Nos Produits */
        #produitsModal .modal-content {
            background: url('https://centreflorence.com/images/slider3.png') no-repeat center center;
            background-size: cover;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
        }
        #produitsModal .modal-header, #produitsModal .modal-footer {
            border-color: rgba(255, 255, 255, 0.3);
        }
        #produitsModal .modal-body {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 5px;
        }
        /* Style pour la grille de photos */
        .photo-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .photo-date-group {
            margin-bottom: 20px;
        }
        .photo-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            padding: 10px;
        }
        .photo-item {
            position: relative;
        }
        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .photo-item img:hover {
            transform: scale(1.05);
        }
        .text-center {
            text-align: center !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- En-tête avec photo de profil -->
        <div class="header-container">
            <div class="profile-bubble">
                <?php if (!empty($client['photo_profil'])): ?>
                    <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $client['photo_profil']; ?>" alt="Photo de profil">
                <?php else: ?>
                    <img src="https://via.placeholder.com/50" alt="Photo de profil par défaut">
                <?php endif; ?>
                <h3 class="mb-0"><?php echo secure($client['prenoms'] . ' ' . $client['nom']); ?></h3>
            </div>
            <button type="button" class="info-button" id="infoButton" data-toggle="modal" data-target="#infoModal">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>
        <!-- Contenu principal -->
        <div class="tab-content" id="myTabContent">
            <!-- Onglet Accueil -->
            <div class="tab-pane fade show active" id="accueil" role="tabpanel" aria-labelledby="accueil-tab">
                <h2 class="mb-3" style="font-weight: bold;">Le Centre Florence Santé du Cheveu</h2>
                <p>Bienvenue sur votre espace personnel. Ici, vous pouvez gérer vos diagnostics, séances de traitement, contrôles et photos.</p>
                <!-- Section RDV -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-calendar-alt"></i> Mes Rendez-vous</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rdvs)): ?>
                            <p class="text-muted">Aucun rendez-vous programmé.</p>
                        <?php else: ?>
                            <?php foreach ($rdvs as $rdv): ?>
                                <div class="card rdv-card <?php echo $rdv['statut']; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h5 class="card-title">
                                                    <i class="fas fa-calendar-check"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv'])); ?>
                                                </h5>
                                                <p class="card-text">
                                                    <strong>Type:</strong> <?php echo ucfirst($rdv['type_rdv']); ?><br>
                                                    <strong>Exécutant:</strong> <?php echo secure($rdv['executant']); ?><br>
                                                    <strong>Statut:</strong>
                                                    <span class="badge status-badge <?php echo $rdv['statut']; ?>">
                                                        <?php echo ucfirst($rdv['statut']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#rdvModal<?php echo $rdv['id']; ?>">
                                                    <i class="fas fa-eye"></i> Détails
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#infoRdvModal">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                                <?php if ($rdv['statut'] == 'planifie' && strtotime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']) > time()): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                        <button type="submit" name="confirm_rdv" class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Confirmer
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                        <button type="submit" name="cancel_rdv" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-times"></i> Annuler
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal pour chaque RDV -->
                                <div class="modal fade" id="rdvModal<?php echo $rdv['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="rdvModalLabel<?php echo $rdv['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="rdvModalLabel<?php echo $rdv['id']; ?>">
                                                    <i class="fas fa-calendar-alt"></i> Détails du Rendez-vous
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6><i class="fas fa-calendar"></i> Date et Heure</h6>
                                                        <p class="mb-3"><?php echo date('d/m/Y à H:i', strtotime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv'])); ?></p>
                                                        <h6><i class="fas fa-user-md"></i> Exécutant</h6>
                                                        <p class="mb-3"><?php echo secure($rdv['executant']); ?></p>
                                                        <h6><i class="fas fa-clipboard"></i> Type de RDV</h6>
                                                        <p class="mb-3"><?php echo ucfirst($rdv['type_rdv']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6><i class="fas fa-info-circle"></i> Statut</h6>
                                                        <p class="mb-3">
                                                            <span class="badge badge-lg status-badge <?php echo $rdv['statut']; ?>">
                                                                <?php echo ucfirst($rdv['statut']); ?>
                                                            </span>
                                                        </p>
                                                        <h6><i class="fas fa-plus"></i> Date de création</h6>
                                                        <p class="mb-3"><?php echo date('d/m/Y à H:i', strtotime($rdv['date_creation'])); ?></p>
                                                        <?php if ($rdv['date_modification'] != $rdv['date_creation']): ?>
                                                            <h6><i class="fas fa-edit"></i> Dernière modification</h6>
                                                            <p class="mb-3"><?php echo date('d/m/Y à H:i', strtotime($rdv['date_modification'])); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php if ($rdv['message_prevention']): ?>
                                                    <div class="alert alert-info">
                                                        <h6><i class="fas fa-bell"></i> Message de prévention</h6>
                                                        <p class="mb-0"><?php echo secure($rdv['message_prevention']); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <?php if ($rdv['statut'] == 'planifie' && strtotime($rdv['date_rdv'] . ' ' . $rdv['heure_rdv']) > time()): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                        <button type="submit" name="confirm_rdv" class="btn btn-success">
                                                            <i class="fas fa-check"></i> Confirmer
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="rdv_id" value="<?php echo $rdv['id']; ?>">
                                                        <button type="submit" name="cancel_rdv" class="btn btn-danger">
                                                            <i class="fas fa-times"></i> Annuler
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Modal pour le message d'information -->
                <div class="modal fade" id="infoRdvModal" tabindex="-1" role="dialog" aria-labelledby="infoRdvModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="infoRdvModalLabel">Information</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>NB : En cas d'impossibilité, veuillez prévenir pour annuler le rendez-vous.</p>
                                <div class="d-flex justify-content-between mt-3">
                                    <div>
                                        <i class="fas fa-phone"></i>
                                        <a href="tel:+2250758099030">+225 0758099030</a>,
                                        <a href="tel:+2250778784394">+225 0778784394</a>,
                                        <a href="tel:+2252722542306">+225 2722542306</a>,
                                        <a href="tel:+2250779449779">+225 0779449779</a>
                                    </div>
                                    <div>
                                        <i class="fas fa-map-marker-alt"></i> Cocody II plateaux Vallon Non loin de la paroisse Sainte Cécile
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Section Conseils Pratiques -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-lightbulb"></i> Derniers Conseils Pratiques</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($conseils)): ?>
                            <p class="text-muted">Aucun conseil disponible.</p>
                        <?php else: ?>
                            <?php foreach ($conseils as $conseil): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo secure($conseil['titre']); ?></h5>
                                        <p class="card-text"><?php echo secure($conseil['description']); ?></p>
                                        <p class="card-text"><small class="text-muted">Publié le <?php echo formatDate($conseil['date_creation']); ?></small></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Bouton Nos Produits -->
                <div class="text-center my-4">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#produitsModal">
                        Nos Produits
                    </button>
                </div>
                <!-- Footer -->
                <div class="footer">
                    <img src="https://centreflorence.com/images/LOGO_BLANC1.png" alt="Logo" class="logo">
                    <div class="social-icons">
                        <a href="https://www.whatsapp.com" target="_blank"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://www.instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://centreflorence.com" target="_blank"><i class="fas fa-globe"></i></a>
                    </div>
                </div>
            </div>
            <!-- Onglet Diagnostics -->
            <div class="tab-pane fade" id="diagnostics" role="tabpanel" aria-labelledby="diagnostics-tab">
                <h2 class="mb-3">Mes Diagnostics</h2>
                <div class="row">
                    <?php foreach ($diagnostics as $index => $diagnostic): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card" data-toggle="modal" data-target="#diagnosticModal<?php echo $index; ?>">
                                <div class="card-body text-center">
                                    <p class="card-text"><?php echo formatDate($diagnostic['date_diagnostic']); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Modal pour chaque diagnostic -->
                        <div class="modal fade" id="diagnosticModal<?php echo $index; ?>" tabindex="-1" role="dialog" aria-labelledby="diagnosticModalLabel<?php echo $index; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="diagnosticModalLabel<?php echo $index; ?>">Détails du Diagnostic</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Date:</strong> <?php echo formatDate($diagnostic['date_diagnostic']); ?></p>
                                        <p><strong>Cheveux abîmés:</strong>
                                            <?php if ($diagnostic['cheveux_abimes']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Cheveux faibles:</strong>
                                            <?php if ($diagnostic['cheveux_faibles']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Cheveux perte densité:</strong>
                                            <?php if ($diagnostic['cheveux_perte_densite']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Cheveux trop gras:</strong>
                                            <?php if ($diagnostic['cheveux_trop_gras']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Alopécie androgénique:</strong>
                                            <?php if ($diagnostic['alopecie_androgenique']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                                <?php if (!empty($diagnostic['alopecie_androgenique_niveau'])): ?>
                                                    (Niveau: <?php echo $diagnostic['alopecie_androgenique_niveau']; ?>)
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Alopécie traction:</strong>
                                            <?php if ($diagnostic['alopecie_traction']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Pelade:</strong>
                                            <?php if ($diagnostic['pelade']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Psoriasis:</strong>
                                            <?php if ($diagnostic['psoriasis']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Teigne:</strong>
                                            <?php if ($diagnostic['teigne']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Texture naturels:</strong>
                                            <?php if ($diagnostic['texture_naturels']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Texture défrisés:</strong>
                                            <?php if ($diagnostic['texture_defrises']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Texture démêlés:</strong>
                                            <?php if ($diagnostic['texture_demeles']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Texture colorés:</strong>
                                            <?php if ($diagnostic['texture_colores']): ?>
                                                <i class="fas fa-check-circle icon-yes"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle icon-no"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Exécutant:</strong> <?php echo secure($diagnostic['executant']); ?></p>
                                        <!-- Affichage des photos du diagnostic -->
                                        <div class="row mt-4">
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                <?php if (!empty($diagnostic[$nomFichier])): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card">
                                                            <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $diagnostic[$nomFichier]; ?>" class="card-img-top" alt="Photo diagnostic <?php echo $i; ?>">
                                                            <div class="card-body text-center">
                                                                <button class="btn btn-sm btn-primary" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $diagnostic[$nomFichier]; ?>')">
                                                                    Agrandir
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Onglet Séances -->
            <div class="tab-pane fade" id="seances" role="tabpanel" aria-labelledby="seances-tab">
                <h2 class="mb-3">Mes Séances de Traitement</h2>
                <div class="row">
                    <?php foreach ($seances as $index => $seance): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card" data-toggle="modal" data-target="#seanceModal<?php echo $index; ?>">
                                <div class="card-body text-center">
                                    <p class="card-text">Séance <?php echo $seance['numero_seance']; ?></p>
                                    <p class="card-text"><small><?php echo formatDate($seance['date_seance']); ?></small></p>
                                </div>
                            </div>
                        </div>
                        <!-- Modal pour chaque séance -->
                        <div class="modal fade" id="seanceModal<?php echo $index; ?>" tabindex="-1" role="dialog" aria-labelledby="seanceModalLabel<?php echo $index; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="seanceModalLabel<?php echo $index; ?>">Détails de la Séance <?php echo $seance['numero_seance']; ?></h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Date:</strong> <?php echo formatDate($seance['date_seance']); ?></p>
                                        <p><strong>Soin:</strong> <?php echo secure($seance['soin']); ?></p>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <p><strong>Microneedle:</strong>
                                                    <?php if ($seance['microneedle']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Steamer:</strong>
                                                    <?php if ($seance['steamer']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Bain d'huile:</strong>
                                                    <?php if ($seance['bain_huile']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Bain médical:</strong>
                                                    <?php if ($seance['bain_medical']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Défrisage:</strong>
                                                    <?php if ($seance['defrisage']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Coloration:</strong>
                                                    <?php if ($seance['coloration']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Gommage:</strong>
                                                    <?php if ($seance['gommage']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Stimulation:</strong>
                                                    <?php if ($seance['stimulation']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if (!empty($seance['autres'])): ?>
                                            <p><strong>Autres:</strong> <?php echo secure($seance['autres']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($seance['executant'])): ?>
                                            <p><strong>Exécutant:</strong> <?php echo secure($seance['executant']); ?></p>
                                        <?php endif; ?>
                                        <!-- Affichage des photos de séance -->
                                        <div class="row mt-4">
                                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                                <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                <?php if (!empty($seance[$nomFichier])): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <div class="card">
                                                            <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $seance[$nomFichier]; ?>" class="card-img-top" alt="Photo séance <?php echo $i; ?>">
                                                            <div class="card-body text-center">
                                                                <button class="btn btn-sm btn-primary" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $seance[$nomFichier]; ?>')">
                                                                    Agrandir
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Onglet Contrôles -->
            <div class="tab-pane fade" id="controles" role="tabpanel" aria-labelledby="controles-tab">
                <h2 class="mb-3">Mes Contrôles</h2>
                <div class="row">
                    <?php foreach ($controles as $index => $controle): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card" data-toggle="modal" data-target="#controleModal<?php echo $index; ?>">
                                <div class="card-body text-center">
                                    <p class="card-text"><?php echo formatDate($controle['date_controle']); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Modal pour chaque contrôle -->
                        <div class="modal fade" id="controleModal<?php echo $index; ?>" tabindex="-1" role="dialog" aria-labelledby="controleModalLabel<?php echo $index; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="controleModalLabel<?php echo $index; ?>">Détails du Contrôle</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Date:</strong> <?php echo formatDate($controle['date_controle']); ?></p>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <p><strong>Réparation rapide fibre:</strong>
                                                    <?php if ($controle['reparation_rapide_fibre']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Réparation lente fibre:</strong>
                                                    <?php if ($controle['reparation_lente_fibre']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Repousse cheveux:</strong>
                                                    <?php if ($controle['repousse_cheveux']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Densité cheveux:</strong>
                                                    <?php if ($controle['densite_cheveux']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Élasticité cheveux:</strong>
                                                    <?php if ($controle['elasticite_cheveux']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                                <p><strong>Force cheveux:</strong>
                                                    <?php if ($controle['force_cheveux']): ?>
                                                        <i class="fas fa-check-circle icon-yes"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle icon-no"></i>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php if (!empty($controle['executant'])): ?>
                                            <p><strong>Exécutant:</strong> <?php echo secure($controle['executant']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($controle['observations'])): ?>
                                            <p><strong>Observations:</strong> <?php echo secure($controle['observations']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($controle['nom_fichier1']) || !empty($controle['nom_fichier2']) || !empty($controle['nom_fichier3'])): ?>
                                            <div class="row mt-3">
                                                <?php for (
                                                    $i = 1; $i <= 3; $i++): ?>
                                                    <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                    <?php if (!empty($controle[$nomFichier])): ?>
                                                        <div class="col-md-4 mb-3 text-center">
                                                            <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $controle[$nomFichier]; ?>" class="img-fluid" alt="Photo de contrôle <?php echo $i; ?>" style="max-height: 200px;">
                                                            <div class="mt-2">
                                                                <button class="btn btn-sm btn-primary" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $controle[$nomFichier]; ?>')">
                                                                    Agrandir
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Onglet Photos -->
            <div class="tab-pane fade" id="photos" role="tabpanel" aria-labelledby="photos-tab">
                <h2 class="mb-3">Mes Photos</h2>
                <div class="photo-grid">
                    <?php
                    // Regrouper les photos par date
                    $groupedPhotos = [];
                    foreach ($photos as $photo) {
                        $date = $photo['date_photo'];
                        if (!isset($groupedPhotos[$date])) {
                            $groupedPhotos[$date] = [];
                        }
                        $groupedPhotos[$date][] = $photo;
                    }

                    // Afficher les photos regroupées par date
                    foreach ($groupedPhotos as $date => $photosByDate): ?>
                        <div class="photo-date-group">
                            <h4><?php echo formatDate($date); ?></h4>
                            <div class="photo-row">
                                <?php foreach ($photosByDate as $index => $photo): ?>
                                    <?php if (!empty($photo['nom_fichier'])): ?>
                                        <div class="photo-item">
                                            <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $photo['nom_fichier']; ?>" data-toggle="modal" data-target="#photoModal<?php echo $date . $index; ?>" alt="Photo">
                                        </div>
                                        <!-- Modal pour chaque photo -->
                                        <div class="modal fade" id="photoModal<?php echo $date . $index; ?>" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel<?php echo $date . $index; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="photoModalLabel<?php echo $date . $index; ?>">Détails de la Photo</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $photo['nom_fichier']; ?>" class="img-fluid" alt="Photo">
                                                        <div class="mt-3">
                                                            <p><strong>Type:</strong> <?php echo $photo['type_photo']; ?></p>
                                                            <p><strong>Date:</strong> <?php echo formatDate($photo['date_photo']); ?></p>
                                                            <?php if (!empty($photo['commentaires'])): ?>
                                                                <p><strong>Commentaires:</strong> <?php echo secure($photo['commentaires']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Onglet Profil -->
            <div class="tab-pane fade" id="profil" role="tabpanel" aria-labelledby="profil-tab">
                <h2 class="mb-3">Mon Profil</h2>
                <?php if (isset($profile_message)): ?>
                    <div class="alert alert-success"><?php echo $profile_message; ?></div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="photo mb-3">
                                    <?php if (!empty($client['photo_profil'])): ?>
                                        <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $client['photo_profil']; ?>" alt="Photo de profil" class="img-thumbnail" style="width: 200px; height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/200" alt="Photo de profil par défaut" class="img-thumbnail">
                                    <?php endif; ?>
                                </div>
                                <form method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="photo_profil">Changer la photo de profil</label>
                                        <input type="file" class="form-control-file" id="photo_profil" name="photo_profil" accept="image/*">
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">Mettre à jour</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-user-edit"></i> Mes Informations</h4>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="nom">Nom</label>
                                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo secure($client['nom']); ?>" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="prenoms">Prénoms</label>
                                            <input type="text" class="form-control" id="prenoms" name="prenoms" value="<?php echo secure($client['prenoms']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="genre">Genre</label>
                                        <select class="form-control" id="genre" name="genre" required>
                                            <option value="masculin" <?php echo $client['genre'] == 'masculin' ? 'selected' : ''; ?>>Masculin</option>
                                            <option value="feminin" <?php echo $client['genre'] == 'feminin' ? 'selected' : ''; ?>>Féminin</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo secure($client['email']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="telephone">Téléphone</label>
                                        <input type="text" class="form-control" id="telephone" name="telephone" value="<?php echo secure($client['telephone']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="ville">Ville</label>
                                        <input type="text" class="form-control" id="ville" name="ville" value="<?php echo secure($client['ville']); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-lock"></i> Réinitialiser le code PIN</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($pin_message)): ?>
                                    <div class="alert alert-success"><?php echo $pin_message; ?></div>
                                <?php endif; ?>
                                <?php if (isset($pin_error)): ?>
                                    <div class="alert alert-danger"><?php echo $pin_error; ?></div>
                                <?php endif; ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label for="ancien_pin">Ancien code PIN</label>
                                        <input type="password" class="form-control" id="ancien_pin" name="ancien_pin" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="nouveau_pin">Nouveau code PIN</label>
                                        <input type="password" class="form-control" id="nouveau_pin" name="nouveau_pin" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirmer_pin">Confirmer le nouveau code PIN</label>
                                        <input type="password" class="form-control" id="confirmer_pin" name="confirmer_pin" required>
                                    </div>
                                    <button type="submit" name="reset_pin" class="btn btn-primary">Réinitialiser le code PIN</button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#appInfoModal">
                                <i class="fas fa-info-circle"></i> En savoir plus sur l'application
                            </button>
                            <a href="deconnexion.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt"></i> Se déconnecter
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals supplémentaires -->
    <!-- Modal pour "En savoir plus sur l'application" -->
    <div class="modal fade" id="appInfoModal" tabindex="-1" role="dialog" aria-labelledby="appInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="appInfoModalLabel">À propos de l'application</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <a href="https://webattou.com" target="_blank">
                        <img src="https://images.wakelet.com/resize?id=3M6-YKxOz4Q-aLIlv0iUz&w=1600&h=actual&q=85" alt="Logo" class="logo mb-3">
                    </a>
                    <p>Nous mettons notre expertise au service de votre réussite.</p>
                    <p>Création de sites web, solutions digitales sur-mesure, optimisation de votre présence en ligne…</p>
                    <p>Nous vous accompagnons à chaque étape de votre transformation numérique.</p>
                    <div class="social-icons mt-3">
                        <a href="https://www.instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a href="https://www.whatsapp.com" target="_blank"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://centreflorence.com" target="_blank"><i class="fas fa-globe"></i></a>
                        <a href="tel:+225075909397"><i class="fas fa-phone"></i></a>
                    </div>
                </div>
                <div class="modal-footer">
                    <p>Contactez-nous dès aujourd'hui pour concrétiser vos idées !</p>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal pour le bouton d'informations -->
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel">Information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <style>
                    .centered-paragraph {
                        text-align: center;
                    }
                    .logo-container {
                        text-align: center;
                        margin: 20px 0;
                    }
                    .logo-container img {
                        max-width: 150px;
                        height: auto;
                    }
                    </style>
                    <div class="logo-container">
                        <img src="https://centreflorence.com/images/LOGO_BLANC1.png" alt="Logo">
                    </div>
                    <p class="centered-paragraph"><strong>BIENVENUE DANS VOTRE HÔPITAL POUR CHEVEU</strong></p>
                    <p class="centered-paragraph"><strong>Le Centre Florence Santé du Cheveu</strong> est un institut capillaire spécialisé. À partir du diagnostic capillaire, nous décelons les anomalies et affections du cheveu et du cuir chevelu dans l'optique d'engager un protocole de traitement personnalisé.</p>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal pour l'aperçu de l'image -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">Aperçu de l'image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" class="img-fluid" alt="Aperçu de l'image" style="max-height: 80vh;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Nos Produits -->
    <div class="modal fade" id="produitsModal" tabindex="-1" role="dialog" aria-labelledby="produitsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="produitsModalLabel"><strong>NOS PRODUITS</strong></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="https://images.wakelet.com/resize?id=3h6GCqywz8uHGysb4guKa&w=1600&h=actual&q=85" alt="Logo" class="logo mb-3">
                    <p>Tous nos produits sont naturels, à 90% Bio. Sans silicone, sans dérivé de pétrole (huiles minérales) et sans paraben, ils sont à base des meilleurs huiles essentielles et végétales de croissance capillaire, ils sont extraits des plantes indiennes, brésiliennes, péruviennes, chinoises et ivoiriennes.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Navigation en bas -->
    <div class="bottom-nav">
        <ul class="nav" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="accueil-tab" data-toggle="tab" href="#accueil" role="tab" aria-controls="accueil" aria-selected="true">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="diagnostics-tab" data-toggle="tab" href="#diagnostics" role="tab" aria-controls="diagnostics" aria-selected="false">
                    <i class="fas fa-file-medical"></i>
                    <span>Diagnostics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="seances-tab" data-toggle="tab" href="#seances" role="tab" aria-controls="seances" aria-selected="false">
                    <i class="fas fa-calendar-check"></i>
                    <span>Séances</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="controles-tab" data-toggle="tab" href="#controles" role="tab" aria-controls="controles" aria-selected="false">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Contrôles</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="photos-tab" data-toggle="tab" href="#photos" role="tab" aria-controls="photos" aria-selected="false">
                    <i class="fas fa-images"></i>
                    <span>Photos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="profil-tab" data-toggle="tab" href="#profil" role="tab" aria-controls="profil" aria-selected="false">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- Boutons flottants -->
    <div class="button-column" id="buttonColumn">
        <a href="traitement.php" class="btn btn-primary">Ajouter une séance</a>
        <a href="diagnostic.php" class="btn btn-primary">Ajouter un diagnostic</a>
        <a href="controle.php" class="btn btn-primary">Ajouter un contrôle</a>
        <a href="photos.php" class="btn btn-primary">Gérer les photos</a>
        <a href="microneedle.php" class="btn btn-primary">Microneedle</a>
    </div>
    <div class="floating-button" id="toggleButton">+</div>
    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Gestion du bouton flottant
        $('#toggleButton').click(function() {
            $(this).toggleClass('rotate');
            $('#buttonColumn .btn').toggleClass('show');
        });
        // Gestion de l'affichage du bouton d'information
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var targetTab = $(e.target).attr("href");
            var infoButton = $('#infoButton');
            if (targetTab === "#accueil") {
                infoButton.removeClass('hidden');
            } else {
                infoButton.addClass('hidden');
            }
        });
        // Activation des onglets avec reload sur Accueil si déjà actif
        $('.nav-link').click(function (e) {
            var targetTab = $(this).attr('href');
            var isActive = $(this).hasClass('active');
            if (targetTab === "#accueil" && isActive) {
                location.reload();
                return;
            }
            e.preventDefault();
            $(this).tab('show');
        });
    });
    // Fonction pour afficher une image en grand
    function showImage(imageSrc) {
        $('#previewImage').attr('src', imageSrc);
        $('#imagePreviewModal').modal('show');
    }
    </script>
    <!-- Début du code d'abonnement aux notifications push -->
    <script>
    const publicVapidKey = 'TA_CLE_PUBLIQUE_VAPID_ICI'; // Mets ici ta clé publique VAPID

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('/service-worker.js')
        .then(function(swReg) {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    swReg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
                    }).then(function(subscription) {
                        fetch('/save_subscription.php', {
                            method: 'POST',
                            body: JSON.stringify(subscription),
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        });
                    });
                }
            });
        });
    }

    // Fonction utilitaire pour la clé VAPID
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    </script>
    <!-- Fin du code d'abonnement aux notifications push -->
</body>
</html>
