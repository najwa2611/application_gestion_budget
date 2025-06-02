<?php
session_start();
require '../db.php'; // Assure-toi d'inclure ta connexion à la base de données

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirige vers la page de login si l'utilisateur n'est pas connecté
    exit();
}

// Récupère l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Récupère les données du formulaire
$amount = $_POST['amount'];
$category = $_POST['category'];
$date = $_POST['date'] ?? date('Y-m-d'); // Si aucune date n'est spécifiée, utilise la date actuelle

// Prépare la requête SQL pour insérer les données dans la table `daily_expenses`
$sql = "INSERT INTO daily_expenses (user_id, category, amount, recorded_at) VALUES (:user_id, :category, :amount, :date)";

// Exécute la requête
try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
    $stmt->bindParam(':date', $date, PDO::PARAM_STR);

    $stmt->execute();

    // Redirige vers la page de récapitulatif après l'enregistrement
    header('Location: challenge.php');
    exit();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
