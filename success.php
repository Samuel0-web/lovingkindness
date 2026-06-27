<?php
$pageTitle = 'Enrollment Successful | Loving Kindness Academy';
$pageDescription = 'Thank you for enrolling. Your learning journey with Loving Kindness Academy begins soon.';
include 'inc/header.php';
?>
<link rel="stylesheet" href="assets/css/success.css?<?php echo filemtime('assets/css/success.css'); ?>">

<section class="success-section">
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="success-title">You're All Set!</h1>
            <p class="success-message">Thank you for enrolling. We've received your request and are excited to welcome you to the Loving Kindness Academy family.</p>
            
            <div class="success-timeline">
                <div class="timeline-step">
                    <div class="step-icon"><i class="fas fa-envelope"></i></div>
                    <div class="step-content">
                        <h4>Step 1: Confirmation Email</h4>
                        <p>Check your inbox for enrollment confirmation</p>
                    </div>
                </div>
                <div class="timeline-step">
                    <div class="step-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="step-content">
                        <h4>Step 2: Consultation Call</h4>
                        <p>We'll contact you within 24 hours</p>
                    </div>
                </div>
                <div class="timeline-step">
                    <div class="step-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="step-content">
                        <h4>Step 3: First Session</h4>
                        <p>Begin your learning journey</p>
                    </div>
                </div>
            </div>
            
            <div class="success-actions">
                <a href="https://wa.me/2349122299653?text=Hello%20I%20just%20enrolled%20on%20your%20website%20and%20I'm%20excited%20to%20start%21" 
                   class="btn-whatsapp" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp"></i> Continue on WhatsApp
                </a>
                <a href="home" class="btn-home">
                    <i class="fas fa-home"></i> Back to Homepage
                </a>
            </div>
            
            <div class="success-note">
                <i class="fas fa-clock"></i>
                <p>Our team typically responds within 2-4 hours during business hours (Mon-Sat, 8am-6pm WAT).</p>
            </div>
        </div>
        
        <div class="success-sidebar">
            <div class="next-steps">
                <h3><i class="fas fa-star"></i> What's Next?</h3>
                <ul>
                    <li><i class="fas fa-check"></i> Review your email for details</li>
                    <li><i class="fas fa-check"></i> Prepare any questions for your consultation</li>
                    <li><i class="fas fa-check"></i> Check your time zone for scheduling</li>
                    <li><i class="fas fa-check"></i> Download our free learning resources</li>
                </ul>
            </div>
            
            <div class="resources">
                <h3><i class="fas fa-gift"></i> Free Resources</h3>
                <a href="#" class="resource-link">
                    <i class="fas fa-download"></i> Parent Guide to Online Learning
                </a>
                <a href="#" class="resource-link">
                    <i class="fas fa-download"></i> Getting Started Checklist
                </a>
            </div>
            
            <div class="contact-support">
                <h3><i class="fas fa-headset"></i> Need Help?</h3>
                <p>Questions? Reach out anytime:</p>
                <a href="https://wa.me/2349122299653" class="support-wa">
                    <i class="fab fa-whatsapp"></i> +234 912 229 9653
                </a>
                <a href="mailto:hello@lovingkindnessacademy.org" class="support-email">
                    <i class="fas fa-envelope"></i> hello@lovingkindnessacademy.org
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'inc/footer.php'; ?>