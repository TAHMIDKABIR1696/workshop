<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'workshop_appointments');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;


// Select database
mysqli_select_db($conn, DB_NAME);

// Create mechanics table
$sql = "CREATE TABLE IF NOT EXISTS mechanics (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    max_daily_cars INT DEFAULT 4
)";



// Create clients table
$sql = "CREATE TABLE IF NOT EXISTS clients (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    car_license VARCHAR(50) NOT NULL,
    car_engine_number VARCHAR(50) NOT NULL
)";


// Create appointments table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    mechanic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(id)
)";



// Insert sample mechanics if none exist
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM mechanics");
$row = mysqli_fetch_assoc($result);
if($row['count'] == 0){
    $mechanics = [
        ['name' => 'Kakashi Hatake', 'specialization' => 'Engine Repair'],
        ['name' => 'Itachi Uchiha', 'specialization' => 'Transmission'],
        ['name' => 'Sasuke Uchiha', 'specialization' => 'Electrical Systems'],
        ['name' => 'Madara Uchiha', 'specialization' => 'Brakes & Suspension'],
        ['name' => 'Shisui Uchiha', 'specialization' => 'General Maintenance']
    ];
    
    foreach($mechanics as $mechanic){
        mysqli_query($conn, "INSERT INTO mechanics (name, specialization) VALUES ('{$mechanic['name']}', '{$mechanic['specialization']}')");
    }
    echo "Sample mechanics added successfully<br>";
}
