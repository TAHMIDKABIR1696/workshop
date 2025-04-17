<?php
// admin/get_mechanics_availability.php - AJAX handler to get mechanics availability
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    exit("Unauthorized access");
}

require_once "../config.php";

if(isset($_POST['date']) && isset($_POST['appointment_id'])) {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $appointment_id = intval($_POST['appointment_id']);
    
    // Get current mechanic assignment
    $current_query = "SELECT mechanic_id FROM appointments WHERE id = $appointment_id";
    $current_result = mysqli_query($conn, $current_query);
    $current = mysqli_fetch_assoc($current_result);
    $current_mechanic_id = $current['mechanic_id'];
    
    // Get all mechanics with availability
    $sql = "SELECT m.id, m.name, m.specialization, m.max_daily_cars,
           (SELECT COUNT(*) FROM appointments a WHERE a.mechanic_id = m.id AND a.appointment_date = '$date' AND a.status = 'scheduled' AND a.id != $appointment_id) as booked
           FROM mechanics m 
           ORDER BY m.name";
    
    $result = mysqli_query($conn, $sql);
    
    while($mechanic = mysqli_fetch_assoc($result)) {
        $available_slots = $mechanic['max_daily_cars'] - $mechanic['booked'];
        $selected = ($mechanic['id'] == $current_mechanic_id) ? 'selected' : '';
        $disabled = ($available_slots <= 0 && $mechanic['id'] != $current_mechanic_id) ? 'disabled' : '';
        
        echo "<option value='{$mechanic['id']}' $selected $disabled>{$mechanic['name']} - {$mechanic['specialization']} ($available_slots slots available)</option>";
    }
} else {
    echo "<option value=''>Select a date first</option>";
}
?>