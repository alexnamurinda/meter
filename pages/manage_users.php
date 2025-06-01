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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $userId = $_POST['id'] ?? null;
        $userType = $_POST['client_category'] ?? null;

        if ($userId && $userType === 'tenant') {
            $deleteQuery = "DELETE FROM clients WHERE client_id = :id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $userId, PDO::PARAM_INT);

            if ($deleteStmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Deletion failed']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }

        exit; // Important: Stop further HTML rendering for AJAX
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
    <title>Admin Dashboard - manage users</title>
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
        /* Additional CSS for manage users page */
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

        .user-table {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .user-table thead th {
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
            
            #searchInput, #apartmentFilter {
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
                                    <h2></i>Registered Clients List</h2>
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

                            <div class="card-body">
                                <!-- Action Bar -->
                                <div class="action-bar mb-4">
                                    <div>
                                        <select id="apartmentFilter" class="form-select form-select-sm d-inline-block w-auto me-2">
                                            <option value="">All Apartments</option>
                                            <?php while ($apartment = $apartmentStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                                <option value="<?php echo htmlspecialchars($apartment['apartment_id']); ?>">
                                                    <?php echo htmlspecialchars($apartment['apartment_id']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <input type="text" id="searchInput" class="form-control form-control-sm d-inline-block w-auto" placeholder="Search by name or phone">
                                    </div>
                                    <a href="add-client.php" class="btn btn-sm btn-primary">
                                        <i class="fas fa-user-plus me-1"></i> Add New Client
                                    </a>
                                </div>

                                <!-- Users Table -->
                                <div class="table-container">
                                    <table class="user-table table table-bordered table-hover">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Client ID</th>
                                                <th>Client Name</th>
                                                <th>Category</th>
                                                <th>Phone Number</th>
                                                <th>Apartment</th>
                                                <th>Room</th>
                                                <th>Date of Registration</th>
                                                <th>Last Login</th>
                                                <th colspan="2">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userTableBody">
                                            <?php while ($client = $clientStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr class="user-row" data-id="<?php echo $client['client_id']; ?>" data-apartment="<?php echo $client['apartment_id']; ?>">
                                                    <td><?php echo htmlspecialchars($client['client_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['client_category']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['phone_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['apartment_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['room_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['registered_on']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['last_login']); ?></td>
                                                    <td><a href="edit_client_details.php?id=<?php echo $client['client_id']; ?>" class="edit-user btn btn-sm btn-outline-primary">Edit</a></td>
                                                    <td><span class="delete-user btn btn-sm btn-outline-danger">Delete</span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
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
                            <p>&copy; 2024 Kooza Technologies. All Rights Reserved.</p>
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

    <!-- Script to delete user -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tableBody = document.getElementById("userTableBody");

            tableBody.addEventListener("click", function(event) {
                if (event.target.classList.contains("delete-user")) {
                    const row = event.target.closest("tr");
                    const userId = row.dataset.id;
                    const clientCategory = row.children[2].textContent;

                    if (confirm("Are you sure you want to delete this client?")) {
                        fetch(window.location.href, {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded",
                                },
                                body: `action=delete&id=${encodeURIComponent(userId)}&client_category=${encodeURIComponent(clientCategory)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    row.remove(); // Remove the row
                                } else {
                                    alert("Error: " + (data.error || "Unable to delete user"));
                                }
                            })
                            .catch(err => {
                                alert("Network error: " + err);
                            });
                    }
                }
            });
        });
    </script>

    <!-- Search and Filter Functionality -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const apartmentFilter = document.getElementById('apartmentFilter');
        const rows = document.querySelectorAll('.user-row');

        function filterRows() {
            const searchValue = searchInput.value.toLowerCase();
            const apartmentValue = apartmentFilter.value;

            rows.forEach(row => {
                const name = row.children[1].textContent.toLowerCase();
                const phone = row.children[3].textContent.toLowerCase();
                const apartment = row.getAttribute('data-apartment');

                const matchesSearch = name.includes(searchValue) || phone.includes(searchValue);
                const matchesApartment = apartmentValue === '' || apartment === apartmentValue;

                row.style.display = matchesSearch && matchesApartment ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterRows);
        apartmentFilter.addEventListener('change', filterRows);
    </script>

</body>

</html>