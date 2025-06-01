document.addEventListener('DOMContentLoaded', function() {
    // Form toggle functionality
    const registerLink = document.querySelector('.register-link');
    const loginLink = document.querySelector('.login-link');
    const loginSection = document.querySelector('.login-section');
    const registerSection = document.querySelector('.register-section');
    
    // Toggle between login and register forms
    registerLink.addEventListener('click', function(e) {
        e.preventDefault();
        loginSection.classList.remove('active');
        registerSection.classList.add('active');
        document.getElementById('userForm').reset();
    });
    
    loginLink.addEventListener('click', function(e) {
        e.preventDefault();
        registerSection.classList.remove('active');
        loginSection.classList.add('active');
        document.querySelector('.register-section form').reset();
    });
    
    // Password visibility toggle for login
    const toggleLoginPassword = document.getElementById('toggleLoginPassword');
    const loginPasswordInput = document.getElementById('login_password');
    
    toggleLoginPassword.addEventListener('click', function() {
        togglePasswordVisibility(loginPasswordInput, this);
    });
    
    // Password visibility toggle for signup
    const toggleSignupPassword = document.getElementById('toggleSignupPassword');
    const signupPasswordInput = document.getElementById('signup_password');
    
    toggleSignupPassword.addEventListener('click', function() {
        togglePasswordVisibility(signupPasswordInput, this);
    });
    
    // Password visibility toggle for confirm password
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    toggleConfirmPassword.addEventListener('click', function() {
        togglePasswordVisibility(confirmPasswordInput, this);
    });
    
    // Function to toggle password visibility
    function togglePasswordVisibility(inputElement, toggleElement) {
        const type = inputElement.getAttribute('type') === 'password' ? 'text' : 'password';
        inputElement.setAttribute('type', type);
        
        // Toggle the eye icon
        const icon = toggleElement.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }
    
    // Phone number validation
    const phoneInput = document.getElementById('phone_number');
    const phoneError = document.getElementById('phone_error');
    
    phoneInput.addEventListener('input', function() {
        const value = phoneInput.value;
        if (value === 'admin' || value.startsWith('0') || value.startsWith('+256')) {
            phoneError.classList.add('d-none');
            phoneInput.classList.remove('is-invalid');
        } else {
            phoneError.classList.remove('d-none');
            phoneInput.classList.add('is-invalid');
        }
    });
    
    // Password strength meter
    const signupPasswordError = document.getElementById('password_error');
    const passwordStrengthMeter = document.getElementById('password-strength-meter');
    
    signupPasswordInput.addEventListener('input', function() {
        const value = signupPasswordInput.value;
        let strength = 0;
        let message = '';
        
        // Length check
        if (value.length >= 5) strength++;
        // Lowercase check
        if (/[a-z]/.test(value)) strength++;
        // Number check
        if (/[0-9]/.test(value)) strength++;
        // Special character check
        if (/[!@#$%^&*(),.?":{}|<>]/.test(value)) strength++;
        
        // Update strength meter
        passwordStrengthMeter.style.width = (strength * 25) + '%';
        
        // Set color based on strength
        if (strength === 0) {
            passwordStrengthMeter.style.backgroundColor = '#dc3545'; // red
        } else if (strength < 2) {
            passwordStrengthMeter.style.backgroundColor = '#dc3545'; // red
            message = 'Password is too weak';
        } else if (strength < 3) {
            passwordStrengthMeter.style.backgroundColor = '#ffc107'; // yellow
            message = 'Password strength is moderate';
        } else if (strength < 4) {
            passwordStrengthMeter.style.backgroundColor = '#28a745'; // green
            message = 'Password strength is good';
        } else {
            passwordStrengthMeter.style.backgroundColor = '#28a745'; // green
            message = 'Password strength is excellent';
        }
        
        // Display message
        if (message) {
            signupPasswordError.textContent = message;
            signupPasswordError.classList.remove('d-none');
            signupPasswordError.style.color = strength < 2 ? '#dc3545' : 
                                             strength < 3 ? '#ffc107' : '#28a745';
        } else {
            signupPasswordError.classList.add('d-none');
        }
    });
    
    // Confirm password validation
    const confirmPasswordError = document.getElementById('confirm_password_error');
    
    confirmPasswordInput.addEventListener('input', function() {
        if (confirmPasswordInput.value !== signupPasswordInput.value) {
            confirmPasswordError.textContent = 'Passwords do not match';
            confirmPasswordError.classList.remove('d-none');
            confirmPasswordInput.classList.add('is-invalid');
        } else {
            confirmPasswordError.classList.add('d-none');
            confirmPasswordInput.classList.remove('is-invalid');
        }
    });
    
    // Error message handling
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage && errorMessage.textContent.trim() !== "") {
        errorMessage.style.display = 'block';
        setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 5000);
    }
    
    // Form validation and animation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Highlight all invalid fields
                const invalidInputs = form.querySelectorAll(':invalid');
                invalidInputs.forEach(input => {
                    input.classList.add('is-invalid');
                    
                    // Add shake animation
                    input.parentElement.classList.add('shake');
                    setTimeout(() => {
                        input.parentElement.classList.remove('shake');
                    }, 500);
                });
            }
            
            form.classList.add('was-validated');
        });
    });
    
    // Add floating label functionality
    const formInputs = document.querySelectorAll('.form-control');
    formInputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.querySelector('label').classList.add('active');
        });
        
        input.addEventListener('blur', () => {
            if (input.value === '') {
                input.parentElement.querySelector('label').classList.remove('active');
            }
        });
        
        // Check if input has value on page load
        if (input.value !== '') {
            input.parentElement.querySelector('label').classList.add('active');
        }
    });
    
    // Add some additional animations to improve UX
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = '';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // Add CSS for shake animation
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .shake {
            animation: shake 0.5s;
        }
    `;
    document.head.appendChild(style);
});