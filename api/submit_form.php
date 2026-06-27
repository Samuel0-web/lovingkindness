<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/NotificationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);

    exit;
}

function sanitize($value) {
    return trim(strip_tags($value));
}

/*
|--------------------------------------------------------------------------
| Honeypot Spam Protection
|--------------------------------------------------------------------------
*/

if (!empty($_POST['website'])) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Spam detected'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    http_response_code(419);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid session token'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Sanitize Inputs
|--------------------------------------------------------------------------
*/

$program = sanitize($_POST['program'] ?? '');
$full_name = sanitize($_POST['full_name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');

$student_name = sanitize($_POST['student_name'] ?? '');
$grade = sanitize($_POST['grade'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');

$preferred_time = sanitize($_POST['preferred_time'] ?? '');
$country = sanitize($_POST['country'] ?? '');

$additional_info = sanitize($_POST['additional_info'] ?? '');

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

$allowedPrograms = ['tutoring', 'teacher_training'];

if (!in_array($program, $allowedPrograms, true)) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid program selected'
    ]);

    exit;
}

if (
    empty($full_name) ||
    empty($email) ||
    empty($phone) ||
    empty($preferred_time) ||
    empty($country)
) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required fields'
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Tutoring Validation
|--------------------------------------------------------------------------
*/

if ($program === 'tutoring') {

    if (
        empty($student_name) ||
        empty($grade) ||
        empty($subject)
    ) {
        http_response_code(422);

        echo json_encode([
            'success' => false,
            'message' => 'Please complete all tutoring fields'
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Metadata
|--------------------------------------------------------------------------
*/

$ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

/*
|--------------------------------------------------------------------------
| Rate Limiting
|--------------------------------------------------------------------------
*/

$rateLimitStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM enrollments
    WHERE ip_address = :ip_address
    AND created_at >= NOW() - INTERVAL 10 MINUTE
");

$rateLimitStmt->execute([
    ':ip_address' => $ip_address
]);

$submissionCount = (int) $rateLimitStmt->fetchColumn();

if ($submissionCount >= 3) {

    http_response_code(429);

    echo json_encode([
        'success' => false,
        'message' => 'Too many submissions. Please try again later.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Duplicate Submission Protection
|--------------------------------------------------------------------------
*/

$duplicateStmt = $pdo->prepare("
    SELECT id
    FROM enrollments
    WHERE email = :email
    AND phone = :phone
    AND program = :program
    AND created_at >= NOW() - INTERVAL 10 MINUTE
    LIMIT 1
");

$duplicateStmt->execute([
    ':email' => $email,
    ':phone' => $phone,
    ':program' => $program
]);

$duplicateExists = $duplicateStmt->fetch();

if ($duplicateExists) {

    http_response_code(409);

    echo json_encode([
        'success' => false,
        'message' => 'You already submitted recently.'
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Save Enrollment
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        INSERT INTO enrollments (
            program,
            full_name,
            email,
            phone,
            student_name,
            grade,
            subject,
            preferred_time,
            country,
            additional_info,
            ip_address,
            user_agent
        )
        VALUES (
            :program,
            :full_name,
            :email,
            :phone,
            :student_name,
            :grade,
            :subject,
            :preferred_time,
            :country,
            :additional_info,
            :ip_address,
            :user_agent
        )
    ");

    $stmt->execute([
        ':program' => $program,
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone' => $phone,
        ':student_name' => $student_name ?: null,
        ':grade' => $grade ?: null,
        ':subject' => $subject ?: null,
        ':preferred_time' => $preferred_time,
        ':country' => $country,
        ':additional_info' => $additional_info ?: null,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);

    $enrollmentId = (int) $pdo->lastInsertId();

    // Normalize the program label
    $programLabel = match ($program) {
        'teacher_training' => 'Teacher Training',
        'tutoring'         => 'Tutoring',
        default            => ucwords(str_replace('_', ' ', $program))
    };

    // Title: Focus on the Program and the Applicant (Persistent and relevant)
    $title = "{$programLabel} Enrollment: {$full_name}";

    // Message: Include all critical details for an immediate "at-a-glance" assessment
    $message = "{$full_name} ({$email}) has submitted an enrollment request for {$programLabel}. " . 
        ($student_name ? "Student: {$student_name} " . 
        ($grade ? "({$grade})" : "") . ". " : "") . 
        ($subject ? "Subject: {$subject}. " : "") . 
        "Preferred Time: {$preferred_time}. " . 
        "Location: {$country}.";

    try {
        NotificationService::notifyAdmins(
            $pdo, 
            ['owner', 'admin'], 
            'new_enrollment', 
            $title, 
            $message, 
            null, 
            'enrollment', 
            $enrollmentId,
            [
                'email' => $email,
                'program' => $programLabel,
                'full_name' => $full_name,
                'student_name' => $student_name,
                'country' => $country
            ]
        );
    } catch (Throwable $e) {
        error_log('[Notification] ' . $e->getMessage());
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    echo json_encode([
        'success' => true,
        'message' => 'Enrollment submitted successfully',
        'redirect' => 'success'
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong'
    ]);
}