<?php
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';

try {
    // Handle adding/editing apartments
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['apartment_id'], $_POST['apartment_name'])) {
            // Check if it's an update
            if (!empty($_POST['update_apartment'])) {
                $stmt = $conn->prepare("UPDATE apartments SET name=? WHERE apartment_id=?");
                $stmt->execute([$_POST['apartment_name'], $_POST['apartment_id']]);
                $message = "Apartment updated successfully!";
            } else {
                // Add new apartment
                $stmt = $conn->prepare("INSERT INTO apartments (apartment_id, name) VALUES (?, ?)");
                $stmt->execute([$_POST['apartment_id'], $_POST['apartment_name']]);
                
                // Auto-create 10 rooms for the new apartment
                $apartment_id = $_POST['apartment_id'];
                $roomNames = [
                    "Room 1", "Room 2", "Room 3", "Room 4", 
                    "Room 5", "Room 6", "Room 7", "Room 8", 
                    "Room 9", "Room 10"
                ];
                
                // Begin transaction for adding multiple rooms
                $conn->beginTransaction();
                
                try {
                    // Create 10 rooms with standard naming
                    for ($i = 1; $i <= 10; $i++) {
                        $room_id = $apartment_id . "-R" . str_pad($i, 3, "0", STR_PAD_LEFT);
                        $room_name = $roomNames[$i-1];
                        
                        // Insert room into rooms table
                        $stmtRoom = $conn->prepare("INSERT INTO rooms (room_id, apartment_id, name) VALUES (?, ?, ?)");
                        $stmtRoom->execute([$room_id, $apartment_id, $room_name]);
                        
                        // Initialize room energy data
                        $stmtRoomEnergy = $conn->prepare("INSERT INTO room_energy (room_id, energy_consumed, remaining_units) VALUES (?, 0.000, 0.000)");
                        $stmtRoomEnergy->execute([$room_id]);
                    }
                    
                    // Commit the transaction if all rooms were added successfully
                    $conn->commit();
                    $message = "Apartment added successfully with 10 default rooms!";
                } catch (PDOException $e) {
                    // Rollback the transaction if there was an error
                    $conn->rollBack();
                    $message = "Apartment added but failed to create default rooms. Error: " . $e->getMessage();
                }
            }
        }

        // Handle adding rooms manually (keeping this functionality)
        if (isset($_POST['room_id'], $_POST['room_name'], $_POST['apartment_id']) && !isset($_POST['apartment_name'])) {
            // Insert room into rooms table
            $stmt = $conn->prepare("INSERT INTO rooms (room_id, apartment_id, name) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['room_id'], $_POST['apartment_id'], $_POST['room_name']]);
            $message = "Room assigned successfully!";

            // Insert corresponding entry into room_energy table to initialize the room's energy data
            $stmtRoomEnergy = $conn->prepare("INSERT INTO room_energy (room_id, energy_consumed, remaining_units) VALUES (?, 0.000, 0.000)");
            $stmtRoomEnergy->execute([$_POST['room_id']]);

            // Optionally, check if room energy insertion was successful and add an additional message
            if ($stmtRoomEnergy) {
                $message .= " Room energy initialized successfully!";
            } else {
                $message .= " Failed to initialize room energy.";
            }
        }

        // Handle deleting apartment
        if (isset($_POST['delete_apartment_id'])) {
            $stmt = $conn->prepare("DELETE FROM apartments WHERE apartment_id = ?");
            $stmt->execute([$_POST['delete_apartment_id']]);
            $message = "Apartment deleted successfully!";
        }
    }

    // Fetch apartments
    $stmt = $conn->query("SELECT * FROM apartments");
    $apartments = $stmt->fetchAll();

    // Fetch rooms
    $stmt = $conn->query("SELECT * FROM rooms");
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Apartments & Rooms</title>
    <link rel="stylesheet" href="admnstyling.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: blue;
        }

        .message {
            color: green;
            text-align: center;
            font-weight: bold;
        }

        .form-container {
            display: flex;
            gap: 20px;
        }

        .left-column,
        .right-column {
            flex: 1;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.2);
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input,
        select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 5px;
        }

        button {
            margin-top: 15px;
            padding: 10px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: rgb(57, 78, 100);
        }

        .delete-btn {
            background: #dc3545;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .apartment-list,
        .room-list {
            margin-top: 10px;
        }

        .apartment-item,
        .room-item {
            padding: 10px;
            background: #f9f9f9;
            margin: 5px 0;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Assign Apartments & Rooms</h1>

        <?php if (isset($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <div class="form-container">
            <!-- Left Column: Apartment Management -->
            <div class="left-column">
                <h2>Manage Apartments</h2>
                <form action="" method="POST">
                    <label for="apartment_id">Apartment ID:</label>
                    <input type="text" name="apartment_id" required>

                    <label for="apartment_name">Apartment Name:</label>
                    <input type="text" name="apartment_name" required>

                    <button type="submit">Add Apartment</button>
                </form>

                <!-- <h3>Existing Apartments</h3>
            <div class="apartment-list">
                <?php foreach ($apartments as $apartment): ?>
                    <div class="apartment-item">
                        <strong><?php echo htmlspecialchars($apartment['name']); ?></strong> 
                        (ID: <?php echo htmlspecialchars($apartment['apartment_id']); ?>)
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="delete_apartment_id" value="<?php echo $apartment['apartment_id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div> -->
            </div>

            <!-- Right Column: Room Management -->
            <div class="right-column">
                <h2>Assign Room to Apartment</h2>
                <form action="" method="POST">
                    <label for="room_id">Room ID:</label>
                    <input type="text" name="room_id" required>

                    <label for="apartment_id">Select Apartment:</label>
                    <select name="apartment_id" required>
                        <option value="">-- Select Apartment --</option>
                        <?php foreach ($apartments as $apartment): ?>
                            <option value="<?php echo $apartment['apartment_id']; ?>">
                                <?php echo $apartment['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="room_name">Room Name:</label>
                    <input type="text" name="room_name" required>

                    <button type="submit">Assign Room</button>
                </form>

                <!-- <h3>Assigned Rooms</h3>
            <div class="room-list">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-item">
                        <?php echo htmlspecialchars($room['name']); ?> (ID: <?php echo htmlspecialchars($room['room_id']); ?>)
                    </div>
                <?php endforeach; ?>
            </div> -->
            </div>
        </div>
        <button style=" width: 100%;"><a href="admindashboard.php" style="text-decoration: none; color: white; padding: 10px 20px; font-size: 16px;">Back to dashboard</a></button>
    </div>

</body>

</html>