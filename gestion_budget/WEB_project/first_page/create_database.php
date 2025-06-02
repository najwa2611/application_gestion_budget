<?php
// ==================== CONFIGURATION DE LA CONNEXION ====================
$host = 'localhost';         // Serveur MySQL
$username = 'root';          // Nom d'utilisateur MySQL
$password = '';              // Mot de passe MySQL (vide par défaut avec XAMPP/WAMP)
$dbname = 'projet_web';      // Nom de la DB à créer

try {
    // 1. Connexion à MySQL (sans sélectionner de DB)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Création de la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Base de données créée (ou déjà existante) : <strong>$dbname</strong><br>";

    // 3. Sélection de la DB
    $pdo->exec("USE $dbname");

    // 4. Création de la table `users`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            gender VARCHAR(10) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            is_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table <strong>users</strong> créée avec succès.<br>";

    // 5. Création de la table `otps`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            otp VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table <strong>otps</strong> créée avec succès.<br>";

    echo "<p style='color: green;'>✅ Base de données prête !</p>";

} catch (PDOException $e) {
    die("<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>");
}
?>