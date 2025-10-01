<?php
session_start();
include '../databases/error_handler.php'; // Include the error handler
include '../databases/databaseconnection.php'; // Include the database connection.
include '../databases/databasecreation.php';

$error_message = "";
$success_message = "";
$current_step = "request"; // Default step: request, verify, or reset

// Check if OTP verification step is active
if (isset($_SESSION['reset_phone']) && !isset($_SESSION['otp_verified'])) {
    $current_step = "verify";
}

// Check if password reset step is active
if (isset($_SESSION['reset_phone']) && isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    $current_step = "reset";
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $phone_number = $_POST['phone_number'];

    // Format phone number to Uganda's standard format
    if (strpos($phone_number, '0') === 0) {
        $phone_number = '+256' . substr($phone_number, 1);
    }

    try {
        // Check if the phone number exists in the database
        $stmt = $conn->prepare("SELECT * FROM clients WHERE phone_number = :phone_number");
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Phone number exists, generate and send OTP
            $otp = rand(100000, 999999);
            $_SESSION['reset_otp'] = $otp; // Store OTP in session
            $_SESSION['reset_phone'] = $phone_number;
            $_SESSION['reset_timestamp'] = time(); // timestamp for expiry check

            $apiUsername = 'fastnetug';
            $apiKey = 'atsk_55f3cd22b22762efe6a8342bcbd478239a69a4aca7588f25694cdaac498101e0d027488d';
            $apiUrl = 'https://api.africastalking.com/version1/messaging';

            $message = "Your password reset code is: $otp";

            // Set up the cURL request
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => $apiUsername,
                'to' => $phone_number,
                'message' => $message
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apiKey: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            // Execute the request
            $response = curl_exec($ch);
            curl_close($ch);

            // Move to OTP verification step
            $current_step = "verify";
            $success_message = "Verification code sent successfully.";
        } else {
            $error_message = "Phone number not found.";
        }
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $user_otp = $_POST['otp'];
    $stored_otp = $_SESSION['reset_otp'] ?? '';
    $timestamp = $_SESSION['reset_timestamp'] ?? 0;

    // Check if OTP has expired (2 minutes)
    if (time() - $timestamp > 120) {
        $error_message = "OTP has expired. Please request a new one.";
        // Clear session data
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_timestamp']);
        $current_step = "request";
    } elseif ($user_otp == $stored_otp) {
        // OTP is correct, set verification flag
        $_SESSION['otp_verified'] = true;
        $current_step = "reset";
        $success_message = "OTP verified successfully.";
    } else {
        $error_message = "Invalid OTP. Please try again.";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // Check if OTP is verified
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
        $error_message = "Please verify your OTP first.";
    } else {
        // Set new password logic
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            // Passwords match and are valid, update in database
            try {
                $phone_number = $_SESSION['reset_phone'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE clients SET client_password = :password WHERE phone_number = :phone_number");
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':phone_number' => $phone_number
                ]);

                if ($stmt->rowCount() > 0) {
                    // Password updated successfully
                    $success_message = "Password updated successfully. Redirecting to login...";

                    // Clear all reset-related session variables
                    unset($_SESSION['reset_otp']);
                    unset($_SESSION['reset_phone']);
                    unset($_SESSION['reset_timestamp']);
                    unset($_SESSION['otp_verified']);

                    // Redirect to login page after 2 seconds
                    header("refresh:2; url=login.php");
                } else {
                    $error_message = "Failed to update password. Please try again.";
                }
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == 'true' && isset($_SESSION['reset_phone'])) {
    // Regenerate OTP
    $otp = rand(100000, 999999);
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_timestamp'] = time();

    $phone_number = $_SESSION['reset_phone'];

    // SMS API setup
    $apiUsername = 'fastnetug';
    $apiKey = 'atsk_55f3cd22b22762efe6a8342bcbd478239a69a4aca7588f25694cdaac498101e0d027488d';
    $apiUrl = 'https://api.africastalking.com/version1/messaging';

    $message = "Your password reset code is: $otp";

    // Set up the cURL request
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $apiUsername,
        'to' => $phone_number,
        'message' => $message
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apiKey: ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    // Execute the request
    $response = curl_exec($ch);
    curl_close($ch);

    $success_message = "Verification code has been resent.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - FastNet Solutions</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/logmain.css">
    <link rel="stylesheet" href="../css/logresp.css">
    <style>
        .otp-input {
            letter-spacing: 10px;
            font-size: 18px;
            text-align: center;
            font-weight: 600;
        }

        .password-info {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: #666;
            position: relative;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: var(--success-color);
            color: white;
        }

        .step::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 2px;
            background: #e0e0e0;
            left: 100%;
        }

        .step:last-child::after {
            display: none;
        }

        .step.completed::after {
            background: var(--success-color);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="auth-container">
            <!-- Logo -->
            <div class="logo-container text-center mb-4">
                <img src="../images/logo.png" alt="company Logo" class="logo">
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo ($current_step == 'request') ? 'active' : (($current_step == 'verify' || $current_step == 'reset') ? 'completed' : ''); ?>">1</div>
                <div class="step <?php echo ($current_step == 'verify') ? 'active' : (($current_step == 'reset') ? 'completed' : ''); ?>">2</div>
                <div class="step <?php echo ($current_step == 'reset') ? 'active' : ''; ?>">3</div>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger error-message" id="errorMessage" role="alert" style="display: block;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" id="successMessage" role="alert" style="display: block;">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Phone Number Request Form -->
                <div class="form-section <?php echo ($current_step == 'request') ? 'active' : ''; ?>" id="request-form">
                    <div class="form-header">
                        <h2>Password Reset</h2>
                        <p>Enter the phone number you registered with</p>
                    </div>

                    <form action="password_reset.php" method="post">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" id="phone_number" name="phone_number" class="form-control" required>
                                <label for="phone_number">Phone Number</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="request_reset" class="btn btn-primary btn-block">
                                Send Code <i class="fas fa-paper-plane ms-2"></i>
                            </button>
                        </div>

                        <div class="form-footer">
                            <p><a href="login.php" class="login-link"><i class="fas fa-arrow-left me-2"></i>Back to Login</a></p>
                        </div>
                    </form>
                </div>

                <!-- OTP Verification Form -->
                <div class="form-section <?php echo ($current_step == 'verify') ? 'active' : ''; ?>" id="verify-form">
                    <div class="form-header">
                        <!-- <h2>Verify Code</h2> -->
                        <p>Enter the verification code sent to your phone</p>
                    </div>

                    <form action="password_reset.php" method="post">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="text" id="otp" name="otp" class="form-control otp-input"
                                    required maxlength="6" pattern="\d{6}" inputmode="numeric">
                                <label for="otp">Enter a 6-digit Code</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="verify_otp" class="btn btn-primary btn-block">
                                Verify Code <i class="fas fa-check-circle ms-2"></i>
                            </button>
                        </div>

                        <div class="form-footer">
                            <p>Didn't receive the code? <a href="password_reset.php?resend=true" class="resend-link">Resend Code</a></p>
                            <p class="mt-2"><a href="password_reset.php?restart=true" class="login-link"><i class="fas fa-arrow-left me-2"></i>Start Over</a></p>
                        </div>
                    </form>
                </div>

                <!-- New Password Form -->
                <div class="form-section <?php echo ($current_step == 'reset') ? 'active' : ''; ?>" id="reset-form">
                    <div class="form-header">
                        <h2>Set New Password</h2>
                        <!-- <p>Create a strong password for your account</p> -->
                    </div>

                    <form action="password_reset.php" method="post">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                                <label for="new_password">New Password</label>
                                <span class="password-toggle" id="toggleNewPassword">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength">
                                <div class="progress mt-2" style="height: 5px; background-color: transparent;">
                                    <div id="password-strength-meter" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="password_error" class="text-danger mt-1 d-none"></small>
                            </div>
                            <div class="password-info">Password must be at least 6 characters long</div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <label for="confirm_password">Confirm Password</label>
                                <span class="password-toggle" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <small id="confirm_password_error" class="text-danger mt-1 d-none"></small>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="reset_password" class="btn btn-primary btn-block">
                                Reset Password <i class="fas fa-key ms-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide messages after 5 seconds
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage && errorMessage.textContent.trim() !== "") {
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 5000);
            }

            const successMessage = document.getElementById('successMessage');
            if (successMessage && successMessage.textContent.trim() !== "") {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 5000);
            }

            // Password visibility toggle for new password
            const toggleNewPassword = document.getElementById('toggleNewPassword');
            const newPasswordInput = document.getElementById('new_password');

            if (toggleNewPassword && newPasswordInput) {
                toggleNewPassword.addEventListener('click', function() {
                    togglePasswordVisibility(newPasswordInput, this);
                });
            }

            // Password visibility toggle for confirm password
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPasswordInput = document.getElementById('confirm_password');

            if (toggleConfirmPassword && confirmPasswordInput) {
                toggleConfirmPassword.addEventListener('click', function() {
                    togglePasswordVisibility(confirmPasswordInput, this);
                });
            }

            // Function to toggle password visibility
            function togglePasswordVisibility(inputElement, toggleElement) {
                const type = inputElement.getAttribute('type') === 'password' ? 'text' : 'password';
                inputElement.setAttribute('type', type);

                // Toggle the eye icon
                const icon = toggleElement.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }

            // Password strength meter
            const newPassword = document.getElementById('new_password');
            const passwordStrengthMeter = document.getElementById('password-strength-meter');
            const passwordError = document.getElementById('password_error');

            if (newPassword && passwordStrengthMeter && passwordError) {
                newPassword.addEventListener('input', function() {
                    const value = newPassword.value;
                    let strength = 0;
                    let message = '';

                    // Length check
                    if (value.length >= 6) strength++;
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
                        passwordError.textContent = message;
                        passwordError.classList.remove('d-none');
                        passwordError.style.color = strength < 2 ? '#dc3545' :
                            strength < 3 ? '#ffc107' : '#28a745';
                    } else {
                        passwordError.classList.add('d-none');
                    }
                });
            }

            // Confirm password validation
            const confirmPassword = document.getElementById('confirm_password');
            const confirmPasswordError = document.getElementById('confirm_password_error');

            if (newPassword && confirmPassword && confirmPasswordError) {
                confirmPassword.addEventListener('input', function() {
                    if (confirmPassword.value !== newPassword.value) {
                        confirmPasswordError.textContent = 'Passwords do not match';
                        confirmPasswordError.classList.remove('d-none');
                        confirmPassword.classList.add('is-invalid');
                    } else {
                        confirmPasswordError.classList.add('d-none');
                        confirmPassword.classList.remove('is-invalid');
                    }
                });
            }

            // Add floating label functionality
            const formInputs = document.querySelectorAll('.form-control');
            formInputs.forEach(input => {
                if (input) {
                    input.addEventListener('focus', () => {
                        const label = input.parentElement.querySelector('label');
                        if (label) label.classList.add('active');
                    });

                    input.addEventListener('blur', () => {
                        if (input.value === '') {
                            const label = input.parentElement.querySelector('label');
                            if (label) label.classList.remove('active');
                        }
                    });

                    // Check if input has value on page load
                    if (input.value !== '') {
                        const label = input.parentElement.querySelector('label');
                        if (label) label.classList.add('active');
                    }
                }
            });

            // Format phone number input
            const phoneInput = document.getElementById('phone_number');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    let value = phoneInput.value.replace(/\D/g, '');
                    if (value.length > 0 && value.charAt(0) !== '0') {
                        value = '0' + value;
                    }
                    phoneInput.value = value;
                });
            }

            // Handle auto-focusing of initial element
            const currentStep = "<?php echo $current_step; ?>";
            if (currentStep === "request" && phoneInput) {
                phoneInput.focus();
            } else if (currentStep === "verify") {
                const otpInput = document.getElementById('otp');
                if (otpInput) otpInput.focus();
            } else if (currentStep === "reset") {
                if (newPassword) newPassword.focus();
            }

            // Handle restart
            const restartLink = document.querySelector('a[href="password_reset.php?restart=true"]');
            if (restartLink) {
                restartLink.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Clear session data
                    fetch('password_reset.php?restart=true', {
                        method: 'GET',
                    }).then(() => {
                        window.location.href = 'password_reset.php';
                    });
                });
            }
        });

        // Handle restart parameter to clear session
        <?php if (isset($_GET['restart']) && $_GET['restart'] === 'true'): ?>
            <?php
            // Clear all reset-related session variables
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_phone']);
            unset($_SESSION['reset_timestamp']);
            unset($_SESSION['otp_verified']);

            // Redirect to the password reset page
            header("Location: password_reset.php");
            exit();
            ?>
        <?php endif; ?>
    </script>
</body>

</html>