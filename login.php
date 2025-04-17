<?php
session_start();

if(isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true){
    header("location: index.php");
    exit;
}

require_once "../config.php";

// Default credentials (for demonstration only)
$admin_username = "tahmid";
$admin_password = "1234";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($username_err) && empty($password_err)){
        if($username === $admin_username && $password === $admin_password){
            session_start();
            $_SESSION["admin_loggedin"] = true;
            $_SESSION["admin_username"] = $username;
            header("location: index.php");
        } else{
            $login_err = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Car Workshop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body {
            background: url('../bg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: white;
        }

        .card {
            background-color: rgba(0, 0, 0, 0.75);
            color: white;
            border: none;
        }

        .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid #ccc;
            color: white;
        }

        .form-control::placeholder {
            color: #ccc;
        }

        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-primary {
            background-color: black;
            color: white;
            border: 1px solid white;
        }

        .btn-primary:hover {
            background-color: white;
            color: black;
        }

        .alert {
            background-color: rgba(0, 0, 0, 0.6);
            border: 1px solid white;
            color: white;
        }

        footer {
            background-color: rgba(0, 0, 0, 0.8) !important;
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
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../book_appointment.php">Book Appointment</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="login.php">Admin Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header text-center">
                    <h3>Admin Login</h3>
                </div>
                <div class="card-body">
                    <?php 
                    if(!empty($login_err)){
                        echo '<div class="alert alert-danger">' . $login_err . '</div>';
                    }        
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username"
                                   class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo $username; ?>">
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password"
                                   class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
