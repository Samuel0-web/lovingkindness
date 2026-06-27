<?php
$pageTitle = 'Enroll Now | Loving Kindness Academy';
$pageDescription = 'Start your virtual learning journey. Book a free consultation or enroll directly in tutoring or teacher training.';
include 'inc/header.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<link rel="stylesheet" href="assets/css/enroll.css?<?php echo filemtime('assets/css/enroll.css'); ?>">

<section class="hero-section enroll-hero">
    <div class="hero-container">
        <div class="hero-content">
            <h1 class="hero-title">Start Your <span class="highlight-gradient">Journey</span></h1>
            <p class="hero-description">Join our global classroom from anywhere. Flexible scheduling, personalized curriculum, and expert educators.</p>
        </div>
    </div>
</section>

<section class="enroll-section">
    <div class="enroll-container">
        <div class="enroll-form-card">
            <h3 class="enroll-form-title">Enrollment Form</h3>

            <form action="api/submit-form" method="POST" class="enroll-form" id="enrollmentForm">
                <input type="text" name="website" hidden>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="form_type" value="enrollment">

                <!-- Program Selection (moved to top) -->
                <div class="form-group">
                    <label for="program">Select Program *</label>
                    <div class="custom-select-wrapper">
                        <select id="program" name="program" required class="custom-select">
                            <option value="" disabled selected>Choose a program</option>
                            <option value="tutoring">K-6 Tutoring</option>
                            <option value="teacher_training">Teacher Training Program</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <!-- Name Field (label changes based on program) -->
                <div class="form-group">
                    <label for="full_name" id="fullNameLabel">Parent/Guardian Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="+234 800 000 0000" required>
                </div>

                <!-- Tutoring‑only fields (hidden by default) -->
                <div id="tutoringFields" class="dynamic-fields" style="display: none;">
                    <div class="form-group">
                        <label for="student_name">Student's Name *</label>
                        <input type="text" id="student_name" name="student_name" placeholder="Full name">
                    </div>

                    <div class="form-group">
                        <label for="grade">Grade Level *</label>
                        <div class="custom-select-wrapper">
                            <select id="grade" name="grade" class="custom-select">
                                <option value="" disabled selected>Select Grade Level</option>
                                <option>Reception</option>
                                <option>Grade 1</option>
                                <option>Grade 2</option>
                                <option>Grade 3</option>
                                <option>Grade 4</option>
                                <option>Grade 5</option>
                                <option>Grade 6</option>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="subject">Preferred Subject *</label>
                        <div class="custom-select-wrapper">
                            <select id="subject" name="subject" class="custom-select">
                                <option value="" disabled selected>Select Subject</option>
                                <option>Literacy / Phonics</option>
                                <option>Mathematics</option>
                                <option>Science</option>
                                <option>General Tutoring</option>
                                <option>All Subjects</option>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                </div>

                <!-- Common fields (always visible) -->
                <div class="form-group">
                    <label for="preferred_time">Preferred Time *</label>
                    <div class="custom-select-wrapper">
                        <select id="preferred_time" name="preferred_time" required class="custom-select">
                            <option value="" disabled selected>Select Time</option>
                            <option>Morning (8am - 12pm)</option>
                            <option>Afternoon (12pm - 4pm)</option>
                            <option>Evening (4pm - 8pm)</option>
                            <option>Weekend (Flexible)</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                    <small class="helper-text">Choose a time that works best in your local timezone</small>
                </div>

                <div class="form-group">
                    <label for="country">Country *</label>
                    <div class="custom-select-wrapper">
                        <select id="country" name="country" required class="custom-select">
                            <option value="" disabled selected>Select Country</option>
                            <option>Nigeria</option>
                            <option>United Kingdom</option>
                            <option>United States</option>
                            <option>Canada</option>
                            <option>Australia</option>
                            <option>Other</option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="additional_info">Additional Information (Optional)</label>
                    <textarea id="additional_info" name="additional_info" rows="3" placeholder="Specific goals, learning challenges, or questions..."></textarea>
                </div>

                <div class="timezone-note">
                    <i class="fas fa-globe"></i> All sessions are scheduled based on your local time zone.
                </div>

                <button type="submit" class="btn-primary btn-submit" id="submitBtn" disabled>
                    <i class="fas fa-calendar-alt"></i> Submit Enrollment
                </button>
            </form>

            <p class="consultation-note">
                <i class="fas fa-clock"></i> We'll contact you within 24 hours to schedule a free consultation.
            </p>
        </div>

        <div class="enroll-sidebar">
            <i class="fas fa-video sidebar-icon"></i>
            <h3 class="sidebar-title">Why Enroll Today?</h3>
            <ul class="benefits-list">
                <li><i class="fas fa-check-circle"></i> 1-on-1 live sessions</li>
                <li><i class="fas fa-check-circle"></i> Global curriculum experts</li>
                <li><i class="fas fa-check-circle"></i> Flexible payment plans</li>
                <li><i class="fas fa-check-circle"></i> Free trial lesson available</li>
                <li><i class="fas fa-check-circle"></i> Multi-timezone scheduling</li>
            </ul>
            <a href="https://wa.me/2349122299653" target="_blank" class="btn-whatsapp-sidebar">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const programSelect = document.getElementById('program');
    const tutoringFields = document.getElementById('tutoringFields');
    const studentName = document.getElementById('student_name');
    const grade = document.getElementById('grade');
    const subject = document.getElementById('subject');
    const preferredTime = document.getElementById('preferred_time');
    const country = document.getElementById('country');
    const fullName = document.getElementById('full_name');
    const fullNameLabel = document.getElementById('fullNameLabel');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const submitBtn = document.getElementById('submitBtn');

    function checkFormValidity() {
        const program = programSelect.value;
        const hasProgram = program !== '';
        const hasPreferredTime = preferredTime.value !== '';
        const hasCountry = country.value !== '';
        const hasNameInfo = fullName.value.trim() !== '' && email.value.trim() !== '' && phone.value.trim() !== '';
        
        let isValid = hasProgram && hasPreferredTime && hasCountry && hasNameInfo;
        
        if (program === 'tutoring') {
            const hasStudentName = studentName.value.trim() !== '';
            const hasGrade = grade.value !== '';
            const hasSubject = subject.value !== '';
            isValid = isValid && hasStudentName && hasGrade && hasSubject;
        }
        
        submitBtn.disabled = !isValid;
        submitBtn.style.opacity = isValid ? '1' : '0.6';
        submitBtn.style.cursor = isValid ? 'pointer' : 'not-allowed';
    }

    function updateNameFieldLabel() {
        const selectedProgram = programSelect.value;
        
        if (selectedProgram === 'tutoring') {
            fullNameLabel.innerHTML = 'Parent/Guardian Full Name *';
            fullName.placeholder = 'Enter parent/guardian full name';
        } else if (selectedProgram === 'teacher_training') {
            fullNameLabel.innerHTML = 'Full Name *';
            fullName.placeholder = 'Enter your full name';
        } else {
            fullNameLabel.innerHTML = 'Parent/Guardian Full Name *';
            fullName.placeholder = 'Enter full name';
        }
    }

    function toggleTutoringFields() {
        const selectedProgram = programSelect.value;
        
        if (selectedProgram === 'tutoring') {
            tutoringFields.style.display = 'block';
            studentName.required = true;
            grade.required = true;
            subject.required = true;
            grade.setAttribute('required', 'required');
            subject.setAttribute('required', 'required');
        } else if (selectedProgram === 'teacher_training') {
            tutoringFields.style.display = 'none';
            studentName.required = false;
            grade.required = false;
            subject.required = false;
            grade.removeAttribute('required');
            subject.removeAttribute('required');
            
            // Clear values
            studentName.value = '';
            grade.value = '';
            subject.value = '';
        }
        
        updateNameFieldLabel();
        checkFormValidity();
    }

    programSelect.addEventListener('change', toggleTutoringFields);
    fullName.addEventListener('input', checkFormValidity);
    email.addEventListener('input', checkFormValidity);
    phone.addEventListener('input', checkFormValidity);
    preferredTime.addEventListener('change', checkFormValidity);
    country.addEventListener('change', checkFormValidity);
    
    if (studentName) studentName.addEventListener('input', checkFormValidity);
    if (grade) grade.addEventListener('change', checkFormValidity);
    if (subject) subject.addEventListener('change', checkFormValidity);
    
    // Initialize
    toggleTutoringFields();

    function showToast(message, type = 'success') {

        const toastContainer = document.getElementById('toastContainer');

        const toast = document.createElement('div');

        toast.className = `toast toast-${type}`;

        let icon = 'fa-circle-check';

        if (type === 'error') {
            icon = 'fa-circle-xmark';
        }

        if (type === 'warning') {
            icon = 'fa-triangle-exclamation';
        }

        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastOut 0.25s ease forwards';

            setTimeout(() => {
                toast.remove();
            }, 250);

        }, 3500);
    }
    
    const form = document.getElementById('enrollmentForm');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (submitBtn.disabled) {
            showToast('Please fill in all required fields before submitting.', 'warning');
            return;
        }

        const originalBtnHTML = submitBtn.innerHTML;

        /*
        |--------------------------------------------------------------------------
        | Loading State
        |--------------------------------------------------------------------------
        */

        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            Submitting...
        `;

        let submissionSuccessful = false;

        try {

            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            let data;

            try {
                data = await response.json();
            } catch {

                const rawResponse = await response.text();

                console.error('Raw server response:', rawResponse);

                throw new Error('Invalid server response');
            }

            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            if (data.success) {

                submitBtn.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    Submitted Successfully
                `;

                submissionSuccessful = true;

                showToast(
                    data.message || 'Enrollment submitted successfully.',
                    'success'
                );

                setTimeout(() => {
                    window.location.href = data.redirect || 'success';
                }, 1500);
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | API Error
            |--------------------------------------------------------------------------
            */

            showToast(data.message || 'Something went wrong.', 'error');

        } catch (error) {

            console.error(error);
            showToast('Network error. Please try again.', 'error');
            

        } finally {

            /*
            |--------------------------------------------------------------------------
            | Reset Button
            |--------------------------------------------------------------------------
            */

            if (!submissionSuccessful) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHTML;
            }
        }
    });
});
</script>

<?php include 'inc/footer.php'; ?>