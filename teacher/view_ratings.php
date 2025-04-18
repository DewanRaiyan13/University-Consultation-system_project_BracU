<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get teacher details and overall ratings
$sql = "SELECT t.*, 
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(DISTINCT r.id) as total_ratings,
    COUNT(CASE WHEN r.rating = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN r.rating = 4 THEN 1 END) as four_star,
    COUNT(CASE WHEN r.rating = 3 THEN 1 END) as three_star,
    COUNT(CASE WHEN r.rating = 2 THEN 1 END) as two_star,
    COUNT(CASE WHEN r.rating = 1 THEN 1 END) as one_star
    FROM teachers t
    LEFT JOIN ratings r ON t.user_id = r.rated_for AND r.role = 'teacher'
    WHERE t.user_id = ?
    GROUP BY t.user_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overview = $stmt->get_result()->fetch_assoc();

// Get detailed ratings with student information and course details
$sql = "SELECT DISTINCT r.id, r.rating, r.comment, r.created_at as rating_date,
    u.name as student_name, c.course_code, c.course_name
    FROM ratings r
    INNER JOIN users u ON r.rated_by = u.id
    INNER JOIN students s ON r.rated_by = s.user_id
    LEFT JOIN enrollments e ON s.student_id = e.student_id
    LEFT JOIN courses c ON e.course_code = c.course_code
    WHERE r.rated_for = ? AND r.role = 'teacher'
    ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$ratings = [];
while ($row = $result->fetch_assoc()) {
    $ratings[] = $row;
}

// Get teacher's name
$name_sql = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($name_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher_data = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Rating Dashboard</title>
    <style>
        :root {
            --primary-color: #3b82f6;
            --background: #f8fafc;
            --card-bg: #ffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.5;
            padding: 2rem 1rem;
        }

        .dashboard {
            max-width: 1000px;
            margin: 0 auto;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .teacher-name {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--text-secondary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-title {
            color: var(--text-secondary);
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .rating-distribution {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .rating-bar {
            display: flex;
            align-items: center;
            margin: 0.75rem 0;
        }

        .rating-label {
            min-width: 60px;
            font-weight: 500;
        }

        .progress-bar {
            flex-grow: 1;
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            margin: 0 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .rating-count {
            min-width: 50px;
            text-align: right;
            color: var(--text-secondary);
        }

        .ratings-list {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .rating-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .rating-item:last-child {
            border-bottom: none;
        }

        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .student-info {
            font-weight: 500;
        }

        .course-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .rating-score {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .rating-comment {
            margin-top: 0.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            color: var(--text-secondary);
        }

        .rating-date {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            text-align: right;
        }

        .back-link {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--primary-color);
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            background: var(--primary-color);
            color: white;
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <h1 class="teacher-name"><?php echo htmlspecialchars($teacher_data['name']); ?></h1>
            <p class="subtitle">Teaching Performance Overview</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h2 class="stat-title">Overall Rating</h2>
                <?php if ($overview && $overview['average_rating'] > 0): ?>
                    <div class="stat-value"><?php echo number_format($overview['average_rating'], 1); ?></div>
                    <div class="rating-emoji">
                        <?php
                        $avg_rating = round($overview['average_rating']);
                        echo match($avg_rating) {
                            1 => "😔",
                            2 => "😕",
                            3 => "😐",
                            4 => "😊",
                            5 => "🤩",
                            default => "😐"
                        };
                        ?>
                    </div>
                <?php else: ?>
                    <div class="stat-value">-</div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <h2 class="stat-title">Total Reviews</h2>
                <div class="stat-value"><?php echo $overview['total_ratings']; ?></div>
            </div>
        </div>

        <div class="rating-distribution">
            <h2 class="stat-title">Rating Distribution</h2>
            <?php
            $total = $overview['total_ratings'] ?: 1;
            $ratings_count = [
                5 => $overview['five_star'],
                4 => $overview['four_star'],
                3 => $overview['three_star'],
                2 => $overview['two_star'],
                1 => $overview['one_star']
            ];

            foreach ($ratings_count as $stars => $count):
                $percentage = ($count / $total) * 100;
            ?>
                <div class="rating-bar">
                    <div class="rating-label"><?php echo $stars; ?> ⭐</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <div class="rating-count"><?php echo $count; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ratings-list">
            <h2 class="stat-title">Recent Reviews</h2>
            <?php if (!empty($ratings)): ?>
                <?php foreach ($ratings as $rating): ?>
                    <div class="rating-item">
                        <div class="rating-header">
                            <div>
                                <div class="student-info"><?php echo htmlspecialchars($rating['student_name']); ?></div>
                                <?php if (isset($rating['course_code'])): ?>
                                    <div class="course-info">
                                        <?php echo htmlspecialchars($rating['course_code'] . ' - ' . $rating['course_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="rating-score">
                                <?php
                                echo match((int)$rating['rating']) {
                                    1 => "😔",
                                    2 => "😕",
                                    3 => "😐",
                                    4 => "😊",
                                    5 => "🤩",
                                    default => "😐"
                                };
                                ?> 
                                <?php echo $rating['rating']; ?>/5
                            </div>
                        </div>
                        <?php if (!empty($rating['comment'])): ?>
                            <div class="rating-comment">
                                "<?php echo htmlspecialchars($rating['comment']); ?>"
                            </div>
                        <?php endif; ?>
                        <div class="rating-date">
                            <?php echo date('F j, Y', strtotime($rating['rating_date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                    No ratings received yet.
                </p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>