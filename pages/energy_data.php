<?php
session_start();
include '../databases/databaseconnection.php';

// Checking if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
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
    $profile_pic = $client['profile_pic'] ?? 'images/profile_pic.png';
    $apartment_id = $client['apartment_id'] ?? '';
    $room_id = $client['room_id'] ?? '';
} else {
    echo json_encode(["error" => "User not found."]);
    exit();
}

// Fetch units consumed and remaining units for a specific room
function fetchRoomEnergyData($room_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT energy_consumed, remaining_units FROM room_energy WHERE room_id = :room_id");
        $stmt->execute(['room_id' => $room_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch Room Energy Data Error: " . $e->getMessage());
        return null;
    }
}

// Fetch actual energy consumption data for the past 24 hours
function fetchActualEnergyData($room_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("SELECT new_consumed, last_updated FROM room_energy WHERE room_id = :room_id AND last_updated >= NOW() - INTERVAL 24 HOUR ORDER BY last_updated ASC");
        $stmt->execute(['room_id' => $room_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch Actual Energy Data Error: " . $e->getMessage());
        return [];
    }
}

// Fetch and store hourly energy data for the room
function fetchAndStoreHourlyRoomEnergyData($room_id, $pdo)
{
    try {
        // Fetch actual energy consumption data
        $room_hourly_data = fetchActualEnergyData($room_id, $pdo);

        // Prepare energy data for response
        $energy_data = [];
        foreach ($room_hourly_data as $entry) {
            $energy_data[] = [
                'time' => date('H:i', strtotime($entry['last_updated'])),
                'new_consumed' => number_format($entry['new_consumed'], 2) // Use real energy consumption value here
            ];

            // Store new consumption data into a history table
            $insertStmt = $pdo->prepare("INSERT INTO room_energy_history (room_id, new_consumed, timestamp) VALUES (:room_id, :new_consumed, :timestamp)");
            $insertStmt->execute([
                'room_id' => $room_id,
                'new_consumed' => $entry['new_consumed'],
                'timestamp' => $entry['last_updated']
            ]);
        }

        // Return the processed energy data
        return $energy_data;
    } catch (PDOException $e) {
        error_log("Fetch and Store Hourly Room Energy Data Error: " . $e->getMessage());
        return [];
    }
}

// Check if room_id is available
if (empty($room_id)) {
    $response = [
        'error' => 'No room assigned to this client',
        'energy_consumed' => 0,
        'remaining_units' => 0,
        'energy_data' => []
    ];
} else {
    // Fetch room energy data
    $room_energy_data = fetchRoomEnergyData($room_id, $conn);
    $energy_consumed = $room_energy_data['energy_consumed'] ?? 0;
    $remaining_units = $room_energy_data['remaining_units'] ?? 0;

    // Fetch and store hourly energy data
    $room_hourly_data = fetchAndStoreHourlyRoomEnergyData($room_id, $conn);

    // Prepare data for JavaScript
    $response = [
        'room_id' => $room_id,
        'energy_consumed' => number_format($energy_consumed, 2),
        'remaining_units' => number_format($remaining_units, 2),
        'energy_data' => $room_hourly_data
    ];
}

// Send JSON response
echo json_encode($response);
?>
