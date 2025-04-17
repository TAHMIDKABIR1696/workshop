<?php
// admin/add_appointment.php - Add new appointment
session_start();

// Include database configuration
require_once "../config.php";

// Define variables and initialize with empty values
$client_id = $mechanic_id = $appointment_date = "";
$client_id_err = $mechanic_id_err = $appointment_date_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate client
    if(empty(trim($_POST["client_id"]))){
        $client_id_err = "Please select a client.";
    } else{
        $client_id = trim($_POST["client_id"]);
    }
    
    // Validate mechanic
    if(empty(trim($_POST["mechanic_id"]))){
        $mechanic_id_err = "Please select a mechanic.";
    } else{
        $mechanic_id = trim($_POST["mechanic_id"]);
        
        // Check if mechanic has reached max daily appointments
        $check_date = trim($_POST["appointment_date"]);
        $check_sql = "SELECT COUNT(*) AS appointment_count FROM appointments 
                      WHERE mechanic_id = ? AND appointment_date = ? AND status != 'cancelled'";
        
        if($stmt = mysqli_prepare($conn, $check_sql)){
            mysqli_stmt_bind_param($stmt, "is", $mechanic_id, $check_date);
            
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $appointment_count);
                mysqli_stmt_fetch($stmt);
                
                // Get mechanic's max daily cars
                $mechanic_sql = "SELECT max_daily_cars FROM mechanics WHERE id = ?";
                $mechanic_stmt = mysqli_prepare($conn, $mechanic_sql);
                mysqli_stmt_bind_param($mechanic_stmt, "i", $mechanic_id);
                mysqli_stmt_execute($mechanic_stmt);
                mysqli_stmt_store_result($mechanic_stmt);
                mysqli_stmt_bind_result($mechanic_stmt, $max_daily_cars);
                mysqli_stmt_fetch($mechanic_stmt);
                mysqli_stmt_close($mechanic_stmt);
                
                if($appointment_count >= $max_daily_cars){
                    $mechanic_id_err = "This mechanic has reached maximum appointments for the selected date.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate appointment date
    if(empty(trim($_POST["appointment_date"]))){
        $appointment_date_err = "Please select an appointment date.";
    } else{
        $appointment_date = trim($_POST["appointment_date"]);
        
        // Check if date is in the future
        $today = date('Y-m-d');
        if($appointment_date < $today){
            $appointment_date_err = "Appointment date must be today or in the future.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($client_id_err) && empty($mechanic_id_err) && empty($appointment_date_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO appointments (client_id, mechanic_id, appointment_date) VALUES (?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "iis", $client_id, $mechanic_id, $appointment_date);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Redirect to appointments page
                header("location: index.php");
                exit();
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Appointment - Car Workshop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    body {
      background: black;
      background-size: cover;
      color: white;
      min-height: 100vh;
    }

    .overlay {
      background-color: rgba(0, 0, 0, 0.7);
      min-height: 100vh;
      padding-bottom: 50px;
    }

    .jumbotron, .card, .navbar, footer {
      background-color: rgba(0, 0, 0, 0.7) !important;
      color: white;
    }

    .card-title, .card-subtitle, .card-text, .nav-link, .navbar-brand {
      color: white !important;
    }

    .btn-outline-primary, .btn-primary {
      color: white;
      border-color: white;
      transition: all 0.3s ease;
    }

    .btn-outline-primary:hover, .btn-primary:hover {
      background-color: white;
      color: black;
      border-color: white;
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
                    <li class="nav-item">
                        <a class="nav-link" href="Manage clients.php">Manage Clients</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, Admin</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container my-5">
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0">Add New Appointment</h2>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Client</label>
                        <select name="client_id" id="client_id" class="form-select <?php echo (!empty($client_id_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Client</option>
                            <?php
                            // Get all clients
                            $clients_sql = "SELECT id, name, car_license FROM clients ORDER BY name";
                            $clients_result = mysqli_query($conn, $clients_sql);
                            
                            while($client_row = mysqli_fetch_assoc($clients_result)){
                                $selected = ($client_id == $client_row['id']) ? 'selected' : '';
                                echo "<option value='{$client_row['id']}' $selected>{$client_row['name']} - {$client_row['car_license']}</option>";
                            }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $client_id_err; ?></span>
                        <div class="mt-2">
                            <a href="add_client.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus"></i> Add New Client
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mechanic_id" class="form-label">Mechanic</label>
                        <select name="mechanic_id" id="mechanic_id" class="form-select <?php echo (!empty($mechanic_id_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Select Mechanic</option>
                            <?php
                            // Get all mechanics
                            $mechanics_sql = "SELECT id, name, specialization FROM mechanics ORDER BY name";
                            $mechanics_result = mysqli_query($conn, $mechanics_sql);
                            
                            while($mechanic_row = mysqli_fetch_assoc($mechanics_result)){
                                $selected = ($mechanic_id == $mechanic_row['id']) ? 'selected' : '';
                                echo "<option value='{$mechanic_row['id']}' $selected>{$mechanic_row['name']} - {$mechanic_row['specialization']}</option>";
                            }
                            ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $mechanic_id_err; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Appointment Date</label>
                        <input type="date" name="appointment_date" id="appointment_date" class="form-control <?php echo (!empty($appointment_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $appointment_date ? $appointment_date : date('Y-m-d'); ?>">
                        <span class="invalid-feedback"><?php echo $appointment_date_err; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" class="btn btn-success">Save Appointment</button>
                        <a href="index.php" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>