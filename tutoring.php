<?php
$pageTitle = 'K-6 Online Tutoring | Loving Kindness Academy';
$pageDescription = 'Personalized literacy, math, and science tutoring for Reception to Grade 6. Global curriculum, experienced educators. Book a free consultation today.';
include 'inc/header.php';
?>
<link rel="stylesheet" href="assets/css/tutoring.css?<?php echo filemtime('assets/css/tutoring.css'); ?>">

<section class="hero-section tutoring-hero">
    <div class="hero-container">
        <div class="hero-content">
            <div class="hero-badge"><i class="fas fa-globe-americas"></i><span>1-on-1 Live Tutoring</span></div>
            <h1 class="hero-title">K-6 <span class="highlight-gradient">Tutoring</span></h1>
            <p class="hero-description">Personalized learning plans for students worldwide — from phonics mastery to creative problem solving. One-on-one live sessions with expert educators.</p>
            <div class="hero-buttons">
                <a href="enroll" class="btn-primary"><i class="fas fa-calendar-alt"></i> Book Free Consultation</a>
                <a href="#how-it-works" class="btn-secondary"><i class="fas fa-play-circle"></i> How It Works</a>
            </div>
            <p style="font-size: 0.85rem; color: var(--gray-600); margin-top: 0.5rem;">
                <i class="fas fa-clock"></i> We'll contact you within 24 hours to schedule your child's first session.
            </p>
            <div class="hero-stats">
                <div class="stat-item"><span class="stat-number">500+</span> <span>Students Tutored</span></div>
                <div class="stat-item"><span class="stat-number">98%</span> <span>Parent Satisfaction</span></div>
                <div class="stat-item"><span class="stat-number">30+</span> <span>Expert Tutors</span></div>
            </div>
        </div>
        <div class="hero-visual">
            <div class="image-frame"><img src="/assets/img/tutoring-hero.jpg" alt="Kids learning online" class="hero-portrait"></div>
            <div class="floating-card card-1"><i class="fas fa-chalkboard-user"></i> <span>Certified Tutors</span></div>
            <div class="floating-card card-2"><i class="fas fa-clock"></i> <span>Flexible Scheduling</span></div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Why Families Trust Us</span>
            <h2 class="section-title">The Loving Kindness Difference</h2>
            <p class="section-subtitle">What sets our tutoring program apart from the rest</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-user-graduate"></i></div>
                <h3>Qualified Educators</h3>
                <p>All tutors are certified teachers with years of classroom and online teaching experience.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-globe"></i></div>
                <h3>Global Curriculum</h3>
                <p>Aligned with UK National Curriculum, US Common Core, Canadian standards, and Nigerian curriculum.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Flexible Scheduling</h3>
                <p>Learn at your own pace with sessions available across all time zones.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Progress Tracking</h3>
                <p>Regular assessments and detailed reports to monitor your child's growth.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section - UPDATED -->
<section id="how-it-works" class="how-it-works-tutoring">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Simple Process</span>
            <h2 class="section-title">How Tutoring Works</h2>
            <p class="section-subtitle">Get started in three easy steps</p>
        </div>
        <div class="steps-container">
            <div class="step-item">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="fas fa-pen-alt"></i></div>
                <h3>Request a Session</h3>
                <p>Fill out our quick form and tell us about your child's needs, goals, and learning style.</p>
            </div>
            <div class="step-divider"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="fas fa-phone-alt"></i></div>
                <h3>We Contact You</h3>
                <p>Our team reaches out within 24 hours to discuss your child's needs and match you with the perfect tutor.</p>
            </div>
            <div class="step-divider"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="fas fa-laptop-house"></i></div>
                <h3>Start Learning</h3>
                <p>Begin your child's learning journey with engaging 1-on-1 sessions and watch them thrive.</p>
            </div>
        </div>
        <div class="steps-cta">
            <a href="enroll" class="btn-primary"><i class="fas fa-calendar-alt"></i> Request a Session</a>
        </div>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--gray-600);">
            <i class="fas fa-clock"></i> We'll contact you within 24 hours to schedule your child's first session.
        </p>
    </div>
</section>

<!-- Subject Areas Section -->
<section class="subject-areas">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Comprehensive Coverage</span>
            <h2 class="section-title">What We Teach</h2>
            <p class="section-subtitle">Full curriculum coverage across all core subjects</p>
        </div>
        <div class="subjects-grid">
            <div class="subject-card">
                <div class="subject-icon"><i class="fas fa-book-reader"></i></div>
                <h3>Literacy & Reading</h3>
                <ul>
                    <li>Phonics & Phonemic Awareness</li>
                    <li>Reading Comprehension</li>
                    <li>Vocabulary Development</li>
                    <li>Creative Writing</li>
                    <li>Grammar & Spelling</li>
                </ul>
            </div>
            <div class="subject-card">
                <div class="subject-icon"><i class="fas fa-calculator"></i></div>
                <h3>Mathematics</h3>
                <ul>
                    <li>Number Sense & Operations</li>
                    <li>Fractions & Decimals</li>
                    <li>Geometry & Measurement</li>
                    <li>Problem Solving</li>
                    <li>Algebra Readiness</li>
                </ul>
            </div>
            <div class="subject-card">
                <div class="subject-icon"><i class="fas fa-flask"></i></div>
                <h3>Science</h3>
                <ul>
                    <li>Life Science & Biology</li>
                    <li>Physical Science</li>
                    <li>Earth & Space Science</li>
                    <li>STEM Experiments</li>
                    <li>Scientific Method</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-tutoring">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Success Stories</span>
            <h2 class="section-title">What Parents Say</h2>
            <p class="section-subtitle">Real results from families around the world</p>
        </div>
        <div class="testimonials-grid-tutoring">
            <div class="testimonial-tutoring-card">
                <div class="testimonial-rating">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p>"My daughter struggled with reading for years. Within 3 months, she went from below grade level to reading confidently. The personalized attention made all the difference!"</p>
                <div class="testimonial-parent">
                    <strong>Sarah O.</strong>
                    <span>Parent, United Kingdom</span>
                </div>
            </div>
            <div class="testimonial-tutoring-card">
                <div class="testimonial-rating">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p>"Flexible scheduling across time zones was essential for our family. The tutors are patient, engaging, and truly care about my son's progress. Highly recommend!"</p>
                <div class="testimonial-parent">
                    <strong>Michael T.</strong>
                    <span>Parent, USA</span>
                </div>
            </div>
            <div class="testimonial-tutoring-card">
                <div class="testimonial-rating">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p>"The Singapore math approach really clicked for my son. His math scores improved dramatically, and he actually enjoys math now. Worth every penny!"</p>
                <div class="testimonial-parent">
                    <strong>Chioma A.</strong>
                    <span>Parent, Nigeria</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing & Packages Section - UPDATED -->
<section class="pricing-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Investment in Education</span>
            <h2 class="section-title">Flexible Plans for Every Family</h2>
            <p class="section-subtitle">Choose the package that works best for your child's needs</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card">
                <div class="pricing-badge">Popular</div>
                <h3>Starter Package</h3>
                <div class="pricing-price">$49<span>/session</span></div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> 1-on-1 live sessions</li>
                    <li><i class="fas fa-check"></i> 4 sessions per month</li>
                    <li><i class="fas fa-check"></i> Monthly progress report</li>
                    <li><i class="fas fa-check"></i> Flexible rescheduling</li>
                </ul>
                <a href="enroll" class="btn-secondary">Request a Session</a>
            </div>
            <div class="pricing-card featured">
                <div class="pricing-badge">Best Value</div>
                <h3>Growth Package</h3>
                <div class="pricing-price">$179<span>/month</span></div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> 8 sessions per month</li>
                    <li><i class="fas fa-check"></i> 1-on-1 live sessions</li>
                    <li><i class="fas fa-check"></i> Weekly progress reports</li>
                    <li><i class="fas fa-check"></i> Priority scheduling</li>
                    <li><i class="fas fa-check"></i> Free learning materials</li>
                </ul>
                <a href="enroll" class="btn-primary">Book Free Consultation</a>
            </div>
            <div class="pricing-card">
                <div class="pricing-badge">Premium</div>
                <h3>Accelerated Package</h3>
                <div class="pricing-price">$349<span>/month</span></div>
                <ul class="pricing-features">
                    <li><i class="fas fa-check"></i> 16 sessions per month</li>
                    <li><i class="fas fa-check"></i> 1-on-1 live sessions</li>
                    <li><i class="fas fa-check"></i> Detailed progress analytics</li>
                    <li><i class="fas fa-check"></i> Priority scheduling</li>
                    <li><i class="fas fa-check"></i> Free learning materials</li>
                    <li><i class="fas fa-check"></i> Monthly parent consultations</li>
                </ul>
                <a href="enroll" class="btn-secondary">Request a Session</a>
            </div>
        </div>
        <p class="pricing-note">
            <i class="fas fa-info-circle"></i> Prices may vary based on location and learning needs. Final plan is confirmed after consultation.
        </p>
        <p class="pricing-note" style="margin-top: 0.5rem;">
            * All plans include a free trial session. Custom packages available upon request.
        </p>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-tutoring">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Common Questions</span>
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Everything you need to know about our tutoring program</p>
        </div>
        <div class="faq-grid-tutoring">
            <div class="faq-item-tutoring">
                <div class="faq-question-tutoring">
                    <i class="fas fa-question-circle"></i>
                    <h3>What age groups do you tutor?</h3>
                </div>
                <div class="faq-answer-tutoring">
                    <p>We specialize in Reception through Grade 6 (ages 4-12), covering all core subjects including literacy, mathematics, and science.</p>
                </div>
            </div>
            <div class="faq-item-tutoring">
                <div class="faq-question-tutoring">
                    <i class="fas fa-question-circle"></i>
                    <h3>How long are tutoring sessions?</h3>
                </div>
                <div class="faq-answer-tutoring">
                    <p>Sessions are typically 45-60 minutes, optimized for maximum engagement and learning retention for young learners.</p>
                </div>
            </div>
            <div class="faq-item-tutoring">
                <div class="faq-question-tutoring">
                    <i class="fas fa-question-circle"></i>
                    <h3>What technology do I need?</h3>
                </div>
                <div class="faq-answer-tutoring">
                    <p>A computer or tablet with a stable internet connection, webcam, and microphone. We'll provide all learning materials digitally.</p>
                </div>
            </div>
            <div class="faq-item-tutoring">
                <div class="faq-question-tutoring">
                    <i class="fas fa-question-circle"></i>
                    <h3>How does pricing work?</h3>
                </div>
                <div class="faq-answer-tutoring">
                    <p>Pricing is confirmed during your free consultation based on your child's needs, location, and package preference. We offer flexible payment options.</p>
                </div>
            </div>
        </div>
        <div class="faq-more-tutoring">
            <a href="contact" class="btn-secondary">Still have questions? Contact us →</a>
        </div>
    </div>
</section>

<!-- Final CTA Section - UPDATED -->
<section class="final-cta">
    <div class="container">
        <div class="cta-content">
            <i class="fas fa-graduation-cap cta-icon"></i>
            <h2>Ready to Transform Your Child's Learning Journey?</h2>
            <p>Join hundreds of satisfied families who have seen remarkable academic progress with our personalized tutoring approach.</p>
            <div class="cta-buttons">
                <a href="enroll" class="btn-primary"><i class="fas fa-calendar-alt"></i> Book Free Consultation</a>
                <a href="https://wa.me/2349122299653" class="btn-secondary-outline" target="_blank">
                    <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                </a>
            </div>
            <p class="cta-note">No credit card required for consultation. We'll contact you within 24 hours.</p>
        </div>
    </div>
</section>

<?php include 'inc/footer.php'; ?>