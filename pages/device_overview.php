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

} catch (PDOException $e) {
    // Handle database errors
    if (getenv('ENVIRONMENT') === 'production') {
        trigger_error("Database error occurred", E_USER_ERROR);
    } else {
        trigger_error("Database error: " . $e->getMessage(), E_USER_ERROR);
    }
}

// Handle AJAX and ESP32 requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'fetch_apartments') {
        $apartments = $conn->query("SELECT * FROM apartments")->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($apartments);
        exit;
    }

    if ($action === 'fetch_rooms' && isset($_GET['apartment_id'])) {
        $apartmentId = $_GET['apartment_id'];
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE apartment_id = ?");
        $stmt->execute([$apartmentId]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($rooms);
        exit;
    }

    if ($action === 'fetch_room_details' && isset($_GET['room_id'])) {
        $roomId = $_GET['room_id'];

        $roomQuery = $conn->prepare("
            SELECT r.*, e.energy_consumed, e.remaining_units 
            FROM rooms r
            LEFT JOIN room_energy e ON r.room_id = e.room_id
            WHERE r.room_id = :room_id
        ");
        $roomQuery->execute(['room_id' => $roomId]);
        $room = $roomQuery->fetch();

        if ($room) {
            header('Content-Type: application/json');
            echo json_encode([
                'room_id' => $room['room_id'],
                'name' => $room['name'],
                'energy_consumed' => $room['energy_consumed'] ?? 0,
                'remaining_units' => $room['remaining_units'] ?? 0,
                'power_status' => $room['power_status']
            ]);
            exit;
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'No data found for this room']);
            exit;
        }
    }

    // **Fetch the power status for ESP32**
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if ($action === 'get_power_status' && isset($_GET['room_id'])) {
        $roomId = $_GET['room_id'];

        $stmt = $conn->prepare("SELECT power_status FROM rooms WHERE room_id = :room_id");
        $stmt->bindParam(':room_id', $roomId, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            header('Content-Type: application/json');
            echo json_encode(['room_id' => $roomId, 'power_status' => $row['power_status']]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Room not found']);
        }
        exit;
    }

    // New endpoint for updating power status
    if ($action === 'update_power_status' && isset($_POST['room_id']) && isset($_POST['power_status'])) {
        $roomId = $_POST['room_id'];
        $powerStatus = $_POST['power_status'];

        try {
            $updateStmt = $conn->prepare("UPDATE rooms SET power_status = :power_status WHERE room_id = :room_id");
            $result = $updateStmt->execute([
                'power_status' => $powerStatus,
                'room_id' => $roomId
            ]);

            // Get the updated power status from the database to confirm update
            $checkStmt = $conn->prepare("SELECT power_status FROM rooms WHERE room_id = :room_id");
            $checkStmt->execute(['room_id' => $roomId]);
            $updatedStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'power_status' => $updatedStatus['power_status'],
                'room_id' => $roomId
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update power status', 'message' => $e->getMessage()]);
            exit;
        }
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
    <title>Admin Dashboard - device Overview</title>
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

        form#filterForm {
            display: flex;
            gap: 20px;
            max-width: 60%;
            margin: 20px auto;
            padding: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
            justify-content: space-around;
            align-items: center;
        }

        form#filterForm label {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            display: none;
        }

        form#filterForm select {
            width: 100%;
            padding: 10px;
            border: 0px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: #fff;
            color: #333;
            outline: none;
            transition: border-color 0.3s;
        }

        form#filterForm select:focus {
            border-color: #007BFF;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        form#filterForm select option {
            padding: 10px;
            background-color: #fff;
            color: #333;
        }

        form#filterForm select option:hover {
            background-color: #007BFF;
            color: #fff;
        }


        form#filterForm button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        form#filterForm button:hover {
            background-color: #0056b3;
        }


        #roomWidgets {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
            max-width: 90%;
            margin-left: 5%;
        }

        .widget {
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
            text-align: center;
        }

        .widget h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 20px;
        }

        /* Power Toggle Switch Styling */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 120px;
            height: 50px;
            margin: 10px 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 40px;
            width: 40px;
            left: 5px;
            bottom: 5px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #2196F3;
        }

        input:focus+.toggle-slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(70px);
        }

        .toggle-status {
            margin-top: 10px;
            font-weight: bold;
            color: #333;
            margin-bottom: 70px;
        }

        .btn {
            text-decoration: none;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            form#filterForm {
                display: flex;
                gap: 10px;
                max-width: 100%;
                margin: 10px auto;
                padding: 20px 10px;
            }

            form#filterForm label {
                font-size: 12px;
            }

            form#filterForm select {
                font-size: 12px;
            }

            form#filterForm button {
                padding: 5px 10px;
                font-size: 14px;
            }

            #roomWidgets {
                max-width: 100%;
                margin-left: 0;
            }

            .toggle-slider {
                cursor: zoom-out;
            }
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
                                    <h2>Device Overview</h2>
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
                                <h5>Total Devices: <?php echo $totalTenants; ?></h5>
                            </div>

                            <div class="admin-dashboard">
                                <form id="filterForm">
                                    <div>
                                        <label for="apartment">Select Apartment:</label>
                                        <select id="apartment" name="apartment" onchange="fetchRooms(this.value)">
                                            <option value="">Select Apartment</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="room">Select Room:</label>
                                        <select id="room" name="room" onchange="fetchRoomDetails(this.value)">
                                            <option value="">Select Room</option>
                                        </select>
                                    </div>
                                </form>

                                <div id="roomWidgets">
                                    <div class="widget">
                                        <h3>Units consumed</h3>
                                        <div id="energyConsumptionGauge"></div>
                                    </div>
                                    <div class="widget">
                                        <h3>Remaining Units</h3>
                                        <div id="remainingUnitsGauge"></div>
                                    </div>
                                    <div class="widget">
                                        <h3>Room Power Control</h3>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="powerToggle">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <div class="toggle-status" id="powerStatus">Status: OFF</div>
                                        <a href="#" id="viewDetailsLink" class="btn">View Details</a>
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
    <script src="../scripts/visual_charts.js"></script>


    <!-- Search and Filter Functionality -->
    <script>
        // Fetch apartments on page load
        fetch('device_overview.php?action=fetch_apartments')
            .then(response => response.json())
            .then(data => {
                const apartmentSelect = document.getElementById('apartment');
                data.forEach(apartment => {
                    apartmentSelect.innerHTML += `<option value="${apartment.apartment_id}">${apartment.name}</option>`;
                });
            });

        // Fetch rooms based on selected apartment
        function fetchRooms(apartmentId) {
            const roomSelect = document.getElementById('room');
            roomSelect.innerHTML = '<option value="">Select Room</option>';
            document.getElementById('roomWidgets').style.display = 'none';

            if (!apartmentId) return;

            fetch(`device_overview.php?action=fetch_rooms&apartment_id=${apartmentId}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(room => {
                        roomSelect.innerHTML += `<option value="${room.room_id}">${room.name}</option>`;
                    });
                });
        }

        // Fetch room details based on selected room
        function fetchRoomDetails(roomId) {
            const roomWidgets = document.getElementById('roomWidgets');
            roomWidgets.style.display = 'none';

            if (!roomId) return;

            fetch(`device_overview.php?action=fetch_room_details&room_id=${roomId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('No room data found');
                    }
                    return response.json();
                })
                .then(room => {
                    // Show widgets
                    roomWidgets.style.display = 'grid';

                    // Create Energy Consumption Gauge
                    Plotly.newPlot('energyConsumptionGauge', [{
                        type: 'indicator',
                        mode: 'gauge+number',
                        value: room.energy_consumed,
                        number: {
                            font: {
                                size: 35
                            },
                            suffix: "<span style='font-size:14px;'>kWh</span>",
                            valueformat: ".2f"
                        },
                        gauge: {
                            axis: {
                                range: [0, 150],
                                tickfont: {
                                    size: 15
                                }
                            },
                            steps: [{
                                    range: [0, 50],
                                    color: "green"
                                },
                                {
                                    range: [50, 100],
                                    color: "green"
                                },
                                {
                                    range: [100, 150],
                                    color: "green"
                                }
                            ],
                            bar: {
                                color: "#f6921e",
                                thickness: 0.5
                            }
                        }
                    }], {
                        responsive: true,
                        height: 220,
                        margin: {
                            t: 20,
                            b: 10,
                            l: 25,
                            r: 45
                        }
                    });

                    // Create Remaining Units Gauge
                    Plotly.newPlot('remainingUnitsGauge', [{
                        type: 'indicator',
                        mode: 'gauge+number',
                        value: room.remaining_units,

                        number: {
                            font: {
                                size: 35
                            },
                            suffix: "<span style='font-size:14px;'>kWh</span>",
                            valueformat: ".2f"
                        },
                        gauge: {
                            axis: {
                                range: [0, 150],
                                tickfont: {
                                    size: 15
                                }
                            },
                            steps: [{
                                    range: [0, 50],
                                    color: "green"
                                },
                                {
                                    range: [50, 100],
                                    color: "green"
                                },
                                {
                                    range: [100, 150],
                                    color: "green"
                                }
                            ],
                            bar: {
                                color: "#f6921e",
                                thickness: 0.5
                            }
                        }
                    }], {
                        responsive: true,
                        height: 220,
                        margin: {
                            t: 20,
                            b: 10,
                            l: 25,
                            r: 45
                        }
                    });

                    // Setup power toggle
                    setupPowerToggle(room);

                    // Update view details link
                    const viewDetailsLink = document.getElementById('viewDetailsLink');
                    viewDetailsLink.href = `view_details.php?room_id=${room.room_id}`;
                })
                .catch(error => {
                    console.error('Error:', error);
                    roomWidgets.style.display = 'none';
                });
        }

        // Power Toggle Setup
        function setupPowerToggle(room) {
            const powerToggle = document.getElementById('powerToggle');
            const powerStatus = document.getElementById('powerStatus');
            const remainingUnits = parseFloat(room.remaining_units);

            // Set initial state
            powerToggle.checked = room.power_status === 'ON';
            powerStatus.textContent = `Status: ${room.power_status}`;

            // Disable toggle if remaining units are 0.1 or below
            if (remainingUnits <= 0.1) {
                powerToggle.disabled = true;
                powerToggle.checked = false; // Force OFF state
                powerStatus.textContent = `Status: OFF (Insufficient Units)`;

                // Update database if power status is currently ON but units are insufficient
                if (room.power_status === 'ON') {
                    const formData = new FormData();
                    formData.append('room_id', room.room_id);
                    formData.append('power_status', 'OFF');

                    fetch('device_overview.php?action=update_power_status', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to update power status');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Update the room object with new status
                                room.power_status = data.power_status;
                                console.log('Power status forced to OFF due to insufficient units');
                            } else {
                                throw new Error('Update was not successful');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating power status:', error);
                            // Despite error, maintain UI in OFF state but log the issue
                        });
                }
            } else {
                powerToggle.disabled = false;
            }

            // Remove previous event listeners
            const oldToggle = powerToggle.cloneNode(true);
            powerToggle.parentNode.replaceChild(oldToggle, powerToggle);

            // Add new event listener
            oldToggle.addEventListener('change', function() {
                const newStatus = this.checked ? 'ON' : 'OFF';

                // Show loading state or disable toggle during update
                this.disabled = true;
                // powerStatus.textContent = `Status: Updating...`;

                // Store reference to toggle element for use in callback
                const toggleElement = this;

                const formData = new FormData();
                formData.append('room_id', room.room_id);
                formData.append('power_status', newStatus);

                fetch('device_overview.php?action=update_power_status', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to update power status');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Update UI with confirmed status from server
                        if (data.success) {
                            // Update the room object with new status
                            room.power_status = data.power_status;

                            // Update UI elements
                            powerStatus.textContent = `Status: ${data.power_status}`;
                            toggleElement.checked = data.power_status === 'ON';

                            console.log('Power status updated successfully to:', data.power_status);
                        } else {
                            throw new Error('Update was not successful');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating power status:', error);

                        // Revert toggle to match the original room state
                        toggleElement.checked = room.power_status === 'ON';
                        powerStatus.textContent = `Status: ${room.power_status} (Update failed)`;

                        // Alert the user
                        alert('Failed to update power status. Please try again.');
                    })
                    .finally(() => {
                        // Re-enable toggle after update attempt
                        toggleElement.disabled = false;
                    });
            });
        }
    </script>
</body>

</html>