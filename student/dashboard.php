<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'student') {
    header('Location: ../index.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$sql = "SELECT name FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();


$sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
        FROM ratings 
        WHERE rated_for = ? AND role = 'student'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$rating = $result->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .rating-display {
            background-color: #fff;
            padding: 10px;
            border-radius: 40px;
            box-shadow: 0 2px 5px rgba(196, 230, 4, 0.94);
            margin-bottom: 15px;
            text-align: center;
        }
        .rating-emoji {
            font-size: 2em;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <header>
                <h1 style="color: rgb(255, 255, 255);"> Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h1>
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
            <p>Based on <?php echo $rating['total_ratings']; ?> ratings</p>
        </div>
        <?php endif; ?>
            
            <nav class="login-form">
                <div class="group">
                    <a href="enroll_courses.php" class="button">Enroll in Courses</a>
                </div>
                <div class="group">
                    <a href="book_consultation.php" class="button">Book Consultations</a>
                </div>
                <div class="group">
                    <a href="rate_teachers.php" class="button">Rate Teachers</a>
                </div>
                <div class="group">
                    <a href="view_ratings.php" class="button">View_ratings</a>
                </div>   
                <div class="group">
                    <a href="../logout.php?logout=true" class="button">Logout</a>
                </div>
            </nav>
        </div>
    </div>
</body>
</html>


