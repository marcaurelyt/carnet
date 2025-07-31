<?php
require_once 'config.php';

// Traitement du formulaire de connexion client
if ($_POST['action'] ?? '' === 'connexion_client') {
    $nom = secure($_POST['nom']);
    $prenoms = secure($_POST['prenoms']);
    $pin = secure($_POST['pin']);
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM clients WHERE nom = ? AND prenoms = ?");
    $stmt->execute([$nom, $prenoms]);
    $client = $stmt->fetch();
    if ($client) {
        if ($client['pin'] === $pin) {
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_nom'] = $client['nom'];
            $_SESSION['client_prenoms'] = $client['prenoms'];
            // Stocker le nom et les prénoms dans un cookie pour une durée d'un an
            setcookie('client_nom', $client['nom'], time() + (86400 * 365), "/");
            setcookie('client_prenoms', $client['prenoms'], time() + (86400 * 365), "/");
            $show_loading = true;
        } else {
            $error = "Code PIN incorrect. Veuillez réessayer.";
        }
    } else {
        $error = "Client non trouvé. Veuillez contacter le centre.";
    }
}

// Vérifier si les cookies de nom et prénoms sont définis
$nom_cookie = $_COOKIE['client_nom'] ?? '';
$prenoms_cookie = $_COOKIE['client_prenoms'] ?? '';

// Traitement du changement de compte
if (isset($_POST['change_account'])) {
    setcookie('client_nom', '', time() - 3600, "/");
    setcookie('client_prenoms', '', time() - 3600, "/");
    $nom_cookie = '';
    $prenoms_cookie = '';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre Florence - Carnet de Rendez-vous</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Ajoutez ce style pour le bouton Changer de compte */
        .change-account-btn {
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .change-account-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        /* Le reste de votre CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            height: 100vh;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(117deg, #667eea 0%, #00f2fd 100%);
            position: relative;
        }
        /* Particules animées en arrière-plan */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s infinite linear;
        }
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
        }
        /* Écran de chargement */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.5s ease;
        }
        .loading-screen.active {
            opacity: 1;
            visibility: visible;
        }
        .loading-logo {
            width: 200px;
            height: 200px;
            background: url('https://centreflorence.com/images/LOGO_BLANC1.png') center/contain no-repeat;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .loading-text {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin-top: 25px;
            text-align: center;
            animation: fadeInUp 1s ease-out;
        }
        .loading-spinner {
            margin-top: 20px;
            animation: fadeInUp 1s ease-out 0.3s both;
        }
        /* Container principal */
        .app-container {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            padding: 15px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow:
                0 32px 64px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 380px;
            max-height: 95vh;
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            animation: cardEntrance 1s ease-out 0.5s forwards;
            border-style: solid;
        }
        @keyframes cardEntrance {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        /* Info button */
        .info-button {
            position: absolute;
            top: 16px;
            right: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
            font-size: 14px;
        }
        .info-button:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        /* Header */
        .header-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 24px 20px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: translateX(-100%);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .florence-logo {
            width: 60px;
            height: 50px;
            background: url('https://centreflorence.com/images/LOGO_BLANC1.png') center/contain no-repeat;
            margin: 0 auto 12px;
            animation: logoFloat 3s ease-in-out infinite;
        }
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        .title-main {
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.025em;
        }
        .subtitle {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        /* Titre carnet */
        .carnet-title {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin: 20px 0;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }
        @keyframes titleGlow {
            from { filter: brightness(1); }
            to { filter: brightness(1.2); }
        }
        /* Formulaire */
        .form-section {
            padding: 0 24px 24px;
        }
        .form-group {
            position: relative;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
        }
        .input-container {
            position: relative;
        }
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 500;
            background: #fafafa;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
            outline: none;
        }
        .form-control:focus + .input-animation {
            transform: scaleX(1);
        }
        .input-animation {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 1px;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .pin-input {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 8px;
        }
        .pin-dots {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
        }
        .pin-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e5e7eb;
            transition: all 0.3s ease;
        }
        .pin-dot.filled {
            background: #667eea;
            transform: scale(1.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 16px;
            width: 100%;
            margin-top: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-primary:hover::before {
            left: 100%;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        /* Footer */
        .footer-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .footer-logo {
            width: 60px;
            height: auto;
            margin-bottom: 12px;
            border-radius: 8px;
        }
        .footer-text {
            font-style: italic;
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .whatsapp-button {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .whatsapp-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
            color: white;
        }
        /* Alert */
        .alert {
            border-radius: 12px;
            margin-bottom: 20px;
            border: none;
            animation: slideInDown 0.5s ease-out;
        }
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        /* Modal */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
            padding: 20px 24px;
        }
        .modal-body {
            padding: 24px;
            font-size: 15px;
            line-height: 1.6;
            color: #374151;
        }
        .modal-footer {
            border-top: none;
            padding: 16px 24px 24px;
        }
        /* Animations utilitaires */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        /* Responsive */
        @media (max-height: 700px) {
            .carnet-title { font-size: 20px; margin: 16px 0; }
            .form-section { padding: 0 20px 20px; }
            .form-group { margin-bottom: 16px; }
            .header-section { padding: 20px 16px 16px; }
            .footer-section { padding: 16px; }
        }
        @media (max-height: 600px) {
            .florence-logo { width: 50px; height: 40px; }
            .title-main { font-size: 18px; }
            .carnet-title { font-size: 18px; margin: 12px 0; }
        }
    </style>
</head>
<body>
    <!-- Particules animées -->
    <div class="particles" id="particles"></div>
    <!-- Écran de chargement -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-logo"></div>
        <div class="loading-text">
            <i class="fas fa-calendar-check me-2"></i>Chargement de votre carnet...
        </div>
        <div class="loading-spinner">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    </div>
    <div class="app-container">
        <div class="login-card">
            <!-- Bouton d'information -->
            <button class="info-button" type="button" data-bs-toggle="modal" data-bs-target="#infoModal">
                <i class="fas fa-info"></i>
            </button>
            <!-- Header -->
            <div class="header-section">
                <div class="florence-logo"></div>
                <h1 class="title-main">Centre Florence</h1>
                <p class="subtitle">Santé du Cheveu</p>
            </div>
            <!-- Titre Carnet -->
            <h2 class="carnet-title">Carnet de rendez-vous</h2>
            <!-- Formulaire -->
            <div class="form-section">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="fade-in-up">
                    <input type="hidden" name="action" value="connexion_client">
                    <?php if (empty($nom_cookie) || empty($prenoms_cookie)): ?>
                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <div class="input-container">
                                <input type="text" class="form-control" name="nom" required>
                                <div class="input-animation"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prénoms</label>
                            <div class="input-container">
                                <input type="text" class="form-control" name="prenoms" required>
                                <div class="input-animation"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="form-label">Nom</label>
                            <div class="input-container">
                                <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($nom_cookie); ?>" readonly>
                                <div class="input-animation"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Prénoms</label>
                            <div class="input-container">
                                <input type="text" class="form-control" name="prenoms" value="<?php echo htmlspecialchars($prenoms_cookie); ?>" readonly>
                                <div class="input-animation"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Code PIN</label>
                        <div class="input-container">
                            <input type="password" class="form-control pin-input" name="pin"
                                   maxlength="4" required placeholder="••••" id="pinInput">
                            <div class="input-animation"></div>
                        </div>
                        <div class="pin-dots" id="pinDots">
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Accéder au carnet
                    </button>
                </form>
                <?php if (!empty($nom_cookie) || !empty($prenoms_cookie)): ?>
                    <form method="POST">
                        <button type="submit" name="change_account" class="change-account-btn">
                            <i class="fas fa-user-switch me-2"></i>Changer de compte
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <!-- Footer -->
            <div class="footer-section">
                <img src="https://images.wakelet.com/resize?id=WtA4gH0unnz1lJooc6GBJ&w=1600&h=actual&q=85"
                     alt="Logo" class="footer-logo">
                <p class="footer-text">Nous redonnons vie à vos cheveux</p>
                <a href="https://wa.me/votre_numero" class="whatsapp-button">
                    <i class="fab fa-whatsapp me-2"></i>Contactez l'Assistance
                </a>
            </div>
        </div>
    </div>
    <!-- Modal d'information -->
    <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Information importante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Ce compte est à usage personnel</strong>, il ne doit en aucun cas être prêté à une tierce personne même si les cas de pathologies sont similaires.</p>
                    <p>En cas de perte ou d'inaccessibilité, veuillez contacter le service client du Centre Florence Santé du cheveu.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-check me-2"></i>J'ai compris
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Génération de particules
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 50;
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particles.appendChild(particle);
            }
        }
        // Animation des points PIN
        function updatePinDots() {
            const pinInput = document.getElementById('pinInput');
            const dots = document.querySelectorAll('.pin-dot');
            pinInput.addEventListener('input', function() {
                const length = this.value.length;
                dots.forEach((dot, index) => {
                    if (index < length) {
                        dot.classList.add('filled');
                    } else {
                        dot.classList.remove('filled');
                    }
                });
            });
        }
        // Filtrage du PIN (nombres uniquement)
        document.getElementById('pinInput').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        // Écran de chargement
        <?php if (isset($show_loading) && $show_loading): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            loadingScreen.classList.add('active');
            setTimeout(() => {
                window.location.href = 'carnet.php';
            }, 2000);
        });
        <?php endif; ?>
        // Animations au chargement
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            updatePinDots();
            // Animation séquentielle des éléments du formulaire
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    group.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, 100 * (index + 1));
            });
        });
        // Effet de ripple sur le bouton
        document.querySelector('.btn-primary').addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        // Style pour l'effet ripple
        const style = document.createElement('style');
        style.textContent = `
            .btn-primary {
                position: relative;
                overflow: hidden;
            }
            .ripple {
                position: absolute;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
            }
            @keyframes rippleEffect {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
