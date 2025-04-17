<?php
// admin/edit_client.php - Edit existing client
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Validate data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // Check if phone already exists (excluding current client)
    $check_phone = mysqli_query($conn, "SELECT id FROM clients WHERE phone = '$phone' AND id != $client_id");
    if (mysqli_num_rows($check_phone) > 0) {
        $errors[] = "A client with this phone number already exists";
    }
    
    // If no errors, update client
    if (empty($errors)) {
        $sql = "UPDATE clients SET 
                name = '$name', 
                phone = '$phone', 
                email = '$email', 
                address = '$address', 
                updated_at = NOW() 
                WHERE id = $client_id";
        
        if (mysqli_query($conn, $sql)) {
            // Redirect to client list with success message
            $_SESSION['success_message'] = "Client updated successfully";
            header('Location: clients.php');
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}

// Get page title and include header
$page_title = "Edit Client";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Edit Client</h2>
            <p class="text-muted">Update client information</p>
        </div>
        <div class="col-auto">
            <a href="clients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Clients
            </a>
        </div>
    </div>

    <?php if(!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Client Information</h5>
        </div>
        <div class="card-body">
            <form action="edit_client.php?id=<?php echo $client_id; ?>" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Client Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($client['name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required
                               value="<?php echo htmlspecialchars($client['phone']); ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($client['email']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($client['address']); ?></textarea>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">Update Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>