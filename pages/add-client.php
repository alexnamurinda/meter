<?php
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';
session_start();

if (
    !isset($_SESSION['admin']) ||
    !is_array($_SESSION['admin']) ||
    empty($_SESSION['admin']) ||
    !isset($_SESSION['admin']['name']) ||
    !isset($_SESSION['admin']['admin_name']) ||
    !isset($_SESSION['admin']['authenticated']) ||
    $_SESSION['admin']['authenticated'] !== true
) {

    // Clear session and redirect
    session_unset();
    session_destroy();
    header("Location: login.php?error=unauthorized");
    exit();
}

// Set timeout for inactivity (e.g., 30 minutes)
$inactive = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    // Session has expired
    session_unset();
    session_destroy();
    header("Location: login.php?error=session_expired");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Check if IP has changed (potential session hijacking)
if (!isset($_SESSION['ip_address'])) {
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
} elseif ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    // IP address has changed, possible session hijacking
    session_unset();
    session_destroy();
    header("Location: login.php?error=security_violation");
    exit();
}

$admin_name = $_SESSION['admin']['name'];  // Admin's full name
$admin_username = $_SESSION['admin']['admin_name'];  // Admin's username

try {
    // First check if the table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'clients'");
    if ($tableCheck->rowCount() == 0) {
        trigger_error("The 'clients' table does not exist in the database", E_USER_ERROR);
    }

    // Fetch employees from the employee table
    $clientQuery = "SELECT * FROM clients";
    $clientStmt = $conn->prepare($clientQuery);
    $clientStmt->execute();

    // Fetch total number of clients
    $totalTenants = 0;
    $countTenantsQuery = "SELECT COUNT(*) FROM clients WHERE client_category = 'tenant'";
    $countStmt = $conn->prepare($countTenantsQuery);
    $countStmt->execute();
    $totalTenants = $countStmt->fetchColumn();


    // Fetch unique apartment IDs for the filter dropdown
    $apartmentQuery = "SELECT DISTINCT apartment_id FROM clients";
    $apartmentStmt = $conn->prepare($apartmentQuery);
    $apartmentStmt->execute();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get POST data
        $clientName = $_POST['client_name'];
        $clientCategory = $_POST['client_category'];
        $phoneNumber = $_POST['phone_number'];
        $clientPassword = password_hash($_POST['client_password'], PASSWORD_BCRYPT);
        $profilePic = $_FILES['profile_pic']['name'] ? $_FILES['profile_pic']['name'] : null;
        $apartmentId = $_POST['apartment_id'] ? $_POST['apartment_id'] : null;
        $roomId = $_POST['room_id'] ? $_POST['room_id'] : null;

        // Handle profile picture upload
        if ($profilePic) {
            $uploadDir = 'images/';
            $uploadFile = $uploadDir . basename($profilePic);

            // Validate the uploaded file
            $fileType = mime_content_type($_FILES['profile_pic']['tmp_name']);
            if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
                echo "Invalid file type. Only JPG, PNG, and GIF files are allowed.";
                exit;
            }

            if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadFile)) {
                echo "Failed to upload profile picture.";
                exit;
            }
        }

        $phoneNumber = preg_replace('/^0/', '+256', $phoneNumber);

        // Insert query
        $insertQuery = "INSERT INTO clients (client_name, client_category, phone_number, client_password, profile_pic, apartment_id, room_id)
                        VALUES (:client_name, :client_category, :phone_number, :client_password, :profile_pic, :apartment_id, :room_id)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindParam(':client_name', $clientName);
        $insertStmt->bindParam(':client_category', $clientCategory);
        $insertStmt->bindParam(':phone_number', $phoneNumber);
        $insertStmt->bindParam(':client_password', $clientPassword);
        $insertStmt->bindParam(':profile_pic', $profilePic);
        $insertStmt->bindParam(':apartment_id', $apartmentId);
        $insertStmt->bindParam(':room_id', $roomId);

        // Execute the insert query
        if ($insertStmt->execute()) {
            echo "<script>
                    alert('New client added successfully!');
                    window.location.href = 'manage_users.php';
                 </script>";
            exit();
        } else {
            echo "Failed to add new client.";
        }
    }
} catch (PDOException $e) {
    // Handle database errors
    if (getenv('ENVIRONMENT') === 'production') {
        trigger_error("Database error occurred", E_USER_ERROR);
    } else {
        trigger_error("Database error: " . $e->getMessage(), E_USER_ERROR);
    }
}

// Set secure headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - add client</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Admin Custom CSS -->
    <link rel="stylesheet" href="../css/admnstyling.css">
    <link rel="stylesheet" href="../css/admin_responsive.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <style>
        /* Additional CSS for add user */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            background-color: #fff;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }


        .page-title {
            color: #5a5c69;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 991.98px) {
            .action-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .action-bar div {
                width: 100%;
                display: flex;
                gap: 0.5rem;
            }

            .action-bar a {
                width: 100%;
            }

         
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="active">
            <div class="sidebar-header">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="fas fa-align-left"></i>
                </button>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="admindashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                    <ul class="collapse list-unstyled" id="userSubmenu">
                        <li>
                            <a href="manage_users.php">
                                <i class="fas fa-user-cog"></i>
                                <span>Manage Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="add-client.php">
                                <i class="fas fa-user-plus"></i>
                                <span>Add Client</span>
                            </a>
                        </li>
                        <li>
                            <a href="user_feedbacks.php">
                                <i class="fas fa-comments"></i>
                                <span>Feedbacks</span>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-exchange-alt"></i>
                                <span>Transactions</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-microchip"></i>
                        <span>Device Management</span>
                    </a>
                    <ul class="collapse list-unstyled" id="deviceSubmenu">
                        <li>
                            <a href="device_overview.php">
                                <i class="fas fa-th-large"></i>
                                <span>Device Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="assign_apartment.php">
                                <i class="fas fa-building"></i>
                                <span>Assign Apartment/Room</span>
                            </a>
                        </li>
                        <li>
                            <a href="registration_approval.php">
                                <i class="fas fa-clipboard-check"></i>
                                <span>Pending Approvals</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-wallet"></i>
                        <span>Account Summary</span>
                    </a>
                    <ul class="collapse list-unstyled" id="accountSubmenu">
                        <li>
                            <a href="#">
                                <i class="fas fa-chart-line"></i>
                                <span>Account Overview</span>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <i class="fas fa-bolt"></i>
                                <span>Top Up Units</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <div class="welcome-card animate__animated animate__fadeIn">
                        <div class="row g-0">
                            <div class="col-md-8">
                                <div class="welcome-content" style="text-align: center;">
                                    <h2></i>Add new client</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid dashboard-content">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card animate__animated animate__fadeIn">
                            <div class="card-header">
                                <h5>Total clients: <?php echo $totalTenants; ?></h5>
                            </div>

                            <div class="main-content-wrapper">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                                                        <div class="form-grid">
                                                            <div>
                                                                <div class="mb-3">
                                                                    <label for="client_name" class="form-label"><i class="fas fa-user me-2"></i>Client Name:</label>
                                                                    <input type="text" class="form-control" id="client_name" name="client_name" required>
                                                                    <div class="invalid-feedback">Please enter a client name.</div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="client_category" class="form-label"><i class="fas fa-tag me-2"></i>Client Category:</label>
                                                                    <select class="form-select" id="client_category" name="client_category" required>
                                                                        <option value="">Select Category</option>
                                                                        <option value="tenant">Tenant</option>
                                                                        <option value="landlord">Landlord</option>
                                                                        <option value="guest">Guest</option>
                                                                    </select>
                                                                    <div class="invalid-feedback">Please select a category.</div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="phone_number" class="form-label"><i class="fas fa-phone me-2"></i>Phone Number:</label>
                                                                    <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                                                                    <div class="invalid-feedback">Please enter a valid phone number.</div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="client_password" class="form-label"><i class="fas fa-lock me-2"></i>Password:</label>
                                                                    <input type="password" class="form-control" id="client_password" name="client_password" required>
                                                                    <div class="invalid-feedback">Please enter a password.</div>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="mb-3">
                                                                    <label for="profile_pic" class="form-label"><i class="fas fa-image me-2"></i>Add Photo:</label>
                                                                    <input type="file" class="form-control" id="profile_pic" name="profile_pic">
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="apartment_id" class="form-label"><i class="fas fa-building me-2"></i>Apartment ID:</label>
                                                                    <input type="text" class="form-control" id="apartment_id" name="apartment_id">
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label for="room_id" class="form-label"><i class="fas fa-door-open me-2"></i>Room ID:</label>
                                                                    <input type="text" class="form-control" id="room_id" name="room_id">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mt-4 text-center">
                                                            <a href="manage_users.php" class="btn btn-secondary me-2">Cancel</a>
                                                            <button type="submit" class="btn btn-primary">Finish</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <p>&copy; 2024 FastNet Solutions. All Rights Reserved.</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Admin Custom JS -->
    <script src="../scripts/admin.js"></script>

    <!-- Form validation script -->
    <script>
        (function() {
            'use strict';
            // Fetch all forms that need validation
            var forms = document.querySelectorAll('.needs-validation');

            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>

</body>

</html>