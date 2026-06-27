<?php
$pageTitle = 'Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | Loving Kindness Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a3a2a 0%, #0f5b3e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .error-code {
            font-size: 120px;
            font-weight: 800;
            color: #e8b12c;
            line-height: 1;
            margin-bottom: 16px;
            text-shadow: 4px 4px 0 rgba(0,0,0,0.1);
        }
        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 16px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #e8b12c;
            color: #0a3a2a;
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-home:hover {
            background: #c48f1a;
            transform: translateY(-2px);
        }
        .error-icon {
            font-size: 80px;
            color: #e8b12c;
            margin-bottom: 20px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-compass"></i>
        </div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <p class="error-message">Oops! The page you're looking for doesn't exist or has been moved.</p>
        <a href="dashboard" class="btn-home">
            <i class="fas fa-home"></i>
            Back to Dashboard
        </a>
    </div>
</body>
</html>