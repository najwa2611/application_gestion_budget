<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// Connexion à la base de données
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['total_budget']) && is_numeric($_POST['total_budget']) && floatval($_POST['total_budget']) > 0) {
        $totalBudget = floatval($_POST['total_budget']);
        $userId = $_SESSION['user_id'];
        $currentMonth = date('n');
        $currentYear = date('Y');

        try {
            // Vérifier s'il y a déjà un budget pour cet utilisateur, mois et année
            $checkSql = "SELECT id FROM budgets WHERE user_id = ? AND month = ? AND year = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$userId, $currentMonth, $currentYear]);

            if ($checkStmt->rowCount() > 0) {
                // Mise à jour du budget existant
                $updateSql = "UPDATE budgets SET total_amount = ? WHERE user_id = ? AND month = ? AND year = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$totalBudget, $userId, $currentMonth, $currentYear]);
                
                $budget = $checkStmt->fetch();
                $_SESSION['current_budget_id'] = $budget['id'];
                $_SESSION['success'] = "Budget mis à jour avec succès.";
            } else {
                // Insertion d'un nouveau budget
                $insertSql = "INSERT INTO budgets (user_id, month, year, total_amount) VALUES (?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([$userId, $currentMonth, $currentYear, $totalBudget]);
                
                $_SESSION['current_budget_id'] = $pdo->lastInsertId();
                $_SESSION['success'] = "Budget enregistré avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        }

        header("Location: basic.php");
        exit();
    } else {
        $_SESSION['error'] = "Veuillez entrer un montant valide.";
        header("Location: basic.php");
        exit();
    }
}
?>