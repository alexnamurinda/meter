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
    $tableCheck = $conn->query("SHOW TABLES LIKE 'feedbacks'");
    if ($tableCheck->rowCount() == 0) {
        trigger_error("The 'feedbacks' table does not exist in the database", E_USER_ERROR);
    }

    // Fetch feedback data
    $query = "SELECT client_name, client_email, feedback_subject, feedback_message, submitted_on FROM feedbacks ORDER BY submitted_on DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total number of feedbacks
    $totalTenants = 0;
    $countTenantsQuery = "SELECT COUNT(*) FROM feedbacks";
    $countStmt = $conn->prepare($countTenantsQuery);
    $countStmt->execute();
    $totalTenants = $countStmt->fetchColumn();
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
    <title>Admin Dashboard - user feedbacks</title>
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
        .table {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .table thead th {
            background-color: var(--dark-color);
            color: #fff;
            white-space: nowrap;
        }

        .table-title {
            margin-bottom: 1.5rem;
            color: #5a5c69;
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

            #searchInput,
            #apartmentFilter {
                width: 100% !important;
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
                                    <h2></i>Clients' Feedback List</h2>
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
                                <h5>Total Feedbacks: <?php echo $totalTenants; ?></h5>
                            </div>

                            <div class="card-body">
                                <?php if (!empty($feedbacks)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Client Name</th>
                                                    <th>Email</th>
                                                    <th>Subject</th>
                                                    <th>Message</th>
                                                    <th>Submitted On</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($feedbacks as $feedback): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($feedback['client_name']); ?></td>
                                                        <td>
                                                            <a href="mailto:<?php echo htmlspecialchars($feedback['client_email']); ?>">
                                                                <?php echo htmlspecialchars($feedback['client_email']); ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($feedback['feedback_subject']); ?></td>
                                                        <td>
                                                            <div class="feedback-message" style="max-height: 100px; overflow-y: auto;">
                                                                <?php echo htmlspecialchars($feedback['feedback_message']); ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo date('Y-m-d H:i:s', strtotime($feedback['submitted_on'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> No feedbacks found.
                                    </div>
                                <?php endif; ?>
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
</body>

</html>