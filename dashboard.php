<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'carnet_cheveu');
define('UPLOAD_DIR', 'uploads/');
// Démarrage de la session
session_start();
// Vérification de l'authentification
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
// Connexion à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
// Fonction pour sécuriser les entrées
function secure_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
// Fonction pour formater les dates
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date('d/m/Y', strtotime($date));
}
// Fonction pour formater les heures
function formatTime($time) {
    if (empty($time) || $time === '00:00:00') {
        return '';
    }
    return date('H:i', strtotime($time));
}
// Mettre à jour le statut des rendez-vous passés
function updateRdvStatus($conn) {
    $currentDateTime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE rdv SET statut = 'Terminé' WHERE CONCAT(date_rdv, ' ', heure_rdv) < :currentDateTime");
    $stmt->bindParam(':currentDateTime', $currentDateTime);
    $stmt->execute();
}
// Appeler la fonction pour mettre à jour le statut des rendez-vous
updateRdvStatus($conn);
// Traitement des formulaires
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ajouter un client
    if (isset($_POST['add_client'])) {
        try {
            $nom = secure_input($_POST['nom']);
            $prenoms = secure_input($_POST['prenoms']);
            $genre = secure_input($_POST['genre']);
            $email = secure_input($_POST['email']);
            $telephone = secure_input($_POST['telephone']);
            $pin = secure_input($_POST['pin']);
            $ville = secure_input($_POST['ville']);
            // Gestion de l'upload de la photo
            $photo_profil = null;
            if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] == UPLOAD_ERR_OK) {
                $telephone = isset($_POST['telephone']) ? secure_input($_POST['telephone']) : '';
                $uploadDir = UPLOAD_DIR . $telephone . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $target_file = $uploadDir . basename($_FILES["photo_profil"]["name"]);
                if (move_uploaded_file($_FILES["photo_profil"]["tmp_name"], $target_file)) {
                    $photo_profil = $_FILES['photo_profil']['name'];
                }
            }
            $stmt = $conn->prepare("INSERT INTO clients (nom, prenoms, genre, email, telephone, pin, ville, photo_profil) VALUES (:nom, :prenoms, :genre, :email, :telephone, :pin, :ville, :photo_profil)");
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenoms', $prenoms);
            $stmt->bindParam(':genre', $genre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':pin', $pin);
            $stmt->bindParam(':ville', $ville);
            $stmt->bindParam(':photo_profil', $photo_profil);
            if ($stmt->execute()) {
                $success_message = "Nouveau client ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Modifier un client
    if (isset($_POST['edit_client'])) {
        try {
            $id = secure_input($_POST['id']);
            $nom = secure_input($_POST['nom']);
            $prenoms = secure_input($_POST['prenoms']);
            $genre = secure_input($_POST['genre']);
            $email = secure_input($_POST['email']);
            $telephone = secure_input($_POST['telephone']);
            $pin = secure_input($_POST['pin']);
            $ville = secure_input($_POST['ville']);
            // Gestion de l'upload de la photo
            $photo_profil = $_POST['existing_photo_profil'];
            if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] == UPLOAD_ERR_OK) {
                $telephone = isset($_POST['telephone']) ? secure_input($_POST['telephone']) : '';
                $uploadDir = UPLOAD_DIR . $telephone . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $target_file = $uploadDir . basename($_FILES["photo_profil"]["name"]);
                if (move_uploaded_file($_FILES["photo_profil"]["tmp_name"], $target_file)) {
                    $photo_profil = $_FILES['photo_profil']['name'];
                }
            }
            $stmt = $conn->prepare("UPDATE clients SET nom = :nom, prenoms = :prenoms, genre = :genre, email = :email, telephone = :telephone, pin = :pin, ville = :ville, photo_profil = :photo_profil WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenoms', $prenoms);
            $stmt->bindParam(':genre', $genre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':pin', $pin);
            $stmt->bindParam(':ville', $ville);
            $stmt->bindParam(':photo_profil', $photo_profil);
            if ($stmt->execute()) {
                $success_message = "Client modifié avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Ajouter un conseil
    if (isset($_POST['add_conseil'])) {
        try {
            $titre = secure_input($_POST['titre']);
            $description = secure_input($_POST['description']);
            $stmt = $conn->prepare("INSERT INTO conseils_pratiques (titre, description) VALUES (:titre, :description)");
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            if ($stmt->execute()) {
                $success_message = "Nouveau conseil ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Modifier un conseil
    if (isset($_POST['edit_conseil'])) {
        try {
            $id = secure_input($_POST['id']);
            $titre = secure_input($_POST['titre']);
            $description = secure_input($_POST['description']);
            $stmt = $conn->prepare("UPDATE conseils_pratiques SET titre = :titre, description = :description WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            if ($stmt->execute()) {
                $success_message = "Conseil modifié avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Ajouter un contrôle
    if (isset($_POST['add_controle'])) {
        try {
            $client_id = secure_input($_POST['client_id']);
            $date_controle = secure_input($_POST['date_controle']);
            $reparation_rapide_fibre = isset($_POST['reparation_rapide_fibre']) ? 1 : 0;
            $reparation_lente_fibre = isset($_POST['reparation_lente_fibre']) ? 1 : 0;
            $repousse_cheveux = isset($_POST['repousse_cheveux']) ? 1 : 0;
            $densite_cheveux = isset($_POST['densite_cheveux']) ? 1 : 0;
            $elasticite_cheveux = isset($_POST['elasticite_cheveux']) ? 1 : 0;
            $force_cheveux = isset($_POST['force_cheveux']) ? 1 : 0;
            $executant = secure_input($_POST['executant']);
            $observations = secure_input($_POST['observations']);
            // Gestion de l'upload des photos
            $nom_fichier1 = null;
            $nom_fichier2 = null;
            $nom_fichier3 = null;
            if (!file_exists(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
            for ($i = 1; $i <= 3; $i++) {
                $fileKey = 'nom_fichier' . $i;
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
                    $file = $_FILES[$fileKey];
                    $fileName = uniqid() . '_' . basename($file['name']);
                    $targetPath = UPLOAD_DIR . $fileName;
                    $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($imageFileType, $allowedTypes)) {
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            ${'nom_fichier' . $i} = $fileName;
                        }
                    }
                }
            }
            $stmt = $conn->prepare("INSERT INTO controles (client_id, date_controle, reparation_rapide_fibre, reparation_lente_fibre, repousse_cheveux, densite_cheveux, elasticite_cheveux, force_cheveux, executant, observations, nom_fichier1, nom_fichier2, nom_fichier3) VALUES (:client_id, :date_controle, :reparation_rapide_fibre, :reparation_lente_fibre, :repousse_cheveux, :densite_cheveux, :elasticite_cheveux, :force_cheveux, :executant, :observations, :nom_fichier1, :nom_fichier2, :nom_fichier3)");
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':date_controle', $date_controle);
            $stmt->bindParam(':reparation_rapide_fibre', $reparation_rapide_fibre);
            $stmt->bindParam(':reparation_lente_fibre', $reparation_lente_fibre);
            $stmt->bindParam(':repousse_cheveux', $repousse_cheveux);
            $stmt->bindParam(':densite_cheveux', $densite_cheveux);
            $stmt->bindParam(':elasticite_cheveux', $elasticite_cheveux);
            $stmt->bindParam(':force_cheveux', $force_cheveux);
            $stmt->bindParam(':executant', $executant);
            $stmt->bindParam(':observations', $observations);
            $stmt->bindParam(':nom_fichier1', $nom_fichier1);
            $stmt->bindParam(':nom_fichier2', $nom_fichier2);
            $stmt->bindParam(':nom_fichier3', $nom_fichier3);
            if ($stmt->execute()) {
                $success_message = "Nouveau contrôle ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Modifier un contrôle
    if (isset($_POST['edit_controle'])) {
        try {
            $id = secure_input($_POST['id']);
            $client_id = secure_input($_POST['client_id']);
            $date_controle = secure_input($_POST['date_controle']);
            $reparation_rapide_fibre = isset($_POST['reparation_rapide_fibre']) ? 1 : 0;
            $reparation_lente_fibre = isset($_POST['reparation_lente_fibre']) ? 1 : 0;
            $repousse_cheveux = isset($_POST['repousse_cheveux']) ? 1 : 0;
            $densite_cheveux = isset($_POST['densite_cheveux']) ? 1 : 0;
            $elasticite_cheveux = isset($_POST['elasticite_cheveux']) ? 1 : 0;
            $force_cheveux = isset($_POST['force_cheveux']) ? 1 : 0;
            $executant = secure_input($_POST['executant']);
            $observations = secure_input($_POST['observations']);
            // Gestion de l'upload de la photo
            $nom_fichier = $_POST['existing_nom_fichier'];
            if (isset($_FILES['nom_fichier']) && $_FILES['nom_fichier']['error'] == UPLOAD_ERR_OK) {
                $telephone = isset($_POST['telephone']) ? secure_input($_POST['telephone']) : '';
                $uploadDir = UPLOAD_DIR . $telephone . '/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $target_file = $uploadDir . basename($_FILES["nom_fichier"]["name"]);
                if (move_uploaded_file($_FILES["nom_fichier"]["tmp_name"], $target_file)) {
                    $nom_fichier = $_FILES['nom_fichier']['name'];
                }
            }
            $stmt = $conn->prepare("UPDATE controles SET client_id = :client_id, date_controle = :date_controle, reparation_rapide_fibre = :reparation_rapide_fibre, reparation_lente_fibre = :reparation_lente_fibre, repousse_cheveux = :repousse_cheveux, densite_cheveux = :densite_cheveux, elasticite_cheveux = :elasticite_cheveux, force_cheveux = :force_cheveux, executant = :executant, observations = :observations, nom_fichier = :nom_fichier WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':date_controle', $date_controle);
            $stmt->bindParam(':reparation_rapide_fibre', $reparation_rapide_fibre);
            $stmt->bindParam(':reparation_lente_fibre', $reparation_lente_fibre);
            $stmt->bindParam(':repousse_cheveux', $repousse_cheveux);
            $stmt->bindParam(':densite_cheveux', $densite_cheveux);
            $stmt->bindParam(':elasticite_cheveux', $elasticite_cheveux);
            $stmt->bindParam(':force_cheveux', $force_cheveux);
            $stmt->bindParam(':executant', $executant);
            $stmt->bindParam(':observations', $observations);
            $stmt->bindParam(':nom_fichier', $nom_fichier);
            if ($stmt->execute()) {
                $success_message = "Contrôle modifié avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Ajouter un diagnostic
    if (isset($_POST['add_diagnostic'])) {
        try {
            $client_id = secure_input($_POST['client_id']);
            $date_diagnostic = secure_input($_POST['date_diagnostic']);
            $cheveux_abimes = isset($_POST['cheveux_abimes']) ? 1 : 0;
            $cheveux_faibles = isset($_POST['cheveux_faibles']) ? 1 : 0;
            $cheveux_perte_densite = isset($_POST['cheveux_perte_densite']) ? 1 : 0;
            $cheveux_trop_gras = isset($_POST['cheveux_trop_gras']) ? 1 : 0;
            $alopecie_androgenique = isset($_POST['alopecie_androgenique']) ? 1 : 0;
            $alopecie_androgenique_niveau = secure_input($_POST['alopecie_androgenique_niveau']);
            $alopecie_traction = isset($_POST['alopecie_traction']) ? 1 : 0;
            $pelade = isset($_POST['pelade']) ? 1 : 0;
            $psoriasis = isset($_POST['psoriasis']) ? 1 : 0;
            $teigne = isset($_POST['teigne']) ? 1 : 0;
            $texture_naturels = isset($_POST['texture_naturels']) ? 1 : 0;
            $texture_defrises = isset($_POST['texture_defrises']) ? 1 : 0;
            $texture_demeles = isset($_POST['texture_demeles']) ? 1 : 0;
            $texture_colores = isset($_POST['texture_colores']) ? 1 : 0;
            $executant = secure_input($_POST['executant']);
            // Gestion de l'upload des photos
            $nom_fichier1 = null;
            $nom_fichier2 = null;
            $nom_fichier3 = null;
            if (!file_exists(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
            for ($i = 1; $i <= 3; $i++) {
                $fileKey = 'nom_fichier' . $i;
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
                    $file = $_FILES[$fileKey];
                    $fileName = uniqid() . '_' . basename($file['name']);
                    $targetPath = UPLOAD_DIR . $fileName;
                    $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($imageFileType, $allowedTypes)) {
                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            ${'nom_fichier' . $i} = $fileName;
                        }
                    }
                }
            }
            $stmt = $conn->prepare("INSERT INTO diagnostics (client_id, date_diagnostic, cheveux_abimes, cheveux_faibles, cheveux_perte_densite, cheveux_trop_gras, alopecie_androgenique, alopecie_androgenique_niveau, alopecie_traction, pelade, psoriasis, teigne, texture_naturels, texture_defrises, texture_demeles, texture_colores, executant, nom_fichier1, nom_fichier2, nom_fichier3) VALUES (:client_id, :date_diagnostic, :cheveux_abimes, :cheveux_faibles, :cheveux_perte_densite, :cheveux_trop_gras, :alopecie_androgenique, :alopecie_androgenique_niveau, :alopecie_traction, :pelade, :psoriasis, :teigne, :texture_naturels, :texture_defrises, :texture_demeles, :texture_colores, :executant, :nom_fichier1, :nom_fichier2, :nom_fichier3)");
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':date_diagnostic', $date_diagnostic);
            $stmt->bindParam(':cheveux_abimes', $cheveux_abimes);
            $stmt->bindParam(':cheveux_faibles', $cheveux_faibles);
            $stmt->bindParam(':cheveux_perte_densite', $cheveux_perte_densite);
            $stmt->bindParam(':cheveux_trop_gras', $cheveux_trop_gras);
            $stmt->bindParam(':alopecie_androgenique', $alopecie_androgenique);
            $stmt->bindParam(':alopecie_androgenique_niveau', $alopecie_androgenique_niveau);
            $stmt->bindParam(':alopecie_traction', $alopecie_traction);
            $stmt->bindParam(':pelade', $pelade);
            $stmt->bindParam(':psoriasis', $psoriasis);
            $stmt->bindParam(':teigne', $teigne);
            $stmt->bindParam(':texture_naturels', $texture_naturels);
            $stmt->bindParam(':texture_defrises', $texture_defrises);
            $stmt->bindParam(':texture_demeles', $texture_demeles);
            $stmt->bindParam(':texture_colores', $texture_colores);
            $stmt->bindParam(':executant', $executant);
            $stmt->bindParam(':nom_fichier1', $nom_fichier1);
            $stmt->bindParam(':nom_fichier2', $nom_fichier2);
            $stmt->bindParam(':nom_fichier3', $nom_fichier3);
            if ($stmt->execute()) {
                $success_message = "Nouveau diagnostic ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Modifier un diagnostic
    if (isset($_POST['edit_diagnostic'])) {
        try {
            $id = secure_input($_POST['id']);
            $client_id = secure_input($_POST['client_id']);
            $date_diagnostic = secure_input($_POST['date_diagnostic']);
            $cheveux_abimes = isset($_POST['cheveux_abimes']) ? 1 : 0;
            $cheveux_faibles = isset($_POST['cheveux_faibles']) ? 1 : 0;
            $cheveux_perte_densite = isset($_POST['cheveux_perte_densite']) ? 1 : 0;
            $cheveux_trop_gras = isset($_POST['cheveux_trop_gras']) ? 1 : 0;
            $alopecie_androgenique = isset($_POST['alopecie_androgenique']) ? 1 : 0;
            $alopecie_androgenique_niveau = secure_input($_POST['alopecie_androgenique_niveau']);
            $alopecie_traction = isset($_POST['alopecie_traction']) ? 1 : 0;
            $pelade = isset($_POST['pelade']) ? 1 : 0;
            $psoriasis = isset($_POST['psoriasis']) ? 1 : 0;
            $teigne = isset($_POST['teigne']) ? 1 : 0;
            $texture_naturels = isset($_POST['texture_naturels']) ? 1 : 0;
            $texture_defrises = isset($_POST['texture_defrises']) ? 1 : 0;
            $texture_demeles = isset($_POST['texture_demeles']) ? 1 : 0;
            $texture_colores = isset($_POST['texture_colores']) ? 1 : 0;
            $executant = secure_input($_POST['executant']);
            // Gestion upload photos
            $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $photoNames = [];
            for ($i = 1; $i <= 3; $i++) {
                $fileKey = 'edit_nom_fichier' . $i;
                $existingKey = 'existing_nom_fichier' . $i;
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '_' . basename($_FILES[$fileKey]['name']);
                    $targetPath = $uploadDir . $fileName;
                    $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($imageFileType, $allowedTypes)) {
                        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                            $photoNames[$i] = $fileName;
                        } else {
                            $photoNames[$i] = $_POST[$existingKey] ?? null;
                        }
                    } else {
                        $photoNames[$i] = $_POST[$existingKey] ?? null;
                    }
                } else {
                    $photoNames[$i] = $_POST[$existingKey] ?? null;
                }
            }
            $stmt = $conn->prepare("UPDATE diagnostics SET client_id = :client_id, date_diagnostic = :date_diagnostic, cheveux_abimes = :cheveux_abimes, cheveux_faibles = :cheveux_faibles, cheveux_perte_densite = :cheveux_perte_densite, cheveux_trop_gras = :cheveux_trop_gras, alopecie_androgenique = :alopecie_androgenique, alopecie_androgenique_niveau = :alopecie_androgenique_niveau, alopecie_traction = :alopecie_traction, pelade = :pelade, psoriasis = :psoriasis, teigne = :teigne, texture_naturels = :texture_naturels, texture_defrises = :texture_defrises, texture_demeles = :texture_demeles, texture_colores = :texture_colores, executant = :executant, nom_fichier1 = :nom_fichier1, nom_fichier2 = :nom_fichier2, nom_fichier3 = :nom_fichier3 WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':date_diagnostic', $date_diagnostic);
            $stmt->bindParam(':cheveux_abimes', $cheveux_abimes);
            $stmt->bindParam(':cheveux_faibles', $cheveux_faibles);
            $stmt->bindParam(':cheveux_perte_densite', $cheveux_perte_densite);
            $stmt->bindParam(':cheveux_trop_gras', $cheveux_trop_gras);
            $stmt->bindParam(':alopecie_androgenique', $alopecie_androgenique);
            $stmt->bindParam(':alopecie_androgenique_niveau', $alopecie_androgenique_niveau);
            $stmt->bindParam(':alopecie_traction', $alopecie_traction);
            $stmt->bindParam(':pelade', $pelade);
            $stmt->bindParam(':psoriasis', $psoriasis);
            $stmt->bindParam(':teigne', $teigne);
            $stmt->bindParam(':texture_naturels', $texture_naturels);
            $stmt->bindParam(':texture_defrises', $texture_defrises);
            $stmt->bindParam(':texture_demeles', $texture_demeles);
            $stmt->bindParam(':texture_colores', $texture_colores);
            $stmt->bindParam(':executant', $executant);
            $stmt->bindParam(':nom_fichier1', $photoNames[1]);
            $stmt->bindParam(':nom_fichier2', $photoNames[2]);
            $stmt->bindParam(':nom_fichier3', $photoNames[3]);
            if ($stmt->execute()) {
                $success_message = "Diagnostic modifié avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Ajouter un rendez-vous
    if (isset($_POST['add_rdv'])) {
        try {
            $client_id = secure_input($_POST['client_id']);
            $date_rdv = secure_input($_POST['date_rdv']);
            $heure_rdv = secure_input($_POST['heure_rdv']);
            $message_prevention = secure_input($_POST['message_prevention']);
            $statut = secure_input($_POST['statut']);
            $type_rdv = secure_input($_POST['type_rdv']);
            $executant = secure_input($_POST['executant']);
            $stmt = $conn->prepare("INSERT INTO rdv (client_id, date_rdv, heure_rdv, message_prevention, statut, type_rdv, executant) VALUES (:client_id, :date_rdv, :heure_rdv, :message_prevention, :statut, :type_rdv, :executant)");
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':date_rdv', $date_rdv);
            $stmt->bindParam(':heure_rdv', $heure_rdv);
            $stmt->bindParam(':message_prevention', $message_prevention);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':type_rdv', $type_rdv);
            $stmt->bindParam(':executant', $executant);
            if ($stmt->execute()) {
                $success_message = "Nouveau rendez-vous ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
    // Modifier un rendez-vous
    if (isset($_POST['edit_rdv'])) {
        try {
            $id = secure_input($_POST['id']);
            $client_id = secure_input($_POST['client_id']);
            $date_rdv = secure_input($_POST['date_rdv']);
            $heure_rdv = secure_input($_POST['heure_rdv']);
            $message_prevention = secure_input($_POST['message_prevention']);
            $statut = secure_input($_POST['statut']);
            $type_rdv = secure_input($_POST['type_rdv']);
            $executant = secure_input($_POST['executant']);
            $stmt = $conn->prepare("UPDATE rdv SET client_id = :client_id, date_rdv = :date_rdv, heure_rdv = :heure_rdv, message_prevention = :message_prevention, statut = :statut, type_rdv = :type_rdv, executant = :executant WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':date_rdv', $date_rdv);
            $stmt->bindParam(':heure_rdv', $heure_rdv);
            $stmt->bindParam(':message_prevention', $message_prevention);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':type_rdv', $type_rdv);
            $stmt->bindParam(':executant', $executant);
            if ($stmt->execute()) {
                $success_message = "Rendez-vous modifié avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
// Ajouter une séance
if (isset($_POST['add_seance'])) {
    try {
        $client_id = secure_input($_POST['client_id']);
        $date_seance = secure_input($_POST['date_seance']);
        $numero_seance = secure_input($_POST['numero_seance']);
        $soin = isset($_POST['soin']) ? secure_input($_POST['soin']) : null;
        $microneedle = isset($_POST['microneedle']) ? 1 : 0;
        $steamer = isset($_POST['steamer']) ? 1 : 0;
        $bain_huile = isset($_POST['bain_huile']) ? 1 : 0;
        $bain_medical = isset($_POST['bain_medical']) ? 1 : 0;
        $defrisage = isset($_POST['defrisage']) ? 1 : 0;
        $coloration = isset($_POST['coloration']) ? 1 : 0;
        $gommage = isset($_POST['gommage']) ? 1 : 0;
        $stimulation = isset($_POST['stimulation']) ? 1 : 0;
        $autres = isset($_POST['autres']) ? secure_input($_POST['autres']) : null;
        $executant = secure_input($_POST['executant']);
        // Gestion de l'upload des photos
        $nom_fichier1 = null;
        $nom_fichier2 = null;
        $nom_fichier3 = null;
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        for ($i = 1; $i <= 3; $i++) {
            if (isset($_FILES['nom_fichier' . $i]) && $_FILES['nom_fichier' . $i]['error'] == UPLOAD_ERR_OK) {
                $file = $_FILES['nom_fichier' . $i];
                $fileName = basename($file['name']);
                $targetPath = UPLOAD_DIR . $fileName;
                // Vérifier si le fichier est une image
                $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowedTypes)) {
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        ${'nom_fichier' . $i} = $fileName;
                    }
                }
            }
        }
        $stmt = $conn->prepare("INSERT INTO seances (client_id, date_seance, numero_seance, soin, microneedle, steamer, bain_huile, bain_medical, defrisage, coloration, gommage, stimulation, autres, executant, nom_fichier1, nom_fichier2, nom_fichier3) VALUES (:client_id, :date_seance, :numero_seance, :soin, :microneedle, :steamer, :bain_huile, :bain_medical, :defrisage, :coloration, :gommage, :stimulation, :autres, :executant, :nom_fichier1, :nom_fichier2, :nom_fichier3)");
        $stmt->bindParam(':client_id', $client_id);
        $stmt->bindParam(':date_seance', $date_seance);
        $stmt->bindParam(':numero_seance', $numero_seance);
        $stmt->bindParam(':soin', $soin);
        $stmt->bindParam(':microneedle', $microneedle);
        $stmt->bindParam(':steamer', $steamer);
        $stmt->bindParam(':bain_huile', $bain_huile);
        $stmt->bindParam(':bain_medical', $bain_medical);
        $stmt->bindParam(':defrisage', $defrisage);
        $stmt->bindParam(':coloration', $coloration);
        $stmt->bindParam(':gommage', $gommage);
        $stmt->bindParam(':stimulation', $stimulation);
        $stmt->bindParam(':autres', $autres);
        $stmt->bindParam(':executant', $executant);
        $stmt->bindParam(':nom_fichier1', $nom_fichier1);
        $stmt->bindParam(':nom_fichier2', $nom_fichier2);
        $stmt->bindParam(':nom_fichier3', $nom_fichier3);
        if ($stmt->execute()) {
            $success_message = "Nouvelle séance ajoutée avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
    // Modifier une séance
if (isset($_POST['edit_seance'])) {
    try {
        $id = secure_input($_POST['id']);
        $client_id = secure_input($_POST['client_id']);
        $date_seance = secure_input($_POST['date_seance']);
        $numero_seance = secure_input($_POST['numero_seance']);
        $soin = isset($_POST['soin']) ? secure_input($_POST['soin']) : null;
        $microneedle = isset($_POST['microneedle']) ? 1 : 0;
        $steamer = isset($_POST['steamer']) ? 1 : 0;
        $bain_huile = isset($_POST['bain_huile']) ? 1 : 0;
        $bain_medical = isset($_POST['bain_medical']) ? 1 : 0;
        $defrisage = isset($_POST['defrisage']) ? 1 : 0;
        $coloration = isset($_POST['coloration']) ? 1 : 0;
        $gommage = isset($_POST['gommage']) ? 1 : 0;
        $stimulation = isset($_POST['stimulation']) ? 1 : 0;
        $autres = isset($_POST['autres']) ? secure_input($_POST['autres']) : null;
        $executant = secure_input($_POST['executant']);
        // Récupérer les noms de fichiers existants
        $existing_nom_fichier1 = isset($_POST['existing_nom_fichier1']) ? $_POST['existing_nom_fichier1'] : null;
        $existing_nom_fichier2 = isset($_POST['existing_nom_fichier2']) ? $_POST['existing_nom_fichier2'] : null;
        $existing_nom_fichier3 = isset($_POST['existing_nom_fichier3']) ? $_POST['existing_nom_fichier3'] : null;
        // Gestion de l'upload des nouveaux fichiers
        $nom_fichier1 = $existing_nom_fichier1;
        $nom_fichier2 = $existing_nom_fichier2;
        $nom_fichier3 = $existing_nom_fichier3;
        if (!file_exists(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        for ($i = 1; $i <= 3; $i++) {
            if (isset($_FILES['nom_fichier' . $i]) && $_FILES['nom_fichier' . $i]['error'] == UPLOAD_ERR_OK) {
                $file = $_FILES['nom_fichier' . $i];
                $fileName = basename($file['name']);
                $targetPath = UPLOAD_DIR . $fileName;
                // Vérifier si le fichier est une image
                $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowedTypes)) {
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        ${'nom_fichier' . $i} = $fileName;
                    }
                }
            }
        }
        $stmt = $conn->prepare("UPDATE seances SET client_id = :client_id, date_seance = :date_seance, numero_seance = :numero_seance, soin = :soin, microneedle = :microneedle, steamer = :steamer, bain_huile = :bain_huile, bain_medical = :bain_medical, defrisage = :defrisage, coloration = :coloration, gommage = :gommage, stimulation = :stimulation, autres = :autres, executant = :executant, nom_fichier1 = :nom_fichier1, nom_fichier2 = :nom_fichier2, nom_fichier3 = :nom_fichier3 WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':client_id', $client_id);
        $stmt->bindParam(':date_seance', $date_seance);
        $stmt->bindParam(':numero_seance', $numero_seance);
        $stmt->bindParam(':soin', $soin);
        $stmt->bindParam(':microneedle', $microneedle);
        $stmt->bindParam(':steamer', $steamer);
        $stmt->bindParam(':bain_huile', $bain_huile);
        $stmt->bindParam(':bain_medical', $bain_medical);
        $stmt->bindParam(':defrisage', $defrisage);
        $stmt->bindParam(':coloration', $coloration);
        $stmt->bindParam(':gommage', $gommage);
        $stmt->bindParam(':stimulation', $stimulation);
        $stmt->bindParam(':autres', $autres);
        $stmt->bindParam(':executant', $executant);
        $stmt->bindParam(':nom_fichier1', $nom_fichier1);
        $stmt->bindParam(':nom_fichier2', $nom_fichier2);
        $stmt->bindParam(':nom_fichier3', $nom_fichier3);
        if ($stmt->execute()) {
            $success_message = "Séance modifiée avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
    // Ajouter un utilisateur
    if (isset($_POST['add_utilisateur'])) {
        try {
            $nom_utilisateur = secure_input($_POST['nom_utilisateur']);
            $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
            $role = secure_input($_POST['role']);
            $nom_complet = secure_input($_POST['nom_complet']);
            $stmt = $conn->prepare("INSERT INTO utilisateurs (nom_utilisateur, nom_complet, mot_de_passe, role) VALUES (:nom_utilisateur, :nom_complet, :mot_de_passe, :role)");
            $stmt->bindParam(':nom_utilisateur', $nom_utilisateur);
            $stmt->bindParam(':nom_complet', $nom_complet);
            $stmt->bindParam(':mot_de_passe', $mot_de_passe);
            $stmt->bindParam(':role', $role);
            if ($stmt->execute()) {
                $success_message = "Nouvel utilisateur ajouté avec succès";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur: " . $e->getMessage();
        }
    }
}
// Suppression d'éléments
if (isset($_GET['delete_client'])) {
    try {
        $id = secure_input($_GET['delete_client']);
        $stmt = $conn->prepare("DELETE FROM clients WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            $success_message = "Client supprimé avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
if (isset($_GET['delete_conseil'])) {
    try {
        $id = secure_input($_GET['delete_conseil']);
        $stmt = $conn->prepare("DELETE FROM conseils_pratiques WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            $success_message = "Conseil supprimé avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
if (isset($_GET['delete_controle'])) {
    try {
        $id = secure_input($_GET['delete_controle']);
        $stmt = $conn->prepare("DELETE FROM controles WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            $success_message = "Contrôle supprimé avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
if (isset($_GET['delete_diagnostic'])) {
    try {
        $id = secure_input($_GET['delete_diagnostic']);
        $stmt = $conn->prepare("DELETE FROM diagnostics WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            $success_message = "Diagnostic supprimé avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
if (isset($_GET['delete_seance'])) {
    try {
        $id = secure_input($_GET['delete_seance']);
        $stmt = $conn->prepare("DELETE FROM seances WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            $success_message = "Séance supprimée avec succès";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur: " . $e->getMessage();
    }
}
// Récupération des données avec filtres de recherche
// Clients
$search_nom = isset($_GET['search_nom']) ? secure_input($_GET['search_nom']) : '';
$search_ville = isset($_GET['search_ville']) ? secure_input($_GET['search_ville']) : '';
$search_genre = isset($_GET['search_genre']) ? secure_input($_GET['search_genre']) : '';
$sql_clients = "SELECT * FROM clients WHERE nom LIKE :search_nom";
if (!empty($search_ville)) {
    $sql_clients .= " AND ville = :search_ville";
}
if (!empty($search_genre)) {
    $sql_clients .= " AND genre = :search_genre";
}
$stmt = $conn->prepare($sql_clients);
$stmt->bindValue(':search_nom', "%$search_nom%");
if (!empty($search_ville)) {
    $stmt->bindParam(':search_ville', $search_ville);
}
if (!empty($search_genre)) {
    $stmt->bindParam(':search_genre', $search_genre);
}
$stmt->execute();
$clients = $stmt->fetchAll();
// Conseils
$stmt = $conn->prepare("SELECT id, titre, description, date_creation FROM conseils_pratiques");
$stmt->execute();
$conseils = $stmt->fetchAll();
// Contrôles
$search_client_id = isset($_GET['search_client_id']) ? secure_input($_GET['search_client_id']) : '';
$search_date_controle = isset($_GET['search_date_controle']) ? secure_input($_GET['search_date_controle']) : '';
$search_executant = isset($_GET['search_executant']) ? secure_input($_GET['search_executant']) : '';
$sql_controles = "SELECT * FROM controles WHERE 1=1";
if (!empty($search_client_id)) {
    $sql_controles .= " AND client_id = :search_client_id";
}
if (!empty($search_date_controle)) {
    $sql_controles .= " AND date_controle = :search_date_controle";
}
if (!empty($search_executant)) {
    $sql_controles .= " AND executant LIKE :search_executant";
}
$stmt = $conn->prepare($sql_controles);
if (!empty($search_client_id)) {
    $stmt->bindParam(':search_client_id', $search_client_id);
}
if (!empty($search_date_controle)) {
    $stmt->bindParam(':search_date_controle', $search_date_controle);
}
if (!empty($search_executant)) {
    $search_executant = "%$search_executant%";
    $stmt->bindParam(':search_executant', $search_executant);
}
$stmt->execute();
$controles = $stmt->fetchAll();
// Diagnostics
$search_client_id_diag = isset($_GET['search_client_id_diag']) ? secure_input($_GET['search_client_id_diag']) : '';
$search_date_diagnostic = isset($_GET['search_date_diagnostic']) ? secure_input($_GET['search_date_diagnostic']) : '';
$search_executant_diag = isset($_GET['search_executant_diag']) ? secure_input($_GET['search_executant_diag']) : '';
$sql_diagnostics = "SELECT * FROM diagnostics WHERE 1=1";
if (!empty($search_client_id_diag)) {
    $sql_diagnostics .= " AND client_id = :search_client_id_diag";
}
if (!empty($search_date_diagnostic)) {
    $sql_diagnostics .= " AND date_diagnostic = :search_date_diagnostic";
}
if (!empty($search_executant_diag)) {
    $sql_diagnostics .= " AND executant LIKE :search_executant_diag";
}
$stmt = $conn->prepare($sql_diagnostics);
if (!empty($search_client_id_diag)) {
    $stmt->bindParam(':search_client_id_diag', $search_client_id_diag);
}
if (!empty($search_date_diagnostic)) {
    $stmt->bindParam(':search_date_diagnostic', $search_date_diagnostic);
}
if (!empty($search_executant_diag)) {
    $search_executant_diag = "%$search_executant_diag%";
    $stmt->bindParam(':search_executant_diag', $search_executant_diag);
}
$stmt->execute();
$diagnostics = $stmt->fetchAll();
// Rendez-vous
$search_client_id_rdv = isset($_GET['search_client_id_rdv']) ? secure_input($_GET['search_client_id_rdv']) : '';
$search_date_rdv = isset($_GET['search_date_rdv']) ? secure_input($_GET['search_date_rdv']) : '';
$search_statut_rdv = isset($_GET['search_statut_rdv']) ? secure_input($_GET['search_statut_rdv']) : '';
$search_executant_rdv = isset($_GET['search_executant_rdv']) ? secure_input($_GET['search_executant_rdv']) : '';
$sql_rdv = "SELECT rdv.*, clients.nom, clients.prenoms FROM rdv JOIN clients ON rdv.client_id = clients.id WHERE 1=1";
if (!empty($search_client_id_rdv)) {
    $sql_rdv .= " AND rdv.client_id = :search_client_id_rdv";
}
if (!empty($search_date_rdv)) {
    $sql_rdv .= " AND rdv.date_rdv = :search_date_rdv";
}
if (!empty($search_statut_rdv)) {
    $sql_rdv .= " AND rdv.statut = :search_statut_rdv";
}
if (!empty($search_executant_rdv)) {
    $sql_rdv .= " AND rdv.executant LIKE :search_executant_rdv";
}
$stmt = $conn->prepare($sql_rdv);
if (!empty($search_client_id_rdv)) {
    $stmt->bindParam(':search_client_id_rdv', $search_client_id_rdv);
}
if (!empty($search_date_rdv)) {
    $stmt->bindParam(':search_date_rdv', $search_date_rdv);
}
if (!empty($search_statut_rdv)) {
    $stmt->bindParam(':search_statut_rdv', $search_statut_rdv);
}
if (!empty($search_executant_rdv)) {
    $search_executant_rdv = "%$search_executant_rdv%";
    $stmt->bindParam(':search_executant_rdv', $search_executant_rdv);
}
$stmt->execute();
$rdv = $stmt->fetchAll();
// Séances
$search_client_id_seance = isset($_GET['search_client_id_seance']) ? secure_input($_GET['search_client_id_seance']) : '';
$search_date_seance = isset($_GET['search_date_seance']) ? secure_input($_GET['search_date_seance']) : '';
$search_executant_seance = isset($_GET['search_executant_seance']) ? secure_input($_GET['search_executant_seance']) : '';
$sql_seances = "SELECT * FROM seances WHERE 1=1";
if (!empty($search_client_id_seance)) {
    $sql_seances .= " AND client_id = :search_client_id_seance";
}
if (!empty($search_date_seance)) {
    $sql_seances .= " AND date_seance = :search_date_seance";
}
if (!empty($search_executant_seance)) {
    $sql_seances .= " AND executant LIKE :search_executant_seance";
}
$stmt = $conn->prepare($sql_seances);
if (!empty($search_client_id_seance)) {
    $stmt->bindParam(':search_client_id_seance', $search_client_id_seance);
}
if (!empty($search_date_seance)) {
    $stmt->bindParam(':search_date_seance', $search_date_seance);
}
if (!empty($search_executant_seance)) {
    $search_executant_seance = "%$search_executant_seance%";
    $stmt->bindParam(':search_executant_seance', $search_executant_seance);
}
$stmt->execute();
$seances = $stmt->fetchAll();
// Utilisateurs
$stmt = $conn->prepare("SELECT * FROM utilisateurs");
$stmt->execute();
$utilisateurs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
    body {
        background-color: rgba(255, 255, 255, 1);
    }
    #clientForm, #conseilForm, #controleForm, #diagnosticForm, #rdvForm, #seanceForm, #utilisateurForm {
        display: none;
        margin: 20px 0;
        padding: 20px;
        background-color: #ffffffff; /* Couleur de fond pour les formulaires */
        border-radius: 5px;
    }
    .form-check {
        margin-bottom: 10px;
    }
    .table-responsive {
        margin-top: 20px;
    }
    .alert {
        margin-top: 20px;
    }
    .modal-content {
        padding: 20px;
    }
    .badge {
        font-size: 1em;
    }
    .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: #6c757d;
            font-size: 14px;
            background-color: white;
            border-top: 1px solid #dee2e6;
        }
        .footer-logo {
            height: 60px;
            display: block;
            margin: 0 auto 10px;
        }
        .footer-text {
            margin-bottom: 5px;
            font-weight: 500;
        }
</style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
            <h1 class="mb-0">TABLEAU DE BORD</h1>
            <div>
                <a href="login.php" class="btn btn-danger">Déconnexion</a>
                <a href="pagesuivi.php" class="btn btn-success">Page de Suivi</a>
                <a href="dashboard.php" class="btn btn-info ml-2"><i class="fas fa-sync-alt"></i> Réactualiser</a>
            </div>
        </div>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="clients-tab" data-toggle="tab" href="#clients" role="tab" aria-controls="clients" aria-selected="true">
                    <i class="fas fa-users"></i> Clients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="conseils-tab" data-toggle="tab" href="#conseils" role="tab" aria-controls="conseils" aria-selected="false">
                    <i class="fas fa-lightbulb"></i> Conseils
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="controles-tab" data-toggle="tab" href="#controles" role="tab" aria-controls="controles" aria-selected="false">
                    <i class="fas fa-clipboard-check"></i> Contrôles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="diagnostics-tab" data-toggle="tab" href="#diagnostics" role="tab" aria-controls="diagnostics" aria-selected="false">
                    <i class="fas fa-diagnoses"></i> Diagnostics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="rdv-tab" data-toggle="tab" href="#rdv" role="tab" aria-controls="rdv" aria-selected="false">
                    <i class="fas fa-calendar-alt"></i> Rendez-vous
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="anciens-rdv-tab" data-toggle="tab" href="#anciens-rdv" role="tab" aria-controls="anciens-rdv" aria-selected="false">
                    <i class="fas fa-history"></i> Ancien Rendez-vous
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="seances-tab" data-toggle="tab" href="#seances" role="tab" aria-controls="seances" aria-selected="false">
                    <i class="fas fa-spa"></i> Séances
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="utilisateurs-tab" data-toggle="tab" href="#utilisateurs" role="tab" aria-controls="utilisateurs" aria-selected="false">
                    <i class="fas fa-user"></i> Utilisateurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="statistiques-tab" data-toggle="tab" href="#statistiques" role="tab" aria-controls="statistiques" aria-selected="false">
                    <i class="fas fa-chart-bar"></i> Statistiques
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="notifications-tab" data-toggle="tab" href="#notifications" role="tab" aria-controls="notifications" aria-selected="false">
                    <i class="fas fa-bell"></i> Notifications
                </a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Onglet Clients -->
            <div class="tab-pane fade show active" id="clients" role="tabpanel" aria-labelledby="clients-tab">
                <button id="showClientForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter un client
                </button>
                <div id="clientForm">
                    <h2>Ajouter un client</h2>
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nom">Nom:</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="prenoms">Prénoms:</label>
                                <input type="text" class="form-control" id="prenoms" name="prenoms" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="genre">Genre:</label>
                                <select class="form-control" id="genre" name="genre" required>
                                    <option value="masculin">Masculin</option>
                                    <option value="feminin">Féminin</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="ville">Ville:</label>
                                <input type="text" class="form-control" id="ville" name="ville">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email">Email:</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="telephone">Téléphone:</label>
                                <input type="text" class="form-control" id="telephone" name="telephone">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="pin">PIN:</label>
                                <input type="text" class="form-control" id="pin" name="pin">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="photo_profil">Photo de profil:</label>
                                <input type="file" class="form-control-file" id="photo_profil" name="photo_profil">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_client">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </form>
                </div>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="mb-0">Rechercher des clients</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="search_nom">Nom:</label>
                                    <input type="text" class="form-control" id="search_nom" name="search_nom" value="<?php echo $search_nom; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_ville">Ville:</label>
                                    <input type="text" class="form-control" id="search_ville" name="search_ville" value="<?php echo $search_ville; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_genre">Genre:</label>
                                    <select class="form-control" id="search_genre" name="search_genre">
                                        <option value="">Tous</option>
                                        <option value="masculin" <?php if ($search_genre == 'masculin') echo 'selected'; ?>>Masculin</option>
                                        <option value="feminin" <?php if ($search_genre == 'feminin') echo 'selected'; ?>>Féminin</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
                <h2 class="mt-4">Liste des clients</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Nom</th>
                                <th>Prénoms</th>
                                <th>Genre</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>PIN</th>
                                <th>Ville</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clients) > 0): ?>
                                <?php foreach ($clients as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <?php if (!empty($row['photo_profil'])): ?>
                                                <img src='<?php echo UPLOAD_DIR . $row['telephone'] . '/' . $row['photo_profil']; ?>' alt='Photo de profil' class="img-thumbnail" width="50">
                                            <?php else: ?>
                                                <span class="text-muted">Aucune photo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $row['nom']; ?></td>
                                        <td><?php echo $row['prenoms']; ?></td>
                                        <td><?php echo ucfirst($row['genre']); ?></td>
                                        <td><?php echo $row['email']; ?></td>
                                        <td><?php echo $row['telephone']; ?></td>
                                        <td><?php echo $row['pin']; ?></td>
                                        <td><?php echo $row['ville']; ?></td>
                                        <td><?php echo formatDate($row['date_creation']); ?></td>
                                        <td>
                                            <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editClientModal'
                                                data-id='<?php echo $row['id']; ?>'
                                                data-nom='<?php echo $row['nom']; ?>'
                                                data-prenoms='<?php echo $row['prenoms']; ?>'
                                                data-genre='<?php echo $row['genre']; ?>'
                                                data-email='<?php echo $row['email']; ?>'
                                                data-telephone='<?php echo $row['telephone']; ?>'
                                                data-pin='<?php echo $row['pin']; ?>'
                                                data-ville='<?php echo $row['ville']; ?>'
                                                data-photo_profil='<?php echo $row['photo_profil']; ?>'>
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <a href='?delete_client=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center">Aucun client trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Onglet Conseils -->
            <div class="tab-pane fade" id="conseils" role="tabpanel" aria-labelledby="conseils-tab">
                <button id="showConseilForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter un conseil
                </button>
                <div id="conseilForm">
                    <h2>Ajouter un conseil</h2>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="titre">Titre:</label>
                            <input type="text" class="form-control" id="titre" name="titre" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_conseil">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </form>
                </div>
                <h2 class="mt-4">Liste des conseils</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>Description</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($conseils) > 0): ?>
                                <?php foreach ($conseils as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['titre']; ?></td>
                                        <td><?php echo substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo formatDate($row['date_creation']); ?></td>
                                        <td>
                                            <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editConseilModal'
                                                data-id='<?php echo $row['id']; ?>'
                                                data-titre='<?php echo $row['titre']; ?>'
                                                data-description='<?php echo $row['description']; ?>'>
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <a href='?delete_conseil=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce conseil?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucun conseil trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Onglet Contrôles -->
            <div class="tab-pane fade" id="controles" role="tabpanel" aria-labelledby="controles-tab">
                <button id="showControleForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter un contrôle
                </button>
                <div id="controleForm">
                    <h2>Ajouter un contrôle</h2>
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="client_id">Client:</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <option value="">Sélectionnez un client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="date_controle">Date de contrôle:</label>
                                <input type="date" class="form-control" id="date_controle" name="date_controle" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>État des cheveux:</label>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="reparation_rapide_fibre" name="reparation_rapide_fibre">
                                        <label class="form-check-label" for="reparation_rapide_fibre">Réparation rapide fibre</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="reparation_lente_fibre" name="reparation_lente_fibre">
                                        <label class="form-check-label" for="reparation_lente_fibre">Réparation lente fibre</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="repousse_cheveux" name="repousse_cheveux">
                                        <label class="form-check-label" for="repousse_cheveux">Repousse cheveux</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="densite_cheveux" name="densite_cheveux">
                                        <label class="form-check-label" for="densite_cheveux">Densité cheveux</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="elasticite_cheveux" name="elasticite_cheveux">
                                        <label class="form-check-label" for="elasticite_cheveux">Élasticité cheveux</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="force_cheveux" name="force_cheveux">
                                        <label class="form-check-label" for="force_cheveux">Force cheveux</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="executant">Exécutant:</label>
                                <input type="text" class="form-control" id="executant" name="executant">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="nom_fichier1">Photo 1 :</label>
                                <input type="file" class="form-control-file" id="nom_fichier1" name="nom_fichier1">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="nom_fichier2">Photo 2 :</label>
                                <input type="file" class="form-control-file" id="nom_fichier2" name="nom_fichier2">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="nom_fichier3">Photo 3 :</label>
                                <input type="file" class="form-control-file" id="nom_fichier3" name="nom_fichier3">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="observations">Observations:</label>
                            <textarea class="form-control" id="observations" name="observations" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_controle">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </form>
                </div>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="mb-0">Rechercher des contrôles</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="search_client_id">Client:</label>
                                    <select class="form-control" id="search_client_id" name="search_client_id">
                                        <option value="">Tous</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_date_controle">Date de contrôle:</label>
                                    <input type="date" class="form-control" id="search_date_controle" name="search_date_controle" value="<?php echo $search_date_controle; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_executant">Exécutant:</label>
                                    <input type="text" class="form-control" id="search_executant" name="search_executant" value="<?php echo $search_executant; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
                <h2 class="mt-4">Liste des contrôles</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Exécutant</th>
                                <th>Observations</th>
                                <th>Photo</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($controles) > 0): ?>
                                <?php foreach ($controles as $row):
                                    $client = array_filter($clients, function($c) use ($row) {
                                        return $c['id'] == $row['client_id'];
                                    });
                                    $client = reset($client);
                                    $clientNom = $client ? $client['nom'] : 'Inconnu';
                                    $clientPrenoms = $client ? $client['prenoms'] : '';
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $clientNom . ' ' . $clientPrenoms; ?></td>
                                        <td><?php echo formatDate($row['date_controle']); ?></td>
                                        <td><?php echo $row['executant']; ?></td>
                                        <td><?php echo substr($row['observations'], 0, 30) . (strlen($row['observations']) > 30 ? '...' : ''); ?></td>
                                        <td>
                                            <div class="d-flex">
                                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                                    <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                    <?php if (!empty($row[$nomFichier])): ?>
                                                        <img src='<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>' alt='Photo' class="img-thumbnail" width="50" style="margin-right: 5px; cursor: pointer;" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>')">                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                                <?php if (empty($row['nom_fichier1']) && empty($row['nom_fichier2']) && empty($row['nom_fichier3'])): ?>
                                                    <span class="text-muted">Aucune photo</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <button class='btn btn-info btn-sm' data-toggle='modal' data-target='#viewControleModal<?php echo $row['id']; ?>'>
                                                <i class="fas fa-eye"></i> Voir
                                            </button>
                                            <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editControleModal'
                                                data-id='<?php echo $row['id']; ?>'
                                                data-client_id='<?php echo $row['client_id']; ?>'
                                                data-date_controle='<?php echo $row['date_controle']; ?>'
                                                data-reparation_rapide_fibre='<?php echo $row['reparation_rapide_fibre']; ?>'
                                                data-reparation_lente_fibre='<?php echo $row['reparation_lente_fibre']; ?>'
                                                data-repousse_cheveux='<?php echo $row['repousse_cheveux']; ?>'
                                                data-densite_cheveux='<?php echo $row['densite_cheveux']; ?>'
                                                data-elasticite_cheveux='<?php echo $row['elasticite_cheveux']; ?>'
                                                data-force_cheveux='<?php echo $row['force_cheveux']; ?>'
                                                data-executant='<?php echo $row['executant']; ?>'
                                                data-observations='<?php echo $row['observations']; ?>'
                                                data-nom_fichier1='<?php echo $row['nom_fichier1']; ?>'
                                                data-nom_fichier2='<?php echo $row['nom_fichier2']; ?>'
                                                data-nom_fichier3='<?php echo $row['nom_fichier3']; ?>'>
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <a href='?delete_controle=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce contrôle?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                        </td>
                                    </tr>
                                    <!-- Modal View Controle -->
                                    <div class="modal fade" id="viewControleModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewControleModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewControleModalLabel<?php echo $row['id']; ?>">Détails du Contrôle</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Date:</strong> <?php echo formatDate($row['date_controle']); ?></p>
                                                    <p><strong>Exécutant:</strong> <?php echo htmlspecialchars($row['executant']); ?></p>
                                                    <p><strong>Client:</strong> <?php echo $clientNom . ' ' . $clientPrenoms; ?></p>
                                                    <p><strong>Observations:</strong> <?php echo htmlspecialchars($row['observations']); ?></p>
                                                    <p><strong>Réparation rapide fibre:</strong> <?php echo $row['reparation_rapide_fibre'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Réparation lente fibre:</strong> <?php echo $row['reparation_lente_fibre'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Repousse cheveux:</strong> <?php echo $row['repousse_cheveux'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Densité cheveux:</strong> <?php echo $row['densite_cheveux'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Élasticité cheveux:</strong> <?php echo $row['elasticite_cheveux'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Force cheveux:</strong> <?php echo $row['force_cheveux'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <div class="row mt-4">
                                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                                            <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                            <?php if (!empty($row[$nomFichier])): ?>
                                                                <div class="col-md-4 mb-3">
                                                                    <div class="card">
                                                                    <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>" class="card-img-top" alt="Photo contrôle <?php echo $i; ?>">                                                                        <div class="card-body text-center">
                                                                    <button class="btn btn-sm btn-primary" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>')">                                                                                Agrandir
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun contrôle trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Onglet Diagnostics -->
            <div class="tab-pane fade" id="diagnostics" role="tabpanel" aria-labelledby="diagnostics-tab">
                <button id="showDiagnosticForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter un diagnostic
                </button>
                <div id="diagnosticForm">
                    <h2>Ajouter un diagnostic</h2>
                    <form method="post" action="">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="client_id">Client:</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <option value="">Sélectionnez un client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="date_diagnostic">Date:</label>
                                <input type="date" class="form-control" id="date_diagnostic" name="date_diagnostic" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Problèmes capillaires:</label>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cheveux_abimes" name="cheveux_abimes">
                                        <label class="form-check-label" for="cheveux_abimes">Cheveux abîmés</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cheveux_faibles" name="cheveux_faibles">
                                        <label class="form-check-label" for="cheveux_faibles">Cheveux faibles</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cheveux_perte_densite" name="cheveux_perte_densite">
                                        <label class="form-check-label" for="cheveux_perte_densite">Perte densité</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="cheveux_trop_gras" name="cheveux_trop_gras">
                                        <label class="form-check-label" for="cheveux_trop_gras">Trop gras</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Types d'alopécie:</label>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="alopecie_androgenique" name="alopecie_androgenique">
                                        <label class="form-check-label" for="alopecie_androgenique">Androgénique</label>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label for="alopecie_androgenique_niveau">Niveau:</label>
                                        <select class="form-control" id="alopecie_androgenique_niveau" name="alopecie_androgenique_niveau">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="alopecie_traction" name="alopecie_traction">
                                        <label class="form-check-label" for="alopecie_traction">Traction</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="pelade" name="pelade">
                                        <label class="form-check-label" for="pelade">Pelade</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Autres problèmes:</label>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="psoriasis" name="psoriasis">
                                        <label class="form-check-label" for="psoriasis">Psoriasis</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="teigne" name="teigne">
                                        <label class="form-check-label" for="teigne">Teigne</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Textures de cheveux:</label>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="texture_naturels" name="texture_naturels">
                                        <label class="form-check-label" for="texture_naturels">Naturels</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="texture_defrises" name="texture_defrises">
                                        <label class="form-check-label" for="texture_defrises">Défrisés</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="texture_demeles" name="texture_demeles">
                                        <label class="form-check-label" for="texture_demeles">Démêlés</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="texture_colores" name="texture_colores">
                                        <label class="form-check-label" for="texture_colores">Colorés</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="executant">Exécutant:</label>
                            <input type="text" class="form-control" id="executant" name="executant">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="nom_fichier1">Photo 1 :</label>
                            <input type="file" class="form-control-file" id="nom_fichier1" name="nom_fichier1">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="nom_fichier2">Photo 2 :</label>
                            <input type="file" class="form-control-file" id="nom_fichier2" name="nom_fichier2">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="nom_fichier3">Photo 3 :</label>
                            <input type="file" class="form-control-file" id="nom_fichier3" name="nom_fichier3">
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_diagnostic">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </form>
                </div>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="mb-0">Rechercher des diagnostics</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="search_client_id_diag">Client:</label>
                                    <select class="form-control" id="search_client_id_diag" name="search_client_id_diag">
                                        <option value="">Tous</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_date_diagnostic">Date:</label>
                                    <input type="date" class="form-control" id="search_date_diagnostic" name="search_date_diagnostic" value="<?php echo $search_date_diagnostic; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_executant_diag">Exécutant:</label>
                                    <input type="text" class="form-control" id="search_executant_diag" name="search_executant_diag" value="<?php echo $search_executant_diag; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
                <h2 class="mt-4">Liste des diagnostics</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Exécutant</th>
                                <th>photos</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($diagnostics) > 0): ?>
                                <?php foreach ($diagnostics as $row):
                                    $client = array_filter($clients, function($c) use ($row) {
                                        return $c['id'] == $row['client_id'];
                                    });
                                    $client = reset($client);
                                    $clientNom = $client ? $client['nom'] : 'Inconnu';
                                    $clientPrenoms = $client ? $client['prenoms'] : '';
                                ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $clientNom . ' ' . $clientPrenoms; ?></td>
                                        <td><?php echo formatDate($row['date_diagnostic']); ?></td>
                                        <td><?php echo $row['executant']; ?></td>
                                        <td>
                                            <div class="d-flex">
                                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                                    <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                    <?php if (!empty($row[$nomFichier])): ?>
                                                        <img src='<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>' alt='Photo' class="img-thumbnail" style="width: 40px; height: 40px; margin-right: 3px; cursor: pointer;" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>')">                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <button class='btn btn-info btn-sm' data-toggle='modal' data-target='#viewDiagnosticModal<?php echo $row['id']; ?>'>
                                                <i class="fas fa-eye"></i> Voir
                                            </button>
                                            <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editDiagnosticModal'
                                                data-id='<?php echo $row['id']; ?>'
                                                data-client_id='<?php echo $row['client_id']; ?>'
                                                data-date_diagnostic='<?php echo $row['date_diagnostic']; ?>'
                                                data-cheveux_abimes='<?php echo $row['cheveux_abimes']; ?>'
                                                data-cheveux_faibles='<?php echo $row['cheveux_faibles']; ?>'
                                                data-cheveux_perte_densite='<?php echo $row['cheveux_perte_densite']; ?>'
                                                data-cheveux_trop_gras='<?php echo $row['cheveux_trop_gras']; ?>'
                                                data-alopecie_androgenique='<?php echo $row['alopecie_androgenique']; ?>'
                                                data-alopecie_androgenique_niveau='<?php echo $row['alopecie_androgenique_niveau']; ?>'
                                                data-alopecie_traction='<?php echo $row['alopecie_traction']; ?>'
                                                data-pelade='<?php echo $row['pelade']; ?>'
                                                data-psoriasis='<?php echo $row['psoriasis']; ?>'
                                                data-teigne='<?php echo $row['teigne']; ?>'
                                                data-texture_naturels='<?php echo $row['texture_naturels']; ?>'
                                                data-texture_defrises='<?php echo $row['texture_defrises']; ?>'
                                                data-texture_demeles='<?php echo $row['texture_demeles']; ?>'
                                                data-texture_colores='<?php echo $row['texture_colores']; ?>'
                                                data-executant='<?php echo $row['executant']; ?>'
                                                data-nom_fichier1='<?php echo $row['nom_fichier1']; ?>'
                                                data-nom_fichier2='<?php echo $row['nom_fichier2']; ?>'
                                                data-nom_fichier3='<?php echo $row['nom_fichier3']; ?>'>
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                            <a href='?delete_diagnostic=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce diagnostic?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                        </td>
                                    </tr>
                                    <!-- Modal View Diagnostic -->
                                    <div class="modal fade" id="viewDiagnosticModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewDiagnosticModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewDiagnosticModalLabel<?php echo $row['id']; ?>">Détails du Diagnostic</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Date:</strong> <?php echo formatDate($row['date_diagnostic']); ?></p>
                                                    <p><strong>Exécutant:</strong> <?php echo htmlspecialchars($row['executant']); ?></p>
                                                    <p><strong>Client:</strong> <?php echo $clientNom . ' ' . $clientPrenoms; ?></p>
                                                    <p><strong>Cheveux abîmés:</strong> <?php echo $row['cheveux_abimes'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Cheveux faibles:</strong> <?php echo $row['cheveux_faibles'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Cheveux perte densité:</strong> <?php echo $row['cheveux_perte_densite'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Cheveux trop gras:</strong> <?php echo $row['cheveux_trop_gras'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Alopécie androgénique:</strong> <?php echo $row['alopecie_androgenique'] ? '<i class="fas fa-check-circle text-success"></i> (Niveau: ' . $row['alopecie_androgenique_niveau'] . ')' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Alopécie traction:</strong> <?php echo $row['alopecie_traction'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Pelade:</strong> <?php echo $row['pelade'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Psoriasis:</strong> <?php echo $row['psoriasis'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Teigne:</strong> <?php echo $row['teigne'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Texture naturels:</strong> <?php echo $row['texture_naturels'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Texture défrisés:</strong> <?php echo $row['texture_defrises'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Texture démêlés:</strong> <?php echo $row['texture_demeles'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <p><strong>Texture colorés:</strong> <?php echo $row['texture_colores'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                                                    <div class="row mt-4">
                                                        <?php for ($i = 1; $i <= 3; $i++): ?>
                                                            <?php $nomFichier = 'nom_fichier' . $i; ?>
                                                            <?php if (!empty($row[$nomFichier])): ?>
                                                                <div class="col-md-4 mb-3">
                                                                    <div class="card">
                                                                    <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>" class="card-img-top" alt="Photo diagnostic <?php echo $i; ?>">                                                                        <div class="card-body text-center">
                                                                    <button class="btn btn-sm btn-primary" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>')">                                                                                Agrandir
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucun diagnostic trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Onglet Rendez-vous -->
            <div class="tab-pane fade" id="rdv" role="tabpanel" aria-labelledby="rdv-tab">
                <button id="showRdvForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter un rendez-vous
                </button>
                <div id="rdvForm">
                    <h2>Ajouter un rendez-vous</h2>
                    <form method="post" action="">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="client_id">Client:</label>
                                <select class="form-control" id="client_id" name="client_id" required>
                                    <option value="">Sélectionnez un client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="date_rdv">Date:</label>
                                <input type="date" class="form-control" id="date_rdv" name="date_rdv" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="heure_rdv">Heure:</label>
                                <input type="time" class="form-control" id="heure_rdv" name="heure_rdv" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="type_rdv">Type:</label>
                                <select class="form-control" id="type_rdv" name="type_rdv" required>
                                    <option value="diagnostic">Diagnostic</option>
                                    <option value="seance">Séance</option>
                                    <option value="controle">Contrôle</option>
                                    <option value="consultation">Consultation</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="statut">Statut:</label>
                                <select class="form-control" id="statut" name="statut" required>
                                    <option value="planifie">Planifié</option>
                                    <option value="confirme">Confirmé</option>
                                    <option value="annule">Annulé</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="message_prevention">Message de prévention:</label>
                            <textarea class="form-control" id="message_prevention" name="message_prevention" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="executant">Exécutant:</label>
                            <input type="text" class="form-control" id="executant" name="executant">
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_rdv">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </form>
                </div>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="mb-0">Rechercher des rendez-vous</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="search_client_id_rdv">Client:</label>
                                    <select class="form-control" id="search_client_id_rdv" name="search_client_id_rdv">
                                        <option value="">Tous</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="search_date_rdv">Date:</label>
                                    <input type="date" class="form-control" id="search_date_rdv" name="search_date_rdv" value="<?php echo $search_date_rdv; ?>">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="search_statut_rdv">Statut:</label>
                                    <select class="form-control" id="search_statut_rdv" name="search_statut_rdv">
                                        <option value="">Tous</option>
                                        <option value="planifie" <?php if ($search_statut_rdv == 'planifie') echo 'selected'; ?>>Planifié</option>
                                        <option value="confirme" <?php if ($search_statut_rdv == 'confirme') echo 'selected'; ?>>Confirmé</option>
                                        <option value="annule" <?php if ($search_statut_rdv == 'annule') echo 'selected'; ?>>Annulé</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="search_executant_rdv">Exécutant:</label>
                                    <input type="text" class="form-control" id="search_executant_rdv" name="search_executant_rdv" value="<?php echo $search_executant_rdv; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
                <h2 class="mt-4">Liste des rendez-vous</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Statut</th>
                                <th>Type</th>
                                <th>Exécutant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rdv) > 0): ?>
                                <?php foreach ($rdv as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['nom'] . ' ' . $row['prenoms']; ?></td>
                                        <td><?php echo formatDate($row['date_rdv']); ?></td>
                                        <td><?php echo formatTime($row['heure_rdv']); ?></td>
                                        <td>
                                            <span class="badge
                                                <?php
                                                switch($row['statut']) {
                                                    case 'confirme': echo 'badge-success'; break;
                                                    case 'annule': echo 'badge-danger'; break;
                                                    case 'termine': echo 'badge-secondary'; break;
                                                    default: echo 'badge-primary';
                                                }
                                                ?>">
                                                <?php echo ucfirst($row['statut']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo ucfirst($row['type_rdv']); ?></td>
                                        <td><?php echo $row['executant']; ?></td>
                                        <td>
                                            <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editRdvModal'
                                                data-id='<?php echo $row['id']; ?>'
                                                data-client_id='<?php echo $row['client_id']; ?>'
                                                data-date_rdv='<?php echo $row['date_rdv']; ?>'
                                                data-heure_rdv='<?php echo $row['heure_rdv']; ?>'
                                                data-message_prevention='<?php echo $row['message_prevention']; ?>'
                                                data-statut='<?php echo $row['statut']; ?>'
                                                data-type_rdv='<?php echo $row['type_rdv']; ?>'
                                                data-executant='<?php echo $row['executant']; ?>'>
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Aucun rendez-vous trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Onglet Ancien Rendez-vous -->
            <div class="tab-pane fade" id="anciens-rdv" role="tabpanel" aria-labelledby="anciens-rdv-tab">
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="mb-0">Rechercher des anciens rendez-vous</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="search_client_id_ancien_rdv">Client:</label>
                                    <select class="form-control" id="search_client_id_ancien_rdv" name="search_client_id_ancien_rdv">
                                        <option value="">Tous</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="search_date_ancien_rdv">Date:</label>
                                    <input type="date" class="form-control" id="search_date_ancien_rdv" name="search_date_ancien_rdv">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="search_executant_ancien_rdv">Exécutant:</label>
                                    <input type="text" class="form-control" id="search_executant_ancien_rdv" name="search_executant_ancien_rdv">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
                <h2 class="mt-4">Liste des anciens rendez-vous</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Statut</th>
                                <th>Type</th>
                                <th>Exécutant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search_client_id_ancien_rdv = isset($_GET['search_client_id_ancien_rdv']) ? secure_input($_GET['search_client_id_ancien_rdv']) : '';
                            $search_date_ancien_rdv = isset($_GET['search_date_ancien_rdv']) ? secure_input($_GET['search_date_ancien_rdv']) : '';
                            $search_executant_ancien_rdv = isset($_GET['search_executant_ancien_rdv']) ? secure_input($_GET['search_executant_ancien_rdv']) : '';
                            $sql_anciens_rdv = "SELECT rdv.*, clients.nom, clients.prenoms FROM rdv JOIN clients ON rdv.client_id = clients.id WHERE statut = 'Terminé'";
                            if (!empty($search_client_id_ancien_rdv)) {
                                $sql_anciens_rdv .= " AND rdv.client_id = :search_client_id_ancien_rdv";
                            }
                            if (!empty($search_date_ancien_rdv)) {
                                $sql_anciens_rdv .= " AND rdv.date_rdv = :search_date_ancien_rdv";
                            }
                            if (!empty($search_executant_ancien_rdv)) {
                                $sql_anciens_rdv .= " AND rdv.executant LIKE :search_executant_ancien_rdv";
                            }
                            $stmt = $conn->prepare($sql_anciens_rdv);
                            if (!empty($search_client_id_ancien_rdv)) {
                                $stmt->bindParam(':search_client_id_ancien_rdv', $search_client_id_ancien_rdv);
                            }
                            if (!empty($search_date_ancien_rdv)) {
                                $stmt->bindParam(':search_date_ancien_rdv', $search_date_ancien_rdv);
                            }
                            if (!empty($search_executant_ancien_rdv)) {
                                $search_executant_ancien_rdv = "%$search_executant_ancien_rdv%";
                                $stmt->bindParam(':search_executant_ancien_rdv', $search_executant_ancien_rdv);
                            }
                            $stmt->execute();
                            $anciens_rdv = $stmt->fetchAll();
                            if (count($anciens_rdv) > 0):
                                foreach ($anciens_rdv as $row):
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['nom'] . ' ' . $row['prenoms']; ?></td>
                                        <td><?php echo formatDate($row['date_rdv']); ?></td>
                                        <td><?php echo formatTime($row['heure_rdv']); ?></td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst($row['statut']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo ucfirst($row['type_rdv']); ?></td>
                                        <td><?php echo $row['executant']; ?></td>
                                    </tr>
                                    <?php
                                endforeach;
                            else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">Aucun ancien rendez-vous trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Onglet Séances -->
            <div class="tab-pane fade" id="seances" role="tabpanel" aria-labelledby="seances-tab">
                <button id="showSeanceForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter une séance
                </button>
               <div id="seanceForm">
    <h2>Ajouter une séance</h2>
    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="client_id">Client:</label>
                <select class="form-control" id="client_id" name="client_id" required>
                    <option value="">Sélectionnez un client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="date_seance">Date:</label>
                <input type="date" class="form-control" id="date_seance" name="date_seance" required>
            </div>
            <div class="form-group col-md-3">
                <label for="numero_seance">Numéro:</label>
                <input type="number" class="form-control" id="numero_seance" name="numero_seance" required>
            </div>
        </div>
        <div class="form-group">
            <label for="soin">Soin:</label>
            <input type="text" class="form-control" id="soin" name="soin">
        </div>
        <div class="form-group">
            <label>Traitements:</label>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="microneedle" name="microneedle">
                        <label class="form-check-label" for="microneedle">Microneedle</label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="steamer" name="steamer">
                        <label class="form-check-label" for="steamer">Steamer</label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="bain_huile" name="bain_huile">
                        <label class="form-check-label" for="bain_huile">Bain huile</label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="bain_medical" name="bain_medical">
                        <label class="form-check-label" for="bain_medical">Bain médical</label>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="defrisage" name="defrisage">
                        <label class="form-check-label" for="defrisage">Défrisage</label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="coloration" name="coloration">
                        <label class="form-check-label" for="coloration">Coloration</label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="gommage" name="gommage">
                        <label class="form-check-label" for="gommage">Gommage</label>
                    </div>
                </div>
                <div class="form-group col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="stimulation" name="stimulation">
                        <label class="form-check-label" for="stimulation">Stimulation</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="autres">Autres traitements:</label>
            <input type="text" class="form-control" id="autres" name="autres">
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="executant">Exécutant:</label>
                <input type="text" class="form-control" id="executant" name="executant">
            </div>
        </div>
        <div class="form-group">
            <label for="nom_fichier1">Photo 1:</label>
            <input type="file" class="form-control-file" id="nom_fichier1" name="nom_fichier1">
        </div>
        <div class="form-group">
            <label for="nom_fichier2">Photo 2:</label>
            <input type="file" class="form-control-file" id="nom_fichier2" name="nom_fichier2">
        </div>
        <div class="form-group">
            <label for="nom_fichier3">Photo 3:</label>
            <input type="file" class="form-control-file" id="nom_fichier3" name="nom_fichier3">
        </div>
        <button type="submit" class="btn btn-primary" name="add_seance">
            <i class="fas fa-save"></i> Ajouter
        </button>
    </form>
</div>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2 class="mb-0">Rechercher des séances</h2>
                    </div>
                    <div class="card-body">
                        <form method="get" action="">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="search_client_id_seance">Client:</label>
                                    <select class="form-control" id="search_client_id_seance" name="search_client_id_seance">
                                        <option value="">Tous</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_date_seance">Date:</label>
                                    <input type="date" class="form-control" id="search_date_seance" name="search_date_seance" value="<?php echo $search_date_seance; ?>">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="search_executant_seance">Exécutant:</label>
                                    <input type="text" class="form-control" id="search_executant_seance" name="search_executant_seance" value="<?php echo $search_executant_seance; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
                <h2 class="mt-4">Liste des séances</h2>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Date</th>
                <th>Numéro</th>
                <th>Exécutant</th>
                <th>Photos</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($seances) > 0): ?>
                <?php foreach ($seances as $row):
                    $client = array_filter($clients, function($c) use ($row) {
                        return $c['id'] == $row['client_id'];
                    });
                    $client = reset($client);
                    $clientNom = $client ? $client['nom'] : 'Inconnu';
                    $clientPrenoms = $client ? $client['prenoms'] : '';
                ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $clientNom . ' ' . $clientPrenoms; ?></td>
                        <td><?php echo formatDate($row['date_seance']); ?></td>
                        <td><?php echo $row['numero_seance']; ?></td>
                        <td><?php echo $row['executant']; ?></td>
                        <td>
                            <div class="d-flex">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <?php $nomFichier = 'nom_fichier' . $i; ?>
                                    <?php if (!empty($row[$nomFichier])): ?>
                                        <img src='<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>' alt='Photo' class="img-thumbnail" style="width: 50px; height: 50px; margin-right: 5px; cursor: pointer;" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>')">                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td>
                            <button class='btn btn-info btn-sm' data-toggle='modal' data-target='#viewSeanceModal<?php echo $row['id']; ?>'>
                                <i class="fas fa-eye"></i> Voir
                            </button>
                            <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editSeanceModal'
                                data-id='<?php echo $row['id']; ?>'
                                data-client_id='<?php echo $row['client_id']; ?>'
                                data-date_seance='<?php echo $row['date_seance']; ?>'
                                data-numero_seance='<?php echo $row['numero_seance']; ?>'
                                data-soin='<?php echo $row['soin']; ?>'
                                data-microneedle='<?php echo $row['microneedle']; ?>'
                                data-steamer='<?php echo $row['steamer']; ?>'
                                data-bain_huile='<?php echo $row['bain_huile']; ?>'
                                data-bain_medical='<?php echo $row['bain_medical']; ?>'
                                data-defrisage='<?php echo $row['defrisage']; ?>'
                                data-coloration='<?php echo $row['coloration']; ?>'
                                data-gommage='<?php echo $row['gommage']; ?>'
                                data-stimulation='<?php echo $row['stimulation']; ?>'
                                data-autres='<?php echo $row['autres']; ?>'
                                data-executant='<?php echo $row['executant']; ?>'
                                data-nom_fichier1='<?php echo $row['nom_fichier1']; ?>'
                                data-nom_fichier2='<?php echo $row['nom_fichier2']; ?>'
                                data-nom_fichier3='<?php echo $row['nom_fichier3']; ?>'>
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <a href='?delete_seance=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette séance?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">Aucune séance trouvée</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
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

<!-- Modal View Seance -->
<div class="modal fade" id="viewSeanceModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewSeanceModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSeanceModalLabel<?php echo $row['id']; ?>">Détails de la Séance</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Date:</strong> <?php echo formatDate($row['date_seance']); ?></p>
                <p><strong>Numéro:</strong> <?php echo htmlspecialchars($row['numero_seance']); ?></p>
                <p><strong>Exécutant:</strong> <?php echo htmlspecialchars($row['executant']); ?></p>
                <p><strong>Client:</strong> <?php echo $clientNom . ' ' . $clientPrenoms; ?></p>
                <p><strong>Soin:</strong> <?php echo htmlspecialchars($row['soin']); ?></p>
                <p><strong>Microneedle:</strong> <?php echo $row['microneedle'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Steamer:</strong> <?php echo $row['steamer'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Bain d'huile:</strong> <?php echo $row['bain_huile'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Bain médical:</strong> <?php echo $row['bain_medical'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Défrisage:</strong> <?php echo $row['defrisage'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Coloration:</strong> <?php echo $row['coloration'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Gommage:</strong> <?php echo $row['gommage'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Stimulation:</strong> <?php echo $row['stimulation'] ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-times-circle text-danger"></i>'; ?></p>
                <p><strong>Autres:</strong> <?php echo htmlspecialchars($row['autres']); ?></p>
                <div class="row mt-4">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <?php $nomFichier = 'nom_fichier' . $i; ?>
                        <?php if (!empty($row[$nomFichier])): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                <img src="<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>" class="card-img-top" alt="Photo séance <?php echo $i; ?>">                                    <div class="card-body text-center">
                                <button class="btn btn-sm btn-primary" onclick="showImage('<?php echo UPLOAD_DIR . $client['telephone'] . '/' . $row[$nomFichier]; ?>')">                                            Agrandir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
function showImage(imageSrc) {
    $('#previewImage').attr('src', imageSrc);
    $('#imagePreviewModal').modal('show');
}
</script>

            <!-- Onglet Utilisateurs -->
            <div class="tab-pane fade" id="utilisateurs" role="tabpanel" aria-labelledby="utilisateurs-tab">
                <button id="showUtilisateurForm" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Ajouter un utilisateur
                </button>
                <div id="utilisateurForm">
                    <h2>Ajouter un utilisateur</h2>
                    <form method="post" action="">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="nom_utilisateur">Nom d'utilisateur:</label>
                                <input type="text" class="form-control" id="nom_utilisateur" name="nom_utilisateur" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nom_complet">Nom complet:</label>
                                <input type="text" class="form-control" id="nom_complet" name="nom_complet" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="mot_de_passe">Mot de passe:</label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="role">Rôle:</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="admin">Administrateur</option>
                                    <option value="user">Utilisateur</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_utilisateur">
                            <i class="fas fa-save"></i> Ajouter
                        </button>
                    </form>
                </div>
                <h2 class="mt-4">Liste des utilisateurs</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Nom complet</th>
                                <th>Rôle</th>
                                <th>Date de création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($utilisateurs) > 0): ?>
                                <?php foreach ($utilisateurs as $row): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo $row['nom_utilisateur']; ?></td>
                                        <td><?php echo $row['nom_complet']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $row['role'] == 'admin' ? 'badge-danger' : 'badge-primary'; ?>">
                                                <?php echo ucfirst($row['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($row['date_creation']); ?></td>
                                        <td>
                                            <!--
                                            <a href='?delete_utilisateur=<?php echo $row['id']; ?>' class='btn btn-danger btn-sm' onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </a>
                                            -->
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucun utilisateur trouvé</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>\
            <!-- Onglet Statistiques -->
            <div class="tab-pane fade" id="statistiques" role="tabpanel" aria-labelledby="statistiques-tab">
                <h2>Statistiques</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">Clients</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($clients); ?></h5>
                                <p class="card-text">Nombre total de clients enregistrés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">Conseils</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($conseils); ?></h5>
                                <p class="card-text">Nombre total de conseils pratiques</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-header">Contrôles</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($controles); ?></h5>
                                <p class="card-text">Nombre total de contrôles effectués</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-header">Diagnostics</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($diagnostics); ?></h5>
                                <p class="card-text">Nombre total de diagnostics réalisés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-danger mb-3">
                            <div class="card-header">Rendez-vous</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($rdv); ?></h5>
                                <p class="card-text">Nombre total de rendez-vous planifiés</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-secondary mb-3">
                            <div class="card-header">Séances</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo count($seances); ?></h5>
                                <p class="card-text">Nombre total de séances réalisées</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Onglet Notifications -->
            <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                <div style="margin: 20px 0; padding: 10px; border: 1px solid #ccc; max-width: 400px;">
                    <h3>Envoyer une notification push</h3>
                    <form id="pushForm" onsubmit="return envoyerNotification(event)">
                        <label for="pushTitle">Titre :</label><br>
                        <input type="text" id="pushTitle" name="pushTitle" value="Promotion !" required style="width:100%"><br><br>
                        <label for="pushBody">Message :</label><br>
                        <textarea id="pushBody" name="pushBody" rows="3" required style="width:100%">Profitez de notre nouvelle offre !</textarea><br><br>
                        <button type="submit">Envoyer la notification</button>
                    </form>
                </div>
                <script>
                function envoyerNotification(event) {
                    event.preventDefault();
                    const title = document.getElementById('pushTitle').value;
                    const body = document.getElementById('pushBody').value;
                    fetch('/send_push.php', {
                        method: 'POST',
                        body: JSON.stringify({ title, body }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    }).then(() => {
                        alert('Notification envoyée !');
                        document.getElementById('pushForm').reset();
                    });
                    return false;
                }
                </script>
            </div>
        </div>
    </div>
    <!-- Modals -->
    <!-- Modal Modifier Client -->
    <div class="modal fade" id="editClientModal" tabindex="-1" role="dialog" aria-labelledby="editClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editClientModalLabel">Modifier un client</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_client_id" name="id">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_nom">Nom:</label>
                                <input type="text" class="form-control" id="edit_nom" name="nom" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_prenoms">Prénoms:</label>
                                <input type="text" class="form-control" id="edit_prenoms" name="prenoms" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_genre">Genre:</label>
                                <select class="form-control" id="edit_genre" name="genre" required>
                                    <option value="masculin">Masculin</option>
                                    <option value="feminin">Féminin</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_ville">Ville:</label>
                                <input type="text" class="form-control" id="edit_ville" name="ville">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_email">Email:</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_telephone">Téléphone:</label>
                                <input type="text" class="form-control" id="edit_telephone" name="telephone">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_pin">PIN:</label>
                                <input type="text" class="form-control" id="edit_pin" name="pin">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_photo_profil">Photo de profil:</label>
                                <input type="file" class="form-control-file" id="edit_photo_profil" name="photo_profil">
                                <input type="hidden" id="existing_photo_profil" name="existing_photo_profil">
                                <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" name="edit_client">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier Conseil -->
    <div class="modal fade" id="editConseilModal" tabindex="-1" role="dialog" aria-labelledby="editConseilModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editConseilModalLabel">Modifier un conseil</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_conseil_id" name="id">
                        <div class="form-group">
                            <label for="edit_titre">Titre:</label>
                            <input type="text" class="form-control" id="edit_titre" name="titre" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description:</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" name="edit_conseil">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier Contrôle -->
    <div class="modal fade" id="editControleModal" tabindex="-1" role="dialog" aria-labelledby="editControleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editControleModalLabel">Modifier un contrôle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_controle_id" name="id">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_client_id">Client:</label>
                                <select class="form-control" id="edit_client_id" name="client_id" required>
                                    <?php foreach ($clients as $client): ?>
                                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_date_controle">Date:</label>
                                <input type="date" class="form-control" id="edit_date_controle" name="date_controle" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>État des cheveux:</label>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_reparation_rapide_fibre" name="reparation_rapide_fibre">
                                        <label class="form-check-label" for="edit_reparation_rapide_fibre">Réparation rapide</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_reparation_lente_fibre" name="reparation_lente_fibre">
                                        <label class="form-check-label" for="edit_reparation_lente_fibre">Réparation lente</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_repousse_cheveux" name="repousse_cheveux">
                                        <label class="form-check-label" for="edit_repousse_cheveux">Repousse</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_densite_cheveux" name="densite_cheveux">
                                        <label class="form-check-label" for="edit_densite_cheveux">Densité</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_elasticite_cheveux" name="elasticite_cheveux">
                                        <label class="form-check-label" for="edit_elasticite_cheveux">Élasticité</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_force_cheveux" name="force_cheveux">
                                        <label class="form-check-label" for="edit_force_cheveux">Force</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_executant">Exécutant:</label>
                                <input type="text" class="form-control" id="edit_executant" name="executant">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_nom_fichier">Photo:</label>
                                <input type="file" class="form-control-file" id="edit_nom_fichier" name="nom_fichier">
                                <input type="hidden" id="existing_nom_fichier" name="existing_nom_fichier">
                                <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_observations">Observations:</label>
                            <textarea class="form-control" id="edit_observations" name="observations" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                        <label>Photo 1 :</label>
                        <input type="file" class="form-control-file" id="edit_nom_fichier1" name="edit_nom_fichier1">
                        <input type="hidden" id="existing_nom_fichier1" name="existing_nom_fichier1">
                        <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                    </div>
                    <div class="form-group">
                        <label>Photo 2 :</label>
                        <input type="file" class="form-control-file" id="edit_nom_fichier2" name="edit_nom_fichier2">
                        <input type="hidden" id="existing_nom_fichier2" name="existing_nom_fichier2">
                        <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                    </div>
                    <div class="form-group">
                        <label>Photo 3 :</label>
                        <input type="file" class="form-control-file" id="edit_nom_fichier3" name="edit_nom_fichier3">
                        <input type="hidden" id="existing_nom_fichier3" name="existing_nom_fichier3">
                        <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                    </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" name="edit_controle">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier Diagnostic -->
    <div class="modal fade" id="editDiagnosticModal" tabindex="-1" role="dialog" aria-labelledby="editDiagnosticModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDiagnosticModalLabel">Modifier un diagnostic</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_diagnostic_id" name="id">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_client_id">Client:</label>
                                <select class="form-control" id="edit_client_id" name="client_id" required>
                                    <?php foreach ($clients as $client): ?>
                                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_date_diagnostic">Date:</label>
                                <input type="date" class="form-control" id="edit_date_diagnostic" name="date_diagnostic" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Problèmes capillaires:</label>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_cheveux_abimes" name="cheveux_abimes">
                                        <label class="form-check-label" for="edit_cheveux_abimes">Cheveux abîmés</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_cheveux_faibles" name="cheveux_faibles">
                                        <label class="form-check-label" for="edit_cheveux_faibles">Cheveux faibles</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_cheveux_perte_densite" name="cheveux_perte_densite">
                                        <label class="form-check-label" for="edit_cheveux_perte_densite">Perte densité</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_cheveux_trop_gras" name="cheveux_trop_gras">
                                        <label class="form-check-label" for="edit_cheveux_trop_gras">Trop gras</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Types d'alopécie:</label>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_alopecie_androgenique" name="alopecie_androgenique">
                                        <label class="form-check-label" for="edit_alopecie_androgenique">Androgénique</label>
                                    </div>
                                    <div class="form-group mt-2">
                                        <label for="edit_alopecie_androgenique_niveau">Niveau:</label>
                                        <select class="form-control" id="edit_alopecie_androgenique_niveau" name="alopecie_androgenique_niveau">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_alopecie_traction" name="alopecie_traction">
                                        <label class="form-check-label" for="edit_alopecie_traction">Traction</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_pelade" name="pelade">
                                        <label class="form-check-label" for="edit_pelade">Pelade</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Autres problèmes:</label>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_psoriasis" name="psoriasis">
                                        <label class="form-check-label" for="edit_psoriasis">Psoriasis</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_teigne" name="teigne">
                                        <label class="form-check-label" for="edit_teigne">Teigne</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Textures de cheveux:</label>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_texture_naturels" name="texture_naturels">
                                        <label class="form-check-label" for="edit_texture_naturels">Naturels</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_texture_defrises" name="texture_defrises">
                                        <label class="form-check-label" for="edit_texture_defrises">Défrisés</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_texture_demeles" name="texture_demeles">
                                        <label class="form-check-label" for="edit_texture_demeles">Démêlés</label>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_texture_colores" name="texture_colores">
                                        <label class="form-check-label" for="edit_texture_colores">Colorés</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_executant">Exécutant:</label>
                            <input type="text" class="form-control" id="edit_executant" name="executant">
                        </div>
                        <div class="form-group">
                            <label>Photo 1 :</label>
                            <input type="file" class="form-control-file" id="edit_nom_fichier1" name="edit_nom_fichier1">
                            <input type="hidden" id="existing_nom_fichier1" name="existing_nom_fichier1">
                            <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                        </div>
                        <div class="form-group">
                            <label>Photo 2 :</label>
                            <input type="file" class="form-control-file" id="edit_nom_fichier2" name="edit_nom_fichier2">
                            <input type="hidden" id="existing_nom_fichier2" name="existing_nom_fichier2">
                            <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                        </div>
                        <div class="form-group">
                            <label>Photo 3 :</label>
                            <input type="file" class="form-control-file" id="edit_nom_fichier3" name="edit_nom_fichier3">
                            <input type="hidden" id="existing_nom_fichier3" name="existing_nom_fichier3">
                            <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" name="edit_diagnostic">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier Rendez-vous -->
    <div class="modal fade" id="editRdvModal" tabindex="-1" role="dialog" aria-labelledby="editRdvModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRdvModalLabel">Modifier un rendez-vous</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_rdv_id" name="id">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_client_id">Client:</label>
                                <select class="form-control" id="edit_client_id" name="client_id" required>
                                    <?php foreach ($clients as $client): ?>
                                        <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="edit_date_rdv">Date:</label>
                                <input type="date" class="form-control" id="edit_date_rdv" name="date_rdv" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="edit_heure_rdv">Heure:</label>
                                <input type="time" class="form-control" id="edit_heure_rdv" name="heure_rdv" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="edit_type_rdv">Type:</label>
                                <select class="form-control" id="edit_type_rdv" name="type_rdv" required>
                                    <option value="diagnostic">Diagnostic</option>
                                    <option value="seance">Séance</option>
                                    <option value="controle">Contrôle</option>
                                    <option value="consultation">Consultation</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_statut">Statut:</label>
                                <select class="form-control" id="edit_statut" name="statut" required>
                                    <option value="planifie">Planifié</option>
                                    <option value="confirme">Confirmé</option>
                                    <option value="annule">Annulé</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_message_prevention">Message de prévention:</label>
                            <textarea class="form-control" id="edit_message_prevention" name="message_prevention" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_executant">Exécutant:</label>
                            <input type="text" class="form-control" id="edit_executant" name="executant">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary" name="edit_rdv">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier Séance -->
<div class="modal fade" id="editSeanceModal" tabindex="-1" role="dialog" aria-labelledby="editSeanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSeanceModalLabel">Modifier une séance</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit_seance_id" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_client_id">Client:</label>
                            <select class="form-control" id="edit_client_id" name="client_id" required>
                                <?php foreach ($clients as $client): ?>
                                    <option value='<?php echo $client['id']; ?>'><?php echo $client['nom'] . ' ' . $client['prenoms']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="edit_date_seance">Date:</label>
                            <input type="date" class="form-control" id="edit_date_seance" name="date_seance" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="edit_numero_seance">Numéro:</label>
                            <input type="number" class="form-control" id="edit_numero_seance" name="numero_seance" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_soin">Soin:</label>
                        <input type="text" class="form-control" id="edit_soin" name="soin">
                    </div>
                    <div class="form-group">
                        <label>Traitements:</label>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_microneedle" name="microneedle">
                                    <label class="form-check-label" for="edit_microneedle">Microneedle</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_steamer" name="steamer">
                                    <label class="form-check-label" for="edit_steamer">Steamer</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_bain_huile" name="bain_huile">
                                    <label class="form-check-label" for="edit_bain_huile">Bain huile</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_bain_medical" name="bain_medical">
                                    <label class="form-check-label" for="edit_bain_medical">Bain médical</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_defrisage" name="defrisage">
                                    <label class="form-check-label" for="edit_defrisage">Défrisage</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_coloration" name="coloration">
                                    <label class="form-check-label" for="edit_coloration">Coloration</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_gommage" name="gommage">
                                    <label class="form-check-label" for="edit_gommage">Gommage</label>
                                </div>
                            </div>
                            <div class="form-group col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_stimulation" name="stimulation">
                                    <label class="form-check-label" for="edit_stimulation">Stimulation</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_autres">Autres traitements:</label>
                        <input type="text" class="form-control" id="edit_autres" name="autres">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="edit_executant">Exécutant:</label>
                            <input type="text" class="form-control" id="edit_executant" name="executant">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_nom_fichier1">Photo 1:</label>
                        <input type="file" class="form-control-file" id="edit_nom_fichier1" name="nom_fichier1">
                        <input type="hidden" id="existing_nom_fichier1" name="existing_nom_fichier1">
                        <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_nom_fichier2">Photo 2:</label>
                        <input type="file" class="form-control-file" id="edit_nom_fichier2" name="nom_fichier2">
                        <input type="hidden" id="existing_nom_fichier2" name="existing_nom_fichier2">
                        <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_nom_fichier3">Photo 3:</label>
                        <input type="file" class="form-control-file" id="edit_nom_fichier3" name="nom_fichier3">
                        <input type="hidden" id="existing_nom_fichier3" name="existing_nom_fichier3">
                        <small class="form-text text-muted">Laisser vide pour conserver l'image actuelle</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-primary" name="edit_seance">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pied de page -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="footer-text">Conçu par</div>
                    <a href="https://www.webattou.com" target="_blank" class="text-decoration-none">
                        <img src="https://images.wakelet.com/resize?id=3M6-YKxOz4Q-aLIlv0iUz&w=1600&h=actual&q=85" alt="WEBATTOU Logo" class="footer-logo">
                    </a>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        // Afficher/masquer les formulaires
        $('#showClientForm').click(function() {
            $('#clientForm').toggle();
        });
        $('#showConseilForm').click(function() {
            $('#conseilForm').toggle();
        });
        $('#showControleForm').click(function() {
            $('#controleForm').toggle();
        });
        $('#showDiagnosticForm').click(function() {
            $('#diagnosticForm').toggle();
        });
        $('#showRdvForm').click(function() {
            $('#rdvForm').toggle();
        });
        $('#showSeanceForm').click(function() {
            $('#seanceForm').toggle();
        });
        $('#showUtilisateurForm').click(function() {
            $('#utilisateurForm').toggle();
        });
        // Modal Modifier Client
        $('#editClientModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var nom = button.data('nom');
            var prenoms = button.data('prenoms');
            var genre = button.data('genre');
            var email = button.data('email');
            var telephone = button.data('telephone');
            var pin = button.data('pin');
            var ville = button.data('ville');
            var photo_profil = button.data('photo_profil');
            var modal = $(this);
            modal.find('.modal-body #edit_client_id').val(id);
            modal.find('.modal-body #edit_nom').val(nom);
            modal.find('.modal-body #edit_prenoms').val(prenoms);
            modal.find('.modal-body #edit_genre').val(genre);
            modal.find('.modal-body #edit_email').val(email);
            modal.find('.modal-body #edit_telephone').val(telephone);
            modal.find('.modal-body #edit_pin').val(pin);
            modal.find('.modal-body #edit_ville').val(ville);
            modal.find('.modal-body #existing_photo_profil').val(photo_profil);
        });
        // Modal Modifier Conseil
        $('#editConseilModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var titre = button.data('titre');
            var description = button.data('description');
            var modal = $(this);
            modal.find('.modal-body #edit_conseil_id').val(id);
            modal.find('.modal-body #edit_titre').val(titre);
            modal.find('.modal-body #edit_description').val(description);
        });
        // Modal Modifier Contrôle
        $('#editControleModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var client_id = button.data('client_id');
            var date_controle = button.data('date_controle');
            var reparation_rapide_fibre = button.data('reparation_rapide_fibre');
            var reparation_lente_fibre = button.data('reparation_lente_fibre');
            var repousse_cheveux = button.data('repousse_cheveux');
            var densite_cheveux = button.data('densite_cheveux');
            var elasticite_cheveux = button.data('elasticite_cheveux');
            var force_cheveux = button.data('force_cheveux');
            var executant = button.data('executant');
            var observations = button.data('observations');
            var nom_fichier = button.data('nom_fichier');
            var modal = $(this);
            modal.find('.modal-body #edit_controle_id').val(id);
            modal.find('.modal-body #edit_client_id').val(client_id);
            modal.find('.modal-body #edit_date_controle').val(date_controle);
            modal.find('.modal-body #edit_reparation_rapide_fibre').prop('checked', reparation_rapide_fibre == 1);
            modal.find('.modal-body #edit_reparation_lente_fibre').prop('checked', reparation_lente_fibre == 1);
            modal.find('.modal-body #edit_repousse_cheveux').prop('checked', repousse_cheveux == 1);
            modal.find('.modal-body #edit_densite_cheveux').prop('checked', densite_cheveux == 1);
            modal.find('.modal-body #edit_elasticite_cheveux').prop('checked', elasticite_cheveux == 1);
            modal.find('.modal-body #edit_force_cheveux').prop('checked', force_cheveux == 1);
            modal.find('.modal-body #edit_executant').val(executant);
            modal.find('.modal-body #edit_observations').val(observations);
            modal.find('.modal-body #existing_nom_fichier').val(nom_fichier);
        });
        // Modal Modifier Diagnostic
        $('#editDiagnosticModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var client_id = button.data('client_id');
            var date_diagnostic = button.data('date_diagnostic');
            var cheveux_abimes = button.data('cheveux_abimes');
            var cheveux_faibles = button.data('cheveux_faibles');
            var cheveux_perte_densite = button.data('cheveux_perte_densite');
            var cheveux_trop_gras = button.data('cheveux_trop_gras');
            var alopecie_androgenique = button.data('alopecie_androgenique');
            var alopecie_androgenique_niveau = button.data('alopecie_androgenique_niveau');
            var alopecie_traction = button.data('alopecie_traction');
            var pelade = button.data('pelade');
            var psoriasis = button.data('psoriasis');
            var teigne = button.data('teigne');
            var texture_naturels = button.data('texture_naturels');
            var texture_defrises = button.data('texture_defrises');
            var texture_demeles = button.data('texture_demeles');
            var texture_colores = button.data('texture_colores');
            var executant = button.data('executant');
            var modal = $(this);
            modal.find('.modal-body #edit_diagnostic_id').val(id);
            modal.find('.modal-body #edit_client_id').val(client_id);
            modal.find('.modal-body #edit_date_diagnostic').val(date_diagnostic);
            modal.find('.modal-body #edit_cheveux_abimes').prop('checked', cheveux_abimes == 1);
            modal.find('.modal-body #edit_cheveux_faibles').prop('checked', cheveux_faibles == 1);
            modal.find('.modal-body #edit_cheveux_perte_densite').prop('checked', cheveux_perte_densite == 1);
            modal.find('.modal-body #edit_cheveux_trop_gras').prop('checked', cheveux_trop_gras == 1);
            modal.find('.modal-body #edit_alopecie_androgenique').prop('checked', alopecie_androgenique == 1);
            modal.find('.modal-body #edit_alopecie_androgenique_niveau').val(alopecie_androgenique_niveau);
            modal.find('.modal-body #edit_alopecie_traction').prop('checked', alopecie_traction == 1);
            modal.find('.modal-body #edit_pelade').prop('checked', pelade == 1);
            modal.find('.modal-body #edit_psoriasis').prop('checked', psoriasis == 1);
            modal.find('.modal-body #edit_teigne').prop('checked', teigne == 1);
            modal.find('.modal-body #edit_texture_naturels').prop('checked', texture_naturels == 1);
            modal.find('.modal-body #edit_texture_defrises').prop('checked', texture_defrises == 1);
            modal.find('.modal-body #edit_texture_demeles').prop('checked', texture_demeles == 1);
            modal.find('.modal-body #edit_texture_colores').prop('checked', texture_colores == 1);
            modal.find('.modal-body #edit_executant').val(executant);
        });
        // Modal Modifier Rendez-vous
        $('#editRdvModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var client_id = button.data('client_id');
            var date_rdv = button.data('date_rdv');
            var heure_rdv = button.data('heure_rdv');
            var message_prevention = button.data('message_prevention');
            var statut = button.data('statut');
            var type_rdv = button.data('type_rdv');
            var executant = button.data('executant');
            var modal = $(this);
            modal.find('.modal-body #edit_rdv_id').val(id);
            modal.find('.modal-body #edit_client_id').val(client_id);
            modal.find('.modal-body #edit_date_rdv').val(date_rdv);
            modal.find('.modal-body #edit_heure_rdv').val(heure_rdv);
            modal.find('.modal-body #edit_message_prevention').val(message_prevention);
            modal.find('.modal-body #edit_statut').val(statut);
            modal.find('.modal-body #edit_type_rdv').val(type_rdv);
            modal.find('.modal-body #edit_executant').val(executant);
        });
        // Modal Modifier Séance
        $('#editSeanceModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var client_id = button.data('client_id');
            var date_seance = button.data('date_seance');
            var numero_seance = button.data('numero_seance');
            var soin = button.data('soin');
            var microneedle = button.data('microneedle');
            var steamer = button.data('steamer');
            var bain_huile = button.data('bain_huile');
            var bain_medical = button.data('bain_medical');
            var defrisage = button.data('defrisage');
            var coloration = button.data('coloration');
            var gommage = button.data('gommage');
            var stimulation = button.data('stimulation');
            var autres = button.data('autres');
            var executant = button.data('executant');
            var nom_fichier = button.data('nom_fichier');
            var modal = $(this);
            modal.find('.modal-body #edit_seance_id').val(id);
            modal.find('.modal-body #edit_client_id').val(client_id);
            modal.find('.modal-body #edit_date_seance').val(date_seance);
            modal.find('.modal-body #edit_numero_seance').val(numero_seance);
            modal.find('.modal-body #edit_soin').val(soin);
            modal.find('.modal-body #edit_microneedle').prop('checked', microneedle == 1);
            modal.find('.modal-body #edit_steamer').prop('checked', steamer == 1);
            modal.find('.modal-body #edit_bain_huile').prop('checked', bain_huile == 1);
            modal.find('.modal-body #edit_bain_medical').prop('checked', bain_medical == 1);
            modal.find('.modal-body #edit_defrisage').prop('checked', defrisage == 1);
            modal.find('.modal-body #edit_coloration').prop('checked', coloration == 1);
            modal.find('.modal-body #edit_gommage').prop('checked', gommage == 1);
            modal.find('.modal-body #edit_stimulation').prop('checked', stimulation == 1);
            modal.find('.modal-body #edit_autres').val(autres);
            modal.find('.modal-body #edit_executant').val(executant);
            modal.find('.modal-body #existing_nom_fichier').val(nom_fichier);
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#editDiagnosticModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            for (var i = 1; i <= 3; i++) {
                var nomFichier = button.data('nom_fichier' + i);
                var modal = $(this);
                modal.find('.modal-body #existing_nom_fichier' + i).val(nomFichier);
            }
        });
    });
    </script>
    <!-- Modal d'aperçu d'image global -->
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
</body>
</html>
<?php
$conn = null;
?>;