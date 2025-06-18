<?php
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';

// Get room_id from query parameter
$room_id = $_GET['room_id'];

// Validate room_id to prevent SQL injection
if (!preg_match('/^[A-Z0-9\-]+$/', $room_id)) {
    echo "0"; // Return 0 for invalid room IDs
    exit;
}

try {
    // Prepare SQL query to get energy units for the specified room
    $sql = "SELECT remaining_units FROM room_energy WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $room_id, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Return the energy units value as a simple float
        echo number_format($result["remaining_units"], 2, '.', '');
    } else {
        // Return 0 if no data found for the room
        echo "0";
    }
} catch (PDOException $e) {
    echo "0"; // Return 0 if there's a database error
}

?>
