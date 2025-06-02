<?php
session_start();

// Paramètres de connexion à la base
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id'])) {
    header("Location: ../first_page/index.html");
    exit();
}

// Récupération des infos utilisateur
$user_name = htmlspecialchars($_SESSION['user_name']);
$userId = $_SESSION['user_id'];
$user_gender = $_SESSION['user_gender'] ?? null;

// Connexion à la base
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}



// Attribution selon genre
if ($user_gender === 'man') {
    $avatar = "./man.jpg";
} elseif ($user_gender === 'woman') {
    $avatar = "./woman.jpg";
}

// Récupération du genre si absent de la session
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
        error_log("Erreur récupération genre : " . $e->getMessage());
    }
}

// Revenu mensuel
$monthlyIncome = 0;
try {
    $stmt = $pdo->prepare("SELECT amount FROM incomes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $incomeData = $stmt->fetch();
    $monthlyIncome = $incomeData['amount'] ?? 0;
} catch (PDOException $e) {
    error_log("Erreur récupération revenu : " . $e->getMessage());
}

// Récupération des dépenses quotidiennes (30 derniers jours)
$expenses = [];
try {
    $sql = "SELECT category, SUM(amount) AS total 
            FROM daily_expenses 
            WHERE user_id = :user_id 
              AND recorded_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()
            GROUP BY category";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération dépenses : " . $e->getMessage());
}

// Initialisation
$needs = 0;
$wants = 0;
$savings = 0;

// Répartition par catégorie
foreach ($expenses as $expense) {
    if ($expense['category'] == 'needs') {
        $needs = $expense['total'];
    } elseif ($expense['category'] == 'wants') {
        $wants = $expense['total'];
    } elseif ($expense['category'] == 'savings') {
        $savings = $expense['total'];
    }
}

// Limites selon la règle 50/30/20
$needs_limit = $monthlyIncome * 0.50;
$wants_limit = $monthlyIncome * 0.30;
$savings_limit = $monthlyIncome * 0.20;

// Calculs de progression (évite division par zéro)
$needs_progress = $needs_limit > 0 ? ($needs / $needs_limit) * 100 : 0;
$wants_progress = $wants_limit > 0 ? ($wants / $wants_limit) * 100 : 0;
$savings_progress = $savings_limit > 0 ? ($savings / $savings_limit) * 100 : 0;

// Message d'alerte si dépassement
if ($needs > $needs_limit || $wants > $wants_limit || $savings > $savings_limit) {
    echo '<div class="alert alert-danger">
            <strong>Challenge échoué !</strong> Vous avez dépassé votre limite mensuelle dans au moins une catégorie.
          </div>';
}
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
            <span>Home</span>
          </li>
          <li>
            <i class="fas fa-cog"></i>
            <a href="../settings/settings.php" style="color: #AAAAAA; text-decoration: none;">Settings</a>
          </li>
          <li>
            <i class="fas fa-user"></i>
            <span>Accounts</span>
          </li>
          <li>
            <i class="fas fa-sign-out-alt"></i>
            <span>Log Out</span>
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
        <span class="banner-content">
      <h1 class="gold-title">
        <i class="fas fa-trophy"></i> 50/30/20 Challenge
      </h1> </span>
        <!-- User Profile -->
        <div class="profile">
          <img src="<?= $avatar ?>" alt="Profile Avatar" />
          <span><?= $user_name ?></span>
        </div>

      </header>
       <!-- Main Content -->
       <main class="main-content">
       <section class="budget-distribution">
  <h2><i class="fas fa-chart-pie"></i> Budget Breakdown</h2>
  <div class="budget-categories">
    <div class="budget-category category-needs">
      <h3><i class="fas fa-home"></i> Needs (50%)</h3>
      <div class="budget-amount"><?php echo number_format($needs_limit, 2); ?>€</div>
    </div>
    <div class="budget-category category-wants">
      <h3><i class="fas fa-smile"></i> Wants (30%)</h3>
      <div class="budget-amount"><?php echo number_format($wants_limit, 2); ?>€</div>
    </div>
    <div class="budget-category category-savings">
      <h3><i class="fas fa-piggy-bank"></i> Savings (20%)</h3>
      <div class="budget-amount"><?php echo number_format($savings_limit, 2); ?>€</div>
    </div>
  </div>
</section>


      <section class="services-grid">
      <section class="budget-section">
  <!-- Part 1: Enter your expenses -->
  <div class="expense-input-section">
    <h2><i class="fas fa-edit"></i> Record Your Expenses</h2>
    <form id="daily-expense-form" method="POST" action="save_daily.php">
      <label for="expense-amount">Amount (€):</label>
      <input type="number" name="amount" id="expense-amount" required>

      <label for="expense-category">Category:</label>
      <select name="category" id="expense-category" required>
        <option value="needs">Needs</option>
        <option value="wants">Wants</option>
        <option value="savings">Savings</option>
      </select>

      <label for="expense-date">Date:</label>
      <input type="date" name="date" id="expense-date" required>

      <button type="submit" class="save-btn">
        <i class="fas fa-save"></i> Save
      </button>
    </form>
  </div>

  <!-- Part 2: Progress -->
  <div class="progress-section">
    <h2><i class="fas fa-chart-line"></i> Your Progress</h2>
    <div class="progress-categories">
      <div class="progress-category">
      <h3>
      Needs (50%)
  </h3>
  
  <div class="progress-bar">
    <div class="progress-fill" style="width: <?php echo $needs_progress; ?>%;"></div>
  </div>
  <span class="progress-text <?php echo ($needs > $needs_limit) ? 'over-budget' : ''; ?>">
  <?php echo number_format($needs, 2); ?>€ / <?php echo number_format($needs_limit, 2); ?>€
</span>

      <div class="progress-category">
        <h3>Wants (30%)</h3>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?php echo $wants_progress; ?>%;"></div>
        </div>
        <span class="progress-text <?php echo ($wants > $wants_limit) ? 'over-budget' : ''; ?>">
  <?php echo number_format($wants, 2); ?>€ / <?php echo number_format($wants_limit, 2); ?>€
</span>
      </div>

      <div class="progress-category">
        <h3>Savings (20%)</h3>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?php echo $savings_progress; ?>%;"></div>
        </div>
        <span class="progress-text <?php echo ($savings > $savings_limit) ? 'over-budget' : ''; ?>">
  <?php echo number_format($savings, 2); ?>€ / <?php echo number_format($savings_limit, 2); ?>€
</span>

      </div>
    </div>
  </div>
</section>

      </section>

      </main>
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
  });


</script>
</body>
</html>