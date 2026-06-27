<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/NotificationService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF CHECK
|--------------------------------------------------------------------------
*/

$csrfToken = $_POST['csrf_token'] ?? '';

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid CSRF token.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| HONEYPOT CHECK
|--------------------------------------------------------------------------
*/

if (!empty($_POST['website'])) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Spam detected.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| RATE LIMITING
|--------------------------------------------------------------------------
*/

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$rateStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE ip_address = ?
    AND created_at >= NOW() - INTERVAL 15 MINUTE
");

$rateStmt->execute([$ipAddress]);
$requestCount = (int) $rateStmt->fetchColumn();

if ($requestCount >= 3) {
    http_response_code(429);
    echo json_encode([
        'ok' => false,
        'message' => 'Too many requests. Please try again later.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SANITIZE INPUTS
|--------------------------------------------------------------------------
*/

$fullName = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$inquiryType = trim($_POST['inquiry_type'] ?? '');
$message = trim($_POST['message'] ?? '');

/*
|--------------------------------------------------------------------------
| NORMALIZE SPACES
|--------------------------------------------------------------------------
*/

$fullName = preg_replace('/\s+/', ' ', $fullName);
$subject = preg_replace('/\s+/', ' ', $subject);
$message = preg_replace('/[ \t]+/', ' ', $message);

$allowedInquiryTypes = [
    'tutoring',
    'teacher-training',
    'admissions',
    'technical',
    'feedback',
    'general'
];

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

$errors = [];

if (
    $fullName === '' ||
    mb_strlen($fullName) < 2 ||
    mb_strlen($fullName) > 100
) {
    $errors[] = 'Please enter a valid full name.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($phone !== '' && mb_strlen($phone) > 30) {
    $errors[] = 'Phone number is too long.';
}

if (
    $subject === '' ||
    mb_strlen($subject) < 3 ||
    mb_strlen($subject) > 200
) {
    $errors[] = 'Please enter a valid subject.';
}

if (!in_array($inquiryType, $allowedInquiryTypes, true)) {
    $errors[] = 'Invalid inquiry type.';
}

if ($message === '' ||  mb_strlen($message) < 15 || mb_strlen($message) > 5000) {
    $errors[] = 'Message must be between 15 and 5000 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => $errors[0]
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SAVE MESSAGE
|--------------------------------------------------------------------------
*/

$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0,1000);

try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, phone, subject,
        inquiry_type, message, ip_address, user_agent)
        VALUES (:full_name, :email, :phone, :subject, :inquiry_type, :message,
            :ip_address, :user_agent
        )
    ");

    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':phone' => $phone ?: null,
        ':subject' => $subject,
        ':inquiry_type' => $inquiryType,
        ':message' => $message,
        ':ip_address' => $ipAddress,
        ':user_agent' => $userAgent
    ]);

    $contactId = (int) $pdo->lastInsertId();

    // Normalize the inquiry type
    $inquiryLabel = match ($inquiryType) {
        'tutoring'         => 'Tutoring',
        'teacher-training' => 'Teacher Training',
        'admissions'       => 'Admissions',
        'technical'        => 'Technical',
        'feedback'         => 'Feedback',
        'general'          => 'General',
        default            => ucwords(str_replace(['_', '-'], ' ', $inquiryType))
    };

    // Title focuses on the Category and Sender (always relevant)
    $title = "{$inquiryLabel} Inquiry from {$fullName}";

    // Body focuses on context
    $message = "{$fullName} sent an inquiry from {$email} " . 
            ($phone ? "(Phone: {$phone}) " : "") . "regarding: \"{$subject}\".";

    try {
        NotificationService::notifyAdmins(
            $pdo, 
            ['owner', 'admin'], 
            'new_contact_message', 
            $title, 
            $message, 
            null, 
            'contact_message', 
            $contactId,
            [
                'email' => $email,
                'subject' => $subject,
                'inquiry_type' => $inquiryLabel,
                'full_name' => $fullName,
                'phone' => $phone
            ]
        );
    } catch (Throwable $e) {
        error_log('[Notification] ' . $e->getMessage());
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Unable to send message right now.'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/

echo json_encode([
    'ok' => true,
    'message' => 'Message sent successfully.'
]);