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

// Fetch total number of clients
$totalTenants = 0;
$countTenantsQuery = "SELECT COUNT(*) FROM clients WHERE client_category = 'tenant'";
$countStmt = $conn->prepare($countTenantsQuery);
$countStmt->execute();
$totalTenants = $countStmt->fetchColumn();

// Fetch active tenants
$activeTenants = 0;
$countTenantsQuery = "SELECT COUNT(*) FROM rooms WHERE power_status = 'ON'";
$countStmt = $conn->prepare($countTenantsQuery);
$countStmt->execute();
$activeTenants = $countStmt->fetchColumn();

// Fetch total units consumed by all tenants
$totalTenantunits = 0;
$countTenantsQuery = "SELECT SUM(energy_consumed) FROM room_energy";
$countStmt = $conn->prepare($countTenantsQuery);
$countStmt->execute();
$totalTenantunits = $countStmt->fetchColumn();

// Fetch pending approvals
$totalapprovals = 0;
$countTenantsQuery = "SELECT COUNT(*) FROM clients WHERE registration_status = 'under review'";
$countStmt = $conn->prepare($countTenantsQuery);
$countStmt->execute();
$totalapprovals = $countStmt->fetchColumn();

// Fetch the most recently registered client
$query = "SELECT client_name, client_category, registered_on FROM clients ORDER BY registered_on DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$recentClient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($recentClient) {
    $fullname = $recentClient['client_name'];
    $category = $recentClient['client_category'];
    $registeredAt = new DateTime($recentClient['registered_on']);
    $now = new DateTime();

    // Get human-readable time difference (e.g. "2 hours ago")
    $interval = $registeredAt->diff($now);
    if ($interval->d > 0) {
        $timeAgo = $interval->d . ' day(s) ago';
    } elseif ($interval->h > 0) {
        $timeAgo = $interval->h . ' hour(s) ago';
    } elseif ($interval->i > 0) {
        $timeAgo = $interval->i . ' minute(s) ago';
    } else {
        $timeAgo = 'Just now';
    }
}


// Fetch rooms with remaining units less than 5
$query = "SELECT room_id, remaining_units, last_updated FROM room_energy WHERE remaining_units < 5 ORDER BY last_updated DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$lowUnitRoom = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lowUnitRoom) {
    $roomNumber = $lowUnitRoom['room_id'];
    $remaining = $lowUnitRoom['remaining_units'];
    $updatedAt = new DateTime($lowUnitRoom['last_updated']);
    $now = new DateTime();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Meter Project</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Admin Custom CSS -->
    <link rel="stylesheet" href="../css/admnstyling.css">
    <link rel="stylesheet" href="../css/admin_responsive.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
                                    <h2>Welcome, <?php echo htmlspecialchars($admin_name . ' (' . $admin_username . ')'); ?></h2>
                                </div>
                            </div>
                            <!-- <div class="col-md-4">
                                <div class="welcome-image">
                                    <img src="../images/banner.jpg" alt="Dashboard Banner">
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid dashboard-content">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card animate__animated animate__fadeInUp">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-3">
                                        <div class="stat-icon bg-primary">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                    <div class="col-9 text-end">
                                        <h3><?php echo $totalTenants; ?></h3>
                                        <p>Total Users</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 67%" aria-valuenow="67" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-3">
                                        <div class="stat-icon bg-success">
                                            <i class="fas fa-microchip"></i>
                                        </div>
                                    </div>
                                    <div class="col-9 text-end">
                                        <h3><?php echo $activeTenants; ?></h3>
                                        <p>Active Devices</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-3">
                                        <div class="stat-icon bg-warning">
                                            <i class="fas fa-bolt"></i>
                                        </div>
                                    </div>
                                    <div class="col-9 text-end">
                                        <h3><?php echo $totalTenantunits; ?></h3>
                                        <p>kWh Consumed</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 54%" aria-valuenow="54" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-3">
                                        <div class="stat-icon bg-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                    <div class="col-9 text-end">
                                        <h3><?php echo $totalapprovals; ?></h3>
                                        <p>Pending approvals</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: 8%" aria-valuenow="8" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Sections -->
                <div class="row">

                    <!-- Recent Activity Section -->
                    <div class="col-lg-8 mb-4">
                        <div class="card animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                            <div class="card-header">
                                <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-list">
                                    <div class="activity-item">
                                        <div class="activity-icon bg-primary">
                                            <i class="fas fa-user-plus"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6>New client registered</h6>
                                            <p><?= htmlspecialchars($fullname) ?> registered as a <?= htmlspecialchars($category) ?></p>
                                            <small><?= $timeAgo ?></small>
                                        </div>
                                    </div>

                                    <?php if (isset($lowUnitRoom)): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon bg-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                            <div class="activity-content">
                                                <h6>Low unit alert</h6>
                                                <p>Room <?= htmlspecialchars($roomNumber) ?> has <?= $remaining ?> kWh remaining</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="activity-item">
                                        <div class="activity-icon bg-info">
                                            <i class="fas fa-cog"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6>System maintenance</h6>
                                            <p>Scheduled maintenance completed</p>
                                            <small>2 days ago</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="#" class="btn btn-sm btn-primary">View All Activity</a>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="col-lg-4 mb-4">
                        <div class="card animate__animated animate__fadeIn" style="animation-delay: 0.4s;">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <button class="btn btn-primary mb-3">
                                        <i class="fas fa-user-plus me-2"></i>Add New Client
                                    </button>
                                    <button class="btn btn-info mb-3">
                                        <i class="fas fa-bolt me-2"></i>Top Up Units
                                    </button>
                                    <!-- <button class="btn btn-info mb-3">
                                        <i class="fas fa-download me-2"></i>Download Reports
                                    </button>
                                    <button class="btn btn-warning">
                                        <i class="fas fa-bell me-2"></i>View Alerts
                                    </button> -->
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
    <script src="../scripts/visual_charts.js"></script>
</body>

</html>