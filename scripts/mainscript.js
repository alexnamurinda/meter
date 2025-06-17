// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize AOS animation library
    AOS.init({
        duration: 800,
        once: false,
        offset: 100,
        easing: 'ease-in-out',
    });

    // Preloader
    const preloader = document.getElementById('preloader');
    if (preloader) {
        window.addEventListener('load', function () {
            preloader.style.opacity = '0';
            setTimeout(function () {
                preloader.style.display = 'none';
            }, 500);
        });
    }

    // Initialize countdown timer
    initCountdownTimer();

    // Navbar scroll effect
    initNavbarScroll();

    // Smooth scrolling for navigation links
    initSmoothScroll();

    // Activate the back to top button
    initBackToTop();

    // Hero heading typewriter effect
    initTypewriterEffect();

    // Form validation
    initFormValidation();

    // Mobile menu toggle
    initMobileMenu();
});

// Countdown Timer
function initCountdownTimer() {
    let launchDate;

    // Check if launch date is already stored in localStorage
    if (localStorage.getItem('launchDate')) {
        launchDate = new Date(localStorage.getItem('launchDate'));
    } else {
        // If not set, calculate it and store it
        launchDate = new Date();
        launchDate.setDate(launchDate.getDate() + 20);
        localStorage.setItem('launchDate', launchDate.toISOString());
    }

    const countdownTimer = setInterval(function () {
        const now = new Date();
        const timeLeft = launchDate - now;

        if (timeLeft > 0) {
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            document.getElementById("days").innerHTML = days < 10 ? '0' + days : days;
            document.getElementById("hours").innerHTML = hours < 10 ? '0' + hours : hours;
            document.getElementById("minutes").innerHTML = minutes < 10 ? '0' + minutes : minutes;
            document.getElementById("seconds").innerHTML = seconds < 10 ? '0' + seconds : seconds;
        } else {
            clearInterval(countdownTimer);
            document.getElementById("countdown-timer").innerHTML = "<div class='countdown-complete'>We're live!</div>";
            // document.getElementById("launch-message").innerHTML = "The service has launched!";
        }
    }, 1000);
}

// Navbar scroll effect
function initNavbarScroll() {
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function () {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

// Smooth scrolling for navigation links
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            // Close mobile menu if open
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse.classList.contains('show')) {
                navbarCollapse.classList.remove('show');
            }

            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // Calculate header height for offset
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;

                window.scrollTo({
                    top: targetPosition - navbarHeight,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Back to top button
function initBackToTop() {
    const backToTopButton = document.getElementById('back-to-top');

    window.addEventListener('scroll', function () {
        if (window.scrollY > 300) {
            backToTopButton.classList.add('show');
        } else {
            backToTopButton.classList.remove('show');
        }
    });

    backToTopButton.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Hero heading typewriter effect
function initTypewriterEffect() {
    const headingElement = document.getElementById('hero-heading');
    const text = 'Manage Your Energy Usage with Real-Time Monitoring';
    let i = 0;
    const speed = 80;

    function typeWriter() {
        if (i < text.length) {
            headingElement.innerHTML += text.charAt(i);
            i++;
            setTimeout(typeWriter, speed);
        } else {
            // Add blinking cursor effect after typing is complete
            headingElement.innerHTML += '<span class="cursor" style="color:#ffc107">|</span>';
            setInterval(() => {
                const cursor = document.querySelector('.cursor');
                if (cursor) {
                    cursor.style.opacity = cursor.style.opacity === '0' ? '1' : '0';
                }
            }, 500);
        }
    }

    // Start the typewriter effect
    if (headingElement) {
        typeWriter();
    }
}

// Form validation and submission
function initFormValidation() {
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Validate form
            const nameInput = contactForm.querySelector('input[name="name"]');
            const emailInput = contactForm.querySelector('input[name="email"]');
            const subjectInput = contactForm.querySelector('input[name="subject"]');
            const messageInput = contactForm.querySelector('textarea[name="message"]');

            let isValid = true;

            if (!nameInput.value.trim()) {
                highlightError(nameInput);
                isValid = false;
            } else {
                removeError(nameInput);
            }

            if (!emailInput.value.trim() || !isValidEmail(emailInput.value)) {
                highlightError(emailInput);
                isValid = false;
            } else {
                removeError(emailInput);
            }

            if (!subjectInput.value.trim()) {
                highlightError(subjectInput);
                isValid = false;
            } else {
                removeError(subjectInput);
            }

            if (!messageInput.value.trim()) {
                highlightError(messageInput);
                isValid = false;
            } else {
                removeError(messageInput);
            }

            if (isValid) {
                const formData = new FormData(contactForm);

                fetch('controllers/feedbackform.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.text())
                    .then(data => {
                        showNotification(data.trim(), 'success');
                        contactForm.reset();
                    })
                    .catch(error => {
                        showNotification('Failed to send message. Please try again.', 'error');
                    });
            }
        });
    }
}

// Highlight error field
function highlightError(inputElement) {
    inputElement.classList.add('is-invalid');
    let errorElement = inputElement.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('invalid-feedback')) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        errorElement.textContent = inputElement.name === 'email'
            ? 'Please enter a valid email address'
            : 'This field is required';
        inputElement.parentNode.insertBefore(errorElement, inputElement.nextSibling);
    }
}

// Remove error highlight
function removeError(inputElement) {
    inputElement.classList.remove('is-invalid');
    const errorElement = inputElement.nextElementSibling;
    if (errorElement && errorElement.classList.contains('invalid-feedback')) {
        errorElement.remove();
    }
}

// Email validation
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Show notification (auto close in 2 sec)
function showNotification(message, type = 'success') {
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        </div>
        <div class="notification-message">${message}</div>
        <button class="notification-close"><i class="fas f-times"></i></button>
    `;

    notificationContainer.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 2000); // 2 seconds
}

// Mobile menu toggle
function initMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    if (navbarToggler && navbarCollapse) {
        navbarToggler.addEventListener('click', function () {
            navbarCollapse.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function (event) {
            if (!navbarToggler.contains(event.target) && !navbarCollapse.contains(event.target) && navbarCollapse.classList.contains('show')) {
                navbarCollapse.classList.remove('show');
            }
        });
    }
}

// Active navigation items based on scroll position
window.addEventListener('scroll', function () {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    let currentSection = '';

    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;
        const navbarHeight = document.querySelector('.navbar').offsetHeight;

        if (window.scrollY >= (sectionTop - navbarHeight - 100)) {
            currentSection = section.getAttribute('id');
        }
    });

    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${currentSection}`) {
            link.classList.add('active');
        }
    });
});