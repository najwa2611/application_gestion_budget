<?php
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

// Check if user is logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: ../first_page/index.html");
    exit();
}

// Get user information from session
$user_name = htmlspecialchars($_SESSION['user_name']);
$user_gender = isset($_SESSION['user_gender']) ? $_SESSION['user_gender'] : null;

// Default avatar path
$avatar = "../assets/images/default.png";

// Set avatar based on gender from session
if ($user_gender === 'man') {
    $avatar = "./man.jpg";
} elseif ($user_gender === 'woman') {
    $avatar = "./woman.jpg";
}

// If gender is not in session, try to get it from database
if (!$user_gender) {
    try {
        // Establish PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch gender from the database based on the full_name
        $stmt = $pdo->prepare("SELECT gender FROM users WHERE full_name = ?");
        $stmt->execute([$user_name]);
        $user = $stmt->fetch();

        // Set avatar path based on gender
        if ($user) {
            if ($user['gender'] === 'man') {
                $avatar = "./man.jpg";
            } elseif ($user['gender'] === 'woman') {
                $avatar = ".woman.jpg";
            }
            
            // Store gender in session for future use
            $_SESSION['user_gender'] = $user['gender'];
        }

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard <?= $user_name ?></title>
  <link rel="stylesheet" href="./pageservice.css" />
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
            <i class="fas fa-sign-out-alt"></i>
            <a href="../logout/logout.php" style="color: #AAAAAA; text-decoration: none;">Log Out</a>
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

      <!-- Main Content -->
      <main class="main-content">
        <!-- Updated Service Cards Section with Proper Buttons -->
        <section class="services-grid">
          <!-- Basic Service Card -->
          <div class="service-card">
            <div class="service-icon">
              <i class="fas fa-wallet"></i>
            </div>
            <div class="title">Basic Service</div>
            <p class="description">Simple tracking of your daily expenses.</p>
            <ul class="service-features">
              <li>Expense categorization</li>
              <li>Monthly reports</li>
              <li>Basic analytics</li>
            </ul>
            <a href="../basic/basic.php" class="service-button">Try Now</a>
          </div>
          
          <!-- 50/30/20 Service Card -->
          <a href="../services50-30-20/index.php" class="service-card" style="text-decoration: none; color: inherit;">
            <div class="service-icon">
              <i class="fas fa-percentage"></i>
            </div>
            <div class="title">50/30/20 Service</div>
            <p class="description">Budgeting method based on financial priorities.</p>
            <ul class="service-features">
              <li>Needs/Wants/Savings</li>
              <li>Automatic allocation</li>
              <li>Progress tracking</li>
            </ul>
            <div class="service-button">Try Now</div>
          </a>
          
          <!-- AI Assistant Card -->
          <div class="service-card">
            <div class="service-icon">
              <i class="fas fa-robot"></i>
            </div>
            <div class="title">AI Assistant</div>
            <p class="description">Personal finance management companion.</p>
            <ul class="service-features">
              <li>Smart recommendations</li>
              <li>24/7 support</li>
              <li>Predictive analysis</li>
            </ul>
            <a href="../assistant_ai/assistant.php" class="service-button">Try Now</a>
          </div>
        </section>
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
  });
  </script>
</body>
</html>