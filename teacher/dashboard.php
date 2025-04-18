<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$sql = "SELECT name FROM teachers WHERE teacher_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    $teacher = array('name' => 'Teacher');
}

$sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
    FROM ratings 
    WHERE rated_for = ? AND role = 'teacher'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$rating = $result->fetch_assoc();

$sql = "SELECT COUNT(*) as pending_count 
    FROM consultation_requests 
    WHERE teacher_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$pending = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
            .stat-card {
            background-color: #fff;
            padding: 5px;
            border-radius: 3px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
            }
        .rating-display {
            background-color: #fff;
            padding: 5px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.94);
            margin-bottom: 5px;
            text-align: center;
        }
        .rating-emoji {
            font-size: 2.5em;
            margin: 10px 0;
        }
        
        .pending-requests {
            color:rgb(238, 23, 45);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <header>
                <h1 style="color: rgb(255, 255, 255);"> Welcome, <?php echo htmlspecialchars($teacher['name']); ?>!</h1>
            </header>
            <?php if ($rating['avg_rating']): ?>
        <div class="rating-display">
            <h3>Your Performance Rating</h3>
            <div class="rating-emoji">
                <?php
                $avg_rating = round($rating['avg_rating']);
                switch($avg_rating) {
                    case 1: echo "😢"; break;
                    case 2: echo "😕"; break;
                    case 3: echo "😐"; break;
                    case 4: echo "😊"; break;
                    case 5: echo "😍"; break;
                }
                ?>
            </div>
            <p>Average Rating: <?php echo number_format($rating['avg_rating'], 1); ?> / 5.0</p>
            <p>Based on <?php echo htmlspecialchars($rating['total_ratings']); ?> student ratings</p>
        </div>
        <?php endif; ?>
        <div class="stat-card">
                <h3>Consultation Requests</h3>
                <p class="pending-requests">
                    <?php echo isset($pending['pending_count']) ? htmlspecialchars($pending['pending_count']) : '0'; ?> Pending Requests
                </p>
                <a href="approve_requests.php" style="text-decoration: none; padding: 8px; background-color:rgb(49, 3, 252); color: white; border-radius: 3px;">Approve Requests</a>
            </div>
            <nav class="login-form">
                <div class="group">
                    <a href="manage_schedule.php" class="button">Manage Schedule</a>
                </div>
                <div class="group">
                    <a href="consultation_routines.php" class="button">Consultation Routines</a>
                </div>
                <div class="group">
                    <a href="rate_students.php" class="button">Rate Students</a>
                </div>
                <div class="group">
                    <a href="view_ratings.php" class="button">view ratings</a>
                </div>
                <div class="group">
                    <a href="../logout.php?logout=true" class="button">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</body>
</html>



