<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../first_page/index.html");
    exit();
}

// Initialize variables
$errors = [];
$success = false;
$userData = [];

try {
    // Establish PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Profile Information Update
        if (isset($_POST['update_profile'])) {
            $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }

            // Check if email exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = "Email already in use by another account";
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, gender = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $gender, $_SESSION['user_id']]);

                // Update session variables
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_gender'] = $gender;

                $success = "Profile updated successfully!";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        // Password Change
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verify current password
            if (!password_verify($current_password, $userData['password'])) {
                $errors[] = "Current password is incorrect";
            }

            // Validate new password
            if ($new_password !== $confirm_password) {
                $errors[] = "New passwords don't match";
            }

            if (strlen($new_password) < 8) {
                $errors[] = "Password must be at least 8 characters long";
            }

            if (empty($errors)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);

                $success = "Password changed successfully!";
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Set avatar based on gender
$avatarPath = "../assets/images/default.png"; // Default avatar
if (isset($userData['gender'])) {
    if ($userData['gender'] === 'man') {
        $avatarPath = "../services/man.jpg";
    } elseif ($userData['gender'] === 'woman') {
        $avatarPath = "../services/woman.jpg";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Settings | Golden Finance</title>
  <link rel="stylesheet" href="./settings.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
  <style>
    /* Additional styles for avatar display */
    .avatar-preview img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #d4af37;
    }
    .avatar-note {
      display: block;
      font-size: 0.8rem;
      color: #777;
      margin-top: 5px;
    }
    .profile img, .profile-dropdown img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
    }
    
    /* Sidebar and overlay styles */
    .layout {
      position: relative;
      display: flex;
      width: 100%;
      min-height: 100vh;
    }
    
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 280px;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.1);
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 100;
      transition: transform 0.3s ease, left 0.3s ease;
      overflow-y: auto;
      transform: translateX(-100%);
    }
    
    .sidebar.visible {
      transform: translateX(0);
    }
    
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 99;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    
    .sidebar-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    
    .main-area {
      flex: 1;
      margin-left: 0;
      transition: margin-left 0.3s ease;
      width: 100%;
    }
    
    .menu-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      width: 30px;
      height: 24px;
      cursor: pointer;
      z-index: 98;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.3s ease;
    }
    
    .menu-btn .bar {
      width: 100%;
      height: 3px;
      background: #D4AF37;
      transition: all 0.4s cubic-bezier(0.68, -0.6, 0.32, 1.6);
      border-radius: 3px;
      box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
    }
    
    .menu-btn:hover .bar {
      box-shadow: 0 0 8px rgba(212, 175, 55, 0.6);
    }
    
    .menu-btn.active .bar:nth-child(1) {
      transform: translateY(10px) rotate(45deg);
    }
    
    .menu-btn.active .bar:nth-child(2) {
      opacity: 0;
      transform: scale(0);
    }
    
    .menu-btn.active .bar:nth-child(3) {
      transform: translateY(-10px) rotate(-45deg);
    }
    
    .close-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      width: 30px;
      height: 30px;
      cursor: pointer;
      z-index: 101;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    
    .close-btn:before, .close-btn:after {
      content: '';
      position: absolute;
      width: 20px;
      height: 2px;
      background-color: #333;
    }
    
    .close-btn:before {
      transform: rotate(45deg);
    }
    
    .close-btn:after {
      transform: rotate(-45deg);
    }
    
    @media (min-width: 993px) {
      .layout.sidebar-open .sidebar {
        transform: translateX(0);
      }
      
      .layout.sidebar-open .main-area {
        margin-left: 280px;
      }
    }
    
    @media (max-width: 992px) {
      .sidebar {
        width: 280px;
      }
      
      .main-area {
        margin-left: 0;
      }
    }
    
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem 2rem;
      background: rgba(26, 26, 26, 0.75);
      border-bottom: 1px solid rgba(212, 175, 55, 0.25);
      position: sticky;
      top: 0;
      z-index: 90;
      backdrop-filter: blur(10px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
      transition: padding 0.3s ease;
    }
    
    .topbar:hover {
      border-bottom-color: rgba(212, 175, 55, 0.4);
    }
    
    .topbar-left h2 {
      font-size: 1.5rem;
      color: #ffffff;
      display: flex;
      align-items: center;
      position: relative;
      transition: all 0.3s ease;
    }
    
    .topbar-left h2:hover {
      color: #FFD700;
      text-shadow: 0 0 10px rgba(212, 175, 55, 0.4);
      transform: scale(1.03);
    }
    
    .gold-spin {
      margin-left: 0.5rem;
      animation: spin 6s linear infinite;
      color: #D4AF37;
      text-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
      transition: all 0.3s ease;
    }
    
    .topbar-left h2:hover .gold-spin {
      color: #FFD700;
      text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
      animation: spin 3s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .topbar-right {
      display: flex;
      align-items: center;
    }
    
    .notifications {
      position: relative;
      margin-right: 1.5rem;
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    
    .notifications:hover {
      transform: scale(1.1);
    }
    
    .notifications i {
      font-size: 1.3rem;
      color: #bbbbbb;
      transition: color 0.3s ease;
    }
    
    .notifications:hover i {
      color: #FFD700;
      text-shadow: 0 0 5px rgba(212, 175, 55, 0.5);
    }
    
    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #ff6b6b;
      color: #000000;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 0.7rem;
      font-weight: bold;
      box-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.5); }
      50% { transform: scale(1.1); box-shadow: 0 0 0 5px rgba(255, 107, 107, 0); }
      100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
    }
    
    .profile-dropdown {
      position: relative;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .profile-dropdown:hover {
      transform: translateY(-2px);
    }
    
    .profile {
      display: flex;
      align-items: center;
      padding: 0.5rem;
      border-radius: 30px;
      border: 1px solid rgba(212, 175, 55, 0.1);
      transition: all 0.3s ease;
      background: rgba(30, 30, 30, 0.4);
    }
    
    .profile:hover {
      background: rgba(212, 175, 55, 0.1);
      border-color: rgba(212, 175, 55, 0.3);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .profile img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 0.5rem;
      border: 1px solid #D4AF37;
      transition: all 0.3s ease;
      box-shadow: 0 0 5px rgba(212, 175, 55, 0.3);
    }
    
    .profile:hover img {
      border-width: 2px;
      border-color: #FFD700;
      box-shadow: 0 0 10px rgba(212, 175, 55, 0.6);
      transform: scale(1.05);
    }
    
    .profile span {
      color: #ffffff;
      font-size: 0.95rem;
      transition: color 0.3s ease;
    }
    
    .profile:hover span {
      color: #FFD700;
    }
    
    .profile i {
      margin-left: 0.5rem;
      color: #bbbbbb;
      font-size: 0.8rem;
      transition: all 0.3s ease;
    }
    
    .profile:hover i {
      color: #FFD700;
      transform: rotate(180deg);
    }
    
    .dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      background: rgba(30, 30, 30, 0.9);
      border: 1px solid rgba(212, 175, 55, 0.3);
      border-radius: 12px;
      width: 200px;
      padding: 0.5rem 0;
      margin-top: 0.8rem;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(10px);
      z-index: 95;
    }
    
    .dropdown-menu::before {
      content: '';
      position: absolute;
      top: -6px;
      right: 20px;
      width: 12px;
      height: 12px;
      background: rgba(30, 30, 30, 0.9);
      border-left: 1px solid rgba(212, 175, 55, 0.3);
      border-top: 1px solid rgba(212, 175, 55, 0.3);
      transform: rotate(45deg);
    }
    
    .dropdown-menu.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      animation: fadeInDropdown 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    }
    
    @keyframes fadeInDropdown {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .dropdown-menu a {
      display: block;
      padding: 0.7rem 1.5rem;
      color: #bbbbbb;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .dropdown-menu a i {
      margin-right: 0.8rem;
      font-size: 0.9rem;
      transition: transform 0.3s ease;
    }
  </style>
</head>
<body>
  <div class="layout" id="layout">
    <!-- Sidebar Overlay (for mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo-container">
        <img src="../logo.jpg" alt="Golden Finance Logo" class="logo-image">
      </div>
      
      <div class="close-btn" id="close-btn"></div>
      
      <div class="sidebar-profile">
        <img src="<?= $avatarPath ?>" alt="<?= htmlspecialchars($userData['full_name'] ?? 'User') ?>">
        <div class="profile-info">
          <h4><?= htmlspecialchars($userData['full_name'] ?? 'User') ?></h4>
          <p><?= htmlspecialchars($userData['email'] ?? '') ?></p>
          <small>Member since <?= date('M Y', strtotime($userData['created_at'] ?? 'now')) ?></small>
        </div>
      </div>
      
      <nav>
        <ul>
          <li>
            <a href="../first_page/index.html">
              <i class="fas fa-home"></i>
              <span>Home</span>
            </a>
          </li>
          <li>
            <a href="../services/pageservice.php">
              <i class="fas fa-chart-pie"></i>
              <span>Services</span>
            </a>
          </li>
          <li class="active">
            <a href="settings.php">
              <i class="fas fa-cog"></i>
              <span>Settings</span>
            </a>
          </li>
        </ul>
      </nav>
      
      <div class="sidebar-footer">
        <a href="../logout/logout.php" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i>
          Logout
        </a>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-area" id="main-area">
      <div class="menu-btn" id="menu-btn">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
      </div>

      <header class="topbar">
        <div class="topbar-left">
          <h2>Account Settings <i class="fas fa-cog gold-spin"></i></h2>
        </div>
        
        <div class="topbar-right">
          <div class="profile-dropdown">
            <div class="profile">
              <img src="<?= $avatarPath ?>" alt="<?= htmlspecialchars($userData['full_name'] ?? 'User') ?>">
              <span><?= explode(' ', $userData['full_name'] ?? 'User')[0] ?></span>
              <i class="fas fa-chevron-down"></i>
            </div>
          </div>
        </div>
      </header>

      <main class="main-content settings-page">
        <!-- Display success/error messages -->
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success">
            <p><?= htmlspecialchars($success) ?></p>
          </div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <div class="settings-tabs">
          <button class="tab-btn active" data-tab="account">Account</button>
          <button class="tab-btn" data-tab="security">Security</button>
        </div>
        
        <!-- Account Settings -->
        <div class="settings-content active" id="account">
          <div class="settings-card">
            <h3 class="settings-title">
              <i class="fas fa-user-circle"></i> Profile Information
            </h3>
            
            <form class="settings-form" method="POST">
              <div class="form-group profile-picture">
                <label>Profile Picture</label>
                <div class="avatar-upload">
                  <div class="avatar-preview">
                    <img src="<?= $avatarPath ?>" id="avatar-preview">
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" required>
              </div>
              
              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
                <?php if (!empty($userData['email']) && $userData['is_verified']): ?>
                  <span class="verified-badge">
                    <i class="fas fa-check-circle"></i> Verified
                  </span>
                <?php elseif (!empty($userData['email'])): ?>
                  <span class="unverified-badge">
                    <i class="fas fa-exclamation-circle"></i> Unverified
                  </span>
                <?php endif; ?>
              </div>
              
              <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                  <option value="man" <?= ($userData['gender'] ?? '') === 'man' ? 'selected' : '' ?>>Male</option>
                  <option value="woman" <?= ($userData['gender'] ?? '') === 'woman' ? 'selected' : '' ?>>Female</option>
                </select>
              </div>
              
              <div class="form-actions">
                <button type="submit" name="update_profile" class="save-btn">
                  <i class="fas fa-save"></i> Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Security Settings -->
        <div class="settings-content" id="security">
          <div class="settings-card">
            <h3 class="settings-title">
              <i class="fas fa-lock"></i> Password & Security
            </h3>
            
            <form class="settings-form" method="POST">
              <div class="form-group">
                <label for="current-password">Current Password</label>
                <div class="password-input">
                  <input type="password" id="current-password" name="current_password" required>
                  <button type="button" class="toggle-password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              
              <div class="form-group">
                <label for="new-password">New Password</label>
                <div class="password-input">
                  <input type="password" id="new-password" name="new_password" required>
                  <button type="button" class="toggle-password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <div class="password-strength">
                  <div class="strength-meter">
                    <span class="strength-bar weak"></span>
                    <span class="strength-bar"></span>
                    <span class="strength-bar"></span>
                  </div>
                  <span class="strength-text">Weak</span>
                </div>
              </div>
              
              <div class="form-group">
                <label for="confirm-password">Confirm New Password</label>
                <div class="password-input">
                  <input type="password" id="confirm-password" name="confirm_password" required>
                  <button type="button" class="toggle-password">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="reset" class="cancel-btn">Cancel</button>
                <button type="submit" name="change_password" class="save-btn">
                  <i class="fas fa-key"></i> Update Password
                </button>
              </div>
            </form>
          </div>
          
          <div class="settings-card">
            <h3 class="settings-title">
              <i class="fas fa-shield-alt"></i> Account Security
            </h3>
            
            <div class="security-item">
              <div>
                <h5>Email Verification</h5>
                <p>Verify your email address for account security</p>
                <small>Status: <?= $userData['is_verified'] ? 'Verified' : 'Not Verified' ?></small>
              </div>
              <?php if (!$userData['is_verified']): ?>
                <button class="gold-btn" id="verify-email-btn">
                  <i class="fas fa-envelope"></i> Verify Email
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
      // DOM Elements
      const menuBtn = document.getElementById('menu-btn');
      const sidebar = document.getElementById('sidebar');
      const closeBtn = document.getElementById('close-btn');
      const sidebarOverlay = document.getElementById('sidebar-overlay');
      const mainArea = document.getElementById('main-area');
      const layout = document.getElementById('layout');

      // Check window width to set initial state
      function checkWidth() {
          if (window.innerWidth >= 993) {
              // For larger screens, set the sidebar to visible initially if preferred
              // Uncomment below to have sidebar open by default on desktop
              // toggleSidebar();
          }
      }

      // Run on page load
      checkWidth();

      // Toggle Sidebar Function
      function toggleSidebar() {
          sidebar.classList.toggle('visible');
          menuBtn.classList.toggle('active');
          sidebarOverlay.classList.toggle('active');
          layout.classList.toggle('sidebar-open');
          
          // Toggle body overflow
          document.body.style.overflow = sidebar.classList.contains('visible') ? 'hidden' : '';
      }

      // Close Sidebar Function
      function closeSidebar() {
          sidebar.classList.remove('visible');
          menuBtn.classList.remove('active');
          sidebarOverlay.classList.remove('active');
          layout.classList.remove('sidebar-open');
          document.body.style.overflow = '';
      }

      // Event Listeners
      menuBtn.addEventListener('click', toggleSidebar);
      closeBtn.addEventListener('click', closeSidebar);
      sidebarOverlay.addEventListener('click', closeSidebar);

      // Close sidebar when pressing Escape key
      document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && sidebar.classList.contains('visible')) {
              closeSidebar();
          }
      });

      // Handle window resize
      window.addEventListener('resize', function() {
          if (window.innerWidth < 993 && sidebar.classList.contains('visible')) {
              // On small screens, ensure overlay is visible when sidebar is open
              sidebarOverlay.classList.add('active');
          }
      });

      // Tab switching functionality
      const tabBtns = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.settings-content');
      
      tabBtns.forEach(btn => {
          btn.addEventListener('click', function() {
              tabBtns.forEach(b => b.classList.remove('active'));
              tabContents.forEach(c => c.classList.remove('active'));
              
              this.classList.add('active');
              const tabId = this.getAttribute('data-tab');
              document.getElementById(tabId).classList.add('active');
          });
      });

      // Toggle password visibility
      const togglePasswords = document.querySelectorAll('.toggle-password');
      togglePasswords.forEach(toggle => {
          toggle.addEventListener('click', function() {
              const input = this.previousElementSibling;
              const icon = this.querySelector('i');
              
              if (input.type === 'password') {
                  input.type = 'text';
                  icon.classList.replace('fa-eye', 'fa-eye-slash');
              } else {
                  input.type = 'password';
                  icon.classList.replace('fa-eye-slash', 'fa-eye');
              }
          });
      });
      // Password strength indicator
      const passwordInput = document.getElementById('new-password');
      if (passwordInput) {
          passwordInput.addEventListener('input', function() {
              const strengthMeter = this.closest('.form-group').querySelector('.strength-meter');
              const strengthText = this.closest('.form-group').querySelector('.strength-text');
              const password = this.value;
              let strength = 0;
              
              if (password.length > 0) strength++;
              if (password.length >= 8) strength++;
              if (/[A-Z]/.test(password)) strength++;
              if (/[0-9]/.test(password)) strength++;
              if (/[^A-Za-z0-9]/.test(password)) strength++;
              
              const bars = strengthMeter.querySelectorAll('.strength-bar');
              bars.forEach((bar, index) => {
                  bar.className = 'strength-bar';
                  if (index < strength) {
                      bar.classList.add(index < 2 ? 'weak' : index < 4 ? 'medium' : 'strong');
                  }
              });
              
              const strengthLabels = ['Weak', 'Weak', 'Medium', 'Medium', 'Strong', 'Very Strong'];
              strengthText.textContent = strengthLabels[strength];
              strengthText.className = 'strength-text ' + 
                  (strength < 2 ? 'weak' : strength < 4 ? 'medium' : 'strong');
          });
      }

      // Email verification button
      const verifyEmailBtn = document.getElementById('verify-email-btn');
      if (verifyEmailBtn) {
          verifyEmailBtn.addEventListener('click', function() {
              fetch('../verify_email.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                  },
                  body: JSON.stringify({
                      user_id: <?= $_SESSION['user_id'] ?>
                  })
              })
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      alert('Verification email sent! Please check your inbox.');
                  } else {
                      alert('Error: ' + data.message);
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  alert('An error occurred while sending verification email');
              });
          });
      }

      // Profile dropdown toggle
      const profileDropdown = document.querySelector('.profile-dropdown');
      if (profileDropdown) {
          profileDropdown.addEventListener('click', function(e) {
              e.stopPropagation();
              this.querySelector('.dropdown-menu').classList.toggle('show');
          });
      }
      
      // Close dropdown when clicking elsewhere
      document.addEventListener('click', function(e) {
          if (!e.target.closest('.profile-dropdown')) {
              document.querySelectorAll('.dropdown-menu').forEach(menu => {
                  menu.classList.remove('show');
              });
          }
      });
  });
  </script>
</body>
</html>