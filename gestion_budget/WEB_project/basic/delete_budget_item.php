<?php
session_start();
include('../db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location:../login/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = $_POST['item_id'] ?? null;

    if (empty($itemId)) {
        $_SESSION['error'] = "No item selected for deletion";
        header("Location: dashboard.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM budget_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $_SESSION['success'] = "Budget item deleted successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting budget item: " . $e->getMessage();
    }

    header("Location: basic.php");
    exit();
}
?>