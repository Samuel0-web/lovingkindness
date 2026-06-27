<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$adminId = $_SESSION['admin_id'] ?? null;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
    
    try {
        $action = $_POST['action'] ?? '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($action === 'update_status') {
            $status = $_POST['status'] ?? '';
            $allowed = ['unread', 'read', 'replied', 'archived', 'spam'];
            if (!in_array($status, $allowed)) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?,
                updated_at = NOW() WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$status, $id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Message not found');
            }
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($action === 'send_reply') {
            $replyMessage = trim($_POST['reply_message'] ?? '');
            if (empty($replyMessage)) {
                throw new Exception('Reply message cannot be empty');
            }
            
            $stmt = $pdo->prepare("UPDATE contact_messages SET reply_message = ?,
                status = 'replied', replied_at = NOW(), updated_at = NOW(), admin_id = ?
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$replyMessage, $adminId, $id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Message not found');
            }
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($action === 'update_notes') {
            $notes = trim($_POST['admin_notes'] ?? '');
            $stmt = $pdo->prepare("UPDATE contact_messages SET admin_notes = ?, 
                updated_at = NOW() WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$notes, $id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Message not found');
            }
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($action === 'delete_message') {
            $stmt = $pdo->prepare("UPDATE contact_messages SET deleted_at = NOW() 
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('Message not found');
            }
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Check if this is a list request or single message request
if (isset($_GET['list'])) {
    // Handle filtered list request
    $statusFilter = $_GET['status'] ?? 'all';
    $inquiryFilter = $_GET['inquiry'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    
    // Build WHERE clause
    $where = ["deleted_at IS NULL"];
    $params = [];
    
    if ($statusFilter !== 'all') {
        $where[] = "status = ?";
        $params[] = $statusFilter;
    }
    
    if ($inquiryFilter !== 'all') {
        $where[] = "inquiry_type = ?";
        $params[] = $inquiryFilter;
    }
    
    if ($search !== '') {
        $where[] = "(full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $where);
    
    // Fetch messages
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, subject, inquiry_type, status, 
        message, reply_message, admin_notes, replied_at, user_agent, ip_address,
        created_at FROM contact_messages {$whereClause} ORDER BY 
        FIELD(status, 'unread', 'read', 'replied', 'archived', 'spam'), created_at DESC
    ");
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalMessages = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages
        WHERE deleted_at IS NULL
    ")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'total' => count($messages),
        'totalMessages' => $totalMessages
    ]);
    exit;
}

// If no 'list' parameter, handle single message request (existing functionality)
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT id, full_name, email, phone, subject, inquiry_type, status, 
    message, reply_message, admin_notes, replied_at, user_agent, ip_address, created_at FROM
    contact_messages WHERE id = ? AND deleted_at IS NULL
");
$stmt->execute([$id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if ($message) {
    // Parse user agent with improved accuracy
    $ua = $message['user_agent'] ?? '';
    $parsed = [
        'browser' => 'Unknown',
        'version' => '',
        'os' => 'Unknown',
        'device' => 'Desktop',
        'isLocal' => false
    ];
    
    // Check for local development
    if ($message['ip_address'] === '::1' || $message['ip_address'] === '127.0.0.1') {
        $parsed['isLocal'] = true;
        $parsed['browser'] = 'N/A';
        $parsed['os'] = 'Local Development';
        $parsed['device'] = 'Localhost';
    } elseif (!empty($ua)) {
        // Browser detection with version
        if (preg_match('/Chrome\/(\d+)/', $ua, $matches) && strpos($ua, 'Edg') === false 
            && strpos($ua, 'OPR') === false) {
            $parsed['browser'] = 'Chrome';
            $parsed['version'] = $matches[1];
        } elseif (preg_match('/Firefox\/(\d+)/', $ua, $matches)) {
            $parsed['browser'] = 'Firefox';
            $parsed['version'] = $matches[1];
        } elseif (preg_match('/Edg\/(\d+)/', $ua, $matches)) {
            $parsed['browser'] = 'Edge';
            $parsed['version'] = $matches[1];
        } elseif (preg_match('/Safari\/(\d+)/', $ua, $matches) && strpos($ua, 'Chrome') === false) {
            $parsed['browser'] = 'Safari';
            $parsed['version'] = $matches[1];
        } elseif (preg_match('/OPR\/(\d+)/', $ua, $matches)) {
            $parsed['browser'] = 'Opera';
            $parsed['version'] = $matches[1];
        }
        
        // OS detection - improved accuracy
        if (strpos($ua, 'Windows NT 10.0') !== false) $parsed['os'] = 'Windows 11/10';
        elseif (strpos($ua, 'Windows NT 6.1') !== false) $parsed['os'] = 'Windows 7';
        elseif (strpos($ua, 'Windows NT') !== false) $parsed['os'] = 'Windows';
        elseif (strpos($ua, 'Mac OS X') !== false || strpos($ua, 'macOS') 
            !== false) $parsed['os'] = 'macOS';
        elseif (strpos($ua, 'iPhone') !== false) { 
            $parsed['os'] = 'iOS'; $parsed['device'] = 'Mobile';
        }
        elseif (strpos($ua, 'iPad') !== false) {
            $parsed['os'] = 'iPadOS'; $parsed['device'] = 'Tablet';
        }
        elseif (strpos($ua, 'Android') !== false) {
            $parsed['os'] = 'Android';
            if (strpos($ua, 'Mobile') !== false) $parsed['device'] = 'Mobile';
            elseif (strpos($ua, 'Tablet') !== false) $parsed['device'] = 'Tablet';
        } elseif (strpos($ua, 'Linux') !== false) {
            $parsed['os'] = 'Linux';
            if (strpos($ua, 'Android') === false) $parsed['device'] = 'Desktop';
        }
    }
    
    // Trim leading whitespace and newlines, but preserve paragraph structure
    $message['message'] = ltrim($message['message']);

    $message['parsed_ua'] = $parsed;
    echo json_encode($message);
} else {
    echo json_encode(['error' => 'Message not found']);
}