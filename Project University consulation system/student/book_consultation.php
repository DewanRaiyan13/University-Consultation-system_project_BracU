<?php
session_start();
include('../includes/db_connect.php');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$student_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_consultation'])) {
    $consultation_hour_id = $_POST['consultation_hour_id'];
    $teacher_id = $_POST['teacher_id'];

   
    $sql_check = "SELECT COUNT(*) AS count, status FROM consultation_requests 
                  WHERE student_id = ? AND consultation_hour_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $student_id, $consultation_hour_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();

   
    if ($row_check['count'] == 0 || $row_check['status'] == 'approved' || $row_check['status'] == 'rejected') {
       
        $sql = "INSERT INTO consultation_requests (student_id, consultation_hour_id, teacher_id, status) 
                VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $student_id, $consultation_hour_id, $teacher_id);
        $stmt->execute();
        $stmt->close();
        $success = "Consultation request sent successfully!";
    } else {
        $error = "You have already requested this consultation hour and it's still pending.";
    }
}


$sql = "SELECT ch.id, ch.teacher_id, ch.day, 
               DATE_FORMAT(ch.time_from, '%h:%i %p') AS time_from, 
               DATE_FORMAT(ch.time_to, '%h:%i %p') AS time_to, 
               u.name AS teacher_name, cr.status 
        FROM consultation_hours ch 
        JOIN users u ON ch.teacher_id = u.id 
        LEFT JOIN consultation_requests cr ON cr.consultation_hour_id = ch.id AND cr.student_id = ? 
        WHERE u.role = 'teacher' AND (cr.student_id IS NULL OR cr.status != 'accepted')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$consultation_hours = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Consultation</title>
    <link rel="stylesheet" href="../css/styles.css">
    
    
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1161ee;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        button {
            padding: 8px 16px;
            border: none;
            background-color: #007bff;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-button {
            display: block;
            text-align: center;
            margin: 10px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .message p {
            text-align: center;
            font-size: 18px;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .message .success {
            background-color: #d4edda;
            color: #155724;
        }
        .message .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <h1>Available Consultation Hours</h1>
            
            <div class="message">
                <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            </div>

            <a href="dashboard.php" class="back-button">Back to Dashboard</a>

            <table>
                <thead>
                    <tr>
                        <th>Teacher Name</th>
                        <th>Day</th>
                        <th>Time From</th>
                        <th>Time To</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($consultation_hours) > 0): ?>
                        <?php foreach ($consultation_hours as $hour): ?>
                            <tr>
                                <td><?= htmlspecialchars($hour['teacher_name']) ?></td>
                                <td><?= htmlspecialchars($hour['day']) ?></td>
                                <td><?= htmlspecialchars($hour['time_from']) ?></td>
                                <td><?= htmlspecialchars($hour['time_to']) ?></td>
                                <td>
                                    <?php if ($hour['status'] == 'pending'): ?>
                                        <span style="color: orange;">Pending</span>
                                    <?php elseif ($hour['status'] == 'approved'): ?>
                                        <span style="color: green;">Approved</span>
                                    <?php elseif ($hour['status'] == 'rejected'): ?>
                                        <span style="color: red;">Rejected</span>
                                    <?php else: ?>
                                        <span style="color: grey;">Not Requested</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hour['status'] === NULL): ?>
                                        <!-- If not requested yet -->
                                        <form method="POST" action="">
                                            <input type="hidden" name="consultation_hour_id" value="<?= $hour['id'] ?>">
                                            <input type="hidden" name="teacher_id" value="<?= $hour['teacher_id'] ?>">
                                            <button type="submit" name="request_consultation">Request Consultation</button>
                                        </form>
                                    <?php elseif ($hour['status'] === 'pending'): ?>
                                        <!-- If the request is pending -->
                                        <span>Pending</span>
                                    <?php else: ?>
                                        <!-- If the request is approved or rejected, allow the student to request again -->
                                        <form method="POST" action="">
                                            <input type="hidden" name="consultation_hour_id" value="<?= $hour['id'] ?>">
                                            <input type="hidden" name="teacher_id" value="<?= $hour['teacher_id'] ?>">
                                            <button type="submit" name="request_consultation">Request Again</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No available consultation hours.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
