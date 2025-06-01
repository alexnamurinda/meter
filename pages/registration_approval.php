<?php
// Include database connection
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';
session_start();

// // Ensure only admin can access
// if (!isset($_SESSION['admin']) || $_SESSION['admin']['authenticated'] !== true) {
//     header("Location: admin_login.php?error=unauthorized");
//     exit();
// }

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $clientId = $_POST['client_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $updateStmt = $conn->prepare("UPDATE clients SET registration_status = 'approved' WHERE client_id = ?");
    } else {
        $updateStmt = $conn->prepare("UPDATE clients SET registration_status = 'rejected' WHERE client_id = ?");
    }

    $updateStmt->execute([$clientId]);
    header("Location: registration_approval.php");
    exit();
}

// Fetch pending requests
$stmt = $conn->query("SELECT * FROM clients WHERE registration_status = 'under review'");
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Pending Approvals</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Admin Custom CSS -->
    <link rel="stylesheet" href="../css/admnstyling.css">
    <link rel="stylesheet" href="../css/admin_responsive.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .table-container {
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .table thead th {
            background-color: var(--dark-color);
            color: #fff;
            white-space: nowrap;
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
            <li><a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li>
                <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-users"></i> <span>User Management</span>
                </a>
                <ul class="collapse list-unstyled" id="userSubmenu">
                    <li><a href="manage_users.php"><i class="fas fa-user-cog"></i> Manage Users</a></li>
                    <li><a href="add-client.php"><i class="fas fa-user-plus"></i> Add Client</a></li>
                    <li><a href="user_feedbacks.php"><i class="fas fa-comments"></i> Feedbacks</a></li>
                    <li><a href="#"><i class="fas fa-exchange-alt"></i> Transactions</a></li>
                </ul>
            </li>
            <li>
                <a href="#deviceSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-microchip"></i> <span>Device Management</span>
                </a>
                <ul class="collapse list-unstyled" id="deviceSubmenu">
                    <li><a href="device_overview.php"><i class="fas fa-th-large"></i> Device Overview</a></li>
                    <li><a href="assign_apartment.php"><i class="fas fa-building"></i> Assign Apartment/Room</a></li>
                    <li><a href="registration_approval.php"><i class="fas fa-clipboard-check"></i> Pending Approvals</a></li>
                </ul>
            </li>
            <li>
                <a href="#accountSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                    <i class="fas fa-wallet"></i> <span>Account Summary</span>
                </a>
                <ul class="collapse list-unstyled" id="accountSubmenu">
                    <li><a href="#"><i class="fas fa-chart-line"></i> Account Overview</a></li>
                    <li><a href="#"><i class="fas fa-bolt"></i> Top Up Units</a></li>
                </ul>
            </li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <div class="welcome-card animate__animated animate__fadeIn">
                    <div class="row g-0">
                        <div class="col-md-12 text-center">
                            <div class="welcome-content">
                                <h2>Pending Residence Approvals</h2>
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
                        <div class="card-body">
                            <?php if (!empty($pendingRequests)): ?>
                                <div class="table-container table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Client ID</th>
                                                <th>Phone Number</th>
                                                <th>Apartment</th>
                                                <th>Room</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingRequests as $request): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($request['client_id']) ?></td>
                                                    <td><?= htmlspecialchars($request['phone_number']) ?></td>
                                                    <td><?= htmlspecialchars($request['apartment_id']) ?></td>
                                                    <td><?= htmlspecialchars($request['room_id']) ?></td>
                                                    <td>
                                                        <form method="post" class="d-flex gap-2">
                                                            <input type="hidden" name="client_id" value="<?= $request['client_id'] ?>">
                                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No pending requests.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Dashboard Content -->
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
