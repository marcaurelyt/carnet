<?php
// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "carnet_cheveu";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des filtres avec validation
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';
$start_month_filter = isset($_GET['start_month_filter']) ? $_GET['start_month_filter'] : date('Y-m');
$end_month_filter = isset($_GET['end_month_filter']) ? $_GET['end_month_filter'] : date('Y-m');
$client_filter = isset($_GET['client_filter']) ? $_GET['client_filter'] : '';

// Construction des conditions WHERE selon les filtres
function buildWhereClause($table, $date_filter, $start_month_filter, $end_month_filter, $client_filter, &$params, $alias = '') {
    $where_conditions = [];
    $dateColumn = $alias ? "$alias.date_creation" : "$table.date_creation";

    switch($date_filter) {
        case 'today':
            $where_conditions[] = "DATE($dateColumn) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "WEEK($dateColumn) = WEEK(CURDATE()) AND YEAR($dateColumn) = YEAR(CURDATE())";
            break;
        case 'month':
            $where_conditions[] = "DATE_FORMAT($dateColumn, '%Y-%m') BETWEEN :start_month AND :end_month";
            $params[':start_month'] = $start_month_filter;
            $params[':end_month'] = $end_month_filter;
            break;
        case 'year':
            $where_conditions[] = "YEAR($dateColumn) = YEAR(CURDATE())";
            break;
    }

    if (!empty($client_filter)) {
        if ($table === 'clients') {
            $clientColumn = $alias ? "$alias.id" : "$table.id";
        } else {
            $clientColumn = $alias ? "$alias.client_id" : "$table.client_id";
        }
        $where_conditions[] = "$clientColumn = :client_id";
        $params[':client_id'] = $client_filter;
    }

    return !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
}

// Fonction pour récupérer les statistiques
function getStats($pdo, $table, $date_filter, $start_month_filter, $end_month_filter, $client_filter, &$params, $alias = '') {
    $where_clause = buildWhereClause($table, $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params, $alias);
    $sql = "SELECT COUNT(*) as count FROM $table $where_clause";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

// Fonction pour récupérer les statistiques mensuelles avec limite de 6 mois
function getMonthlyStats($pdo, $table) {
    $sql = "SELECT DATE_FORMAT(date_creation, '%Y-%m') as mois,
                   COUNT(*) as count
            FROM $table
            WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
            ORDER BY mois ASC
            LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupération des statistiques
$params = [];
$stats_clients = getStats($pdo, 'clients', $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params);
$params = [];
$stats_diagnostics = getStats($pdo, 'diagnostics', $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params);
$params = [];
$stats_controles = getStats($pdo, 'controles', $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params);
$params = [];
$stats_seances = getStats($pdo, 'seances', $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params);
$params = [];
$stats_rdv = getStats($pdo, 'rdv', $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params);

// Statistiques mensuelles pour les graphiques
$monthly_clients = getMonthlyStats($pdo, 'clients');
$monthly_diagnostics = getMonthlyStats($pdo, 'diagnostics');
$monthly_seances = getMonthlyStats($pdo, 'seances');

// Récupération de la liste des clients pour le filtre
$clients_stmt = $pdo->query("SELECT id, nom, prenoms FROM clients ORDER BY nom");
$clients_list = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour récupérer les données détaillées
function getDetailedData($pdo, $table, $date_filter, $start_month_filter, $end_month_filter, $client_filter, $alias = '', $limit = 10) {
    $params = [];
    $where_clause = buildWhereClause($table, $date_filter, $start_month_filter, $end_month_filter, $client_filter, $params, $alias);

    switch($table) {
        case 'clients':
            $sql = "SELECT c.*, COUNT(DISTINCT d.id) as nb_diagnostics, COUNT(DISTINCT s.id) as nb_seances
                    FROM clients c
                    LEFT JOIN diagnostics d ON c.id = d.client_id
                    LEFT JOIN seances s ON c.id = s.client_id
                    $where_clause
                    GROUP BY c.id
                    ORDER BY c.date_creation DESC
                    LIMIT $limit";
            break;
        case 'diagnostics':
            $sql = "SELECT d.*, CONCAT(c.nom, ' ', c.prenoms) as client_nom
                    FROM diagnostics d
                    JOIN clients c ON d.client_id = c.id
                    $where_clause
                    ORDER BY d.date_creation DESC
                    LIMIT $limit";
            break;
        case 'controles':
            $sql = "SELECT co.*, CONCAT(c.nom, ' ', c.prenoms) as client_nom
                    FROM controles co
                    JOIN clients c ON co.client_id = c.id
                    $where_clause
                    ORDER BY co.date_creation DESC
                    LIMIT $limit";
            break;
        case 'seances':
            $sql = "SELECT s.*, CONCAT(c.nom, ' ', c.prenoms) as client_nom
                    FROM seances s
                    JOIN clients c ON s.client_id = c.id
                    $where_clause
                    ORDER BY s.date_creation DESC
                    LIMIT $limit";
            break;
        case 'rdv':
            $sql = "SELECT r.*, CONCAT(c.nom, ' ', c.prenoms) as client_nom
                    FROM rdv r
                    JOIN clients c ON r.client_id = c.id
                    $where_clause
                    ORDER BY r.date_creation DESC
                    LIMIT $limit";
            break;
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupération des données détaillées
$detailed_clients = getDetailedData($pdo, 'clients', $date_filter, $start_month_filter, $end_month_filter, $client_filter, 'c');
$detailed_diagnostics = getDetailedData($pdo, 'diagnostics', $date_filter, $start_month_filter, $end_month_filter, $client_filter, 'd');
$detailed_controles = getDetailedData($pdo, 'controles', $date_filter, $start_month_filter, $end_month_filter, $client_filter, 'co');
$detailed_seances = getDetailedData($pdo, 'seances', $date_filter, $start_month_filter, $end_month_filter, $client_filter, 's');
$detailed_rdv = getDetailedData($pdo, 'rdv', $date_filter, $start_month_filter, $end_month_filter, $client_filter, 'r');

// Préparation des données pour le graphique mensuel
$all_months = [];
$current_month = date('Y-m');
for ($i = 5; $i >= 0; $i--) {
    $all_months[] = date('Y-m', strtotime("-$i months", strtotime($current_month)));
}

// Compléter les données manquantes avec des zéros
function completeMonthlyData($data, $all_months) {
    $completed_data = [];
    $existing_months = array_column($data, 'mois');
    foreach ($all_months as $month) {
        if (in_array($month, $existing_months)) {
            $index = array_search($month, $existing_months);
            $completed_data[] = $data[$index];
        } else {
            $completed_data[] = ['mois' => $month, 'count' => 0];
        }
    }
    return $completed_data;
}

$monthly_clients = completeMonthlyData($monthly_clients, $all_months);
$monthly_diagnostics = completeMonthlyData($monthly_diagnostics, $all_months);
$monthly_seances = completeMonthlyData($monthly_seances, $all_months);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Carnet Capillaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .stat-card.clients { border-left-color: #007bff; }
        .stat-card.diagnostics { border-left-color: #28a745; }
        .stat-card.controles { border-left-color: #ffc107; }
        .stat-card.seances { border-left-color: #dc3545; }
        .stat-card.rdv { border-left-color: #6f42c1; }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        .filter-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .data-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .text-purple {
            color: #6f42c1;
        }
        .chart-legend {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        .chart-legend-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        .chart-legend-color {
            width: 20px;
            height: 3px;
            margin-right: 5px;
            border-radius: 2px;
        }
        .refresh-btn {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #667eea;
            color: white;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 1000;
            transition: all 0.3s;
        }
        .refresh-btn:hover {
            background-color: #764ba2;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
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
<body class="bg-light">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h2 mb-0">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    PAGE DE SUIVI - Le Centre Florence Santé du Cheveu
                </h1>
                <p class="text-muted">Suivi et statistiques détaillées</p>
            </div>
        </div>
        <!-- Filtres -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <select name="date_filter" class="form-select">
                        <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
                        <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>Cette semaine</option>
                        <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>Intervalle de mois</option>
                        <option value="year" <?= $date_filter == 'year' ? 'selected' : '' ?>>Cette année</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mois de début</label>
                    <input type="month" name="start_month_filter" class="form-control" value="<?= $start_month_filter ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mois de fin</label>
                    <input type="month" name="end_month_filter" class="form-control" value="<?= $end_month_filter ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Client spécifique</label>
                    <select name="client_filter" class="form-select">
                        <option value="">Tous les clients</option>
                        <?php foreach($clients_list as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= $client_filter == $client['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['nom'] . ' ' . $client['prenoms']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-light d-block w-100">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
        <!-- Statistiques Cards -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-2 mb-3">
                <div class="card stat-card clients h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Clients</h6>
                                <h3 class="mb-0"><?= $stats_clients ?></h3>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2 mb-3">
                <div class="card stat-card diagnostics h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Diagnostics</h6>
                                <h3 class="mb-0"><?= $stats_diagnostics ?></h3>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-stethoscope fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2 mb-3">
                <div class="card stat-card controles h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Contrôles</h6>
                                <h3 class="mb-0"><?= $stats_controles ?></h3>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-clipboard-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2 mb-3">
                <div class="card stat-card seances h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Séances</h6>
                                <h3 class="mb-0"><?= $stats_seances ?></h3>
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-spa fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2 mb-3">
                <div class="card stat-card rdv h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">RDV</h6>
                                <h3 class="mb-0"><?= $stats_rdv ?></h3>
                            </div>
                            <div class="text-purple">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Graphiques -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2"></i>
                        Évolution mensuelle des activités
                    </h5>
                    <div style="height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #007bff;"></div>
                            <span>Clients</span>
                        </div>
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #28a745;"></div>
                            <span>Diagnostics</span>
                        </div>
                        <div class="chart-legend-item">
                            <div class="chart-legend-color" style="background-color: #dc3545;"></div>
                            <span>Séances</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-pie me-2"></i>
                        Répartition des activités
                    </h5>
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Données détaillées -->
        <div class="row">
            <!-- Clients récents -->
            <div class="col-lg-6 mb-4">
                <div class="data-section">
                    <h5 class="mb-3">
                        <i class="fas fa-users me-2"></i>
                        Clients récents
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Diagnostics</th>
                                    <th>Séances</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detailed_clients as $client): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($client['nom'] . ' ' . $client['prenoms']) ?></strong>
                                        <br><small class="text-muted"><?= $client['ville'] ?? 'N/A' ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($client['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($client['telephone'] ?? 'N/A') ?></td>
                                    <td><span class="badge bg-success"><?= $client['nb_diagnostics'] ?></span></td>
                                    <td><span class="badge bg-danger"><?= $client['nb_seances'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Diagnostics récents -->
            <div class="col-lg-6 mb-4">
                <div class="data-section">
                    <h5 class="mb-3">
                        <i class="fas fa-stethoscope me-2"></i>
                        Diagnostics récents
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Exécutant</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detailed_diagnostics as $diag): ?>
                                <tr>
                                    <td><?= htmlspecialchars($diag['client_nom']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($diag['date_diagnostic'])) ?></td>
                                    <td><?= htmlspecialchars($diag['executant'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if($diag['cheveux_abimes']): ?>
                                            <span class="badge bg-warning">Abîmés</span>
                                        <?php endif; ?>
                                        <?php if($diag['alopecie_androgenique']): ?>
                                            <span class="badge bg-danger">Alopécie</span>
                                        <?php endif; ?>
                                        <?php if(!$diag['cheveux_abimes'] && !$diag['alopecie_androgenique']): ?>
                                            <span class="badge bg-success">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Contrôles récents -->
            <div class="col-lg-6 mb-4">
                <div class="data-section">
                    <h5 class="mb-3">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Contrôles récents
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Exécutant</th>
                                    <th>Résultats</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detailed_controles as $controle): ?>
                                <tr>
                                    <td><?= htmlspecialchars($controle['client_nom']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($controle['date_controle'])) ?></td>
                                    <td><?= htmlspecialchars($controle['executant'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if($controle['reparation_rapide_fibre']): ?>
                                            <span class="badge bg-success">Réparation rapide</span>
                                        <?php endif; ?>
                                        <?php if($controle['elasticite_cheveux']): ?>
                                            <span class="badge bg-info">Élasticité OK</span>
                                        <?php endif; ?>
                                        <?php if(!$controle['reparation_rapide_fibre'] && !$controle['elasticite_cheveux']): ?>
                                            <span class="badge bg-warning">Aucun résultat</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Séances récentes -->
            <div class="col-lg-6 mb-4">
                <div class="data-section">
                    <h5 class="mb-3">
                        <i class="fas fa-spa me-2"></i>
                        Séances récentes
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>N° Séance</th>
                                    <th>Date</th>
                                    <th>Traitements</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detailed_seances as $seance): ?>
                                <tr>
                                    <td><?= htmlspecialchars($seance['client_nom']) ?></td>
                                    <td><span class="badge bg-primary"><?= $seance['numero_seance'] ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($seance['date_seance'])) ?></td>
                                    <td>
                                        <?php if($seance['microneedle']): ?>
                                            <span class="badge bg-danger">Microneedle</span>
                                        <?php endif; ?>
                                        <?php if($seance['steamer']): ?>
                                            <span class="badge bg-info">Steamer</span>
                                        <?php endif; ?>
                                        <?php if($seance['bain_medical']): ?>
                                            <span class="badge bg-success">Bain médical</span>
                                        <?php endif; ?>
                                        <?php if(!$seance['microneedle'] && !$seance['steamer'] && !$seance['bain_medical']): ?>
                                            <span class="badge bg-secondary">Aucun traitement</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- RDV récents -->
            <div class="col-lg-12 mb-4">
                <div class="data-section">
                    <h5 class="mb-3">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Rendez-vous récents
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Exécutant</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($detailed_rdv as $rdv): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rdv['client_nom']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></td>
                                    <td><?= date('H:i', strtotime($rdv['heure_rdv'])) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst($rdv['type_rdv']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'planifie' => 'bg-warning',
                                            'confirme' => 'bg-info',
                                            'termine' => 'bg-success',
                                            'annule' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="badge <?= $status_class[$rdv['statut']] ?? 'bg-secondary' ?>">
                                            <?= ucfirst($rdv['statut']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($rdv['executant'] ?? 'N/A') ?></td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($rdv['message_prevention'] ?? '', 0, 50)) ?><?= strlen($rdv['message_prevention'] ?? '') > 50 ? '...' : '' ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
    <!-- Bouton d'actualisation -->
    <button class="refresh-btn" id="refreshBtn" title="Actualiser">
        <i class="fas fa-sync-alt"></i>
    </button>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données pour les graphiques
        const monthlyData = {
            labels: [<?php echo "'" . implode("','", array_column($monthly_clients, 'mois')) . "'"; ?>],
            datasets: [
                {
                    label: 'Clients',
                    data: [<?php echo implode(',', array_column($monthly_clients, 'count')); ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Diagnostics',
                    data: [<?php echo implode(',', array_column($monthly_diagnostics, 'count')); ?>],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Séances',
                    data: [<?php echo implode(',', array_column($monthly_seances, 'count')); ?>],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        };

        // Configuration du graphique linéaire mensuel
        const monthlyChartConfig = {
            type: 'line',
            data: monthlyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                }
            }
        };

        // Graphique linéaire mensuel
        const ctx1 = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx1, monthlyChartConfig);

        // Graphique en secteurs
        const ctx2 = document.getElementById('pieChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Clients', 'Diagnostics', 'Contrôles', 'Séances', 'RDV'],
                datasets: [{
                    data: [<?= $stats_clients ?>, <?= $stats_diagnostics ?>, <?= $stats_controles ?>, <?= $stats_seances ?>, <?= $stats_rdv ?>],
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6f42c1'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gestion du bouton d'actualisation
        document.getElementById('refreshBtn').addEventListener('click', function() {
            // Ajouter une animation de rotation
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');

            // Récupérer les paramètres actuels
            const urlParams = new URLSearchParams(window.location.search);
            const params = {};
            urlParams.forEach((value, key) => {
                params[key] = value;
            });

            // Construire l'URL avec les paramètres existants
            const queryString = new URLSearchParams(params).toString();
            const url = window.location.pathname + (queryString ? '?' + queryString : '');

            // Recharger la page après un court délai pour permettre à l'animation de se voir
            setTimeout(() => {
                window.location.href = url;
            }, 500);
        });
    </script>
</body>
</html>
