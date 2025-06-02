<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$currentMonth = date('n');
$currentYear = date('Y');

$response = [
    'totalBudget' => 0,
    'totalSpent' => 0,
    'categories' => [],
    'monthlyData' => []
];

try {
    // Get main budget
    $stmt = $pdo->prepare("SELECT id, total_amount FROM budgets WHERE user_id = ? AND month = ? AND year = ?");
    $stmt->execute([$userId, $currentMonth, $currentYear]);
    $budget = $stmt->fetch();

    if ($budget) {
        $response['totalBudget'] = (float)$budget['total_amount'];
        
        // Get all budget items
        $stmt = $pdo->prepare("
            SELECT category, amount as budget, spent, created_at 
            FROM budget_items 
            WHERE budget_id = ?
            ORDER BY created_at
        ");
        $stmt->execute([$budget['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process categories
        $categories = [];
        foreach ($items as $item) {
            $category = $item['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'budget' => 0,
                    'spent' => 0
                ];
            }
            $categories[$category]['budget'] += (float)$item['budget'];
            $categories[$category]['spent'] += (float)$item['spent'];
        }

        // Format category data
        foreach ($categories as $name => $data) {
            $variance = $data['budget'] - $data['spent'];
            $response['categories'][] = [
                'name' => $name,
                'budget' => $data['budget'],
                'spent' => $data['spent'],
                'variance' => $variance,
                'variancePercent' => $data['budget'] > 0 ? ($variance / $data['budget']) * 100 : 0
            ];
        }

        // Calculate totals
        $response['totalSpent'] = array_sum(array_column($response['categories'], 'spent'));

        // Get monthly data (last 6 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(amount) as budget,
                SUM(spent) as spent
            FROM budget_items
            WHERE budget_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute([$budget['id']]);
        $response['monthlyData'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}