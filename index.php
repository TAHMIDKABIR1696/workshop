<?php
session_start();
require_once "../config.php";

// Initialize filters with proper validation
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$mechanic_filter = isset($_GET['mechanic_id']) ? intval($_GET['mechanic_id']) : 0;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Validate and format date
if (!empty($date_filter)) {
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_filter);
    if (!$date_obj) {
        // Fallback to current date if invalid
        $date_filter = date('Y-m-d');
    } else {
        $date_filter = $date_obj->format('Y-m-d');
    }
}

// Prepare SQL query with proper escaping
$sql = "SELECT a.id, c.name as client_name, c.phone, c.car_license, a.appointment_date, 
        m.name as mechanic_name, m.id as mechanic_id, a.status
        FROM appointments a
        JOIN clients c ON a.client_id = c.id
        JOIN mechanics m ON a.mechanic_id = m.id
        WHERE 1=1";

// Add filters with prepared statements (converted to mysqli for this example)
$params = [];
$types = '';

if(!empty($date_filter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if($mechanic_filter > 0) {
    $sql .= " AND a.mechanic_id = ?";
    $params[] = $mechanic_filter;
    $types .= 'i';
}

if(!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql .= " ORDER BY a.appointment_date DESC, m.name ASC";

// Prepare and execute query with parameters
$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count query
$count_sql = "SELECT COUNT(*) as total FROM appointments a WHERE 1=1";
if(!empty($date_filter)) {
    $count_sql .= " AND a.appointment_date = '$date_filter'";
}
if($mechanic_filter > 0) {
    $count_sql .= " AND a.mechanic_id = $mechanic_filter";
}
if(!empty($status_filter)) {
    $count_sql .= " AND a.status = '$status_filter'";
}
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Car Workshop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #000;
            color: #f8f9fa;
        }
        .hero-section {
            background: black;
        }
        .hero-title {
            color: #f8f9fa;
        }
        .mechanic-card {
            background-color: #1a1a1a;
            border: 1px solid #333;
            color: #f8f9fa;
        }
        .mechanic-name {
            color: #f8f9fa;
        }
        .mechanic-specialty {
            color: #aaa;
        }
        .slots-available {
            color: #007bff;
        }
        footer {
            background-color: #111;
            color: #ccc;
        }
        .navbar {
            background-color: #121212 !important;
        }
        .nav-link {
            color: #ddd !important;
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
                    <a class="nav-link active" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mechanics.php">Manage Mechanics</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="nav-link">Admin</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <h1 class="mb-4">Appointment Dashboard</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Appointments</h5>
        </div>
        <div class="card-body">
            <form action="index.php" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="date" class="form-label">Appointment Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-4">
                    <label for="mechanic_id" class="form-label">Mechanic</label>
                    <select class="form-select" id="mechanic_id" name="mechanic_id">
                        <option value="0">All Mechanics</option>
                        <?php
                        $mechanic_sql = "SELECT id, name FROM mechanics ORDER BY name";
                        $mechanic_result = mysqli_query($conn, $mechanic_sql);
                        
                        while($mechanic = mysqli_fetch_assoc($mechanic_result)){
                            $selected = ($mechanic['id'] == $mechanic_filter) ? 'selected' : '';
                            echo "<option value='".htmlspecialchars($mechanic['id'])."' $selected>".htmlspecialchars($mechanic['name'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Appointments</h5>
            <div>
                <span class="badge bg-primary"><?php echo htmlspecialchars($count_row['total']); ?> Appointments</span>
            </div>
        </div>
        <div class="card-body">
            <?php if(mysqli_num_rows($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Phone</th>
                            <th>Car License</th>
                            <th>Date</th>
                            <th>Mechanic</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['car_license']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['appointment_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['mechanic_name']); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo ($row['status'] == 'scheduled') ? 'bg-warning' : 
                                        (($row['status'] == 'completed') ? 'bg-success' : 'bg-danger'); 
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_appointment.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('Are you sure you want to delete this appointment?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">No appointments found with the selected filters.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="add_appointment.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Appointment
        </a>
    </div>
</div>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <p>&copy; <?php echo date("Y"); ?> Welcome ADMIN</p>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>