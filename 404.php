<?php
$pageTitle = 'Page Not Found | Loving Kindness Academy';
$pageDescription = 'The page you are looking for could not be found. Let us help you find your way back to learning.';
// No header include needed for 404 page as we want full control
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="robots" content="noindex, follow">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/404.css?<?php echo filemtime('assets/css/404.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
</head>
<body class="error-page-body">

<div class="error-container">
    <div class="error-content">
        <div class="error-icon">
            <i class="fas fa-hand-sparkles"></i>
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">Oops! It seems you've wandered off the learning path. The page you're looking for doesn't exist or may have been moved.</p>
        
        <div class="error-actions">
            <a href="home" class="btn-primary">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <a href="enroll" class="btn-secondary">
                <i class="fas fa-calendar-alt"></i> Book Consultation
            </a>
        </div>
        
        <div class="error-help">
            <p>Need help finding something?</p>
            <a href="contact" class="help-link">
                <i class="fas fa-envelope"></i> Contact Support
            </a>
            <a href="https://wa.me/2349122299653" class="help-link whatsapp" target="_blank">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
        </div>
        
        <div class="error-links">
            <h3>You might be looking for:</h3>
            <div class="quick-links">
                <a href="tutoring"><i class="fas fa-chalkboard-user"></i> K-6 Tutoring</a>
                <a href="training"><i class="fas fa-certificate"></i> Teacher Training</a>
                <a href="about"><i class="fas fa-user-graduate"></i> About Helen</a>
                <a href="enroll"><i class="fas fa-graduation-cap"></i> Enroll Now</a>
                <a href="contact"><i class="fas fa-phone-alt"></i> Contact Us</a>
            </div>
        </div>
    </div>
</div>

<footer class="error-footer">
    <p>&copy; <?php echo date("Y"); ?> Loving Kindness Academy — Lead Instructor: Helen Ugochi-Chukwu. All rights reserved.</p>
</footer>

</body>
</html>