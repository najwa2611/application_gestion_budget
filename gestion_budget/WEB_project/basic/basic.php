<?php
session_start();
include('../db.php');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location:../login/login.php");
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Handle avatar
$avatarPaths = [
    'man' => "./man.jpg",
    'woman' => "./woman.jpg",
    'default' => "../assets/images/default.png"
];

if (empty($_SESSION['user_gender'])) {
    $stmt = $pdo->prepare("SELECT gender FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $_SESSION['user_gender'] = $result['gender'] ?? null;
}

$avatar = $avatarPaths[$_SESSION['user_gender']] ?? $avatarPaths['default'];

// Récupérer le budget actuel de l'utilisateur
$currentMonth = date('n');
$currentYear = date('Y');
$currentBudget = null;
$totalBudget = 0;
$totalSpent = 0;

// Récupérer le budget principal
$stmt = $pdo->prepare("SELECT id, total_amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
$stmt->execute([$userId, $currentMonth, $currentYear]);
if ($stmt->rowCount() > 0) {
    $currentBudget = $stmt->fetch();
    $_SESSION['current_budget_id'] = $currentBudget['id'];
    $totalBudget = $currentBudget['total_amount'];
}

// Récupérer le total dépensé (somme de tous les items)
if (isset($_SESSION['current_budget_id'])) {
    $spentStmt = $pdo->prepare("SELECT SUM(spent) as total_spent FROM budget_items WHERE budget_id = ?");
    $spentStmt->execute([$_SESSION['current_budget_id']]);
    $spentResult = $spentStmt->fetch();
    $totalSpent = $spentResult['total_spent'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Tracker | Financial Dashboard</title>
    <link rel="stylesheet" href="basic.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <li class="active">
                        <a href="#">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="Analytics.php">
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
                    <button class="sidebar-toggle closed">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>Financial Dashboard</h1>
                        <p>Monthly Budget Overview</p>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    
                    <div class="user-profile">
                        <div class="user-info">
                            <span class="welcome">Welcome back,</span>
                            <span class="username"><?php echo htmlspecialchars($userName); ?></span>
                        </div>
                        <div class="user-avatar">
                            <img src="<?= $avatar ?>" alt="User Avatar">
                        </div>
                    </div>
                </div>
            </header>

            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification show success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification show error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card budget">
                        <div class="card-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-title">Total Budget</span>
                            <span class="card-value" id="total-budget">$<?= number_format($totalBudget, 2) ?></span>
                            <span class="card-change positive">+2.4% from last month</span>
                        </div>
                    </div>
                    
                    <div class="summary-card spent">
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-title">Total Spent</span>
                            <span class="card-value" id="total-spent">$<?= number_format($totalSpent, 2) ?></span>
                            <span class="card-change negative">-1.2% from last month</span>
                        </div>
                    </div>
                    
                    <div class="summary-card remaining">
                        <div class="card-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-content">
                            <span class="card-title">Remaining</span>
                            <span class="card-value" id="remaining">$<?= number_format(max(0, $totalBudget - $totalSpent), 2) ?></span>
                            <span class="card-change positive">+3.8% from last month</span>
                        </div>
                    </div>
                </div>

                <!-- Main Content Sections -->
                <div class="content-sections">
                    <!-- Budget Form Section -->
                    <section class="budget-form-section">
                        <div class="section-header">
                            <h2><i class="fas fa-wallet"></i> Set Total Budget</h2>
                        </div>
                        
                        <form id="set-total-budget-form" method="POST" action="add_budget.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="total-budget-input">Total Monthly Budget ($)</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                        <input type="number" id="total-budget-input" name="total_budget" min="1" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Set Total Budget
                            </button>
                        </form>

                        <div class="section-header" style="margin-top: 30px;">
                            <h2><i class="fas fa-plus-circle"></i> Add Budget Item</h2>
                            <div class="section-actions">
                                <button class="btn-icon">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        
                        <form id="add-budget-item-form" method="POST" action="add_budget_item.php">
                            <input type="hidden" name="budget_id" value="<?php echo $_SESSION['current_budget_id'] ?? ''; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <select id="category" name="category" required onchange="toggleCustomCategory()">
                                        <option value="">Select a category</option>
                                        <option value="Food">Food</option>
                                        <option value="Housing">Housing</option>
                                        <option value="Utilities">Utilities</option>
                                        <option value="Transportation">Transportation</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Healthcare">Healthcare</option>
                                        <option value="Savings">Savings</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <input type="text" id="custom-category" name="custom_category" placeholder="Or enter custom category" style="display: none;">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="amount">Budget Amount ($)</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                        <input type="number" name="amount" id="amount" min="0" step="0.01" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="spent">Amount Spent ($)</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                        <input type="number" name="spent" id="spent" min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Budget Item
                            </button>
                        </form>
                    </section>

                    <!-- Budget List Section -->
                    <section class="budget-list-section">
                        <div class="section-header">
                            <h2><i class="fas fa-list-ul"></i> Budget Items</h2>
                            <div class="section-actions">
                                <div class="search-box">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="search" placeholder="Search categories...">
                                </div>
                                <button id="sort-amount" class="btn btn-secondary">
                                    <i class="fas fa-sort-amount-down"></i> Sort
                                </button>
                            </div>
                        </div>
                        
                        <div class="budget-items-container" id="budget-items-container">
                            <?php
                            if (isset($_SESSION['current_budget_id'])) {
                                $budgetId = $_SESSION['current_budget_id'];
                                $sql = "SELECT * FROM budget_items WHERE budget_id = ? ORDER BY created_at DESC";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$budgetId]);
                                $budgetItems = $stmt->fetchAll();
                                
                                if (count($budgetItems) > 0) {
                                    foreach ($budgetItems as $item) {
                                        $budgetAmount = (float)$item['amount'];
                                        $spentAmount = (float)$item['spent'];
                                        $remaining = max(0, $budgetAmount - $spentAmount);
                                        $percentage = $budgetAmount > 0 ? min(100, ($spentAmount / $budgetAmount) * 100) : 0;
                                        
                                        // Format the date
                                        $createdAt = new DateTime($item['created_at']);
                                        $formattedDate = $createdAt->format('M j, Y');
                                        ?>
                                        <div class="budget-item-card" data-id="<?= $item['id'] ?>">
                                            <div class="budget-item-header">
                                                <h3 class="budget-item-title"><?= htmlspecialchars($item['category']) ?> <span class="budget-item-date"><?= $formattedDate ?></span></h3>
                                            </div>
                                            
                                            <div class="budget-item-amounts">
                                                <span>Budget: $<?= number_format($budgetAmount, 2) ?></span>
                                                <span>Spent: $<?= number_format($spentAmount, 2) ?></span>
                                                <span>Remaining: $<?= number_format($remaining, 2) ?></span>
                                            </div>
                                            
                                            <div class="progress-container">
                                                <div class="progress-labels">
                                                    <span>0%</span>
                                                    <span><?= round($percentage) ?>%</span>
                                                    <span>100%</span>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="budget-item-actions">
                                                <button class="action-btn edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" action="delete_budget_item.php" style="display: inline;">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <button type="submit" class="action-btn delete" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    echo '<div class="empty-state">
                                        <i class="fas fa-coins"></i>
                                        <h3>No Budget Items Added</h3>
                                        <p>Start by adding your first budget item above</p>
                                    </div>';
                                }
                            } else {
                                echo '<div class="empty-state">
                                    <i class="fas fa-coins"></i>
                                    <h3>No Budget Created</h3>
                                    <p>Please create a budget first</p>
                                </div>';
                            }
                            ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script>
    function toggleCustomCategory() {
        const category = document.getElementById('category');
        const custom = document.getElementById('custom-category');
        if (category.value === 'Other') {
            custom.style.display = 'block';
            custom.required = true;
        } else {
            custom.style.display = 'none';
            custom.required = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleCustomCategory();
        document.getElementById('category').addEventListener('change', toggleCustomCategory);
    });
    </script>
    <script src="basic.js"></script>
</body>
</html>