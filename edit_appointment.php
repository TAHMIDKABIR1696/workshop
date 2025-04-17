<?php
// admin/edit_appointment.php - Edit appointment details
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config.php";

$message = "";
$messageClass = "";

// Check if id parameter exists
if(!isset($_GET["id"]) || empty($_GET["id"])){
    header("location: index.php");
    exit;
}

$id = intval($_GET["id"]);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $mechanic_id = intval($_POST["mechanic_id"]);
    $appointment_date = mysqli_real_escape_string($conn, $_POST["appointment_date"]);
    $status = mysqli_real_escape_string($conn, $_POST["status"]);
    
    // Check if the selected mechanic has available slots for the date
    if($status == 'scheduled') {
        $sql = "SELECT m.max_daily_cars, 
               (SELECT COUNT(*) FROM appointments a WHERE a.mechanic_id = $mechanic_id AND a.appointment_date = '$appointment_date' AND a.status = 'scheduled' AND a.id != $id) as booked
               FROM mechanics m 
               WHERE m.id = $mechanic_id";
        $result = mysqli_query($conn, $sql);
        $mechanic = mysqli_fetch_assoc($result);
        
        if($mechanic['booked'] >= $mechanic['max_daily_cars']){
            $message = "The selected mechanic is fully booked on this date. Please select another mechanic or date.";
            $messageClass = "alert-danger";
        } else {
            // Update appointment
            $sql = "UPDATE appointments SET mechanic_id = $mechanic_id, appointment_date = '$appointment_date', status = '$status' WHERE id = $id";
            
            if(mysqli_query($conn, $sql)){
                $message = "Appointment updated successfully.";
                $messageClass = "alert-success";
            } else {
                $message = "Error: " . mysqli_error($conn);
                $messageClass = "alert-danger";
            }
        }
    } else {
        // Update appointment for non-scheduled statuses (no need to check availability)
        $sql = "UPDATE appointments SET mechanic_id = $mechanic_id, appointment_date = '$appointment_date', status = '$status' WHERE id = $id";
        
        if(mysqli_query($conn, $sql)){
            $message = "Appointment updated successfully.";
            $messageClass = "alert-success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageClass = "alert-danger";
        }
    }
}

// Get appointment details
$sql = "SELECT a.*, c.name as client_name, c.phone, c.car_license, c.car_engine_number, c.address 
        FROM appointments a 
        JOIN clients c ON a.client_id = c.id 
        WHERE a.id = $id";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 0){
    header("location: index.php");
    exit;
}

$appointment = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment - Car Workshop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Car Workshop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="mechanics.php">Manage Mechanics</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Edit Appointment</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if(!empty($message)): ?>
        <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Client Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($appointment['client_name']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['phone']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($appointment['address']); ?></p>
                        <p><strong>Car License:</strong> <?php echo htmlspecialchars($appointment['car_license']); ?></p>
                        <p><strong>Engine Number:</strong> <?php echo htmlspecialchars($appointment['car_engine_number']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="appointment_date" class="form-label">Appointment Date</label>
                                    <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                           value="<?php echo $appointment['appointment_date']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mechanic_id" class="form-label">Assigned Mechanic</label>
                                    <select class="form-select" id="mechanic_id" name="mechanic_id" required>
                                        <?php
                                        // Get all mechanics
                                        $mechanic_sql = "SELECT m.id, m.name, m.specialization, m.max_daily_cars,
                                                        (SELECT COUNT(*) FROM appointments a WHERE a.mechanic_id = m.id AND a.appointment_date = '{$appointment['appointment_date']}' AND a.status = 'scheduled' AND a.id != $id) as booked
                                                        FROM mechanics m 
                                                        ORDER BY m.name";
                                        $mechanic_result = mysqli_query($conn, $mechanic_sql);
                                        
                                        while($mechanic = mysqli_fetch_assoc($mechanic_result)){
                                            $available_slots = $mechanic['max_daily_cars'] - $mechanic['booked'];
                                            $selected = ($mechanic['id'] == $appointment['mechanic_id']) ? 'selected' : '';
                                            $disabled = ($available_slots <= 0 && $mechanic['id'] != $appointment['mechanic_id']) ? 'disabled' : '';
                                            
                                            echo "<option value='{$mechanic['id']}' $selected $disabled>{$mechanic['name']} - {$mechanic['specialization']} ($available_slots slots available)</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="scheduled" <?php echo ($appointment['status'] == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="completed" <?php echo ($appointment['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($appointment['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; <?php echo date("Y"); ?> Car Workshop. All rights reserved.</p>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            // Function to check mechanic availability on date change
            $('#appointment_date').change(function(){
                var date = $(this).val();
                var appointmentId = <?php echo $id; ?>;
                
                $.ajax({
                    url: 'get_mechanics_availability.php',
                    type: 'POST',
                    data: {
                        date: date,
                        appointment_id: appointmentId
                    },
                    success: function(response){
                        $('#mechanic_id').html(response);
                    }
                });
            });
        });
    </script>
</body>
</html>