<?php
include 'db.php';

header('Content-Type: application/json');

if (isset($_GET['apartment_id'])) {
    $apartmentId = $_GET['apartment_id'];

    try {
        // Subquery to get room IDs already assigned to clients
        $stmt = $conn->prepare("
            SELECT room_id, name 
            FROM rooms 
            WHERE apartment_id = ? 
            AND room_id NOT IN (
                SELECT room_id 
                FROM clients 
                WHERE room_id IS NOT NULL 
                AND apartment_id = ?
            )
        ");
        $stmt->execute([$apartmentId, $apartmentId]);
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rooms);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error fetching rooms: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No apartment ID provided']);
}