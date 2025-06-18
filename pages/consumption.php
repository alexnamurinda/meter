<?php
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';

// Set headers to allow cross-origin requests if needed
header('Content-Type: application/json');

// Response array
$response = array(
    'success' => false,
    'message' => '',
    'updated_rooms' => array()
);

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Only POST requests are allowed';
    echo json_encode($response);
    exit;
}

// Check if required parameters exist
if (!isset($_POST['room_id']) || !isset($_POST['remaining']) || !isset($_POST['consumed'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

// Get the arrays from the POST data
$roomIds = $_POST['room_id'];
$remainingUnits = $_POST['remaining'];
$consumedEnergy = $_POST['consumed'];

// Check if arrays have the same length
if (count($roomIds) !== count($remainingUnits) || count($roomIds) !== count($consumedEnergy)) {
    $response['message'] = 'Array lengths do not match';
    echo json_encode($response);
    exit;
}

try {
    // Begin transaction for consistency
    $conn->beginTransaction();

    // Process each room
    for ($i = 0; $i < count($roomIds); $i++) {
        $roomId = $roomIds[$i];
        $remaining = floatval($remainingUnits[$i]);
        $consumed = floatval($consumedEnergy[$i]);

        // First check if the room exists in the rooms table
        $checkRoomStmt = $conn->prepare("SELECT room_id FROM rooms WHERE room_id = :room_id");
        $checkRoomStmt->bindParam(':room_id', $roomId);
        $checkRoomStmt->execute();

        if ($checkRoomStmt->rowCount() === 0) {
            // Room doesn't exist, skip this entry
            continue;
        }

        // Check if the room already has an entry in room_energy
        $checkEnergyStmt = $conn->prepare("SELECT id FROM room_energy WHERE room_id = :room_id");
        $checkEnergyStmt->bindParam(':room_id', $roomId);
        $checkEnergyStmt->execute();

        if ($checkEnergyStmt->rowCount() > 0) {
            // Update existing record
            $updateStmt = $conn->prepare("UPDATE room_energy SET 
                                        energy_consumed = :consumed, 
                                        remaining_units = :remaining 
                                        WHERE room_id = :room_id");
            $updateStmt->bindParam(':consumed', $consumed);
            $updateStmt->bindParam(':remaining', $remaining);
            $updateStmt->bindParam(':room_id', $roomId);
            $updateStmt->execute();
        } else {
            // Insert new record
            $insertStmt = $conn->prepare("INSERT INTO room_energy 
                                        (room_id, energy_consumed, remaining_units) 
                                        VALUES (:room_id, :consumed, :remaining)");
            $insertStmt->bindParam(':room_id', $roomId);
            $insertStmt->bindParam(':consumed', $consumed);
            $insertStmt->bindParam(':remaining', $remaining);
            $insertStmt->execute();
        }

        // Add to updated rooms array
        $response['updated_rooms'][] = array(
            'room_id' => $roomId,
            'consumed' => $consumed,
            'remaining' => $remaining
        );
    }

    // Update daily energy consumption
    $today = date('Y-m-d');
    $totalConsumed = array_sum($consumedEnergy);

    // Check if we have an entry for today
    $checkDailyStmt = $conn->prepare("SELECT date FROM daily_energy_consumption WHERE date = :date");
    $checkDailyStmt->bindParam(':date', $today);
    $checkDailyStmt->execute();

    if ($checkDailyStmt->rowCount() > 0) {
        // Update existing daily record
        $updateDailyStmt = $conn->prepare("UPDATE daily_energy_consumption 
                                          SET energy_consumed = energy_consumed + :total_consumed 
                                          WHERE date = :date");
        $updateDailyStmt->bindParam(':total_consumed', $totalConsumed);
        $updateDailyStmt->bindParam(':date', $today);
        $updateDailyStmt->execute();
    } else {
        // Insert new daily record
        $insertDailyStmt = $conn->prepare("INSERT INTO daily_energy_consumption 
                                          (date, energy_consumed) 
                                          VALUES (:date, :total_consumed)");
        $insertDailyStmt->bindParam(':date', $today);
        $insertDailyStmt->bindParam(':total_consumed', $totalConsumed);
        $insertDailyStmt->execute();
    }

    // Commit the transaction
    $conn->commit();

    // Set success response
    $response['success'] = true;
    $response['message'] = 'Consumption data updated successfully';
} catch (PDOException $e) {
    // Roll back the transaction if something failed
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    // Handle any other exceptions
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Send the response
echo json_encode($response);
