<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_budget_id'])) {
    header("Location: ../login/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $budgetId = $_SESSION['current_budget_id'];
    $category = $_POST['category'] ?? '';
    $customCategory = $_POST['custom_category'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $spent = floatval($_POST['spent'] ?? 0);
    $itemId = $_POST['item_id'] ?? null; // For edit functionality

    // Validation des données
    if (empty($category) || $amount <= 0 || $spent < 0) {
        $_SESSION['error'] = "Veuillez remplir tous les champs correctement";
        header("Location: basic.php");
        exit();
    }

    // Utiliser la catégorie personnalisée si "Other" est sélectionné
    if ($category === "Other" && !empty($customCategory)) {
        $category = $customCategory;
    }

    // New validation logic for budget constraints
    try {
        // Get total budget amount
        $stmt = $pdo->prepare("SELECT total_amount FROM budgets WHERE id = ?");
        $stmt->execute([$budgetId]);
        $totalBudget = $stmt->fetchColumn();

        // Get sum of all budget items (excluding current item if editing)
        $sumStmt = $pdo->prepare("SELECT SUM(amount) FROM budget_items WHERE budget_id = ? AND id != ?");
        $sumStmt->execute([$budgetId, $itemId]);
        $sumBudgets = $sumStmt->fetchColumn() ?? 0;

        // Check if new sum would exceed total budget
        if (($sumBudgets + $amount) > $totalBudget) {
            $_SESSION['error'] = "Le total des items de budget ne peut pas dépasser le budget total.";
            header("Location: basic.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la vérification du budget : " . $e->getMessage();
        header("Location: basic.php");
        exit();
    }

    try {
        // Existing database logic
        $checkSql = "SELECT id FROM budget_items WHERE budget_id = ? AND category = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$budgetId, $category]);

        if ($checkStmt->rowCount() > 0) {
            // Mise à jour si l'item existe déjà
            $updateSql = "UPDATE budget_items SET amount = ?, spent = ? WHERE budget_id = ? AND category = ?";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$amount, $spent, $budgetId, $category]);
            $_SESSION['success'] = "Item de budget mis à jour avec succès.";
        } else {
            // Insertion d'un nouvel item
            $insertSql = "INSERT INTO budget_items (budget_id, category, amount, spent) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([$budgetId, $category, $amount, $spent]);
            $_SESSION['success'] = "Nouvel item de budget ajouté avec succès.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    }

    header("Location: basic.php");
    exit();
}
?>