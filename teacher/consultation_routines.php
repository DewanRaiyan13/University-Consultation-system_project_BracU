<?php
require '../includes/session_handler.php';
require '../includes/db_connect.php';


if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];


$sql = "SELECT day, DATE_FORMAT(time_from, '%h:%i %p') AS time_from, DATE_FORMAT(time_to, '%h:%i %p') AS time_to 
        FROM consultation_hours WHERE teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$consultations = $stmt->get_result();


$sql_requests = "SELECT cr.student_id, ch.day, DATE_FORMAT(ch.time_from, '%h:%i %p') AS time_from, DATE_FORMAT(ch.time_to, '%h:%i %p') AS time_to, cr.status
                 FROM consultation_requests cr
                 JOIN consultation_hours ch ON cr.consultation_hour_id = ch.id
                 WHERE cr.teacher_id = ? AND cr.status = 'approved'";
$stmt_requests = $conn->prepare($sql_requests);
$stmt_requests->bind_param("i", $teacher_id);
$stmt_requests->execute();
$requests = $stmt_requests->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Routines</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background-color: rgb(255, 255, 255);
            box-shadow: 0 2px 10px rgb(0, 0, 0);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #2c3e50;
            background-color:rgb(38, 77, 248);
            color: white;
        }
        
        th {
            font-weight: 600;
        }
        
        tr:hover td {
            background-color: #2c3e50;
        }

        h1, h2 {
            color: rgb(226, 235, 241);
            text-align: center;
            margin: 20px 0;
        }

        .consultation-table {
            margin-bottom: 30px;
        }

        .back-button {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgb(226, 235, 241);
            text-decoration: none;
        }

        [colspan] {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <h1>Consultation Routines</h1>
            <table class="consultation-table">
                <thead>
                    <tr>
                        <th style="width: 33%;">Day</th>
                        <th style="width: 33%;">Time From</th>
                        <th style="width: 33%;">Time To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($consultations->num_rows > 0) {
                        while ($row = $consultations->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['day']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['time_from']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['time_to']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>No consultation hours assigned.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <h2>Approved Consultation Requests</h2>
            <table class="consultation-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Student ID</th>
                        <th style="width: 20%;">Day</th>
                        <th style="width: 20%;">Time From</th>
                        <th style="width: 20%;">Time To</th>
                        <th style="width: 20%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($requests->num_rows > 0) {
                        while ($row = $requests->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['day']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['time_from']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['time_to']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No approved consultation requests.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <br>
            <a href="dashboard.php" class="back-button">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>