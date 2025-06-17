<?php
session_start();
date_default_timezone_set('Africa/Kampala');
include '../databases/databaseconnection.php';

// Ensure only a user can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['authenticated'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Access the session data (phone_number)
$phone_number = $_SESSION['user']['phone_number'];

// Fetch user details using PDO
$sql = "SELECT * FROM clients WHERE phone_number = :phone_number LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() === 1) {
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    $client_id = $client['client_id'];
    $profile_pic = $client['profile_pic'] ?? '../images/profile_pic.png';
    $apartment_id = $client['apartment_id'] ?? '';
    $room_id = $client['room_id'] ?? '';
} else {
    echo "User not found.";
    exit();
}

// Greeting logic
$current_hour = date('H');
if ($current_hour >= 5 && $current_hour < 12) {
    $greeting = "Good Morning";
} elseif ($current_hour >= 12 && $current_hour < 16) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

// Profile Pic logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (!empty($_FILES['profile_pic']['name'])) {
        $file_name = $_FILES['profile_pic']['name'];
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $target_directory = "../images/";
        $target_file = $target_directory . basename($file_name);

        // Validate and move uploaded file
        if (move_uploaded_file($file_tmp, $target_file)) {
            // Update the database with the new profile picture path
            $update_sql = "UPDATE clients SET profile_pic = :profile_pic WHERE client_id = :client_id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindParam(':profile_pic', $target_file, PDO::PARAM_STR);
            $update_stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);

            if ($update_stmt->execute()) {
                // Refresh the page to reflect the updated profile picture
                header("Location: userdashboard.php");
                exit();
            } else {
                echo "Failed to update profile picture in the database.";
            }
        } else {
            echo "Failed to upload the profile picture.";
        }
    } else {
        echo "Please select a file to upload.";
    }
}

// Handling apartment and room selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_residence'])) {
    $apartmentId = $_POST['apartment'] ?? null;
    $roomId = $_POST['room'] ?? null;
    $password = $_POST['password'] ?? null;

    // Validate input
    if (empty($apartmentId) || empty($roomId) || empty($password)) {
        die('Please fill in all fields.');
    }

    try {
        // Verify password
        $stmt = $conn->prepare("SELECT client_password FROM clients WHERE phone_number = ?");
        $stmt->execute([$phone_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['client_password'])) {
            echo "<script>
            alert('Incorrect password. Please try again!');
            window.location.href = 'userdashboard.php';
          </script>";
            exit();
        }

        // Update apartment_id, room_id, and set registration_status to 'under review'
        $updateStmt = $conn->prepare("
            UPDATE clients 
            SET apartment_id = ?, room_id = ?, registration_status = 'under review'
            WHERE phone_number = ?
        ");
        $updateStmt->execute([$apartmentId, $roomId, $phone_number]);

        // Send notification to admin (assuming an 'admin_notifications' table)
        $notifyStmt = $conn->prepare("
            INSERT INTO admin_notifications (client_id, message, status)
            VALUES (?, 'New residence request pending approval.', 'unread')
        ");
        $notifyStmt->execute([$client_id]);

        // Success: Display success alert
        echo "<script>
                alert('Your residence request is under review. Please wait for admin approval.');
                window.location.href = 'userdashboard.php';
              </script>";
        exit();
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        exit();
    }
}

// Initialize the options string for apartments
$apartmentOptions = '';
try {
    // Fetch apartments from the database
    $stmt = $conn->query("SELECT apartment_id, name FROM apartments");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Build the options string
        $apartmentOptions .= "<option value='{$row['apartment_id']}'>{$row['name']}</option>";
    }
} catch (PDOException $e) {
    $apartmentOptions = "<option value=''>Error loading apartments</option>";
}


// Fetch the user's current apartment, room, and registration status
try {
    $stmt = $conn->prepare("
        SELECT 
            a.name AS apartment_name, 
            r.name AS room_name,
            c.registration_status
        FROM clients c
        LEFT JOIN apartments a ON c.apartment_id = a.apartment_id
        LEFT JOIN rooms r ON c.room_id = r.room_id
        WHERE c.phone_number = ?
    ");
    $stmt->execute([$phone_number]);
    $userResidence = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userResidence && !empty($userResidence['apartment_name']) && !empty($userResidence['room_name'])) {
        $status = htmlspecialchars($userResidence['registration_status']);

        if ($status == "under review") {
            $residenceHeading = '<h2 style="text-align:center; color: orange;">Residence Request: Under Review</h2>';
        } elseif ($status == "approved") {
            $residenceHeading = sprintf(
                '<h2 style="text-align:center;">Manage Your Energy Usage in %s, %s</h2>',
                htmlspecialchars($userResidence['apartment_name']),
                htmlspecialchars($userResidence['room_name'])
            );
        } else {
            $residenceHeading = '<h2 style="text-align:center;">Manage Your Energy Usage with Real-Time Monitoring</h2>';
        }
    } else {
        $residenceHeading = '<h2 style="text-align:center;">Manage Your Energy Usage with Real-Time Monitoring</h2>';
    }
} catch (PDOException $e) {
    $residenceHeading = '<h2 style="text-align:center;">Manage Your Energy Usage with Real-Time Monitoring</h2>';
}

// Fetch alerts for this user's room
$alerts = [];

if (!empty($room_id)) {
    try {
        $stmt = $conn->prepare("
            SELECT alert_text, alert_date 
            FROM alerts 
            WHERE room_id = :room_id 
            ORDER BY alert_date DESC 
            LIMIT 5
        ");
        $stmt->bindParam(':room_id', $room_id, PDO::PARAM_STR);
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Error fetching alerts: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>welcome to kooza smart electric meter dashboard</title>
    <link rel="stylesheet" href="../css/userdashboard.css">
    <link rel="stylesheet" href="../css/userdash_responsive.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="" />
</head>

<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <img src="../images/logo.png" alt="Logo" class="logo">
            <h2><?php echo $greeting . ", " .  htmlspecialchars(ucwords(strtolower(strtok($_SESSION['user']['name'], ' ')))); ?> !</h2>
            <nav class="nav">
                <div class="user-profile">
                    <img src="<?php echo $profile_pic; ?>" alt="User Profile" class="profile-dp" id="profilePic">
                </div>
            </nav>
        </header>

        <!-- Hidden menu for changing profile picture -->
        <div id="profilePicMenu" class="profile-pic-menu">
            <form action="" method="POST" enctype="multipart/form-data">
                <label for="profile_pic">Update Profile Picture:</label>
                <input type="file" name="profile_pic" id="profile_pic" required>
                <button type="submit" name="upload">Update</button>
            </form>
        </div>



        <div class="dashboard-content">
            <div class="menu-container">
                <button class="hamburger-menu" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-details" id="menu">
                    <ul>
                        <li><a href="#" id="dashboard-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="#" id="usage-link"><i class="fas fa-bolt"></i> Usage Overview</a></li>
                        <li><a href="#" id="recharge-link"><i class="fas fa-credit-card"></i> Recharge Meter</a></li>
                        <li><a href="#" id="payment-link"><i class="fas fa-money-bill-wave"></i> Payment History</a></li>
                        <li>
                            <a href="#" id="alerts-link">
                                <i class="fas fa-bell"></i> Low Balance Alerts
                                <span class="badge" id="notification-count">2</span>
                            </a>
                        </li>
                        <li><a href="#" id="support-link"><i class="fas fa-life-ring"></i> Support & FAQs</a></li>
                        <li><a href="#" id="settings-link"><i class="fas fa-id-card"></i> My Account</a></li>
                        <a href="logout.php"><button class="log-button">Logout</button></a>
                    </ul>
                </div>
            </div>


            <div class="dashboard-actions">
                <div id="dashboard-content1" class="content-section" style="display: block;">
                    <div class="dashboard-text">
                        <?php echo $residenceHeading; ?>
                    </div>
                    <div class="dashboard-grid">
                        <div class="dashboard-item" id="section1">
                            <div id="sensorGaugeFront" style="width:100%; height:100%"></div>
                        </div>


                        <div class="dashboard-item" id="section2">
                            <div class="flip-card">
                                <div class="flip-card-inner" id="flipCard">
                                    <!-- Front of the card -->
                                    <div class="flip-card-front">
                                        <div id="energyConsumptionGauge" style="width: 100%; height: 100%;"></div>
                                    </div>

                                    <!-- Back of the card -->
                                    <div class="flip-card-back" style="display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: #1e90ff; color: white; text-align: center; border-radius: 5px; padding: 10px;">
                                        <div style="font-size: 15px; font-weight: bold; margin-bottom: 5px;">Kooza Smart Meter Solutions</div>
                                        <div style="width: 80%; height: 1px; background-color: white; margin: 10px 0;"></div>
                                        <div style="font-size: 10px; line-height: 1.6; margin-bottom: 10px;">
                                            <span>Email: support@koozasmart.com</span><br>
                                            <span>Phone: +256 780 393671</span><br>
                                            <span>Website: www.koozasmart.com</span>
                                        </div>
                                        <div>
                                            <img src="../images/logo.png" alt="QR Code" style="width: 80px; height: 50px; border-radius: 5px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-item" id="section3">
                            <h3 style="text-align:center; color: #333">Low consumption Tips</h3>
                            <div class="tip-card">
                                <div class="card-inner">
                                    <div class="card-front">
                                        <p>Turn off appliances when not in use to save energy.</p>
                                    </div>
                                    <div class="card-back">
                                        <p>Consider using energy-efficient bulbs and devices.</p>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="flip-card" id="dailyEnergyChart1"></div> -->
                        </div>
                    </div>

                    <!-- <div class="tip-card">
                        <h3 style="text-align:center; color: #333">Low consumption Tips</h3>
                        <div class="card-inner">
                            <div class="card-front">
                                <p>Turn off appliances when not in use to save energy.</p>
                            </div>
                            <div class="card-back">
                                <p>Consider using energy-efficient bulbs and devices.</p>
                            </div>
                        </div>
                    </div> -->
                </div>

                <!-- usage section -->
                <div id="usage-content" class="content-section" style="display:none;">
                    <div class="usage-container">
                        <div class="usage-summary">
                            <div class="summary-card">
                                <h4>Today's Usage</h4>
                                <p id="today-usage">Loading...</p>
                            </div>
                            <div class="summary-card">
                                <h4>This Week</h4>
                                <p id="weekly-usage">Loading...</p>
                            </div>
                            <div class="summary-card">
                                <h4>This Month</h4>
                                <p id="monthly-usage">Loadind..</p>
                            </div>
                        </div>
                        <div id="dailyEnergyChart2" class="chart-container" style="width: fit-content;">
                        </div>
                    </div>
                </div>

                <!-- Recharge -->
                <div id="recharge-content" class="content-section" style="display:none;">
                    <div class="recharge-container">
                        <!-- Package Selection -->
                        <div class="package-selection">
                            <h3>Choose a Package</h3>
                            <div class="packages">
                                <button class="package-btn" data-amount="10000" data-kwh="20">20 kWh - UGX 10,000</button>
                                <button class="package-btn" data-amount="20000" data-kwh="40">40 kWh - UGX 20,000</button>
                                <button class="package-btn" data-amount="50000" data-kwh="100">100 kWh - UGX 50,000</button>
                                <button class="package-btn" data-amount="100000" data-kwh="220">220 kWh - UGX 100,000</button>
                            </div>
                        </div>

                        <!-- Manual Recharge -->
                        <form id="recharge-form" class="manual-recharge">
                            <h3>Or Enter Custom Amount</h3>
                            <!-- <label for="recharge-amount">Enter Amount to Recharge (UGX):</label> -->
                            <input type="number" id="recharge-amount" placeholder="Enter Amount to Recharge e.g., 10,000" required>
                            <p id="recharge-message" style="text-align:center; margin:10px; color: green;"></p>
                            <!-- <label for="payment-method">Choose Payment Method:</label> -->
                            <select id="payment-method">
                                <option value="" disabled selected>Select Payment Method</option>
                                <option value="mtn">MTN Mobile Money</option>
                                <option value="airtel">Airtel Money</option>
                                <option value="card">Credit/Debit Card</option>
                            </select>
                            <div id="payment-details"></div>
                            <button type="submit" class="recharge-btn">Recharge Now</button>
                        </form>
                    </div>
                </div>

                <!-- Account settings -->
                <div id="account-content" class="content-section" style="display:none;">
                    <div class="account-container">
                        <h3>Set up your Account</h3>
                        <div id="selection-alert" class="alert alert-info" style="display: none;">
                            <strong>Important:</strong> Once confirmed, you will not be able to select new apartment or room again. Kindly contact Customer support to be assigned a new room.
                        </div>

                        <form id="residence-form" class="residence" method="post" action="">
                            <div class="form-container">
                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="apartment">Select Apartment:</label>
                                        <select id="apartment" name="apartment" required>
                                            <option value="">-- Select your Apartment --</option>
                                            <?php echo $apartmentOptions; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-column">
                                    <div class="form-group">
                                        <label for="room">Select Room:</label>
                                        <select id="room" name="room" required>
                                            <option value="">-- Available Rooms --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Password Section -->
                            <div id="password-section" style="display:none; margin-top: 20px;">
                                <label for="password">Enter your password to confirm changes:</label>
                                <input type="password" id="password" name="password" required />
                                <button type="submit" name="save_residence" id="save-residence-button">Save Residence</button>
                            </div>

                            <button type="button" id="show-password-section" style="margin-top: 20px;">Save Changes</button>
                        </form>
                    </div>
                </div>

                <?php
                // Fetch transactions for the logged-in user
                $transactions_sql = "SELECT * FROM transactions WHERE client_id = :client_id ORDER BY transaction_date ASC";
                $transactions_stmt = $conn->prepare($transactions_sql);
                $transactions_stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
                $transactions_stmt->execute();
                $transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <!-- Payment Content Section -->
                <div id="payment-content" class="content-section" style="display:none;">
                    <!-- Download Button -->
                    <div class="download-button-container">
                        <button id="download-pdf" class="download-btn">Download Statement (PDF)</button>
                    </div>

                    <!-- Payment History Container -->
                    <div class="payment-history-container">
                        <!-- Payment Table -->
                        <div class="payment-table-wrapper">
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Amount Paid</th>
                                        <th>Units Received</th>
                                        <th>Payment Method</th>
                                        <th>Transaction Details</th>
                                    </tr>
                                </thead>
                                <tbody id="payment-records">
                                    <?php if (!empty($transactions)) : ?>
                                        <?php foreach ($transactions as $transaction) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($transaction['transaction_date']); ?></td>
                                                <td>UGX <?= number_format($transaction['amount']); ?></td>
                                                <td><?= htmlspecialchars($transaction['kwh']); ?> kWh</td>
                                                <td><?= htmlspecialchars($transaction['payment_method']); ?></td>
                                                <td><?= htmlspecialchars($transaction['payment_details']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5">No payment history available.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="payment-footer">
                        <p>Kooza Smart Meter Solutions</p>
                        <p>123 Energy Street, Kampala, Uganda</p>
                        <p>+256 780 393671 | support@smartmeter.com</p>
                        <p><a href="index.php" target="_blank">www.koozasmartmeter.com</a></p>
                        <p><em>Thank you for choosing Kooza Smart Meter Solutions!</em></p>
                    </div>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        // When the button is clicked, create the PDF
                        document.getElementById("download-pdf").addEventListener("click", function() {
                            // Create a new jsPDF instance
                            const {
                                jsPDF
                            } = window.jspdf;
                            const doc = new jsPDF();

                            // Capture the table and convert it to a format that jsPDF can use
                            let table = document.querySelector(".payment-table");

                            // Add title to the PDF
                            doc.setFontSize(16);
                            doc.text("Payment Statement", 14, 20);

                            // Add the table to the PDF
                            doc.autoTable({
                                html: '.payment-table'
                            });

                            // Save the generated PDF
                            doc.save('payment_statement.pdf');
                        });
                    });
                </script>


                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        fetch('fetch_transactions.php')
                            .then(response => response.json())
                            .then(data => {
                                let paymentRecords = document.getElementById("payment-records");
                                paymentRecords.innerHTML = ""; // Clear existing rows

                                if (data.error) {
                                    paymentRecords.innerHTML = `<tr><td colspan="5">${data.error}</td></tr>`;
                                    return;
                                }

                                data.forEach(transaction => {
                                    let row = `<tr>
                        <td>${transaction.transaction_date}</td>
                        <td>UGX ${transaction.amount.toLocaleString()}</td>
                        <td>${transaction.kwh} kWh</td>
                        <td>${transaction.payment_method}</td>
                        <td>${transaction.transaction_id}</td>
                    </tr>`;
                                    paymentRecords.innerHTML += row;
                                });
                            })
                            .catch(error => console.error('Error fetching transactions:', error));
                    });
                </script>

                <!-- Alerts -->
                <div id="alerts-content" class="content-section" style="display:none;">
                    <div class="alerts-container">
                        <h2>Recent Alerts</h2>
                        <div id="current-alerts">
                            <?php if (!empty($alerts)): ?>
                                <?php foreach ($alerts as $index => $alert): ?>
                                    <p>
                                        <strong><?= htmlspecialchars($alert['alert_date']) ?>:</strong>
                                        <?= htmlspecialchars($alert['alert_text']) ?>
                                    </p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No alerts at the moment for your room. Stay tuned!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Support -->
                <div id="support-content" class="content-section" style="display:none;">
                    <div class="support-container">
                        <!-- FAQs Section -->
                        <h3>FAQs</h3>
                        <ul id="faq-list">
                            <!-- Initially Displayed FAQs -->
                            <li>
                                <strong>What happens when my balance runs out?</strong>
                                <p>Your energy supply will be interrupted. You can restore services by recharging your meter.</p>
                            </li>
                        </ul>
                        <!-- Hidden Additional FAQs -->
                        <ul id="more-faqs" style="display: none;">
                            <li>
                                <strong>Can I schedule automatic recharges?</strong>
                                <p>Yes, you can set up automatic recharges in the "Recharge" section using your preferred payment method.</p>
                            </li>
                            <li>
                                <strong>How do I view my payment history?</strong>
                                <p>Navigate to the "Payment History" section to view a detailed record of all transactions.</p>
                            </li>
                        </ul>
                        <button id="read-more-btn" class="faq-btn">Read More</button>
                    </div>

                    <!-- Chat with Customer Care -->
                    <div class="chat-container">
                        <h3>Chat with Customer Care</h3>
                        <div id="chat-box" class="chat-box">
                            <div class="chat-messages" id="chat-messages">
                                <p class="bot-message">Welcome! How can we assist you today?</p>
                            </div>
                            <form id="chat-form">
                                <input type="text" id="chat-input" placeholder="Type your message here..." required>
                                <button type="submit">Send</button>
                            </form>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../scripts/payment.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

    <script src="../scripts/visual_charts.js"></script>

    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <!-- <script src="myCharts.js"></script> -->
    <script src="../scripts/event_listener.js"></script>
    <!-- <script src="maps.js"></script> -->
    <!-- <script src="notifications.js"></script> -->
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
    <script
        type="text/javascript"
        src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.getElementById("download-pdf").addEventListener("click", function() {
            const doc = new jsPDF(); // Ensure jsPDF is included in your project
            const paymentTable = document.querySelector(".payment-table");
            const rows = Array.from(paymentTable.querySelectorAll("tr")).map(row =>
                Array.from(row.cells).map(cell => cell.textContent)
            );

            doc.text("Payment History", 14, 16);
            doc.autoTable({
                head: [rows[0]], // Table headers
                body: rows.slice(1), // Table rows
                startY: 20,
            });

            doc.save("Payment_History.pdf");
        });
    </script>
    <script>
        // Mark Alerts as Read
        document.addEventListener('DOMContentLoaded', () => {
            const notificationBadge = document.getElementById('notification-count');
            const alertsContent = document.getElementById('alerts-content');
            const currentAlerts = document.getElementById('current-alerts');

            // Simulate fetching new alerts count
            let unreadAlerts = 2;
            notificationBadge.textContent = unreadAlerts;

            notificationBadge.addEventListener('click', () => {
                unreadAlerts = 0;
                notificationBadge.textContent = unreadAlerts;
                notificationBadge.style.display = 'none'; // Hide badge
                alertsContent.style.display = 'block'; // Show alerts section
            });
        });

        // Dynamic Consumption Tips (Optional for Expansion)
        const tips = [
            "Turn off appliances when not in use to save energy.",
            "Consider using energy-efficient bulbs and devices.",
            "Monitor your daily usage to identify patterns.",
            "Reduce energy usage during peak hours."
        ];
        let currentTip = 0;

        // Change tip every 5 seconds
        setInterval(() => {
            currentTip = (currentTip + 1) % tips.length;
            document.querySelector(".card-front p").textContent = tips[currentTip];
            document.querySelector(".card-back p").textContent =
                tips[(currentTip + 1) % tips.length];
        }, 5000);
    </script>
    <script>
        // Toggle FAQs
        document.getElementById("read-more-btn").addEventListener("click", function() {
            const moreFAQs = document.getElementById("more-faqs");
            const btn = this;
            if (moreFAQs.style.display === "none") {
                moreFAQs.style.display = "block";
                btn.textContent = "Read Less";
            } else {
                moreFAQs.style.display = "none";
                btn.textContent = "Read More";
            }
        });

        document.getElementById('chat-form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from refreshing the page

            const chatInput = document.getElementById('chat-input');
            const chatMessages = document.getElementById('chat-messages');
            const userMessage = chatInput.value.trim();

            if (userMessage !== '') {
                // Create a new user message element
                const userMessageElement = document.createElement('p');
                userMessageElement.classList.add('user-message');
                userMessageElement.textContent = userMessage;

                // Append the user message to the chat
                chatMessages.appendChild(userMessageElement);

                // Scroll to the latest message
                chatMessages.scrollTop = chatMessages.scrollHeight;

                // Clear the input field
                chatInput.value = '';

                // Simulate a bot reply (optional)
                setTimeout(() => {
                    const botReplyElement = document.createElement('p');
                    botReplyElement.classList.add('bot-message');
                    botReplyElement.textContent = 'Thank you for your message. Our support team will get back to you shortly.';
                    chatMessages.appendChild(botReplyElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 1000);
            }
        });
    </script>

    <script>
        function updateEnergyData() {
            fetch('energy_data.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('today-usage').textContent = data.energy_consumed + ' kWh';
                    document.getElementById('weekly-usage').textContent = data.energy_consumed + ' kWh';
                    document.getElementById('monthly-usage').textContent = data.energy_consumed + ' kWh';
                })
                .catch(error => console.error('Error fetching energy data:', error));
        }

        // Update every 5 seconds
        setInterval(updateEnergyData, 100);

        // Initial update
        updateEnergyData();
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const menuToggle = document.getElementById("menu-toggle");
            const menu = document.getElementById("menu");

            // Toggle the menu when the hamburger icon is clicked
            menuToggle.addEventListener("click", function(event) {
                event.stopPropagation(); // Prevent click from propagating to the document
                menu.classList.toggle("show");
            });

            // Close the menu when clicking anywhere outside it
            document.addEventListener("click", function() {
                if (menu.classList.contains("show")) {
                    menu.classList.remove("show");
                }
            });

            // Close the menu when a menu item is clicked
            menu.addEventListener("click", function(event) {
                if (event.target.tagName === "A" || event.target.tagName === "BUTTON") {
                    menu.classList.remove("show");
                }
            });
        });
    </script>

    <script>
        // Fetch rooms dynamically
        const apartmentSelect = document.getElementById('apartment');
        const roomSelect = document.getElementById('room');
        const selectionAlert = document.getElementById('selection-alert');
        const passwordSection = document.getElementById('password-section');
        const saveButton = document.getElementById('show-password-section');
        const form = document.getElementById('residence-form');

        apartmentSelect.addEventListener('change', async function() {
            const apartmentId = this.value;
            roomSelect.innerHTML = '<option value="">-- Available Rooms --</option>'; // Reset rooms

            if (apartmentId) {
                try {
                    const response = await fetch(`fetch_rooms.php?apartment_id=${apartmentId}`);
                    const data = await response.json();

                    if (data.error) {
                        console.error('Error:', data.error);
                        roomSelect.innerHTML = '<option value="">No rooms available</option>';
                    } else if (data.length === 0) {
                        roomSelect.innerHTML = '<option value="">No available rooms</option>';
                    } else {
                        data.forEach(room => {
                            const option = document.createElement('option');
                            option.value = room.room_id;
                            option.textContent = room.name;
                            roomSelect.appendChild(option);
                        });
                    }

                    selectionAlert.style.display = 'block';
                } catch (error) {
                    console.error('Error fetching rooms:', error);
                    roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
                }
            }
        });

        // Show password section
        saveButton.addEventListener('click', function() {
            const apartmentId = apartmentSelect.value;
            const roomId = roomSelect.value;

            if (apartmentId && roomId) {
                passwordSection.style.display = 'block';
                this.style.display = 'none';
            } else {
                alert('Please select both an apartment and a room before saving.');
            }
        });
    </script>

    <!-- Include Plotly.js -->
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <script src="../scripts/energy_chart.js"></script>

    <script>
        // Get the profile picture and menu elements
        const profilePic = document.getElementById('profilePic');
        const profilePicMenu = document.getElementById('profilePicMenu');

        profilePic.onclick = function(event) {
            event.stopPropagation();

            // Toggle visibility of the menu
            if (profilePicMenu.style.display === 'block') {
                profilePicMenu.style.display = 'none'; // Hide the menu if it is already visible
            } else {
                profilePicMenu.style.display = 'block'; // Show the menu if it is hidden
            }
        }

        // Hide the menu when clicking anywhere else on the page
        document.onclick = function(event) {
            if (!profilePic.contains(event.target) && !profilePicMenu.contains(event.target)) {
                profilePicMenu.style.display = 'none';
            }
        }
    </script>

</body>

</html>