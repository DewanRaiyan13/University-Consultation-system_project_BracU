<?php

include('../includes/db_connect.php');


session_start();
if ($_SESSION['role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}


$query = "SELECT teacher_id, course_code, section, class_time FROM enrollments";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <header>
                <h1 style="color: rgb(226, 235, 241); text-align: center;">All Teachers' Schedule</h1>
            </header>

            <div class="group">
                <table style="width: 100%; border-collapse: collapse; background-color: rgba(255, 255, 255, 0.1);">
                    <thead>
                        <tr style="background-color: rgba(40, 57, 101, 0.9); color: white;">
                            <th>Teacher ID</th>
                            <th>Course Code</th>
                            <th>Section</th>
                            <th>Class Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr style='color: rgb(226, 235, 241); height: 40px;'>";
                                echo "<td>" . htmlspecialchars($row['teacher_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['course_code']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['section']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['class_time']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr>";
                            echo "<td colspan='4' style='text-align: center; color: rgb(226, 235, 241);'>No enrollments found</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
