<?php
session_start();
include '../databases/error_handler.php'; // Include the error handler
include '../databases/databaseconnection.php'; // Include the database connection.
include '../databases/databasecreation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'];

    if (isset($_SESSION['otp']) && $_SESSION['otp'] == $entered_otp) {
        // OTP is valid, save user to the database
        $registration_data = $_SESSION['registration_data'];

        // Convert phone number to standard format if needed
        $phone_number = $registration_data['phone_number'];

        try {
            $stmt = $conn->prepare("INSERT INTO clients (client_name, phone_number, client_category, client_password) VALUES (:name, :phone_number, :category, :password)");
            $stmt->execute([
                ':name' => $registration_data['name'],
                ':phone_number' => $phone_number,
                ':category' => $registration_data['category'],
                ':password' => $registration_data['password'] // Already hashed in login.php
            ]);

            // Clear session data
            unset($_SESSION['otp']);
            unset($_SESSION['registration_data']);

            // Set success message in session
            $_SESSION['registration_success'] = "Registration successful! Please login with your credentials.";
            
            // Redirect to login page
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error_message = "Error saving user: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid OTP. Please try again.";
    }
}

// Function to resend OTP if needed (placeholder for now)
if (isset($_GET['resend']) && $_GET['resend'] == 'true') {
    // Regenerate OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    
    // Get phone number from session
    $phone_number = $_SESSION['registration_data']['phone_number'];
    
    // SMS API setup (reused from login.php)
    $apiUsername = 'agritech_info';
    $apiKey = 'atsk_d30afdc12c16b290766e27594e298b4c82fa0ca3d87f723f7a2576aa9a6d0b9d096fa012';
    $apiUrl = 'https://api.africastalking.com/version1/messaging';

    // Prepare the message
    $message = "Your OTP code is: $otp";

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
    
    // Set success message
    $success_message = "OTP has been resent to your phone.";
    
    // Redirect back to OTP page
    header("Location: OTP_verification.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - FastNet Solutions</title>
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
            letter-spacing: 15px;
            font-size: 20px;
            text-align: center;
            font-weight: 600;
        }
        .otp-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
            width: 100%;
            color: #666;
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

            <!-- OTP Form Container -->
            <div class="form-container">
                <div class="form-section otp-section active">
                    <div class="form-header">
                        <h2>Verify Your Account</h2>
                    </div>

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

                    <div class="otp-info">
                        A  verification code has been sent to 
                        <strong><?php echo isset($_SESSION['registration_data']['phone_number']) ? htmlspecialchars($_SESSION['registration_data']['phone_number']) : 'your phone'; ?></strong>
                    </div>

                    <form action="OTP_verification.php" method="post">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="text" id="otp" name="otp" class="form-control otp-input" required maxlength="6" pattern="\d{6}" inputmode="numeric">
                                <label for="otp">Enter OTP code here</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                Verify <i class="fas fa-check-circle ms-2"></i>
                            </button>
                        </div>
                        
                        <div class="form-footer">
                            <p>Didn't receive the code? <a href="OTP_verification.php?resend=true" class="resend-link">Resend OTP</a></p>
                            <p class="mt-2"><a href="login.php" class="login-link"><i class="fas fa-arrow-left me-2"></i>Back to Login</a></p>
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
            // Auto-focus OTP input field
            document.getElementById('otp').focus();
            
            // Auto-hide error messages after 5 seconds
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage && errorMessage.textContent.trim() !== "") {
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 5000);
            }
            
            // Auto-hide success messages after 5 seconds
            const successMessage = document.getElementById('successMessage');
            if (successMessage && successMessage.textContent.trim() !== "") {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 5000);
            }
            
            // Add floating label functionality
            const otpInput = document.getElementById('otp');
            
            otpInput.addEventListener('focus', () => {
                otpInput.parentElement.querySelector('label').classList.add('active');
            });
            
            otpInput.addEventListener('blur', () => {
                if (otpInput.value === '') {
                    otpInput.parentElement.querySelector('label').classList.remove('active');
                }
            });
            
            // Check if input has value on page load
            if (otpInput.value !== '') {
                otpInput.parentElement.querySelector('label').classList.add('active');
            }
            
            // Add button animation
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
            
            // Restrict OTP input to numbers only
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    </script>
</body>

</html>