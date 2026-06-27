<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'e' => 'Unauthorized'
    ]);

    exit;
}

$currentRole = $_SESSION['role'] ?? null;
$allowedRoles = ['owner', 'admin'];

if (!in_array($currentRole, $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'e' => 'Forbidden'
    ]);

    exit;
}

function jsonInput() {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function requirePost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        echo json_encode([
            'ok' => false,
            'e' => 'Method not allowed'
        ]);

        exit;
    }
}

function requireAjax() {
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    if (strtolower($requestedWith) !== 'xmlhttprequest') {
        http_response_code(403);

        echo json_encode([
            'ok' => false,
            'e' => 'Invalid request'
        ]);

        exit;
    }
}

function validateEnrollmentStatus($status) {
    $allowed = ['pending', 'contacted', 'consultation_booked', 'enrolled', 'rejected'];
    return in_array($status, $allowed, true);
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'updateStatus') {
        requirePost();
        requireAjax();
        verify_csrf();

        $data = jsonInput();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $status = trim($data['status'] ?? '');

        if ($id <= 0 || !$status) {
            throw new Exception('Missing required fields');
        }

        if (!validateEnrollmentStatus($status)) {
            throw new Exception('Invalid status');
        }

        $stmt = $pdo->prepare("UPDATE enrollments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);

        echo json_encode([
            'ok' => true
        ]);

        exit;
    }

    if ($action === 'get') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id <= 0) {
            throw new Exception('Invalid ID');
        }

        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, country, student_name,
            grade, subject, preferred_time, additional_info, program, status, ip_address,
            user_agent, created_at, updated_at FROM enrollments WHERE id = ? LIMIT 1
        ");

        $stmt->execute([$id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            throw new Exception('Enrollment not found');
        }

        echo json_encode([
            'ok' => true,
            'enrollment' => $enrollment
        ]);

        exit;
    }

    if ($action === 'delete') {
        requirePost();
        requireAjax();
        verify_csrf();

        $data = jsonInput();
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        if ($id <= 0) {
            throw new Exception('Invalid ID');
        }

        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Enrollment not found');
        }

        echo json_encode([
            'ok' => true
        ]);

        exit;
    }

    if ($action === 'search') {
        requireAjax();

        $search = trim($_GET['search'] ?? '');
        if (mb_strlen($search) > 100) { throw new Exception('Search too long'); }
        $program = trim($_GET['program'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $allowedPrograms = ['tutoring', 'teacher_training'];

        if ($program !== '') {

            if (!in_array($program, $allowedPrograms, true)) {
                throw new Exception('Invalid program');
            }

            $where[] = "program = ?";
            $params[] = $program;
        }

        if ($status !== '') {
            if (!validateEnrollmentStatus($status)) {
                throw new Exception('Invalid status');
            }

            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, program, status, created_at,
            preferred_time, additional_info, student_name, grade, subject
            FROM enrollments $whereClause ORDER BY created_at DESC LIMIT 50
        ");

        $stmt->execute($params);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'enrollments' => $enrollments
        ]);

        exit;
    }

    http_response_code(400);

    echo json_encode([
        'ok' => false,
        'e' => 'Invalid action'
    ]);

} catch (Exception $e) {
    http_response_code(400);

    error_log(sprintf(
        '[Enrollment API] %s in %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    echo json_encode([
        'ok' => false,
        'e' => 'Something went wrong'
    ]);
}