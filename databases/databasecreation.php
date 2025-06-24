<?php
// Include the database connection
include 'databaseconnection.php';

try {
    // Only create database if not in production
    if (getenv('ENVIRONMENT') !== 'production') {
        // Check if the database exists, if not, create it
        $createDbQuery = "CREATE DATABASE IF NOT EXISTS $db_name";
        $conn->exec($createDbQuery);
    }

    // Now connect to the database
    $conn = new PDO("mysql:host=$hostname;port=$port;dbname=$db_name;charset=$charset", $username, $password, $options);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the apartments table
    $createapartmentsTableQuery = "
    CREATE TABLE IF NOT EXISTS apartments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        apartment_id VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(50) NOT NULL
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createapartmentsTableQuery);

    // Create the rooms table
    $createroomsTableQuery = "
    CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(50) NOT NULL UNIQUE,
        apartment_id VARCHAR(50) NOT NULL,
        name VARCHAR(50) NOT NULL,
        power_status ENUM('ON', 'OFF') DEFAULT 'OFF',
        FOREIGN KEY (apartment_id) REFERENCES apartments(apartment_id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createroomsTableQuery);

    // Create the clients table
    $createclientsTableQuery = "
    CREATE TABLE IF NOT EXISTS clients (
        client_id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(50) NOT NULL,
        client_category VARCHAR(20) NOT NULL,
        phone_number VARCHAR(13) NOT NULL,
        client_password VARCHAR(255) NOT NULL,
        profile_pic VARCHAR(255) DEFAULT NULL,
        apartment_id VARCHAR(50) DEFAULT NULL,
        room_id VARCHAR(50) DEFAULT NULL UNIQUE,
        registration_status ENUM('pending', 'under review', 'approved', 'rejected') DEFAULT 'pending',
        registered_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME DEFAULT NULL,
        FOREIGN KEY (apartment_id) REFERENCES apartments(apartment_id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE   
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createclientsTableQuery);

    // Create the admin_notifications table
    $createadmin_notificationsQuery = "
    CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        message TEXT NOT NULL,
        status ENUM('unread', 'read') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createadmin_notificationsQuery);

    // Create the admin table
    $createAdminTableQuery = "
    CREATE TABLE IF NOT EXISTS admin (
        admin_id INT AUTO_INCREMENT PRIMARY KEY,
        admin_name VARCHAR(50) NOT NULL UNIQUE,
        admin_fullname VARCHAR(50) NOT NULL,
        admin_password VARCHAR(255) NOT NULL,
        last_login DATETIME DEFAULT NULL
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createAdminTableQuery);

    // Create the room_energy table
    $createroom_energyTableQuery = "
    CREATE TABLE IF NOT EXISTS room_energy (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL UNIQUE,
        energy_consumed DECIMAL(10,3) NOT NULL
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createroom_energyTableQuery);

    // Create the feedbacks table
    $createFeedbackTableQuery = "
    CREATE TABLE IF NOT EXISTS feedbacks (
        feedback_id INT AUTO_INCREMENT PRIMARY KEY,
        client_name VARCHAR(50) NOT NULL,
        client_email VARCHAR(100) NOT NULL,
        feedback_subject VARCHAR(100) NOT NULL,
        feedback_message TEXT NOT NULL,
        submitted_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createFeedbackTableQuery);

    // Create the room_energy table with comprehensive tracking columns
    $createRoomEnergyTableQuery = "
    CREATE TABLE IF NOT EXISTS room_energy (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(50) NOT NULL,
        energy_consumed DECIMAL(10, 3) NOT NULL DEFAULT 0.000,
        remaining_units DECIMAL(10, 3) NOT NULL DEFAULT 0.000,
        new_consumed DECIMAL(10, 3) DEFAULT NULL,
        plotted_value DECIMAL(10, 3) DEFAULT NULL,
        total_purchased DECIMAL(10,3) DEFAULT 0.000,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createRoomEnergyTableQuery);

    // Create the transactions table
    $createtransactionsTableQuery = "
    CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        kwh DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_details TEXT,
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES clients(client_id) 
    )   ENGINE=InnoDB;
    ";
    $conn->exec($createtransactionsTableQuery);

    // Create the alerts table
    $createAlertsTableQuery = "
    CREATE TABLE IF NOT EXISTS alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(50) NOT NULL,
        alert_text TEXT NOT NULL,
        alert_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";

    $conn->exec($createAlertsTableQuery);


    // Create the room_energy_history table
    $createRoomEnergyHistoryTableQuery = "
    CREATE TABLE IF NOT EXISTS room_energy_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id VARCHAR(50) NOT NULL,
        energy_consumed DECIMAL(10, 3) NOT NULL,
        energy_added DECIMAL(10, 3) DEFAULT NULL,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    $conn->exec($createRoomEnergyHistoryTableQuery);
} catch (PDOException $e) {
    // More detailed error reporting for troubleshooting
    echo "<p>Database Error: " . $e->getMessage() . "</p>";
    die();
}
