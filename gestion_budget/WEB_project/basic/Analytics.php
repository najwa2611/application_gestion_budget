<?php
session_start();
require_once('../db.php');

// Configuration et connexion DB
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'projet_web',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database connection error");
}

// Vérification authentification
if (!isset($_SESSION['user_id'])) {
    header("Location: ../first_page/index.html");
    exit();
}

// Récupération données utilisateur
$user = [
    'id' => $_SESSION['user_id'],
    'name' => htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'),
    'gender' => $_SESSION['user_gender'] ?? null
];

// Gestion avatar
$avatarPaths = [
    'man' => "./man.jpg",
    'woman' => "./woman.jpg",
    'default' => "../assets/images/default.png"
];

if (empty($user['gender'])) {
    $stmt = $pdo->prepare("SELECT gender FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch();
    $user['gender'] = $result['gender'] ?? null;
    $_SESSION['user_gender'] = $user['gender'];
}

$avatar = $avatarPaths[$user['gender']] ?? $avatarPaths['default'];

// Gestion budget
$currentDate = [
    'month' => date('n'),
    'year' => date('Y')
];

$budgetData = [
    'total' => 0,
    'spent' => 0,
    'remaining' => 0,
    'items' => []
];

// Récupération budget principal
$stmt = $pdo->prepare("
    SELECT id, total_amount 
    FROM budgets 
    WHERE user_id = ? AND month = ? AND year = ?
");
$stmt->execute([$user['id'], $currentDate['month'], $currentDate['year']]);

if ($stmt->rowCount() > 0) {
    $budget = $stmt->fetch();
    $_SESSION['current_budget_id'] = $budget['id'];
    $budgetData['total'] = (float)$budget['total_amount'];

    // Récupération dépenses
    $stmt = $pdo->prepare("
        SELECT SUM(spent) as total_spent 
        FROM budget_items 
        WHERE budget_id = ?
    ");
    $stmt->execute([$budget['id']]);
    $result = $stmt->fetch();
    $budgetData['spent'] = (float)($result['total_spent'] ?? 0);
    $budgetData['remaining'] = max(0, $budgetData['total'] - $budgetData['spent']);

    // Récupération items
    $stmt = $pdo->prepare("
        SELECT *, 
               (amount - spent) as remaining,
               (spent / amount * 100) as percentage
        FROM budget_items
        WHERE budget_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$budget['id']]);
    $budgetData['items'] = $stmt->fetchAll();
}

// Prepare data for analytics charts
$chartData = [
    'totalBudget' => $budgetData['total'],
    'totalSpent' => $budgetData['spent'],
    'categories' => []
];

foreach ($budgetData['items'] as $item) {
    $chartData['categories'][] = [
        'name' => $item['category'],
        'budget' => (float)$item['amount'],
        'spent' => (float)$item['spent']
    ];
}

// Gestion notifications
$notifications = [];
if (isset($_SESSION['success'])) {
    $notifications['success'] = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $notifications['error'] = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expencio | Advanced Analytics</title>
    <link rel="stylesheet" href="basic.css">
    <link rel="stylesheet" href="Analytics.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-trendline"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <img src="../logo.jpg" alt="Expencio Logo" class="logo-image">
                </div>
                <button class="close-sidebar-btn" title="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="basic.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="analytics.php">
                            <i class="fas fa-chart-pie"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="../services/pageservice.php" id="aiAssistantBtn">
                            <i class="fas fa-robot"></i>
                            <span>Services</span>
                        </a>
                    </li>
                    <li>
                        <a href="../settings/settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Header Bar -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Advanced Financial Analytics</h1>
                        <p>Data-driven budget insights and trends</p>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="export-actions">
                        <button class="btn btn-secondary" id="export-pdf">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                    <div class="user-profile">
                        <div class="user-info">
                            <span class="welcome">Welcome back,</span>
                            <span class="username"><?= $user['name'] ?></span>
                        </div>
                        <div class="user-avatar">
                            <img src="<?= $avatar ?>" alt="User Avatar">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Analytics Content -->
            <div class="content-wrapper">
                <!-- Time Period and Filters -->
                <div class="analytics-controls">
                    <div class="time-period-selector">
                        <button class="btn btn-secondary active" data-period="month">
                            <i class="fas fa-calendar-alt"></i> This Month
                        </button>
                        <button class="btn btn-secondary" data-period="quarter">
                            <i class="fas fa-calendar"></i> Last Quarter
                        </button>
                        <button class="btn btn-secondary" data-period="year">
                            <i class="fas fa-calendar-day"></i> This Year
                        </button>
                        <button class="btn btn-secondary" data-period="custom">
                            <i class="fas fa-calendar-week"></i> Custom Range
                        </button>
                    </div>
                    
                    <div class="chart-filters">
                        <div class="filter-group">
                            <label for="chart-type"><i class="fas fa-chart-bar"></i> Chart Type</label>
                            <select id="chart-type" class="form-select">
                                <option value="all">All Charts</option>
                                <option value="distribution">Budget Distribution</option>
                                <option value="trend">Spending Trend</option>
                                <option value="comparison">Budget vs Actual</option>
                                <option value="breakdown">Category Breakdown</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="data-view"><i class="fas fa-table"></i> Data View</label>
                            <select id="data-view" class="form-select">
                                <option value="amounts">Amounts</option>
                                <option value="percentages">Percentages</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Summary -->
                <div class="metrics-summary">
                    <div class="metric-card">
                        <div class="metric-value" id="total-budget-metric">$<?= number_format($budgetData['total'], 2) ?></div>
                        <div class="metric-label">Total Budget</div>
                        <div class="metric-change positive">
                            <i class="fas fa-caret-up"></i> <span>0.0%</span> from last period
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="total-spent-metric">$<?= number_format($budgetData['spent'], 2) ?></div>
                        <div class="metric-label">Total Spent</div>
                        <div class="metric-change negative">
                            <i class="fas fa-caret-down"></i> <span>0.0%</span> from last period
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="savings-rate-metric">0.0%</div>
                        <div class="metric-label">Savings Rate</div>
                        <div class="metric-change positive">
                            <i class="fas fa-caret-up"></i> <span>0.0%</span> from last period
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value" id="overspending-metric">$0.00</div>
                        <div class="metric-label">Overspending</div>
                        <div class="metric-change negative">
                            <i class="fas fa-exclamation-triangle"></i> <span>0.0%</span> of budget
                        </div>
                    </div>
                </div>

                <!-- Analytics Grid -->
                <div class="analytics-grid">
                    <!-- Budget Distribution Chart -->
                    <section class="analytics-card chart-card" data-chart-type="distribution">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-pie"></i> Budget Allocation</h2>
                            <div class="section-actions">
                                <button class="btn-icon chart-fullscreen" data-chart="distribution">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="btn-icon chart-download" data-chart="distribution">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="budgetDistributionChart"></canvas>
                        </div>
                        <div class="chart-legend" id="distribution-legend"></div>
                    </section>

                    <!-- Spending Trend Chart -->
                    <section class="analytics-card chart-card" data-chart-type="trend">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-line"></i> Spending Trend with Forecast</h2>
                            <div class="section-actions">
                                <button class="btn-icon chart-fullscreen" data-chart="trend">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="btn-icon chart-download" data-chart="trend">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="spendingTrendChart"></canvas>
                        </div>
                        <div class="chart-toolbar">
                            <button class="btn btn-sm btn-secondary trend-toggle" data-trend="3months">
                                3 Months
                            </button>
                            <button class="btn btn-sm btn-secondary trend-toggle" data-trend="6months">
                                6 Months
                            </button>
                            <button class="btn btn-sm btn-secondary trend-toggle active" data-trend="12months">
                                12 Months
                            </button>
                        </div>
                    </section>

                    <!-- Budget vs Actual -->
                    <section class="analytics-card chart-card" data-chart-type="comparison">
                        <div class="section-header">
                            <h2><i class="fas fa-balance-scale"></i> Budget vs Actual Variance</h2>
                            <div class="section-actions">
                                <button class="btn-icon chart-fullscreen" data-chart="comparison">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="btn-icon chart-download" data-chart="comparison">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="budgetVsActualChart"></canvas>
                        </div>
                        <div class="variance-summary" id="variance-summary"></div>
                    </section>

                    <!-- Category Breakdown -->
                    <section class="analytics-card chart-card" data-chart-type="breakdown">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-bar"></i> Category Performance</h2>
                            <div class="section-actions">
                                <button class="btn-icon chart-fullscreen" data-chart="breakdown">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <button class="btn-icon chart-download" data-chart="breakdown">
                                    <i class="fas fa-download"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="categoryBreakdownChart"></canvas>
                        </div>
                        <div class="performance-table">
                            <table id="category-performance-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Budget</th>
                                        <th>Spent</th>
                                        <th>Variance</th>
                                        <th>% of Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Filled by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- Fullscreen Chart Modal -->
    <div class="modal" id="chart-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-chart-title">Chart Title</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <canvas id="modal-chart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const budgetData = <?= json_encode($chartData) ?>;
    </script>
    
    <script src="basic.js"></script>
    <script src="Analytics.js"></script>
</body>
</html>