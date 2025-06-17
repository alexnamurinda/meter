<?php
session_start();
date_default_timezone_set('Africa/Kampala');
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';

header("Content-Type: application/json");

// Ensure user is authenticated
if (!isset($_SESSION['user']) || $_SESSION['user']['authenticated'] !== true) {
    echo json_encode(["message" => "Unauthorized access."]);
    exit();
}

$phone_number = $_SESSION['user']['phone_number'];

// Fetch client details, including room_id
$sql = "SELECT client_id, room_id FROM clients WHERE phone_number = :phone_number LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
$stmt->execute();
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo json_encode(["message" => "User not found."]);
    exit();
}

$client_id = $client['client_id'];
$room_id = $client['room_id']; // Get the room ID

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['amount']) || !isset($data['kwh']) || !isset($data['method'])) {
    echo json_encode(["message" => "Invalid input."]);
    exit();
}

$amount = floatval($data['amount']);
$kwh = floatval($data['kwh']);
$method = htmlspecialchars($data['method']);
$paymentDetails = json_encode($data['paymentData']); // Store additional payment info

// Insert transaction into the database
$sql = "INSERT INTO transactions (client_id, phone_number, amount, kwh, payment_method, payment_details, transaction_date) 
        VALUES (:client_id, :phone_number, :amount, :kwh, :method, :paymentDetails, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
$stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
$stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
$stmt->bindParam(':kwh', $kwh, PDO::PARAM_STR);
$stmt->bindParam(':method', $method, PDO::PARAM_STR);
$stmt->bindParam(':paymentDetails', $paymentDetails, PDO::PARAM_STR);

if ($stmt->execute()) {
    $sql = "UPDATE room_energy 
            SET remaining_units = remaining_units + :kwh 
            WHERE room_id = :room_id";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':room_id', $room_id, PDO::PARAM_STR);
    $stmt->bindParam(':kwh', $kwh, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Recharge successful! You received {$kwh} kWh for UGX {$amount}.", "reload" => true]);
    } else {
        echo json_encode(["message" => "Error updating room energy."]);
    }
} else {
    echo json_encode(["message" => "Error processing transaction."]);
}
