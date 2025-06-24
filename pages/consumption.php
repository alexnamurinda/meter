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
        $currentRemaining = floatval($remainingUnits[$i]);
        $currentConsumed = floatval($consumedEnergy[$i]);

        // First check if the room exists in the rooms table
        $checkRoomStmt = $conn->prepare("SELECT room_id FROM rooms WHERE room_id = :room_id");
        $checkRoomStmt->bindParam(':room_id', $roomId);
        $checkRoomStmt->execute();

        if ($checkRoomStmt->rowCount() === 0) {
            // Room doesn't exist, skip this entry
            continue;
        }

        // Check if the room already has an entry in room_energy
        $checkEnergyStmt = $conn->prepare("SELECT id, energy_consumed, remaining_units, total_purchased, new_consumed, plotted_value FROM room_energy WHERE room_id = :room_id");
        $checkEnergyStmt->bindParam(':room_id', $roomId);
        $checkEnergyStmt->execute();
        $existingData = $checkEnergyStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingData) {
            // Update existing record
            $previousConsumed = floatval($existingData['energy_consumed']);
            $previousRemaining = floatval($existingData['remaining_units']);
            $totalPurchased = floatval($existingData['total_purchased']);
            
            // Calculate the new consumption (difference from previous)
            $newConsumption = $currentConsumed - $previousConsumed;
            
            // If there's new consumption, subtract it from remaining units
            if ($newConsumption > 0) {
                $newRemaining = max(0, $previousRemaining - $newConsumption);
            } else {
                // No new consumption, keep the current remaining or use device reported value
                $newRemaining = $currentRemaining;
            }
            
            // If remaining units increased, it means new units were purchased
            if ($currentRemaining > $previousRemaining && $newConsumption >= 0) {
                $newPurchase = $currentRemaining - $previousRemaining + $newConsumption;
                $totalPurchased += $newPurchase;
                $newRemaining = $currentRemaining;
            }

            // Calculate plotted value (for visualization/graphs)
            $plottedValue = $totalPurchased - $currentConsumed;

            $updateStmt = $conn->prepare("UPDATE room_energy SET 
                                        energy_consumed = :consumed, 
                                        remaining_units = :remaining,
                                        new_consumed = :new_consumed,
                                        plotted_value = :plotted_value,
                                        total_purchased = :total_purchased,
                                        last_updated = NOW()
                                        WHERE room_id = :room_id");
            $updateStmt->bindParam(':consumed', $currentConsumed);
            $updateStmt->bindParam(':remaining', $newRemaining);
            $updateStmt->bindParam(':new_consumed', $newConsumption);
            $updateStmt->bindParam(':plotted_value', $plottedValue);
            $updateStmt->bindParam(':total_purchased', $totalPurchased);
            $updateStmt->bindParam(':room_id', $roomId);
            $updateStmt->execute();
            
            $finalRemaining = $newRemaining;
        } else {
            // Insert new record - first time setup
            $plottedValue = $currentRemaining; // Initially, plotted value equals remaining
            
            $insertStmt = $conn->prepare("INSERT INTO room_energy 
                                        (room_id, energy_consumed, remaining_units, new_consumed, plotted_value, total_purchased, last_updated) 
                                        VALUES (:room_id, :consumed, :remaining, :new_consumed, :plotted_value, :total_purchased, NOW())");
            $insertStmt->bindParam(':room_id', $roomId);
            $insertStmt->bindParam(':consumed', $currentConsumed);
            $insertStmt->bindParam(':remaining', $currentRemaining);
            $insertStmt->bindParam(':new_consumed', $currentConsumed); // First time, new_consumed = total consumed
            $insertStmt->bindParam(':plotted_value', $plottedValue);
            $insertStmt->bindParam(':total_purchased', $currentRemaining); // Initially, remaining = total purchased
            $insertStmt->execute();
            
            $finalRemaining = $currentRemaining;
        }

        // Add to updated rooms array
        $response['updated_rooms'][] = array(
            'room_id' => $roomId,
            'consumed' => $currentConsumed,
            'remaining' => $finalRemaining,
            'new_consumed' => isset($newConsumption) ? $newConsumption : $currentConsumed,
            'plotted_value' => isset($plottedValue) ? $plottedValue : $currentRemaining,
            'calculated_balance' => $finalRemaining
        );
    }

    // Update daily energy consumption with only new consumption
    $today = date('Y-m-d');
    
    // Calculate total new consumption for today
    $totalNewConsumption = 0;
    foreach ($response['updated_rooms'] as $room) {
        // Get previous day's consumption to calculate new consumption
        $prevConsumptionStmt = $conn->prepare("SELECT energy_consumed FROM room_energy 
                                              WHERE room_id = :room_id AND date = :date");
        $prevConsumptionStmt->bindParam(':room_id', $room['room_id']);
        $prevConsumptionStmt->bindParam(':date', $today);
        $prevConsumptionStmt->execute();
        $prevData = $prevConsumptionStmt->fetch(PDO::FETCH_ASSOC);
        
        $previousDailyConsumed = $prevData ? floatval($prevData['energy_consumed']) : 0;
        $newDailyConsumption = $room['consumed'] - $previousDailyConsumed;
        
        if ($newDailyConsumption > 0) {
            $totalNewConsumption += $newDailyConsumption;
            
            // Update or insert daily room consumption
            if ($prevData) {
                $updateDailyRoomStmt = $conn->prepare("UPDATE room_energy 
                                                      SET energy_consumed = :consumed 
                                                      WHERE room_id = :room_id AND date = :date");
                $updateDailyRoomStmt->bindParam(':consumed', $room['consumed']);
                $updateDailyRoomStmt->bindParam(':room_id', $room['room_id']);
                $updateDailyRoomStmt->bindParam(':date', $today);
                $updateDailyRoomStmt->execute();
            } else {
                $insertDailyRoomStmt = $conn->prepare("INSERT INTO room_energy 
                                                      (room_id, date, energy_consumed) 
                                                      VALUES (:room_id, :date, :consumed)");
                $insertDailyRoomStmt->bindParam(':room_id', $room['room_id']);
                $insertDailyRoomStmt->bindParam(':date', $today);
                $insertDailyRoomStmt->bindParam(':consumed', $room['consumed']);
                $insertDailyRoomStmt->execute();
            }
        }
    }

    // Update overall daily energy consumption
    if ($totalNewConsumption > 0) {
        $checkDailyStmt = $conn->prepare("SELECT date FROM room_energy WHERE date = :date");
        $checkDailyStmt->bindParam(':date', $today);
        $checkDailyStmt->execute();

        if ($checkDailyStmt->rowCount() > 0) {
            // Update existing daily record
            $updateDailyStmt = $conn->prepare("UPDATE room_energy 
                                              SET energy_consumed = energy_consumed + :total_consumed 
                                              WHERE date = :date");
            $updateDailyStmt->bindParam(':total_consumed', $totalNewConsumption);
            $updateDailyStmt->bindParam(':date', $today);
            $updateDailyStmt->execute();
        } else {
            // Insert new daily record
            $insertDailyStmt = $conn->prepare("INSERT INTO room_energy 
                                              (date, energy_consumed) 
                                              VALUES (:date, :total_consumed)");
            $insertDailyStmt->bindParam(':date', $today);
            $insertDailyStmt->bindParam(':total_consumed', $totalNewConsumption);
            $insertDailyStmt->execute();
        }
    }

    // Commit the transaction
    $conn->commit();

    // Set success response
    $response['success'] = true;
    $response['message'] = 'Consumption data updated successfully with calculated balances';
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
