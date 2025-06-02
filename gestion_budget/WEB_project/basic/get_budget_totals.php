<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit();
}

$userId = $_SESSION['user_id'];
$currentMonth = date('n');
$currentYear = date('Y');

try {
    // Récupérer le budget total
    $budgetStmt = $pdo->prepare("SELECT total_amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
    $budgetStmt->execute([$userId, $currentMonth, $currentYear]);
    $budget = $budgetStmt->fetch();
    
    $totalBudget = $budget ? $budget['total_amount'] : 0;
    
    // Récupérer le total dépensé
    $spentStmt = $pdo->prepare("SELECT SUM(spent) as total_spent FROM budget_items WHERE budget_id IN 
                              (SELECT id FROM budgets WHERE user_id = ? AND month = ? AND year = ?)");
    $spentStmt->execute([$userId, $currentMonth, $currentYear]);
    $spent = $spentStmt->fetch();
    
    $totalSpent = $spent['total_spent'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'total_budget' => (float)$totalBudget,
        'total_spent' => (float)$totalSpent
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>