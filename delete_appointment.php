<?php
// admin/delete_appointment.php - Delete appointment

session_start();

// Include database configuration
require_once "../config.php";

// Debug mode - uncomment these lines if you need to troubleshoot
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Check if ID parameter exists
if(!isset($_GET['id']) || empty($_GET['id'])) {
    // No ID provided, redirect back to dashboard
    header("Location: index.php?error=missing_id");
    exit();
}

// Get and sanitize appointment ID
$appointment_id = intval($_GET['id']);

// Check if appointment exists
$check_sql = "SELECT id FROM appointments WHERE id = ?";
if($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if(mysqli_stmt_num_rows($stmt) == 0) {
        // Appointment not found
        mysqli_stmt_close($stmt);
        header("Location: index.php?error=not_found");
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Delete the appointment using prepared statement
$delete_sql = "DELETE FROM appointments WHERE id = ?";
if($stmt = mysqli_prepare($conn, $delete_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    
    if(mysqli_stmt_execute($stmt)) {
        // Successful deletion
        mysqli_stmt_close($stmt);
        header("Location: index.php?success=appointment_deleted");
        exit();
    } else {
        // Error occurred
        $error = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        header("Location: index.php?error=delete_failed&message=" . urlencode($error));
        exit();
    }
} else {
    // Prepare statement failed
    header("Location: index.php?error=prepare_failed&message=" . urlencode(mysqli_error($conn)));
    exit();
}
?>