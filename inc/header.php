<?php
// Default meta values if not set by page
$pageTitle = $pageTitle ?? 'Loving Kindness Academy | K-6 Tutoring & Teacher Training';
$pageDescription = $pageDescription ?? 'Premium online tutoring for Reception to Grade 6, and professional certification for online educators. Global curriculum — UK, US, CAN, NGA.';

// Function to check active page for clean URLs
function isActiveRoute($routeName) {
    $currentUri = trim($_SERVER['REQUEST_URI'], '/');
    // Remove base path if needed
    $base = dirname($_SERVER['SCRIPT_NAME']);
    $base = str_replace('\\', '/', $base);
    $base = trim($base, '/');
    if ($base) {
        $currentUri = preg_replace('#^' . preg_quote($base, '#') . '#', '', $currentUri);
    }
    $currentUri = trim($currentUri, '/');
    
    // Handle home page
    if (empty($currentUri) && $routeName === 'home') {
        return 'active';
    }
    
    return ($currentUri === $routeName) ? 'active' : '';
}

// Alternative: check by PHP file name (for fallback)
function isActiveFile($fileName) {
    $currentFile = basename($_SERVER['SCRIPT_NAME'], '.php');
    return ($currentFile === $fileName) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="online tutoring, teacher training, K-6 education, virtual learning, phonics, numeracy">
    <meta name="author" content="Loving Kindness Academy">
    <meta name="robots" content="index, follow">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="canonical" href="https://www.lovingkindnessacademy.org/<?php echo basename($_SERVER['SCRIPT_NAME']); ?>">
    <link rel="stylesheet" href="assets/css/global.css?<?php echo filemtime('assets/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #FDFBF7; line-height: 1.5; }
    </style>
</head>
<body>

<nav class="main-nav">
    <div class="nav-container">
        <div class="logo-wrapper">
            <a href="home" class="logo-link">
                <img src="img\logo.jpg" alt="Loving Kindness Academy Logo" class="logo-img">
                <span class="logo-text">Loving Kindness Academy</span>
            </a>
        </div>
        <div class="nav-links">
            <a href="tutoring" class="nav-link <?php echo isActiveRoute('tutoring'); ?>">K-6 Tutoring</a>
            <a href="training" class="nav-link <?php echo isActiveRoute('training'); ?>">Teacher Training</a>
            <a href="about" class="nav-link <?php echo isActiveRoute('about'); ?>">About Helen</a>
        </div>
        <div class="nav-actions">
            <a href="contact" class="cta-wa-button"><i class="fab fa-whatsapp"></i> Contact Us</a>
            <a href="enroll" class="cta-enroll-nav"><i class="fas fa-graduation-cap"></i> Enroll Now</a>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu"><i class="fas fa-bars"></i></button>
    </div>
    <div class="mobile-menu" id="mobileMenu">
        <a href="tutoring" class="mobile-link <?php echo isActiveRoute('tutoring'); ?>"><i class="fas fa-chalkboard-user"></i> K-6 Tutoring</a>
        <a href="training" class="mobile-link <?php echo isActiveRoute('training'); ?>"><i class="fas fa-chalkboard"></i> Teacher Training</a>
        <a href="about" class="mobile-link <?php echo isActiveRoute('about'); ?>"><i class="fas fa-user-graduate"></i> About Helen</a>
        <a href="contact" class="mobile-wa"><i class="fab fa-whatsapp"></i> Contact Us</a>
        <a href="enroll" class="mobile-enroll"><i class="fas fa-graduation-cap"></i> Enroll Now</a>
    </div>
</nav>

<script>
(function() {
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    if(mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const expanded = mobileMenu.classList.toggle('open');
            mobileBtn.setAttribute('aria-expanded', expanded);
        });
        const links = mobileMenu.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', () => mobileMenu.classList.remove('open'));
        });
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !mobileBtn.contains(event.target) && mobileMenu.classList.contains('open')) {
                mobileMenu.classList.remove('open');
            }
        });
    }
})();
</script>