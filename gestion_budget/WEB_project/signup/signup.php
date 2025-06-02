<?php
/* =============================================================================
 * DATABASE CONFIGURATION
 * ========================================================================== */
$host = 'localhost';
$dbname = 'projet_web';
$username = 'root';
$password = '';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        gender VARCHAR(10) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS otps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email)
    )");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

/* =============================================================================
 * PHPMailer Configuration
 * ========================================================================== */
require __DIR__ . '../../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =============================================================================
 * SIGNUP PROCESS WITH FULL NAME
 * ========================================================================== */
$signupError = $verificationError = '';
$showOtpForm = $emailSent = $verificationSuccess = false;

if (isset($_POST['signup'])) {
    // Sanitize and validate input
    $full_name = trim(htmlspecialchars($_POST['full_name']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $gender = isset($_POST['gender']) ? $_POST['gender'] : null;
    
    // Debug: Log received form data
    error_log("Signup attempt - Full Name: $full_name, Email: $email");
    
    // Validate inputs
    if (empty($full_name)) {
        $signupError = "Full name is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signupError = "Invalid email format";
    } elseif (strlen($password) < 8) {
        $signupError = "Password must be at least 8 characters";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $signupError = "Password must contain at least one number";
    } else {
        // Check if email already exists and is verified
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && $user['is_verified']) {
                $signupError = "Email already registered";
                error_log("Email already registered: $email");
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                if ($hashedPassword === false) {
                    throw new Exception("Password hashing failed");
                }
                
                // Insert or update user
                if ($user) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ?, gender = ? WHERE email = ?");
                    $stmt->execute([$full_name, $hashedPassword, $gender, $email]);
                    error_log("Updated existing unverified user: $email");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, gender) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$full_name, $email, $hashedPassword, $gender]);
                    error_log("Created new user: $email");
                }
                
                // Generate 6-digit OTP
                date_default_timezone_set('Africa/Casablanca');
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes expiration
                
                // Store OTP in database
                $stmt = $pdo->prepare("REPLACE INTO otps (email, otp, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $otp, $expiresAt]);
                error_log("OTP generated for $email: $otp (expires $expiresAt)");
                
                // Send OTP via Email using PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings (configure with your SMTP)
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'fadwatech111@gmail.com';
                    $mail->Password   = 'qzxfnrhfucuuhvng';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->SMTPDebug = 2; // Enable verbose debug output
                    $mail->Debugoutput = function($str, $level) {
                        error_log("PHPMailer debug level $level: $str");
                    };
                    
                    // Recipients
                    $mail->setFrom('fadwatech111@gmail.com', 'OTP Verification');
                    $mail->addAddress($email, $full_name);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Verification Code';
                    $mail->Body = "
                            <!DOCTYPE html>
                            <html>
                            <head>
                                <style type='text/css'>
                                    body {
                                        font-family: 'Arial', sans-serif;
                                        line-height: 1.6;
                                        color: #333333;
                                        max-width: 600px;
                                        margin: 0 auto;
                                        padding: 20px;
                                    }
                                    .header {
                                        color: #000000;
                                        border-bottom: 2px solid #D4AF37;
                                        padding-bottom: 10px;
                                        margin-bottom: 20px;
                                    }
                                    .otp-code {
                                        font-size: 28px;
                                        font-weight: bold;
                                        letter-spacing: 3px;
                                        color: #D4AF37;
                                        background-color: #000000;
                                        padding: 15px 25px;
                                        margin: 20px 0;
                                        display: inline-block;
                                        border-radius: 4px;
                                        text-align: center;
                                    }
                                    .footer {
                                        margin-top: 30px;
                                        padding-top: 15px;
                                        border-top: 1px solid #eeeeee;
                                        font-size: 12px;
                                        color: #777777;
                                    }
                                    .signature {
                                        margin-top: 20px;
                                        color: #000000;
                                        font-weight: bold;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class='header'>
                                    <h1>Account Verification</h1>
                                </div>
                                
                                <p>Dear $full_name,</p>
                                
                                <p>Thank you for registering with our service. To complete your account verification, please use the following One-Time Password (OTP):</p>
                                
                                <div class='otp-code'>$otp</div>
                                
                                <p>This verification code will expire in 5 minutes. For security reasons, please do not share this code with anyone.</p>
                                
                                <p>If you did not request this verification, please disregard this email or contact our support team immediately.</p>
                                
                                <div class='signature'>
                                    <p>Best regards,</p>
                                    <p>The Support Team</p>
                                </div>
                                
                                <div class='footer'>
                                    <p>© ".date('Y')." Expencio. All rights reserved.</p>
                                </div>
                            </body>
                            </html>
                            ";
                    
                    $mail->send();
                    $emailSent = true;
                    error_log("Email sent successfully to $email");
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $e->getMessage());
                    // Fallback to file if email fails
                    file_put_contents('otp_log.txt', "[" . date('Y-m-d H:i:s') . "] OTP for $email: $otp (Expires: $expiresAt)\n", FILE_APPEND);
                    $emailSent = true;
                    error_log("OTP logged to file for $email");
                }
                
                $showOtpForm = true;
            }
        } catch (PDOException $e) {
            error_log("Database error during registration: " . $e->getMessage());
            $signupError = "Registration error. Please try again. Error: " . $e->getMessage();
        } catch (Exception $e) {
            error_log("General error during registration: " . $e->getMessage());
            $signupError = "Registration error. Please try again. Error: " . $e->getMessage();
        }
    }
}

/* =============================================================================
 * OTP VERIFICATION PROCESS
 * ========================================================================== */
if (isset($_POST['verify_otp'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $otp = preg_replace('/[^0-9]/', '', $_POST['otp']);
    
    error_log("OTP verification attempt for $email with code: $otp");
    
    try {
        // Verify OTP exists and isn't expired
        $stmt = $pdo->prepare("SELECT * FROM otps WHERE email = ? AND otp = ? AND expires_at > NOW()");
        $stmt->execute([$email, $otp]);
        
        if ($stmt->fetch()) {
            // Mark user as verified
            $pdo->prepare("UPDATE users SET is_verified = TRUE WHERE email = ?")->execute([$email]);
            
            // Delete used OTP
            $pdo->prepare("DELETE FROM otps WHERE email = ?")->execute([$email]);
            
            // Get user data for session
            $stmt = $pdo->prepare("SELECT id, full_name, gender FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Start session and set user data
            if (!session_id()) session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_gender'] = $user['gender'];
            $_SESSION['user_email'] = $email;
            
            $verificationSuccess = true;
            error_log("OTP verification successful for $email");
        } else {
            $verificationError = "Invalid or expired OTP";
            error_log("Invalid OTP attempt for $email");
        }
    } catch (PDOException $e) {
        error_log("Database error during OTP verification: " . $e->getMessage());
        $verificationError = "Verification error. Please try again. Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification System</title>
    <link rel="stylesheet" href="./signup.css">
</head>
<body>
    <div class="container">
        <?php if (!$verificationSuccess): ?>
            <!-- SIGNUP FORM -->
            <h1>Create Account</h1>
            
            <?php if ($signupError): ?>
                <div class="message error"><?= htmlspecialchars($signupError) ?></div>
            <?php endif; ?>
            
            <?php if (!$showOtpForm): ?>
                <form method="POST" id="signupForm">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required
                               value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required >
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <span class="radio-label">Man</span>
                                <input type="radio" name="gender" value="man" 
                                    <?= isset($_POST['gender']) && $_POST['gender'] === 'man' ? 'checked' : '' ?> required>
                                <span class="radio-custom"></span>
                            </label>
                            <label class="radio-option">
                                <span class="radio-label">Woman</span>
                                <input type="radio" name="gender" value="woman"
                                    <?= isset($_POST['gender']) && $_POST['gender'] === 'woman' ? 'checked' : '' ?>>
                                <span class="radio-custom"></span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" name="signup" class="signup-btn">Register</button>
                </form>
                <div class="login-link">
                    Have an account? <a href="../login/login.php">Log In</a>
                </div>
            <?php endif; ?>
            
            <!-- OTP VERIFICATION FORM -->
            <?php if ($showOtpForm): ?>
                <div id="otpForm">
                    <div class="message info">
                        We've sent a verification code to <?= htmlspecialchars($email) ?>.
                        <?php if (file_exists('otp_log.txt')): ?>
                            <br><small>(Test OTP available in otp_log.txt)</small>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                        
                        <div class="form-group">
                            <label for="otp">Verification Code</label>
                            <input type="text" id="otp" name="otp" 
                                   placeholder="Enter 6-digit code" 
                                   pattern="\d{6}" required
                                   maxlength="6">
                        </div>
                        
                        <button type="submit" name="verify_otp" class="signup-btn">Verify Account</button>
                    </form>
                    
                    <?php if ($verificationError): ?>
                        <div class="message error">
                            <?= htmlspecialchars($verificationError) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- VERIFICATION SUCCESS -->
            <div class="message success">
                ✓ Account verified successfully!
            </div>
            <div class="login-link">
                <a href="../services/pageservice.php">Explore our services</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-focus OTP input and allow only numbers
        document.getElementById('otp')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        <?php if ($showOtpForm): ?>
            document.getElementById('otp')?.focus();
        <?php endif; ?>
    </script>
</body>
</html>