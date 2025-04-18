<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user details and ratings based on role
if ($role == 'teacher') {
    $sql = "SELECT t.*, 
            COALESCE(AVG(r.rating), 0) as average_rating,
            COUNT(DISTINCT r.id) as total_ratings
            FROM teachers t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN ratings r ON t.user_id = r.rated_for 
            WHERE t.user_id = ?
            GROUP BY t.teacher_id";
} else {
    $sql = "SELECT s.*, 
            COALESCE(AVG(r.rating), 0) as average_rating,
            COUNT(DISTINCT r.id) as total_ratings
            FROM students s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN ratings r ON s.user_id = r.rated_for 
            WHERE s.user_id = ? AND r.role = 'student'
            GROUP BY s.student_id";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overview = $stmt->get_result()->fetch_assoc();

// Get detailed ratings with comments and course information
if ($role == 'teacher') {
    $sql = "SELECT r.*, u.name as rater_name, r.created_at as rating_date
            FROM ratings r
            INNER JOIN users u ON r.rated_by = u.id
            INNER JOIN students s ON r.rated_by = s.user_id
            WHERE r.rated_for = ? AND r.role = 'teacher'
            ORDER BY r.created_at DESC";
} else {
    $sql = "SELECT r.*, u.name as rater_name, r.created_at as rating_date,
            c.course_code, c.course_name
            FROM ratings r
            INNER JOIN users u ON r.rated_by = u.id
            INNER JOIN teachers t ON r.rated_by = t.user_id
            LEFT JOIN courses c ON r.course_id = c.course_id
            WHERE r.rated_for = ? AND r.role = 'student'
            ORDER BY r.created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ratings = $stmt->get_result();

// Get user's name
$name_sql = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($name_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .ratings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .rating-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .rating-emoji {
            font-size: 2em;
            text-align: center;
            margin: 10px 0;
        }
        .course-info {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }
        .rating-date {
            color: #666;
            font-size: 0.9em;
            margin-top: 10px;
        }
        .average-rating {
            font-size: 1.5em;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="ratings-container">
        <h2>Ratings for <?php echo htmlspecialchars($user_data['name']); ?></h2>

        <div class="rating-overview">
            <h3>Overall Rating</h3>
            <?php if ($overview && $overview['average_rating'] > 0): ?>
                <div class="rating-emoji">
                    <?php
                    $avg_rating = round($overview['average_rating']);
                    echo match($avg_rating) {
                        1 => "😢",
                        2 => "😕",
                        3 => "😐",
                        4 => "😊",
                        5 => "😍",
                        default => "❓"
                    };
                    ?>
                </div>
                <div class="average-rating">
                    <?php echo number_format($overview['average_rating'], 1); ?>/5.0
                </div>
                <p>Based on <?php echo $overview['total_ratings']; ?> ratings</p>
            <?php else: ?>
                <p>No ratings yet</p>
            <?php endif; ?>
        </div>

        <h3>Recent Ratings</h3>
        <?php if ($ratings && $ratings->num_rows > 0): ?>
            <?php while ($rating = $ratings->fetch_assoc()): ?>
                <div class="rating-card">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo htmlspecialchars($rating['rater_name']); ?></strong>
                            <?php if ($role == 'student' && isset($rating['course_code'])): ?>
                                <div class="course-info">
                                    Course: <?php echo htmlspecialchars($rating['course_code'] . ' - ' . $rating['course_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="rating-emoji">
                            <?php
                            echo match($rating['rating']) {
                                1 => "😢",
                                2 => "😕",
                                3 => "😐",
                                4 => "😊",
                                5 => "😍",
                                default => "❓"
                            };
                            ?>
                        </span>
                    </div>
                    <?php if (!empty($rating['comment'])): ?>
                        <p style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                            "<?php echo htmlspecialchars($rating['comment']); ?>"
                        </p>
                    <?php endif; ?>
                    <div class="rating-date">
                        <?php echo date('F j, Y', strtotime($rating['rating_date'])); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-ratings">
                <p>No ratings received yet.</p>
            </div>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>