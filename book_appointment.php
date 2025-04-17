<?php
// book_appointment.php - Appointment booking page
require_once "config.php";

$message = "";
$messageClass = "";

// Check if form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $car_license = mysqli_real_escape_string($conn, $_POST['car_license']);
    $car_engine = mysqli_real_escape_string($conn, $_POST['car_engine']);
    $mechanic_id = mysqli_real_escape_string($conn, $_POST['mechanic_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    
    // Validate appointment date is not in the past
    if(strtotime($appointment_date) < strtotime(date('Y-m-d'))){
        $message = "Appointment date cannot be in the past.";
        $messageClass = "alert-danger";
    } else {
        // Check if client already has an appointment on the selected date with ANY mechanic
        $sql = "SELECT a.id FROM appointments a 
                JOIN clients c ON a.client_id = c.id 
                WHERE c.phone = '$phone' AND a.appointment_date = '$appointment_date' AND a.status = 'scheduled'";
        $result = mysqli_query($conn, $sql);
        
        if(mysqli_num_rows($result) > 0){
            $message = "You already have an appointment scheduled for this date with another mechanic.";
            $messageClass = "alert-danger";
        } else {
            // Check if mechanic has reached maximum daily appointments
            $sql = "SELECT m.max_daily_cars, 
                   (SELECT COUNT(*) FROM appointments a WHERE a.mechanic_id = $mechanic_id AND a.appointment_date = '$appointment_date' AND a.status = 'scheduled') as booked
                   FROM mechanics m 
                   WHERE m.id = $mechanic_id";
            $result = mysqli_query($conn, $sql);
            $mechanic = mysqli_fetch_assoc($result);
            
            if($mechanic['booked'] >= $mechanic['max_daily_cars']){
                $message = "The selected mechanic has reached their maximum number of appointments for this date. Please select another mechanic or date.";
                $messageClass = "alert-danger";
            } else {
                // First check if client exists
                $sql = "SELECT id FROM clients WHERE phone = '$phone' AND car_license = '$car_license'";
                $result = mysqli_query($conn, $sql);
                
                if(mysqli_num_rows($result) > 0){
                    // Client exists, get client ID
                    $client = mysqli_fetch_assoc($result);
                    $client_id = $client['id'];
                    
                    // Update client information
                    $sql = "UPDATE clients SET name = '$name', address = '$address', car_engine_number = '$car_engine' WHERE id = $client_id";
                    mysqli_query($conn, $sql);
                } else {
                    // Insert new client
                    $sql = "INSERT INTO clients (name, address, phone, car_license, car_engine_number) 
                            VALUES ('$name', '$address', '$phone', '$car_license', '$car_engine')";
                    
                    if(mysqli_query($conn, $sql)){
                        $client_id = mysqli_insert_id($conn);
                    } else {
                        $message = "Error: " . $sql . "<br>" . mysqli_error($conn);
                        $messageClass = "alert-danger";
                    }
                }
                
                // Create appointment
                if(!empty($client_id)){
                    $sql = "INSERT INTO appointments (client_id, mechanic_id, appointment_date) 
                            VALUES ($client_id, $mechanic_id, '$appointment_date')";
                    
                    if(mysqli_query($conn, $sql)){
                        $message = "Appointment booked successfully!";
                        $messageClass = "alert-success";
                    } else {
                        $message = "Error: " . $sql . "<br>" . mysqli_error($conn);
                        $messageClass = "alert-danger";
                    }
                }
            }
        }
    }
}

// Get mechanic ID from URL if provided
$selected_mechanic = isset($_GET['mechanic_id']) ? intval($_GET['mechanic_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Car Workshop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">

    <style>
        body {
            background: url('bg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: white;
        }

        .jumbotron, .card, .navbar, footer {
            background-color: rgba(0, 0, 0, 0.7) !important;
            color: white;
        }

        .card-title, .card-subtitle, .card-text, .nav-link, .navbar-brand {
            color: white !important;
        }

        .btn-outline-primary {
            color: white;
            border-color: white;
        }

        .btn-outline-primary:hover {
            background-color: white;
            color: black;
        }

        .btn-primary {
            background-color: transparent;
            border: 2px solid white;
            color: white;
            font-weight: bold;
            transition: 0.3s ease;
            padding: 10px 20px;
            border-radius: 8px;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background-color: white;
            color: black;
            transform: scale(1.05);
        }

        .container {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Home</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="book_appointment.php">Book Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="mb-4">Book an Appointment</h1>
        
        <?php if(!empty($message)): ?>
        <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="car_license" class="form-label">Car License Number</label>
                            <input type="text" class="form-control" id="car_license" name="car_license" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="car_engine" class="form-label">Car Engine Number</label>
                            <input type="text" class="form-control" id="car_engine" name="car_engine" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="appointment_date" class="form-label">Appointment Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mechanic_id" class="form-label">Select Mechanic</label>
                            <select class="form-select" id="mechanic_id" name="mechanic_id" required>
                                <option value="">Choose a mechanic</option>
                                <?php
                                // Get all mechanics and their availability
                                $sql = "SELECT m.id, m.name, m.specialization, m.max_daily_cars 
                                       FROM mechanics m";
                                $result = mysqli_query($conn, $sql);
                                
                                while($mechanic = mysqli_fetch_assoc($result)){
                                    $selected = ($mechanic['id'] == $selected_mechanic) ? 'selected' : '';
                                    echo "<option value='{$mechanic['id']}' $selected>{$mechanic['name']} - {$mechanic['specialization']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="availability-info">
                        <!-- Availability info will be loaded here via AJAX -->
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            // Function to check mechanic availability
            function checkAvailability() {
                var mechanicId = $('#mechanic_id').val();
                var appointmentDate = $('#appointment_date').val();
                var phone = $('#phone').val();
                
                if(mechanicId && appointmentDate) {
                    $.ajax({
                        url: 'check_availability.php',
                        type: 'POST',
                        data: {
                            mechanic_id: mechanicId,
                            appointment_date: appointmentDate,
                            phone: phone
                        },
                        success: function(response){
                            $('#availability-info').html(response);
                        }
                    });
                }
            }
            
            // Check availability when mechanic or date changes
            $('#mechanic_id, #appointment_date, #phone').change(function(){
                if($('#phone').val()) {
                    checkAvailability();
                }
            });
            
            // Initial check if all values are set
            if($('#mechanic_id').val() && $('#appointment_date').val() && $('#phone').val()) {
                checkAvailability();
            }
        });
    </script>
</body>
</html>