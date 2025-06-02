<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['monthly-income']) && !empty($_POST['monthly-income'])) {
        // Retrieve the data
        $incomeAmount = floatval($_POST['monthly-income']);
        $userId = $_SESSION['user_id'];

        // Check if the user already has an income entry
        $sqlCheck = "SELECT * FROM incomes WHERE user_id = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([$userId]);

        if ($stmtCheck->rowCount() > 0) {
            // Update existing income
            $sql = "UPDATE incomes SET amount = ?, recorded_at = CURRENT_TIMESTAMP WHERE user_id = ?";
        } else {
            // Insert new income
            $sql = "INSERT INTO incomes (user_id, amount) VALUES (?, ?)";
        }

        $stmt = $pdo->prepare($sql);
        $params = $stmtCheck->rowCount() > 0 
            ? [$incomeAmount, $userId]
            : [$userId, $incomeAmount];

        if ($stmt->execute($params)) {
            $_SESSION['success'] = "Income saved successfully!";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Error saving income.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please enter a valid amount.";
        header("Location: index.php");
        exit();
    }
}
?>
