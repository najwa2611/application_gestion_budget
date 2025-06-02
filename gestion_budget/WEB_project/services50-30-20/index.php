<?php
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Suppression d'une dépense si une demande POST est reçue
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expense_id'])) {
        $expenseId = intval($_POST['delete_expense_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE expense_id = ? AND user_id = ?");
            $stmt->execute([$expenseId, $_SESSION['user_id']]);
            $_SESSION['success'] = "Expense deleted successfully.";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting expense: " . $e->getMessage();
        }
    }
    
    // Mise à jour d'une dépense si une demande POST est reçue
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expense'])) {
        $expenseId = intval($_POST['expense_id']);
        $name = htmlspecialchars($_POST['name']);
        $category = htmlspecialchars($_POST['category']);
        $amount = floatval($_POST['amount']);
        
        try {
            $stmt = $pdo->prepare("UPDATE expenses SET name = ?, category = ?, amount = ? WHERE expense_id = ? AND user_id = ?");
            $stmt->execute([$name, $category, $amount, $expenseId, $_SESSION['user_id']]);
            $_SESSION['success'] = "Expense updated successfully!";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating expense: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id'])) {
    header("Location: ../first_page/index.html");
    exit();
}

// Récupération des infos utilisateur depuis la session
$user_name = htmlspecialchars($_SESSION['user_name']);
$userId = $_SESSION['user_id'];
$user_gender = $_SESSION['user_gender'] ?? null;

// Définition de l'avatar par défaut
$avatar = "../assets/images/default.png";

// Détermine l'avatar selon le genre en session
if ($user_gender === 'man') {
    $avatar = "./man.jpg";
} elseif ($user_gender === 'woman') {
    $avatar = "./woman.jpg";
}

// Si le genre n'est pas connu, on va le chercher en base
if (!$user_gender) {
    try {
        $stmt = $pdo->prepare("SELECT gender FROM users WHERE full_name = ?");
        $stmt->execute([$user_name]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['gender'] === 'man') {
                $avatar = "./man.jpg";
            } elseif ($user['gender'] === 'woman') {
                $avatar = "./woman.jpg";
            }
            $_SESSION['user_gender'] = $user['gender'];
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du genre: " . $e->getMessage());
    }
}

// Récupération du revenu de l'utilisateur
$monthlyIncome = 0;
try {
    $stmt = $pdo->prepare("SELECT amount FROM incomes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $incomeData = $stmt->fetch();
    $monthlyIncome = $incomeData['amount'] ?? 0;
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération du revenu: " . $e->getMessage());
}

// Récupération des dépenses
$totalExpenses = 0;
$expenses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$userId]);
    $expenses = $stmt->fetchAll();

    foreach ($expenses as $expense) {
        $totalExpenses += $expense['amount'];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des dépenses: " . $e->getMessage());
}

// Calculs selon la règle 50/30/20
$needsBudget = $monthlyIncome * 0.5;      // 50% pour les besoins
$wantsBudget = $monthlyIncome * 0.3;      // 30% pour les envies
$savingsBudget = $monthlyIncome * 0.2;    // 20% pour l'épargne

$remainingNeedsBudget = $needsBudget - $totalExpenses;
$progressPercentage = $needsBudget > 0 ? ($totalExpenses / $needsBudget) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard <?= $user_name ?></title>
  <link rel="stylesheet" href="./style.css" />
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
</head>
<body>
  <div class="layout">
    <!-- Sidebar Navigation Menu -->
    <aside class="sidebar" id="sidebar">
      <!-- Logo Container with Image -->
      <div class="logo-container">
        <img src="../logo.jpg" alt="Finance Dashboard Logo" class="logo-image">
      </div>
      
      <!-- Close Button (X) -->
      <div class="close-btn" id="close-btn"></div>
      
      <!-- Navigation Menu -->
      <nav>
        <ul>
          <li>
            <i class="fas fa-home"></i>
            <a href="../first_page/index.html" style="color: #AAAAAA; text-decoration: none;">Home</a>
          </li>
          <li>
            <i class="fas fa-cog"></i>
            <a href="../settings/settings.php" style="color: #AAAAAA; text-decoration: none;">Settings</a>
          </li>
          <li>
            <i class="fas fa-user"></i>
            <a href="../services/pageservice.php" style="color: #AAAAAA; text-decoration: none;">Services</a>
          </li>
          <li>
            <i class="fas fa-sign-out-alt"></i>
            <a href="../logout/logout.php" style="color: #AAAAAA; text-decoration: none;">log Out</a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="main-area" id="main-area">
      <!-- Menu Toggle Button -->
      <div class="menu-btn" id="menu-btn">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
      </div>

      <!-- Top Bar -->
      <header class="topbar">
        <h2>Welcome <?= $user_name ?> <span class="wave">👋</span></h2>

        <!-- User Profile -->
        <div class="profile">
          <img src="<?= $avatar ?>" alt="Profile Avatar" />
          <span><?= $user_name ?></span>
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
      
      <!-- Main Content -->
      <main class="main-content">
        <!-- Updated Service Cards Section with Proper Buttons -->
        <section class="services-grid">
          <!-- Income Card -->
          <div class="service-card">
            <div class="card-header">
              <h2><i class="fas fa-coins"></i>Income</h2>
            </div>
            <div class="card-body">
              <form method="POST" action="save_income.php">
                <div class="form-group">
                  <label for="monthly-income">Monthly Net Income</label>
                  <div class="input-with-icon">
                    <input type="number" id="monthly-income" name="monthly-income" 
                           value="<?php echo $monthlyIncome; ?>" step="1" required>
                    <span class="currency">€</span>
                  </div>
                </div>
                
                <!-- Affichage des budgets 50/30/20 -->
                <div class="input-with-icon">
                  <div class="breakdown-item">
                    <span class="breakdown-label">50% Needs Budget:</span>
                    <span class="breakdown-amount"><?php echo number_format($needsBudget, 2); ?>€</span>
                  </div>
                  <div class="breakdown-item">
                    <span class="breakdown-label">30% Wants Budget:</span>
                    <span class="breakdown-amount"><?php echo number_format($wantsBudget, 2); ?>€</span>
                  </div>
                  <div class="breakdown-item">
                    <span class="breakdown-label">20% Savings Budget:</span>
                    <span class="breakdown-amount"><?php echo number_format($savingsBudget, 2); ?>€</span>
                  </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save
                </button>
              </form>
            </div>
          </div>
          
          <!-- Expenses Card -->
          <div class="input-with-icon">
            <div class="card-header">
              <h2><i class="fas fa-plus-circle"></i>My needs</h2>
            </div>
            <div class="card-body">
              <form method="POST" action="save_expense.php">
                <div class="form-group">
                  <label for="expense-name">Expense Name</label>
                  <input type="text" id="expense-name" name="name"  required>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label for="expense-amount">Category</label>
                    <select id="expense-category" name="category" required>
                      <option value="">-- Select --</option>
                      <option value="Housing">Housing</option>
                      <option value="Utilities">Utilities</option>
                      <option value="Food">Food</option>
                      <option value="Transportation">Transportation</option>
                      <option value="Entertainment">Entertainment</option>
                      <option value="Health">Health</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="expense-amount">Amount</label>
                    <div class="input-with-icon">
                      <input type="number" id="expense-amount" name="amount" step="1" required>
                      <span class="currency">€</span>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Type</label>
                  <div class="radio-group">
                    <label class="radio-option">
                      <input type="radio" name="type" value="Fixed" checked>
                      <span class="radio-custom"></span>
                      Fixed
                    </label>
                    <label class="radio-option">
                      <input type="radio" name="type" value="Variable">
                      <span class="radio-custom"></span>
                      Variable
                    </label>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-plus"></i> Add Expense
                </button>
              </form>
            </div>
          </div>
          
          <!-- Monthly Expenses Card -->
          <div class="service-card">
            <div class="card-header">
              <div class="card-title">
                <h2><i class="fas fa-list-ul"></i> Monthly Expenses</h2>
                <span class="badge"><?php echo count($expenses); ?></span>
              </div>
            </div>

            <div class="card-body">
              <!-- Progress bar -->
              <div class="budget-progress">
                <div class="progress-info">
                  <span>Needs Budget: <?php echo number_format($remainingNeedsBudget, 2); ?>€ remaining</span>
                  <span><?php echo number_format($progressPercentage, 0); ?>% used</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo min($progressPercentage, 100); ?>%"></div>
                </div>
                <div class="budget-limits">
                  <small>Limit: <?php echo number_format($needsBudget, 2); ?>€ (50% of income)</small>
                </div>
              </div>

              <!-- Expenses list -->
              <div class="expense-list">
                <?php if ($totalExpenses > $needsBudget): ?>
                  <div class="alert-warning" id="budget-warning">
                    <div class="warning-text">
                      ⚠️ Warning: Your total needs expenses have exceeded 50% of your monthly income.<br>
                      Please reduce your spending to stay within the 50/30/20 rule.
                    </div>
                    
                  </div>
                  
                  <!-- Container for expense edit table (hidden by default) -->
                  <div id="expense-edit-container" class="expense-edit-container" style="display: none;">
                    <form method="POST" action="" id="edit-all-expenses-form">
                      <table class="expense-edit-table">
                        <thead>
                          <tr>
                            <th>Expense Name</th>
                            <th>Category</th>
                            <th>Amount (€)</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($expenses as $expense): ?>
                            <tr>
                              <td>
                                <input type="text" class="expense-input-name" name="name_<?= $expense['expense_id'] ?>" value="<?= htmlspecialchars($expense['name']) ?>" required>
                              </td>
                              <td>
                                <select name="category_<?= $expense['expense_id'] ?>" required>
                                  <option value="Housing" <?= $expense['category'] === 'Housing' ? 'selected' : '' ?>>Housing</option>
                                  <option value="Utilities" <?= $expense['category'] === 'Utilities' ? 'selected' : '' ?>>Utilities</option>
                                  <option value="Food" <?= $expense['category'] === 'Food' ? 'selected' : '' ?>>Food</option>
                                  <option value="Transportation" <?= $expense['category'] === 'Transportation' ? 'selected' : '' ?>>Transportation</option>
                                  <option value="Entertainment" <?= $expense['category'] === 'Entertainment' ? 'selected' : '' ?>>Entertainment</option>
                                  <option value="Health" <?= $expense['category'] === 'Health' ? 'selected' : '' ?>>Health</option>
                                  <option value="Other" <?= $expense['category'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                              </td>
                              <td>
                                <div class="expense-edit-form">
                                  <input type="number" class="expense-input-amount" name="amount_<?= $expense['expense_id'] ?>" value="<?= number_format($expense['amount'], 2, '.', '') ?>" step="0.01" required>
                                  <span class="currency-symbol">€</span>
                                </div>
                              </td>
                              <td class="table-actions">
                                <button type="button" class="delete-btn" data-expense-id="<?= $expense['expense_id'] ?>" title="Delete">
                                  <i class="fas fa-trash-alt"></i>
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                      
                      <div class="action-buttons">
                        <button type="button" class="btn-cancel-all" id="cancel-edit-all-btn">Cancel</button>
                        <button type="submit" class="btn-save-all" id="save-edit-all-btn">Save All Changes</button>
                      </div>
                    </form>
                  </div>
                <?php endif; ?>
                
                <div id="regular-expense-list" <?= $totalExpenses > $needsBudget ? '' : 'style="display: block;"' ?>>
                  <?php if (count($expenses) > 0): ?>
                    <?php foreach ($expenses as $expense): ?>
                      <div class="expense-item" id="expense-<?= $expense['expense_id'] ?>">
                        <div class="expense-details">
                          <span class="expense-name"><?php echo htmlspecialchars($expense['name']); ?></span>
                          <span class="expense-category"><?php echo htmlspecialchars($expense['category']); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                          <span class="amount1"><?php echo number_format($expense['amount'], 2); ?>€</span>
                          <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                            <input type="hidden" name="delete_expense_id" value="<?= $expense['expense_id']; ?>">
                            <button type="submit" class="delete-btn" title="Delete">
                              <i class="fas fa-trash-alt"></i>
                            </button>
                          </form>
                          <button class="edit-btn edit-single-btn" data-expense-id="<?= $expense['expense_id'] ?>" title="Edit">
                            <i class="fas fa-edit"></i>
                          </button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="empty-state">
                      <i class="fas fa-receipt"></i>
                      <p>No expenses recorded</p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="card-footer">
              <div class="total-expenses">
                <span>Total:</span>
                <span class="amount"><?php echo number_format($totalExpenses, 2); ?>€</span>
              </div>
            </div>
          </div>
        </section>
        
        <div class="challenge-start">
          <?php if ($totalExpenses > $needsBudget): ?>
            <div class="alert-warning" style="color: #f39c12; font-weight: bold; margin-bottom: 10px;">
              ⚠️ Your needs exceed 50% of your income. Please adjust your expenses to start the challenge.
            </div>
            <button type="submit" class="btn btn-primary gold-effect">
              <i class="fas fa-gem"></i> Start the 50/30/20 Challenge
            </button>
          <?php else: ?>
            <form action="challenge.php" method="GET">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Start the 50/30/20 Challenge
              </button>
            </form>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');
    const mainArea = document.getElementById('main-area');
    const closeBtn = document.getElementById('close-btn');
    
    // Toggle sidebar when menu button is clicked
    menuBtn.addEventListener('click', function() {
      this.classList.toggle('active');
      sidebar.classList.toggle('visible');
      
      if (window.innerWidth >= 992) {
        mainArea.classList.toggle('shifted');
      }
    });
    
    // Close sidebar when X button is clicked
    closeBtn.addEventListener('click', function() {
      sidebar.classList.remove('visible');
      menuBtn.classList.remove('active');
      
      if (window.innerWidth >= 992) {
        mainArea.classList.remove('shifted');
      }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
      if (window.innerWidth < 992 && 
          !sidebar.contains(event.target) && 
          !menuBtn.contains(event.target) && 
          sidebar.classList.contains('visible')) {
        sidebar.classList.remove('visible');
        menuBtn.classList.remove('active');
      }
    });
  // Add animation to service cards on hover
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px)';
      });
      card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
    });

    // Handle the edit expenses button
    const editExpensesBtn = document.getElementById('edit-expenses-btn');
    const expenseEditContainer = document.getElementById('expense-edit-container');
    const regularExpenseList = document.getElementById('regular-expense-list');
    const cancelEditAllBtn = document.getElementById('cancel-edit-all-btn');

    // Show edit table when edit button is clicked
    if (editExpensesBtn) {
      editExpensesBtn.addEventListener('click', function() {
        expenseEditContainer.style.display = 'block';
        regularExpenseList.style.display = 'none';
      });
    }

    // Hide edit table when cancel button is clicked
    if (cancelEditAllBtn) {
      cancelEditAllBtn.addEventListener('click', function() {
       expenseEditContainer.style.display = 'none';
        regularExpenseList.style.display = 'block';
      });
    }

    // Handle Save All Changes button
    const editAllExpensesForm = document.getElementById('edit-all-expenses-form');
    if (editAllExpensesForm) {
      editAllExpensesForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Collect all expense data
        const formData = new FormData(this);
        formData.append('update_expense', 'true');
        
        // Submit form data via AJAX
        fetch('update_all_expenses.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Show success message and reload page
            alert(data.message);
            window.location.reload();
          } else {
            // Show error message
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred. Please try again.');
        });
      });
    }

    // Handle individual edit buttons
    const editSingleBtns = document.querySelectorAll('.edit-single-btn');
    editSingleBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        const expenseId = this.getAttribute('data-expense-id');
        const expenseItem = document.getElementById('expense-' + expenseId);
        
        // Get current expense data
        const name = expenseItem.querySelector('.expense-name').textContent;
        const category = expenseItem.querySelector('.expense-category').textContent;
        const amount = expenseItem.querySelector('.amount1').textContent.replace('€', '').trim();
        
        // Create edit form
        const form = document.createElement('form');
        form.classList.add('edit-form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="expense_id" value="${expenseId}">
          <input type="hidden" name="update_expense" value="true">
          <div class="edit-form-row">
            <input type="text" name="name" value="${name}" required>
            <select name="category" required>
              <option value="Housing" ${category === 'Housing' ? 'selected' : ''}>Housing</option>
              <option value="Utilities" ${category === 'Utilities' ? 'selected' : ''}>Utilities</option>
              <option value="Food" ${category === 'Food' ? 'selected' : ''}>Food</option>
              <option value="Transportation" ${category === 'Transportation' ? 'selected' : ''}>Transportation</option>
              <option value="Entertainment" ${category === 'Entertainment' ? 'selected' : ''}>Entertainment</option>
              <option value="Health" ${category === 'Health' ? 'selected' : ''}>Health</option>
              <option value="Other" ${category === 'Other' ? 'selected' : ''}>Other</option>
            </select>
            <input type="number" name="amount" value="${amount}" step="0.01" required>
          </div>
          <div class="edit-form-buttons">
            <button type="button" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn">Save</button>
          </div>
        `;
        
        // Replace expense item with form
        expenseItem.style.display = 'none';
        expenseItem.parentNode.insertBefore(form, expenseItem.nextSibling);
        
        // Handle cancel button
        form.querySelector('.cancel-btn').addEventListener('click', function() {
          form.remove();
          expenseItem.style.display = 'flex';
        });
      });
    });

    // Handle delete buttons in edit table
    const deleteButtons = document.querySelectorAll('.expense-edit-table .delete-btn');
    if (deleteButtons) {
      deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          if (confirm('Are you sure you want to delete this expense?')) {
            const expenseId = this.getAttribute('data-expense-id');
            
            // Create and submit form to delete expense
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_expense_id';
            input.value = expenseId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
          }
        });
      });
    }

    // Auto-hide notifications after 5 seconds
    const notifications = document.querySelectorAll('.notification.show');
    notifications.forEach(notification => {
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 5000);
    });
  });
  </script>
</body>
</html>