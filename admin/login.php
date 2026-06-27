<?php
require __DIR__ . '/config/db.php';

// redirect if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard");
    exit;
}

$emailError = '';
$passwordError = '';
$error = '';

$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid request");
    }

    // rate limit (AFTER CSRF check)
    if ($_SESSION['login_attempts'] >= 5) {
        if (time() - $_SESSION['last_attempt_time'] < 300) {
            $error = "Too many attempts. Try again in 5 minutes.";
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }

    // 🚨 STOP HERE if rate limited
    if (!empty($error)) {
        // do nothing else, just let form display error
    } else {

        // NOW continue normal login logic

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email) {
            $emailError = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Enter a valid email";
        }

        if (!$password) {
            $passwordError = "Password is required";
        }

        if (!$emailError && !$passwordError && !$error) {

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (
                !$admin ||
                $admin['auth_provider'] !== 'email' ||
                !password_verify($password, $admin['password'])
            ) {
                sleep(1); // slow down brute force
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                $error = "Invalid email or password";
            } else {
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                // ✅ ADD THIS
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);

                $_SESSION['login_attempts'] = 0;

                header("Location: dashboard");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, height=device-height">
    <title>Admin Login | Loving Kindness Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-box">
                <div class="login-header">
                    <img src="../img/logo.jpg" alt="Loving Kindness Academy Logo" class="img-logo">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your admin account</p>
                </div>
                <form class="login-form" method="POST">

                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="floating-group" id="emailGroup">
                        <input type="email" id="email" placeholder=" " value="<?= htmlspecialchars($email ?? '') ?>" name="email" required autocomplete="off">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            <span>Email Address</span>
                        </label>
                        <div class="error-message" id="emailError">
                            <?= htmlspecialchars($emailError) ?>
                        </div>
                    </div>

                    <div class="floating-group" id="passwordGroup">
                        <div class="password-wrapper">
                            <input type="password" id="password" placeholder=" " name="password" required autocomplete="off">
                            <label for="password">
                                <i class="fas fa-lock"></i>
                                <span>Password</span>
                            </label>
                            <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
                        </div>
                        <div class="error-message" id="passwordError">
                            <?= htmlspecialchars($passwordError) ?>
                        </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message" id="generalError">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                    </div>

                    <div class="form-options">
                        <a href="forgot-password" class="forgot">Forgot password?</a>
                    </div>

                    <button type="submit" class="login-button" id="submitBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div class="login-footer">
                    <a href="../home">
                        <i class="fas fa-arrow-left"></i>
                        Back to website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            // DOM Elements
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailGroup = document.getElementById('emailGroup');
            const passwordGroup = document.getElementById('passwordGroup');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const submitBtn = document.getElementById('submitBtn');
            const generalError = document.getElementById('generalError');

            if (generalError && generalError.textContent.trim() !== '') {
                generalError.style.display = 'block';
            }

            function clearGeneralError() {
                if (generalError) {
                    generalError.textContent = '';
                    generalError.style.display = 'none';
                }
            }
            

            // Helper function to show error
            function showError(element, errorDiv, message) {
                element.classList.add('error');
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }

            // Helper function to clear error
            function clearError(element, errorDiv) {
                element.classList.remove('error');
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }

            // Validate email
            function validateEmail() {
                const email = emailInput.value.trim();
                if (!email) {
                    clearGeneralError();
                    showError(emailInput, emailError, 'Email address is required');
                    return false;
                }
                if (!email.includes('@') || !email.includes('.')) {
                    clearGeneralError();
                    showError(emailInput, emailError, 'Please enter a valid email address');
                    return false;
                }
                clearError(emailInput, emailError);
                return true;
            }

            // Validate password
            function validatePassword() {
                const password = passwordInput.value.trim();
                if (!password) {
                    clearGeneralError();
                    showError(passwordInput, passwordError, 'Password is required');
                    return false;
                }
                clearError(passwordInput, passwordError);
                return true;
            }

            // Real-time validation on input
            emailInput.addEventListener('input', function() {
                validateEmail();
                this.classList.add('has-value');
            });

            passwordInput.addEventListener('input', function() {
                validatePassword();
                this.classList.add('has-value');
            });

            // Floating label on page load
            document.querySelectorAll('.floating-group input').forEach(input => {
                if (input.value.trim() !== '') {
                    input.classList.add('has-value');
                }
            });

            // Toggle password visibility
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            // Add shake animation to CSS dynamically
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    20% { transform: translateX(-6px); }
                    40% { transform: translateX(6px); }
                    60% { transform: translateX(-3px); }
                    80% { transform: translateX(3px); }
                }
            `;
            document.head.appendChild(style);

            // show backend errors styling
            if (emailError && emailError.textContent.trim().length > 0) {
                emailError.style.display = 'block';
                emailInput.classList.add('error');
            }

            if (passwordError && passwordError.textContent.trim().length > 0) {
                passwordError.style.display = 'block';
                passwordInput.classList.add('error');
            }

            document.querySelector('.login-form').addEventListener('submit', function() {
                submitBtn.innerHTML = 'Signing in...';
                submitBtn.disabled = true;
            });
        })();
    </script>
</body>
</html>