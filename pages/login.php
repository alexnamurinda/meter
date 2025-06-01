<!-- Backend logic -->
<?php
include '../databases/error_handler.php'; // Include the error handler
include '../databases/databaseconnection.php'; // Include the database connection.
include '../databases/databasecreation.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['phone_number']); // Used for both phone numbers and admin names
    $password = $_POST['password'];

    // Authentication logic
    try {
        // Use the existing connection from db.php
        // $conn already contains the PDO connection
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Check if the username is one of the predefined admin names
    if (in_array($username, ['admin1', 'admin2', 'admin3'])) {
        // Admin authentication
        $query = "SELECT * FROM admin WHERE admin_name = :username LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['admin_password'])) {
            // Clear any user session before logging in as admin
            unset($_SESSION['user']);

            // Set admin session
            $_SESSION['admin'] = [
                'name' => $admin['admin_fullname'],
                'admin_name' => $admin['admin_name'],
                'authenticated' => true
            ];

            // Update last login time
            $updateLoginTime = $conn->prepare("UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE admin_name = :username");
            $updateLoginTime->bindParam(':username', $username);
            $updateLoginTime->execute();

            // Redirect to admin dashboard
            header("Location: admindashboard.php");
            exit();
        } else {
            $error_message = "Invalid Admin Credentials.";
        }
    } else {
        // Normalize phone number for clients
        if (strpos($username, '0') === 0) {
            $username = '+256' . substr($username, 1);
        }

        // Client authentication
        $query = "SELECT * FROM clients WHERE phone_number = :username LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['client_password'])) {
            // Clear any admin session before logging in as user
            unset($_SESSION['admin']);

            // Set user session
            $_SESSION['user'] = [
                'name' => $user['client_name'],
                'phone_number' => $user['phone_number'],
                'category' => $user['client_category'],
                'authenticated' => true
            ];

            // Update last login time for client
            $updateLoginTime = $conn->prepare("UPDATE clients SET last_login = CURRENT_TIMESTAMP WHERE phone_number = :username");
            $updateLoginTime->bindParam(':username', $username);
            $updateLoginTime->execute();

            // Redirect to user dashboard
            header("Location: userdashboard.php");
            exit();
        } else {
            $error_message = "Invalid Phone Number or Password.";
        }
    }
}

// Phone number normalization function
function normalizePhoneNumber($phone_number)
{
    $phone_number = preg_replace('/\D/', '', $phone_number); // Remove non-numeric characters
    if (substr($phone_number, 0, 1) === '0') {
        $phone_number = '+256' . substr($phone_number, 1); // Replace leading '0' with '+256'
    } elseif (substr($phone_number, 0, 4) !== '+256') {
        $phone_number = '+256' . $phone_number; // Ensure it starts with '+256'
    }
    return $phone_number;
}

// Registration logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    // Form data
    $username = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $category = $_POST['category'];
    $password = $_POST['password'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT); // Hash the password

    // List of admin usernames
    $adminUsers = ['admin1', 'admin2', 'admin3'];

    if (in_array($phone_number, $adminUsers)) {
        // Check if admin already exists
        $query = "SELECT * FROM admin WHERE admin_name = :admin_name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':admin_name', $phone_number);
        $stmt->execute();
        $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAdmin) {
            $error_message = "Credentials not allowed!";
        } else {
            // Insert admin details into the admin table
            $insertQuery = "INSERT INTO admin (admin_name, admin_fullname, admin_password, last_login) 
                            VALUES (:admin_name, :name, :admin_password, NOW())";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bindParam(':admin_name', $phone_number);
            $insertStmt->bindParam(':name', $username); // Insert full name into admin_fullname
            $insertStmt->bindParam(':admin_password', $hashedPassword);
            $insertStmt->execute();

            // Redirect admin to login page
            header("Location: login.php");
            exit();
        }
    } else {
        // Normalize phone number to Uganda format (+256)
        $phone_number = normalizePhoneNumber($phone_number);

        // Check if the phone number already exists
        $query = "SELECT * FROM clients WHERE phone_number = :phone_number";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':phone_number', $phone_number);
        $stmt->execute();
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $error_message = "This phone number is already registered!";
        } else {
            // Temporarily store registration data
            $_SESSION['registration_data'] = [
                'name' => $username,
                'phone_number' => $phone_number,
                'category' => $category,
                'password' => $hashedPassword
            ];

            // OTP generation and storage
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;

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

            // Decode the response
            $responseDecoded = json_decode($response, true);

            if (isset($responseDecoded['SMSMessageData']['Recipients']) && count($responseDecoded['SMSMessageData']['Recipients']) > 0) {
                // Redirect to OTP verification page
                header("Location: OTP_verification.php");
                exit();
            } else {
                header("Location: OTP_verification.php");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sign Up-Kooza Smart Meter</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/logmain.css">
    <link rel="stylesheet" href="../css/logresp.css">
</head>

<body>
    <div class="container">
        <div class="auth-container">
            <!-- Logo -->
            <div class="logo-container text-center mb-4">
                <img src="../images/logo.png" alt="company Logo" class="logo">
            </div>

            <!-- Auth Forms Container -->
            <div class="form-container">
                <!-- Login Form -->
                <div class="form-section login-section active">
                    <div class="form-header">
                        <h2>Welcome Back</h2>
                        <!-- <p>Log in to your Kooza Smart Meter account</p> -->
                    </div>

                    <div class="alert alert-danger error-message" id="errorMessage" role="alert">
                        <?php if (!empty($error_message)) echo htmlspecialchars($error_message); ?>
                    </div>

                    <form id="userForm" action="#" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" id="login_phone" name="phone_number" class="form-control" required>
                                <label for="login_phone">Phone Number</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="login_password" name="password" class="form-control" required>
                                <label for="login_password">Password</label>
                                <span class="password-toggle" id="toggleLoginPassword">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="login" value="login" class="btn btn-primary btn-block">
                                Log In <i class="fas fa-sign-in-alt ms-2"></i>
                            </button>
                        </div>

                        <div class="form-group text-end">
                            <a href="password_reset.php" class="forgot-password">Forgot Password?</a>
                        </div>

                        <div class="form-footer">
                            <p>Don't have an account? <a href="#" class="register-link">Register</a></p>
                        </div>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="form-section register-section">
                    <div class="form-header">
                        <h2>Create Account</h2>
                        <!-- <p>Register a new Kooza Smart Meter account</p> -->
                    </div>

                    <form id="signupForm" action="login.php" method="POST">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" id="name" name="name" class="form-control" required>
                                <label for="name">Full Name</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" id="phone_number" name="phone_number" class="form-control" required>
                                <label for="phone_number">Phone Number</label>
                            </div>
                            <small id="phone_error" class="text-danger mt-1 d-none">Use format: 0 or +256</small>
                        </div>

                        <input type="hidden" name="category" value="tenant">

                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="signup_password" name="password" class="form-control" required>
                                <label for="signup_password">Password</label>
                                <span class="password-toggle" id="toggleSignupPassword">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength">
                                <div class="progress mt-2" style="height: 5px; background-color: transparent;">
                                    <div id="password-strength-meter" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="password_error" class="text-danger mt-1 d-none"></small>
                            </div>
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
                            <button type="submit" name="signup" value="sign up" class="btn btn-primary btn-block">
                                Register <i class="fas fa-user-plus ms-2"></i>
                            </button>
                        </div>

                        <!-- to cater for form submission after alert -->
                        <input type="hidden" name="signup" value="signup"> 

                        <div class="form-footer">
                            <p>Already have an account? <a href="#" class="login-link">Login</a></p>
                        </div>
                    </form>
                    <script>
                        document.getElementById('signupForm').addEventListener('submit', function(e) {
                            e.preventDefault(); // prevent the default submission for now

                            // show the alert
                            if (confirm('Remember to complete your account settings under "My Account".')) {
                                // if user clicks OK, proceed with submission
                                this.submit();
                            }
                            // else do nothing (cancel submission)
                        });
                    </script>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../scripts/auth.js"></script>
</body>

</html>