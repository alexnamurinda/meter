<?php
include '../databases/error_handler.php';
include '../databases/databasecreation.php';
include '../databases/databaseconnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['name'], $_POST['email'], $_POST['subject'], $_POST['message'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);

        $sql = "INSERT INTO feedbacks (client_name, client_email, feedback_subject, feedback_message) 
                VALUES (:name, :email, :subject, :message)";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);

            if ($stmt->execute()) {
                echo "✅ Message sent successfully";
            } else {
                echo "❌ Failed to send message.";
            }
        } catch (PDOException $e) {
            echo "❌ Error: " . $e->getMessage();
        }
    } else {
        echo "❌ Please fill in all fields.";
    }
}
