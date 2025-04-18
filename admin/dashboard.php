<?php
require '../includes/session_handler.php';
require '../includes/db_connect.php';


if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <header>
                <h1 style = "color:rgb(226, 235, 241)">Admin Dashboard</h1>
            </header>
            
            <nav class="login-form">
                <div class="group">
                    <a href="manage_users.php" class="button">Manage Users</a>
                </div>
                <div class="group">
                    <a href="manage_courses.php" class="button">Manage Courses</a>
                </div>
                <div class="group">
                    <a href="assign_courses.php" class="button">Assign / Enroll Courses</a>
                </div>
                <div class="group">
                    <a href="consultation_schedule.php" class="button">Consultation Schedules</a>
                </div>
                <div class="group">
                    <a href="../logout.php" class="button">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</body>
</html>

