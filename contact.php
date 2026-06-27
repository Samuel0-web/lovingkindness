<?php
$pageTitle = 'Contact Us | Loving Kindness Academy';
$pageDescription = 'Reach out for tutoring inquiries, teacher training, or any questions. We respond within 24 hours.';
include 'inc/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<link rel="stylesheet" href="assets/css/contact.css?<?= filemtime('assets/css/contact.css') ?>">

<section class="hero-section contact-hero-section">
    <div class="hero-container">
        <div class="hero-content">
            <h1 class="hero-title">Contact <span class="highlight-gradient">Us</span></h1>
            <p class="hero-description">We'd love to hear from you. Send a message, and our team will get back to you promptly.</p>
        </div>
    </div>
</section>

<section class="contact-form-section">
    <div class="contact-form-container">
        <div class="contact-form-card">
            <h3 class="contact-form-title">Send a Message</h3>
            <form class="contact-form" id="contactForm">
                <input type="hidden" name="csrf_token"  value="<?= $_SESSION['csrf_token'] ?>">

                <div class="hp-field">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="name" class="form-label">Full Name *</label>
                    <input type="text" id="name" name="name" placeholder="Your full name" required maxlength="100" class="contact-input">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required maxlength="150" class="contact-input">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+234 XXX XXX XXXX" maxlength="30" class="contact-input">
                </div>
                
                <div class="form-group">
                    <label for="subject" class="form-label">Subject *</label>
                    <input type="text" id="subject" name="subject" placeholder="What is this regarding?" required maxlength="200" class="contact-input">
                </div>
                
                <div class="form-group">
                    <label for="inquiry_type" class="form-label">Inquiry Type *</label>
                    <select id="inquiry_type" name="inquiry_type" required class="contact-select">
                        <option value="" disabled selected>Select an option</option>
                        <option value="tutoring">K-6 Tutoring Inquiry</option>
                        <option value="teacher-training">Teacher Training Inquiry</option>
                        <option value="admissions">Admissions Inquiry</option>
                        <option value="technical">Technical Support</option>
                        <option value="feedback">Feedback & Suggestions</option>
                        <option value="general">General Inquiry</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label">Message *</label>

                    <textarea id="message" name="message" rows="5"
                        placeholder="Please provide details about your inquiry..."
                        required maxlength="5000" class="contact-textarea"
                    ></textarea>

                    <div class="message-meta">
                        <small id="messageCount">0 / 5000</small>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary contact-submit-btn" id="submitBtn">
                    <span class="btn-text"><i class="fas fa-paper-plane"></i> Send Message</span>
                    <span class="btn-loading" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>
                </button>
                
                <div class="form-message" id="formMessage" style="display: none;"></div>
                
                <div class="trust-badges">
                    <div class="trust-item">
                        <i class="fas fa-clock"></i>
                        <span>We typically respond within 24 hours</span>
                    </div>
                    <div class="trust-item">
                        <i class="fas fa-lock"></i>
                        <span>Your information is kept private and secure</span>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="contact-info-grid">
            <h3 class="contact-info-title">Reach Out Directly</h3>
            
            <div class="contact-cards">
                <div class="contact-mini-card">
                    <div class="contact-card-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-card-content">
                        <h4 class="contact-card-title">Phone Numbers</h4>
                        <p class="contact-card-detail">+234 912 229 9653</p>
                        <p class="contact-card-detail">+234 910 515 6232</p>
                    </div>
                </div>
                
                <div class="contact-mini-card">
                    <div class="contact-card-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-card-content">
                        <h4 class="contact-card-title">Email Address</h4>
                        <p class="contact-card-detail">hello@lovingkindnessacademy.org</p>
                    </div>
                </div>
                
                <div class="contact-mini-card">
                    <div class="contact-card-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="contact-card-content">
                        <h4 class="contact-card-title">WhatsApp</h4>
                        <p class="contact-card-detail">+234 912 229 9653</p>
                        <p class="contact-card-subtitle">Fastest response time</p>
                    </div>
                </div>
                
                <div class="contact-mini-card">
                    <div class="contact-card-icon">
                        <i class="far fa-clock"></i>
                    </div>
                    <div class="contact-card-content">
                        <h4 class="contact-card-title">Office Hours</h4>
                        <p class="contact-card-detail">Monday – Saturday</p>
                        <p class="contact-card-detail">8am – 6pm (WAT)</p>
                    </div>
                </div>
            </div>
            
            <div class="contact-whatsapp-cta">
                <a href="https://wa.me/2349122299653" class="whatsapp-btn" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-whatsapp"></i>
                    <span>Chat with us on WhatsApp</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
const messageInput = document.getElementById('message');
const messageCount = document.getElementById('messageCount');

messageInput.addEventListener('input', () => {

    const currentLength = messageInput.value.length;

    messageCount.textContent = `${currentLength} / 5000`;

});

document.getElementById('contactForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = this;

    const submitBtn = document.getElementById('submitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoading = submitBtn.querySelector('.btn-loading');
    const formMessage = document.getElementById('formMessage');

    submitBtn.disabled = true;

    btnText.style.display = 'none';
    btnLoading.style.display = 'inline-flex';

    formMessage.style.display = 'none';

    try {

        const formData = new FormData(form);

        const response = await fetch('api/contact-submit', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        formMessage.className = `form-message ${data.ok ? 'success' : 'error'}`;

        formMessage.innerHTML = `
            <i class="fas ${data.ok ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            ${data.message}
        `;

        formMessage.style.display = 'flex';

        if (data.ok) {

            form.reset();

            messageCount.textContent = '0 / 5000';

            const inquiryType = document.getElementById('inquiry_type');

            if (inquiryType) {
                inquiryType.selectedIndex = 0;
            }
        }

    } catch (error) {

        formMessage.className = 'form-message error';

        formMessage.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            Something went wrong. Please try again.
        `;

        formMessage.style.display = 'flex';

    } finally {

        submitBtn.disabled = false;

        btnText.style.display = 'inline-flex';
        btnLoading.style.display = 'none';
    }
});
</script>

<?php include 'inc/footer.php'; ?>