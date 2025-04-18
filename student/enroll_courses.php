<?php
include ('../includes/db_connect.php');

session_start();


if ($_SESSION['role'] != 'student') {
    header('Location: ../index.php');
    exit;
}

$student_id = $_SESSION['user_id'];  



$query = "SELECT student_id, course_code, section, class_time 
          FROM enrollments 
          WHERE student_id = ?"; // 

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);  
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background-color: rgb(255, 255, 255);
            box-shadow: 0 2px 10px rgb(51, 59, 133);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #2c3e50;
        }
        
        th {
            background-color: #34495e;
            color: white;
            font-weight: 600;
        }
        
        td {
            background-color: #34495e;
            color: white;
        }
        
        tr:hover td {
            background-color: #2c3e50;
        }

        .no-data {
            text-align: center;
            padding: 20px;
        }

        .back-button {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgb(226, 235, 241);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <h2 style="color: rgb(226, 235, 241); text-align: center;">Enrollment Schedule</h2>
           
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Student ID</th>
                        <th style="width: 25%;">Course Code</th>
                        <th style="width: 25%;">Section</th>
                        <th style="width: 25%;">Class Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['student_id']) . "</td>";
                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['course_code']) . "</td>";
                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['section']) . "</td>";
                            echo "<td style='text-align: center;'>" . htmlspecialchars($row['class_time']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='no-data'>No enrollments found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <a href="dashboard.php" class="back-button">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

