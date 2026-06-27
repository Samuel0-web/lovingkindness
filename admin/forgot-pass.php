<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, height=device-height">
    <title>Forgot Password | Loving Kindness Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/forgot-password.css">
</head>
<body>
    <div class="forgot-wrapper">
        <div class="forgot-container">
            <div class="forgot-box">
                <div class="forgot-header">
                    <a href="login" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <img src="../img/logo.jpg" alt="Loving Kindness Academy Logo" class="img-logo">
                    <h2>Forgot Password?</h2>
                    <p>No worries, we'll send you reset instructions</p>
                </div>

                <form class="forgot-form" id="forgotForm">
                    <div class="floating-group" id="emailGroup">
                        <input type="email" id="email" name="email" required autocomplete="off">
                        <label for="email">
                            <i class="fas fa-envelope"></i>
                            <span>Email Address</span>
                        </label>
                        <div class="error-message" id="emailError"></div>
                    </div>

                    <button type="submit" class="reset-button" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        Send Reset Instructions
                    </button>

                    <div class="success-message" id="successMessage">
                        <i class="fas fa-check-circle"></i>
                        <span>Reset instructions sent to your email</span>
                    </div>
                </form>

                <div class="forgot-footer">
                    <a href="login">
                        <i class="fas fa-sign-in-alt"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const emailInput = document.getElementById('email');
            const emailGroup = document.getElementById('emailGroup');
            const emailError = document.getElementById('emailError');
            const forgotForm = document.getElementById('forgotForm');
            const submitBtn = document.getElementById('submitBtn');
            const successMessage = document.getElementById('successMessage');

            // Demo email for testing
            const DEMO_EMAIL = 'admin@lovingkindnessacademy.org';

            function showError(element, errorDiv, message) {
                element.classList.add('error');
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }

            function clearError(element, errorDiv) {
                element.classList.remove('error');
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }

            function showSuccess() {
                successMessage.classList.add('show');
                setTimeout(() => {
                    successMessage.classList.remove('show');
                }, 5000);
            }

            function validateEmail() {
                const email = emailInput.value.trim();
                if (!email) {
                    showError(emailInput, emailError, 'Email address is required');
                    return false;
                }
                if (!email.includes('@') || !email.includes('.')) {
                    showError(emailInput, emailError, 'Please enter a valid email address');
                    return false;
                }
                clearError(emailInput, emailError);
                return true;
            }

            emailInput.addEventListener('input', function() {
                validateEmail();
                this.classList.add('has-value');
            });

            // Floating label on page load
            if (emailInput.value.trim() !== '') {
                emailInput.classList.add('has-value');
            }

            // Form submission
            forgotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const isValid = validateEmail();
                
                if (isValid) {
                    const email = emailInput.value.trim();
                    
                    // Show loading state
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Sending...';
                    submitBtn.disabled = true;
                    
                    // Simulate API call
                    setTimeout(() => {
                        if (email === DEMO_EMAIL) {
                            showSuccess();
                            emailInput.value = '';
                            emailInput.classList.remove('has-value');
                            clearError(emailInput, emailError);
                        } else {
                            showError(emailInput, emailError, 'No account found with this email address');
                        }
                        
                        // Reset button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        
                        // Shake animation for error
                        if (email !== DEMO_EMAIL) {
                            const forgotBox = document.querySelector('.forgot-box');
                            forgotBox.style.animation = 'shake 0.3s ease';
                            setTimeout(() => {
                                forgotBox.style.animation = '';
                            }, 300);
                        }
                    }, 1500);
                }
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
        })();
    </script>
</body>
</html>