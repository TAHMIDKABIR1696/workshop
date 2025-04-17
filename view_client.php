<?php
// admin/view_client.php - View client details with their appointments
session_start();
require_once '../includes/db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if client ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid client ID";
    header('Location: clients.php');
    exit();
}

$client_id = mysqli_real_escape_string($conn, $_GET['id']);

// Get client data
$client_query = mysqli_query($conn, "SELECT * FROM clients WHERE id = $client_id");
if (mysqli_num_rows($client_query) == 0) {
    $_SESSION['error_message'] = "Client not found";
    header('Location: clients.php');
    exit();
}

$client = mysqli_fetch_assoc($client_query);

// Get client appointments
$appointments_sql = "SELECT a.*, m.name as mechanic_name 
                    FROM appointments a 
                    JOIN mechanics m ON a.mechanic_id = m.id 
                    WHERE a.client_id = $client_id 
                    ORDER BY a.appointment_date DESC, a.id DESC";
$appointments_result = mysqli_query($conn, $appointments_sql);

// Get page title and include header
$page_title = "Client Details";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Client Details</h2>
            <p class="text-muted">View detailed information and appointment history</p>
        </div>
        <div class="col-auto">
            <a href="clients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Clients
            </a>
            <a href="edit_client.php?id=<?php echo $client_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Client
            </a>
            <a href="add_appointment.php?client_id=<?php echo $client_id; ?>" class="btn btn-success">
                <i class="fas fa-calendar-plus"></i> New Appointment
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Client Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">Name:</span>
                            <span><?php echo htmlspecialchars($client['name']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">Phone:</span>
                            <span><?php echo htmlspecialchars($client['phone']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">Email:</span>
                            <span><?php echo !empty($client['email']) ? htmlspecialchars($client['email']) : '<em>Not provided</em>'; ?></span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold">Address:</span>
                            <p class="mb-0 mt-1"><?php echo !empty($client['address']) ? nl2br(htmlspecialchars($client['address'])) : '<em>Not provided</em>'; ?></p>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">Created:</span>
                            <span><?php echo date('M d, Y', strtotime($client['created_at'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Client's Vehicle Information -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Vehicles</h5>
                    <a href="add_vehicle.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add Vehicle
                    </a>
                </div>
                <div class="card-body">
                    <?php
                    // Get client vehicles
                    $vehicles_sql = "SELECT * FROM vehicles WHERE client_id = $client_id ORDER BY id DESC";
                    $vehicles_result = mysqli_query($conn, $vehicles_sql);
                    
                    if (mysqli_num_rows($vehicles_result) > 0):
                    ?>
                    <div class="list-group">
                        <?php while($vehicle = mysqli_fetch_assoc($vehicles_result)): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($vehicle['license_plate']); ?></h6>
                                <small>
                                    <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="text-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </small>
                            </div>
                            <p class="mb-1">
                                <small class="text-muted">Engine: <?php echo htmlspecialchars($vehicle['engine_number']); ?></small>
                            </p>
                            <?php if(!empty($vehicle['make']) || !empty($vehicle['model'])): ?>
                            <small><?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted">
                        <p>No vehicles registered for this client.</p>
                        <a href="add_vehicle.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-outline-primary">
                            Add First Vehicle
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Appointment History</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($appointments_result) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Vehicle</th>
                                    <th>Mechanic</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($appointment = mysqli_fetch_assoc($appointments_result)): 
                                    // Get vehicle info
                                    $vehicle_id = $appointment['vehicle_id'];
                                    $vehicle_query = mysqli_query($conn, "SELECT license_plate FROM vehicles WHERE id = $vehicle_id");
                                    $vehicle = mysqli_fetch_assoc($vehicle_query);
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo $vehicle ? htmlspecialchars($vehicle['license_plate']) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($appointment['mechanic_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['service_description']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo ($appointment['status'] == 'scheduled') ? 'bg-warning' : 
                                                (($appointment['status'] == 'completed') ? 'bg-success' : 'bg-danger'); 
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">This client has no appointment history.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>