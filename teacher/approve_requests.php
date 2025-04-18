<?php
session_start();
include('../includes/db_connect.php');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];  // either 'accept' or 'reject'

    try {
       
        if ($action == 'accept') {
            $update_sql = "UPDATE consultation_requests SET status = 'approved' WHERE id = ?";
        } elseif ($action == 'reject') {
            $update_sql = "UPDATE consultation_requests SET status = 'rejected' WHERE id = ?";
        }

        
        if (isset($request_id)) {
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $request_id);
            $update_stmt->execute();
        }

        $success = "Consultation request has been " . ($action == 'accept' ? 'approved' : 'rejected') . " successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}


$sql = "SELECT cr.id, cr.student_id, cr.status, u.name AS student_name, ch.day, ch.time_from, ch.time_to
        FROM consultation_requests cr
        JOIN users u ON cr.student_id = u.id
        JOIN consultation_hours ch ON cr.consultation_hour_id = ch.id
        WHERE cr.teacher_id = ? AND cr.status = 'pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Consultation Requests</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <header>
                <h1 style="color: rgb(226, 235, 241); text-align: center;">Approve or Reject Consultation Requests</h1>
            </header>

           
            <div class="group">
                <?php if (isset($success)) echo "<p style='color: green;'>$success</p>"; ?>
                <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
            </div>

           
            <div class="group">
                <a href="dashboard.php" class="button">Back to Dashboard</a>
            </div>

            
            <h2 style="color: rgb(226, 235, 241); text-align: center;">Pending Consultation Requests</h2>
            <div class="group">
                <table border="1" style="width: 100%; border-collapse: collapse; background-color: rgba(255, 255, 255, 0.1);">
                    <thead>
                        <tr style="background-color: rgba(40, 57, 101, 0.9); color: white;">
                            <th>Student Name</th>
                            <th>Day</th>
                            <th>Time From</th>
                            <th>Time To</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($requests as $request): ?>
                                <tr style="color: rgb(226, 235, 241);">
                                    <td><?= htmlspecialchars($request['student_name']) ?></td>
                                    <td><?= htmlspecialchars($request['day']) ?></td>
                                    <td><?= htmlspecialchars($request['time_from']) ?></td>
                                    <td><?= htmlspecialchars($request['time_to']) ?></td>
                                    <td>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <span style="color: orange;">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" name="action" value="accept" class="button" style="background-color: green;">Accept</button>
                                            <button type="submit" name="action" value="reject" class="button" style="background-color: red;">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: rgb(226, 235, 241);">No pending consultation requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

