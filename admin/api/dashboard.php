<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);

    exit;
}

require_once __DIR__ . '/../services/DashboardService.php';

header('Content-Type: application/json');

$monthly = DashboardService::getMonthlyEnrollmentData($pdo);
$programs = DashboardService::getProgramDistribution($pdo);
$stats = DashboardService::getStats($pdo);

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'statusPercentages' => DashboardService::getStatusPercentages($stats),
    'monthlyLabels' => $monthly['labels'],
    'monthlyEnrollments' => $monthly['counts'],
    'programLabels' => $programs['labels'],
    'programDistribution' => $programs['counts']
]);