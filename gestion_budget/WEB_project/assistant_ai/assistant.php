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
$initials = substr($user_name, 0, 2); // Get initials for avatar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetPro | AI Assistant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assistant.css">
</head>
<body>
    <div class="app-container">
     <!-- Modifier cette partie dans assistant.php -->
<aside class="sidebar" id="sidebar"> <!-- Ajouter l'ID sidebar -->
    <div class="sidebar-brand">
        <!-- Logo Container with Image -->
        <div class="logo-c">
            <img src="../logo.jpg" alt="Finance Dashboard Logo" class="logo-image">
        </div>
    </div>
    
    <!-- Close Button (X) -->
    
    
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
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<!-- Menu Toggle Button -->
<div class="menu-btn" id="menu-btn">
    <div class="bar"></div>
    <div class="bar"></div>
    <div class="bar"></div>
</div>
      
    

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                   
                     <h2>Welcome <?= $user_name ?> <span class="wave">👋</span></h2>
                </div>
                
                <div class="header-right">
                     <!-- User Profile -->
                    <div class="profile">
                  <img src="<?= $avatar ?>" alt="Profile Avatar" />
                 <span><?= $user_name ?></span>
        </div>
                </div>
            </header>

            <!-- Chat Interface -->
            <div class="chat-interface">
                <div class="message-container" id="messageContainer">
                    <!-- Welcome message will be inserted here by JavaScript -->
                </div>
                
                <div class="input-area">
                    <div class="input-container">
                        <textarea id="userInput" placeholder="Ask your financial question..." rows="1"></textarea>
                        <button class="send-btn" id="sendButton">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <p class="disclaimer">
                        <i class="fas fa-info-circle"></i> Advice is for informational purposes only.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
  
    document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');
    const closeBtn = document.getElementById('close-btn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Fonction pour ouvrir la sidebar
    function openSidebar() {
        sidebar.classList.add('visible');
        sidebarOverlay.classList.add('active');
        menuBtn.classList.add('active');
        document.body.style.overflow = 'hidden'; // Empêche le défilement de la page
    }
    
    // Fonction pour fermer la sidebar
    function closeSidebar() {
        sidebar.classList.remove('visible');
        sidebarOverlay.classList.remove('active');
        menuBtn.classList.remove('active');
        document.body.style.overflow = ''; // Rétablit le défilement
    }
    
    // Ouvrir la sidebar
    menuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('visible')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    // Fermer la sidebar
    closeBtn.addEventListener('click', closeSidebar);
    sidebarOverlay.addEventListener('click', closeSidebar);
    
    // Empêcher la propagation du clic dans la sidebar
    sidebar.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Fermer la sidebar avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('visible')) {
            closeSidebar();
        }
    });
});
        // Pass the PHP username to JavaScript
        const userName = "<?= addslashes($user_name) ?>";
    </script>
    <script src="assistant.js"></script>
</body>
</html>