<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_SESSION['user_id'];
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];

    if (empty($name) || $amount <= 0) {
        $_SESSION['error'] = "Please fill in all fields correctly.";
        header("Location: index.php");
        exit();
    }

    // Vérifier le revenu de l'utilisateur
    $stmt = $pdo->prepare("SELECT amount FROM incomes WHERE user_id = ?");
    $stmt->execute([$userId]);
    $incomeData = $stmt->fetch();
    $monthlyIncome = $incomeData['amount'] ?? 0;

    if ($monthlyIncome <= 0) {
        $_SESSION['error'] = "No income recorded. Please enter your income before adding expenses.";
        header("Location: index.php");
        exit();
    }

    $needsLimit = $monthlyIncome * 0.5;

    // Total actuel des dépenses "needs"
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND category IN ('housing', 'utilities', 'health', 'food')");
    $stmt->execute([$userId]);
    $expenseData = $stmt->fetch();
    $totalNeeds = $expenseData['total'] ?? 0;

    // Vérifier si la dépense existe déjà (même name + category pour cet utilisateur)
    $stmt = $pdo->prepare("SELECT expense_id, amount FROM expenses WHERE user_id = ? AND name = ? AND category = ?");
    $stmt->execute([$userId, $name, $category]);
    $existingExpense = $stmt->fetch();

    // Simulation du nouveau total
    $newTotalNeeds = $totalNeeds + $amount;
    if ($existingExpense && in_array($category, ['housing', 'utilities', 'health', 'food'])) {
        $newTotalNeeds = $totalNeeds - $existingExpense['amount'] + $amount;
    }

    if ($newTotalNeeds > $needsLimit && in_array($category, ['housing', 'utilities', 'health', 'food'])) {
        $_SESSION['error'] = "⚠️ This expense pushes your 'needs' above 50% of your income. You cannot start the 50/30/20 challenge until you stay within limits.";
        header("Location: index.php");
        exit();
    }

    if ($existingExpense) {
        // Mise à jour si même name + category existent déjà
        $stmt = $pdo->prepare("UPDATE expenses SET amount = ?, type = ?, recorded_at = NOW() WHERE expense_id = ?");
        $stmt->execute([$amount, $type, $existingExpense['expense_id']]);
        $_SESSION['success'] = "✅ Expense updated successfully!";
    } else {
        // Sinon insertion
        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, name, category, amount, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $category, $amount, $type]);
        $_SESSION['success'] = "✅ Expense added successfully!";
    }

    header("Location: index.php");
    exit();
}
?>
