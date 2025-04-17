<?php
// admin/clients.php - Client management for admin
session_start();
require_once "../config.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle client deletion if requested
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Check if client has appointments before deleting
    $check_appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE client_id = $delete_id");
    $has_appointments = mysqli_fetch_assoc($check_appointments)['count'] > 0;
    
    if ($has_appointments) {
        $delete_message = "Cannot delete client because they have existing appointments.";
        $delete_status = "danger";
    } else {
        // Delete the client
        mysqli_query($conn, "DELETE FROM clients WHERE id = $delete_id");
        if (mysqli_affected_rows($conn) > 0) {
            $delete_message = "Client deleted successfully.";
            $delete_status = "success";
        } else {
            $delete_message = "Error deleting client.";
            $delete_status = "danger";
        }
    }
}

// Initialize search filters
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build the SQL query for clients
$sql = "SELECT * FROM clients WHERE 1=1";

// Apply search filter if provided
if (!empty($search_term)) {
    $sql .= " AND (name LIKE '%$search_term%' OR phone LIKE '%$search_term%' OR email LIKE '%$search_term%' OR address LIKE '%$search_term%')";
}

$sql .= " ORDER BY name ASC";
$result = mysqli_query($conn, $sql);

// Get page title and include header
$page_title = "Client Management";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Client Management</h2>
            <p class="text-muted">Manage workshop clients and their information</p>
        </div>
        <div class="col-auto">
            <a href="add_client.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Client
            </a>
        </div>
    </div>

    <?php if(isset($delete_message)): ?>
    <div class="alert alert-<?php echo $delete_status; ?> alert-dismissible fade show" role="alert">
        <?php echo $delete_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Search Clients</h5>
        </div>
        <div class="card-body">
            <form action="clients.php" method="get" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, phone, email or address" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="clients.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Clients</h5>
            <div>
                <?php
                // Count total clients
                $count_sql = "SELECT COUNT(*) as total FROM clients WHERE 1=1";
                if (!empty($search_term)) {
                    $count_sql .= " AND (name LIKE '%$search_term%' OR phone LIKE '%$search_term%' OR email LIKE '%$search_term%' OR address LIKE '%$search_term%')";
                }
                $count_result = mysqli_query($conn, $count_sql);
                $count_row = mysqli_fetch_assoc($count_result);
                ?>
                <span class="badge bg-primary"><?php echo $count_row['total']; ?> Clients</span>
            </div>
        </div>
        <div class="card-body">
            <?php if(mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Car License</th>
                            <th>Total Appointments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($client = mysqli_fetch_assoc($result)): 
                            // Get appointment count for this client
                            $client_id = $client['id'];
                            $appt_count_sql = "SELECT COUNT(*) as total FROM appointments WHERE client_id = $client_id";
                            $appt_count_result = mysqli_query($conn, $appt_count_sql);
                            $appt_count = mysqli_fetch_assoc($appt_count_result)['total'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['name']); ?></td>
                            <td><?php echo htmlspecialchars($client['phone']); ?></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo htmlspecialchars($client['address']); ?></td>
                            <td><?php echo htmlspecialchars($client['car_license']); ?></td>
                            <td><?php echo $appt_count; ?></td>
                            <td>
                                <a href="view_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="clients.php?delete_id=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this client?');" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">No clients found matching your search criteria.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>