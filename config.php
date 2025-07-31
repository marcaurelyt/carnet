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
    return date('d/m/Y', strtotime($date));
}

// Démarrage de la session
session_start();
?>