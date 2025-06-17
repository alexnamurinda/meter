<?php
session_start();
include '../databases/databaseconnection.php';
include '../databases/error_handler.php';

$clientId = $_GET['id'] ?? null;
if (!$clientId) {
    echo "Client ID not provided.";
    exit;
}

$clientQuery = "SELECT * FROM clients WHERE client_id = :id";
$clientStmt = $conn->prepare($clientQuery);
$clientStmt->bindParam(':id', $clientId, PDO::PARAM_INT);
$clientStmt->execute();
$client = $clientStmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    echo "Client not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = $_POST['client_id'];
    $clientName = $_POST['client_name'];
    $clientCategory = $_POST['client_category'];
    $phoneNumber = preg_replace('/^0/', '+256', $_POST['phone_number']);
    $apartment = $_POST['apartment_id'];
    $room = $_POST['room_id'];

    $updateQuery = "UPDATE clients 
                    SET client_name = :client_name, 
                        client_category = :client_category, 
                        phone_number = :phone_number,
                        apartment_id = :apartment_id, 
                        room_id = :room_id
                    WHERE client_id = :client_id";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
    $updateStmt->bindParam(':client_name', $clientName);
    $updateStmt->bindParam(':client_category', $clientCategory);
    $updateStmt->bindParam(':phone_number', $phoneNumber);
    $updateStmt->bindParam(':apartment_id', $apartment);
    $updateStmt->bindParam(':room_id', $room);

    if ($updateStmt->execute()) {
        header("Location: manage_users.php");
        exit;
    } else {
        echo "Failed to update client.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Update Client</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root {
      --primary-color: #4e73df;
      --primary-hover: #2653d4;
      --secondary-color: #858796;
      --success-color: #1cc88a;
      --info-color: #36b9cc;
      --warning-color: #f6c23e;
      --danger-color: #e74a3b;
      --light-color: #f8f9fc;
      --dark-color: #5a5c69;
    }

    body {
      background-color: var(--light-color);
    }

    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      padding: 60px;
      top: 0;
    }

    .profile-img {
      width: 110px;
      height: 110px;
      object-fit: cover;
      border-radius: 50%;
    }

    .btn-primary {
      background-color: var(--primary-color);
      border: none;
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
    }

    .btn-info {
      background-color: var(--info-color);
      border: none;
    }

    .btn-info:hover {
      background-color: #2aa8ba;
    }

    .form-label {
      font-weight: 600;
      color: var(--dark-color);
    }

    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
    }

    @media (max-width: 768px) {
      .action-buttons {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>

<body>
<div class="container my-5">
  <div class="card">
    <div class="text-center mb-4">
      <img src="<?= !empty($client['profile_pic']) ? htmlspecialchars($client['profile_pic']) : 'images/profile_pic.png'; ?>" class="profile-img mb-3" alt="Profile Picture">
      <h4 class="text-dark"><?= htmlspecialchars($client['client_name']); ?></h4>
    </div>

    <form method="POST">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Client ID</label>
          <input type="text" class="form-control" name="client_id" value="<?= htmlspecialchars($client['client_id']); ?>" readonly>

          <label class="form-label mt-2">Full Name</label>
          <input type="text" class="form-control" name="client_name" value="<?= htmlspecialchars($client['client_name']); ?>" required>

          <label class="form-label mt-2">Category</label>
          <input type="text" class="form-control" name="client_category" value="<?= htmlspecialchars($client['client_category']); ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Phone Number</label>
          <input type="text" class="form-control" name="phone_number" value="<?= htmlspecialchars($client['phone_number']); ?>" required>

          <label class="form-label mt-2">Apartment ID</label>
          <input type="text" class="form-control" name="apartment_id" value="<?= htmlspecialchars($client['apartment_id']); ?>">

          <label class="form-label mt-2">Room ID</label>
          <input type="text" class="form-control" name="room_id" value="<?= htmlspecialchars($client['room_id']); ?>">
        </div>
      </div>

      <div class="action-buttons">
        <a href="admindashboard.php" class="btn btn-info px-4">← Back to Dashboard</a>
        <input type="submit" value="Update Client" class="btn btn-primary px-4">
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
