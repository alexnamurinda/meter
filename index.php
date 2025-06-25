<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <title>Smart Meter Project - FastNet Solutions Company</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- AOS Animations Library -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/mainstyle.css">
</head>

<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="loader"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <img src="images/logo.png" alt="FastNet Solutions Logo" class="logo-image">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#team">Our Team</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact-section">Contact</a>
                    </li>
                </ul>
                <!-- <div class="nav-cta d-none d-lg-block">
                    <a href="#contact-section" class="btn btn-primary">Get Started</a>
                </div> -->
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-bg"></div>
        <div class="container h-100">
            <div class="row h-100 align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content" data-aos="fade-right">
                        <h1 id="hero-heading"></h1>
                        <p>Track power usage, get alerts on low balances, and control your energy consumption with ease</p>
                        <div class="hero-cta">
                            <a href="pages/login.php" class="btn btn-primary" target="_blank">Get started</a>
                            <a href="#how-it-works" class="btn btn-outline">Learn More</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-image" data-aos="fade-left">
                        <img src="images/smartswitch.jpg" alt="Smart Meter" class="img-fluid rounded-4 shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="feature-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h3>Accurate Metering</h3>
                        <p>Precise energy tracking for fair billing</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Payments</h3>
                        <p>Pay bills easily through your phone</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Usage Analytics</h3>
                        <p>Monitor and optimize your consumption</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services section-padding" id="services">
        <div class="container">
            <div class="section-header text-center mb-5">
                <!-- <span class="section-subtitle">What We Offer</span> -->
                <h2 class="section-title">Key Project Features</h2>
                <div class="section-divider"></div>
                <!-- <p class="section-description">Innovative solutions for efficient energy management</p> -->
            </div>
            <div class="row">
                <!-- Service Card 1 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="service-card-image">
                            <img src="images/smartmeter.jpeg" alt="Smart Meter Image" class="img-fluid">
                            <div class="overlay"></div>
                        </div>
                        <div class="service-card-body">
                            <div class="service-icon">
                                <i class='bx bx-chip'></i>
                            </div>
                            <h3>Smart Energy Metering</h3>
                            <p>Our Smart Meter solution provides individual energy tracking within multi-user buildings. Each resident's power usage is measured and billed separately, ensuring fair and accurate billing across shared spaces.</p>
                            <a href="#contact-section" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Service Card 2 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-card-image">
                            <img src="images/payer.png" alt="Payment Systems" class="img-fluid">
                            <div class="overlay"></div>
                        </div>
                        <div class="service-card-body">
                            <div class="service-icon">
                                <i class='bx bx-credit-card'></i>
                            </div>
                            <h3>Automatic Payment & Disconnection</h3>
                            <p>With our integrated mobile payment options, users can conveniently pay for energy usage via mobile money. The system ensures continuous service by automatically disconnecting users with insufficient balances until payments are made.</p>
                            <a href="#contact-section" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>

                <!-- Service Card 3 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-card-image">
                            <img src="images/mornitoring.png" alt="Usage Monitoring" class="img-fluid">
                            <div class="overlay"></div>
                        </div>
                        <div class="service-card-body">
                            <div class="service-icon">
                                <i class='bx bx-line-chart'></i>
                            </div>
                            <h3>Real-Time Usage Monitoring</h3>
                            <p>Track and manage energy consumption in real time. Our system allows users to monitor their energy usage through a user-friendly online platform, providing insights that help optimize consumption and reduce unnecessary costs.</p>
                            <a href="#contact-section" class="service-link">Learn More <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about section-padding" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-lg-1 order-2">
                    <div class="about-content" data-aos="zoom-in">
                        <span class="section-subtitle">About Us</span>
                        <h2 class="section-title">Innovative Solutions for Energy Management</h2>
                        <div class="section-divider"></div>
                        <p>FastNet Solutions is a pioneering technology company dedicated to creating innovative solutions that address pressing societal challenges. Our projects span across multiple sectors, including energy, agriculture, healthcare, and ICT, with a shared vision of empowering communities and transforming lives.</p>

                        <div class="mission-vision-tabs">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="mission-tab" data-bs-toggle="tab" data-bs-target="#mission" type="button" role="tab" aria-controls="mission" aria-selected="true">Our Mission</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="vision-tab" data-bs-toggle="tab" data-bs-target="#vision" type="button" role="tab" aria-controls="vision" aria-selected="false">Our Vision</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="mission" role="tabpanel" aria-labelledby="mission-tab">
                                    <div class="mission-vision-content">
                                        <img src="images/mission_icon.png" alt="Mission Icon" class="mission-vision-icon">
                                        <p>To develop sustainable, technology-driven solutions that improve resource efficiency, promote equitable access to essential services, and empower individuals to thrive in shared spaces.</p>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="vision" role="tabpanel" aria-labelledby="vision-tab">
                                    <div class="mission-vision-content">
                                        <img src="images/vision_icon.png" alt="Vision Icon" class="mission-vision-icon">
                                        <p>To be a leader in innovative technology solutions that simplify complex systems, enhance quality of life, and foster sustainable development across sectors and communities.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="about-stats">
                            <div class="row">
                                <div class="col-6 col-md-3">
                                    <div class="stat-item">
                                        <h3>500+</h3>
                                        <p>Clients</p>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="stat-item">
                                        <h3>20+</h3>
                                        <p>Projects</p>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="stat-item">
                                        <h3>98%</h3>
                                        <p>Satisfaction</p>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="stat-item">
                                        <h3>24/7</h3>
                                        <p>Support</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 order-lg-2 order-1 mb-5 mb-lg-0">
                    <div class="about-image" data-aos="zoom-in">
                        <img src="images/rep.png" alt="Smart Meter" class="img-fluid main-img rounded-4 shadow-lg">
                        <div class="experience-badge">
                            <span class="years">5+</span>
                            <span class="text">Years of Experience</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works section-padding" id="how-it-works">
        <div class="container">
            <div class="section-header text-center mb-5">
                <!-- <span class="section-subtitle">Process</span> -->
                <h2 class="section-title">How It Works</h2>
                <div class="section-divider"></div>
                <p class="section-description">Simple steps to manage your energy consumption</p>
            </div>

            <div class="timeline">
                <div class="timeline-item" data-aos="zoom-in">
                    <div class="timeline-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="timeline-content">
                        <span class="step-number">01</span>
                        <h3>Hardware Installation and Sign up</h3>
                        <p>The Smart Meter is installed at your building to track energy usage for all users. Each user then registers by providing their mobile number to create an account.</p>
                    </div>
                </div>

                <div class="timeline-item" data-aos="zoom-in">
                    <div class="timeline-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="timeline-content">
                        <span class="step-number">02</span>
                        <h3>Real-Time Energy Monitoring</h3>
                        <p>The system continuously tracks each user's energy consumption and provides real-time data to monitor usage and costs via a central online platform.</p>
                    </div>
                </div>

                <div class="timeline-item" data-aos="zoom-in">
                    <div class="timeline-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="timeline-content">
                        <span class="step-number">03</span>
                        <h3>Mobile Payments</h3>
                        <p>Users can easily make payments for their energy consumption through mobile money. They will also receive alerts when their balance is running low.</p>
                    </div>
                </div>

                <div class="timeline-item" data-aos="zoom-in">
                    <div class="timeline-icon">
                        <i class="fas fa-plug"></i>
                    </div>
                    <div class="timeline-content">
                        <span class="step-number">04</span>
                        <h3>Automatic Disconnection & Reconnection</h3>
                        <p>When a user's balance reaches zero, the system automatically disconnects the energy supply. Upon payment, the energy is reconnected, ensuring continuous service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section section-padding" id="team">
        <div class="container">
            <div class="section-header text-center mb-5">
                <!-- <span class="section-subtitle">Our Experts</span> -->
                <h2 class="section-title">Meet Our Team</h2>
                <div class="section-divider"></div>
                <p class="section-description">The minds behind the success of advanced smart meter project</p>
            </div>

            <div class="row">
                <!-- Team Member 1 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="team-card-image">
                            <img src="images/supervisor.jpeg" alt="Dr. Mayur Kuma Chhiper" class="img-fluid">
                            <div class="social-overlay">
                                <a href="#" class="social-icon"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                        <div class="team-card-content">
                            <h3>Dr. Mayur Kuma Chhipa</h3>
                            <p>Supervisor</p>
                        </div>
                    </div>
                </div>

                <!-- Team Member 2 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="team-card-image">
                            <img src="images/developer.jpg" alt="Namurinda Alex" class="img-fluid">
                            <div class="social-overlay">
                                <a href="https://www.linkedin.com/in/namurinda-alex-25217a255/" target="_blank" class="social-icon"><i class="fab fa-linkedin"></i></a>
                                <a href="#" target="_blank" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="https://github.com/namurindaalex" target="_blank" class="social-icon"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                        <div class="team-card-content">
                            <h3>Namurinda Alex</h3>
                            <p>Student Innovator</p>
                        </div>
                    </div>
                </div>

                <!-- Team Member 3 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="team-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="team-card-image">
                            <img src="images/profile_pic.png" alt="Ahaisibwe William" class="img-fluid">
                        </div>
                        <div class="team-card-content">
                            <h3>FastNet solutions company</h3>
                            <p>Sponsor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials section-padding">
        <div class="container">
            <div class="section-header text-center mb-5">
                <!-- <span class="section-subtitle">Testimonials</span> -->
                <h2 class="section-title">What Our Clients Say</h2>
                <div class="section-divider"></div>
                <!-- <p class="section-description">Feedback from our valued customers</p> -->
            </div>

            <div class="testimonial-slider">
                <div class="row">
                    <div class="col-md-4">
                        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                            <div class="testimonial-icon">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <p class="testimonial-text">The Smart Meter solution has completely transformed how we manage energy in our apartment complex. Billing is now fair and transparent.</p>
                            <div class="testimonial-author">
                                <div class="author-image">
                                    <img src="images/profile_pic.png" alt="John Smith" class="img-fluid">
                                </div>
                                <div class="author-info">
                                    <h4>John Smith</h4>
                                    <p>Property Manager</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                            <div class="testimonial-icon">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <p class="testimonial-text">I love how I can monitor my electricity usage in real-time and make payments through my phone. It's so convenient and helps me budget better.</p>
                            <div class="testimonial-author">
                                <div class="author-image">
                                    <img src="images/sara.jpg" alt="Sarah Johnson" class="img-fluid">
                                </div>
                                <div class="author-info">
                                    <h4>Sarah Pretty</h4>
                                    <p>Apartment Resident</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                            <div class="testimonial-icon">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <p class="testimonial-text">As a landlord, this system has eliminated disputes about electricity bills. The automatic disconnection feature ensures I always get paid on time.</p>
                            <div class="testimonial-author">
                                <div class="author-image">
                                    <img src="images/micheal.jpg" alt="Robert Davis" class="img-fluid">
                                </div>
                                <div class="author-info">
                                    <h4>Robert Michelle</h4>
                                    <p>Building Owner</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq section-padding">
        <div class="container">
            <div class="section-header text-center mb-5">
                <!-- <span class="section-subtitle">FAQ</span> -->
                <h2 class="section-title">Frequently Asked Questions</h2>
                <div class="section-divider"></div>
                <!-- <p class="section-description">Answers to common questions about our services</p> -->
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ Item 1 -->
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="100">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How does the Smart Meter system work?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Our Smart Meter system works by installing individual meters in multi-user buildings. Each user's energy consumption is tracked separately, ensuring fair billing. The system includes mobile payment integration, real-time monitoring through an online platform, and automatic disconnection/reconnection based on account balance.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="200">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What payment methods are supported?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We support various mobile money payment options including MTN Mobile Money, Airtel Money, and other popular local payment platforms. This allows users to conveniently pay for their energy consumption directly from their phones.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 3 -->
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="300">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    How are alerts and notifications sent to users?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Users receive SMS notifications for low balance alerts, payment confirmations, and usage reports. Our system automatically sends notifications when account balances fall below a certain threshold, allowing users to top up before disconnection occurs.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 4 -->
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="400">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Is there a backup system in case of power outages?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, our Smart Meters are equipped with backup batteries that allow them to continue functioning during power outages. The system also stores usage data locally, which is synchronized with the central server once power is restored.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section section-padding" id="contact-section">
        <div class="container">
            <div class="section-header text-center mb-5">
                <!-- <span class="section-subtitle">Get In Touch</span> -->
                <h2 class="section-title">Contact Us</h2>
                <div class="section-divider"></div>
                <p class="section-description">Have questions? We'd love to hear from you</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <div class="contact-info-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Our Location</h3>
                        <p>Lugogo Bypass, Plot 145</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <div class="contact-info-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Us</h3>
                        <p><a href="mailto:koozaelectric@gmail.com">namurindaalex43@gmail.com</a></p>
                    </div>
                </div>

                <!-- <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <div class="contact-info-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h3>Call Us</h3>
                        <p><a href="tel:+256780393671">+256 780 393 671</a></p>
                    </div>
                </div> -->

                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <div class="contact-info-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="contact-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h3>WhatsApp</h3>
                        <p><a href="https://wa.me/256744766410" target="_blank">+256 744 766 410</a></p>
                    </div>
                </div>

            </div>

            <div class="row mt-5">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="contact-form-wrapper" data-aos="zoom-in" style="text-align: center;">
                        <h3>Send Us a Message</h3>
                        <form class="contact-form" action="index.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <input type="text" class="form-control" name="name" placeholder="Your Name" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <input type="email" class="form-control" name="email" placeholder="Your Email" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <input type="text" class="form-control" name="subject" placeholder="Subject" required>
                            </div>
                            <div class="form-group mb-4">
                                <textarea class="form-control" name="message" rows="5" placeholder="Your Message" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>

                        <!-- Notification message container -->
                        <!-- Global Notification Container -->
                        <div class="notification-container"></div>

                    </div>
                </div>


                <div class="col-lg-6">
                    <div class="contact-map-wrapper" data-aos="zoom-in">
                        <h3>View map</h3>
                        <div class="contact-map">
                            <iframe
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7441375042476!2d32.6123367748952!3d0.31978206307815095!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177dbc8643e5db1b%3A0xddf437982d3eae3d!2sLugogo%20Bypass%2C%20Kampala!5e0!3m2!1sen!2sug!4v1718648672283!5m2!1sen!2sug"
                                width="100%"
                                height="350"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content" data-aos="zoom-in">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <h2>Subscribe to Our Newsletter</h2>
                        <p>Stay updated with our latest news and offers</p>
                    </div>
                    <div class="col-lg-6">
                        <form class="newsletter-form">
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="Enter your email" required>
                                <button class="btn btn-primary" type="submit">Subscribe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row footer-content">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <div class="footer-about">
                        <div class="footer-logo">
                            <img src="images/logo.png" alt="FastNet Solutions Logo" class="img-fluid">
                        </div>
                        <p>Protecting your bills through technology. FastNet Solutions provides innovative solutions for efficient energy management in shared spaces.</p>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h3>Quick Links</h3>
                        <ul class="list-unstyled">
                            <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                            <li><a href="#services"><i class="fas fa-chevron-right"></i> Services</a></li>
                            <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                            <li><a href="#how-it-works"><i class="fas fa-chevron-right"></i> How It Works</a></li>
                            <li><a href="#team"><i class="fas fa-chevron-right"></i> Our Team</a></li>
                            <li><a href="#contact-section"><i class="fas fa-chevron-right"></i> Contact</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <div class="footer-links">
                        <h3>Our Services</h3>
                        <ul class="list-unstyled">
                            <li><a href="#services"><i class="fas fa-chevron-right"></i> Smart Energy Metering</a></li>
                            <li><a href="#services"><i class="fas fa-chevron-right"></i> Automatic Payment Systems</a></li>
                            <li><a href="#services"><i class="fas fa-chevron-right"></i> Real-Time Usage Monitoring</a></li>
                            <li><a href="#services"><i class="fas fa-chevron-right"></i> Energy Consumption Analytics</a></li>
                            <li><a href="#services"><i class="fas fa-chevron-right"></i> Automated Alerts & Notifications</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <div class="footer-contact">
                        <h3>Contact Us</h3>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-map-marker-alt"></i> Lugogo Bypass, Plot 145</li><br>
                            <li><i class="fas fa-phone-alt"></i> <a href="tel:+256780393671">+256 744 766 410</a></li><br>
                            <li><i class="fas fa-envelope"></i> <a href="mailto:namurindaalex43@gmail.com">namurindaalex43@gmail.com</a></li><br>
                            <li><i class="fas fa-clock"></i> Monday - Friday: 9AM - 5PM</li><br>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="copyright">© 2025 FastNet Solutions. All Rights Reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="footer-links">
                            <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="back-to-top" title="Back to Top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- AOS Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <!-- Custom JavaScript -->
    <script src="scripts/mainscript.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", initCountdownTimer);
    </script> <!-- To be removed on launch -->

</body>

</html>