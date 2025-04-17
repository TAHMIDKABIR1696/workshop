<?php
// admin/mechanics.php - Manage mechanics
session_start();

// Check if admin is logged in
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../config.php";

$message = "";
$messageClass = "";

// Process form submission for adding mechanic
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_mechanic'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    
    // Validate input
    if(empty($name) || empty($specialization)) {
        $message = "Please fill all fields.";
        $messageClass = "alert-danger";
    } else {
        // Insert mechanic
        $sql = "INSERT INTO mechanics (name, specialization) VALUES ('$name', '$specialization')";
        
        if(mysqli_query($conn, $sql)){
            $message = "Mechanic added successfully.";
            $messageClass = "alert-success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageClass = "alert-danger";
        }
    }
}

// Process mechanic update
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_mechanic'])){
    $mechanic_id = intval($_POST['mechanic_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    
    // Validate input
    if(empty($name) || empty($specialization)) {
        $message = "Please fill all fields.";
        $messageClass = "alert-danger";
    } else {
        // Update mechanic
        $sql = "UPDATE mechanics SET name = '$name', specialization = '$specialization' WHERE id = $mechanic_id";
        
        if(mysqli_query($conn, $sql)){
            $message = "Mechanic updated successfully.";
            $messageClass = "alert-success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageClass = "alert-danger";
        }
    }
}

// Process mechanic deletion
if(isset($_GET['delete_id'])){
    $delete_id = intval($_GET['delete_id']);
    
    // Check if mechanic has appointments
    $check_sql = "SELECT COUNT(*) as appointment_count FROM appointments WHERE mechanic_id = $delete_id";
    $check_result = mysqli_query($conn, $check_sql);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if($check_row['appointment_count'] > 0){
        $message = "Cannot delete mechanic. There are appointments assigned to this mechanic.";
        $messageClass = "alert-danger";
    } else {
        // Delete mechanic
        $sql = "DELETE FROM mechanics WHERE id = $delete_id";
        
        if(mysqli_query($conn, $sql)){
            $message = "Mechanic deleted successfully.";
            $messageClass = "alert-success";
        } else {
            $message = "Error: " . mysqli_error($conn);
            $messageClass = "alert-danger";
        }
    }
}

// Get all mechanics
$sql = "SELECT * FROM mechanics ORDER BY name";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Mechanics - Car Workshop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

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
            color: black;
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
                        <a class="nav-link active" href="mechanics.php">Manage Mechanics</a>
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
        <h1 class="mb-4">Manage Mechanics</h1>
        
        <?php if(!empty($message)): ?>
        <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Mechanic</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="specialization" class="form-label">Specialization</label>
                                <input type="text" class="form-control" id="specialization" name="specialization" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_mechanic" class="btn btn-primary">Add Mechanic</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Mechanics List</h5>
                        <span class="badge bg-primary"><?php echo mysqli_num_rows($result); ?> Mechanics</span>
                    </div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Specialization</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-mechanic" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                    data-specialization="<?php echo htmlspecialchars($row['specialization']); ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="mechanics.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this mechanic?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">No mechanics found. Add your first mechanic using the form.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editMechanicModal" tabindex="-1" aria-labelledby="editMechanicModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMechanicModalLabel">Edit Mechanic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="editMechanicForm">
                        <input type="hidden" name="mechanic_id" id="edit_mechanic_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_specialization" class="form-label">Specialization</label>
                            <input type="text" class="form-control" id="edit_specialization" name="specialization" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editMechanicForm" name="update_mechanic" class="btn btn-primary">Update Mechanic</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript for edit mechanic modal
        document.addEventListener('DOMContentLoaded', function() {
            // Get all edit buttons
            const editButtons = document.querySelectorAll('.edit-mechanic');
            
            // Add click event listener to each edit button
            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Get mechanic data from data attributes
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const specialization = this.getAttribute('data-specialization');
                    
                    // Set values in the modal form
                    document.getElementById('edit_mechanic_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_specialization').value = specialization;
                    
                    // Show modal
                    const editModal = new bootstrap.Modal(document.getElementById('editMechanicModal'));
                    editModal.show();
                });
            });
        });
    </script>
</body>
</html>