<?php
// check_availability.php - Checks mechanic availability for a specific date
require_once "config.php";

// Get data from AJAX request
$mechanic_id = mysqli_real_escape_string($conn, $_POST['mechanic_id']);
$appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
$phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';

// Initialize HTML output
$html = '';

// Validate date
if(strtotime($appointment_date) < strtotime(date('Y-m-d'))){
    $html = '<div class="alert alert-danger">Selected date is in the past. Please choose a future date.</div>';
} else {
    // Get mechanic info and availability
    $sql = "SELECT m.name, m.specialization, m.max_daily_cars, 
           (SELECT COUNT(*) FROM appointments a WHERE a.mechanic_id = $mechanic_id AND a.appointment_date = '$appointment_date' AND a.status = 'scheduled') as booked
           FROM mechanics m 
           WHERE m.id = $mechanic_id";
    $result = mysqli_query($conn, $sql);
    
    if($mechanic = mysqli_fetch_assoc($result)){
        $available_slots = $mechanic['max_daily_cars'] - $mechanic['booked'];
        
        // Check if client already has appointment on this date with any mechanic
        $client_has_appointment = false;
        if(!empty($phone)){
            $sql = "SELECT a.id FROM appointments a 
                    JOIN clients c ON a.client_id = c.id 
                    WHERE c.phone = '$phone' AND a.appointment_date = '$appointment_date' AND a.status = 'scheduled'";
            $result = mysqli_query($conn, $sql);
            $client_has_appointment = mysqli_num_rows($result) > 0;
        }
        
        $html .= '<div class="card-text mb-3">';
        $html .= '<strong>Mechanic:</strong> ' . $mechanic['name'] . ' (' . $mechanic['specialization'] . ')<br>';
        
        if($client_has_appointment){
            $html .= '<div class="alert alert-warning mt-2">
                     <i class="bi bi-exclamation-triangle"></i> You already have an appointment scheduled for this date.
                     </div>';
        } 
        elseif($available_slots <= 0){
            $html .= '<div class="alert alert-danger mt-2">
                     <i class="bi bi-x-circle"></i> No available slots for this date. Please select another date or mechanic.
                     </div>';
        } 
        else {
            $html .= '<div class="alert alert-success mt-2">
                     <i class="bi bi-check-circle"></i> ' . $available_slots . ' slots available for this date. You can book!
                     </div>';
        }
        
        $html .= '</div>';
    } else {
        $html = '<div class="alert alert-danger">Mechanic not found.</div>';
    }
}

echo $html;
?>