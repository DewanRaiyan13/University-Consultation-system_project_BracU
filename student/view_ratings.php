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
            WHERE s.user_id = ?
            GROUP BY s.student_id";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overview = $stmt->get_result()->fetch_assoc();

// Get detailed ratings with comments
if ($role == 'teacher') {
    $sql = "SELECT r.*, u.name as rater_name, r.created_at as rating_date
            FROM ratings r
            INNER JOIN users u ON r.rated_by = u.id
            INNER JOIN students s ON r.rated_by = s.user_id
            WHERE r.rated_for = ? AND r.role = 'teacher'
            ORDER BY r.created_at DESC";
} else {
    $sql = "SELECT r.*, u.name as rater_name, r.created_at as rating_date
            FROM ratings r
            INNER JOIN users u ON r.rated_by = u.id
            INNER JOIN teachers t ON r.rated_by = t.user_id
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

// Debug information
$debug = false; // Set to true to see debug info
if ($debug) {
    echo "User ID: " . $user_id . "<br>";
    echo "Role: " . $role . "<br>";
    echo "SQL Error (if any): " . $conn->error . "<br>";
    
    // Print overview data
    echo "<pre>Overview: ";
    print_r($overview);
    echo "</pre>";
    
    // Print ratings data
    echo "<pre>Ratings: ";
    while ($row = $ratings->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Reset ratings result pointer
    $ratings->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating Dashboard</title>
    <style>
        :root {
            --primary-color: #3b82f6;
            --background: #f8fafc;
            --card-bg: #ffffff;
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

        .dashboard-title {
            text-align: center;
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2.5rem;
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

        .rating-emoji {
            font-size: 3rem;
            margin: 0.5rem 0;
        }

        .ratings-list {
            background: var(--card-bg);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .rating-item {
            padding: 1rem 0;
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

        .rating-score {
            font-weight: 600;
            color: var(--primary-color);
        }

        .rating-date {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .rating-comment {
            color: var(--text-secondary);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
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
        <h1 class="dashboard-title"><?php echo htmlspecialchars($user_data['name']); ?>'s Rating Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h2 class="stat-title">Overall Rating</h2>
                <?php if ($overview && $overview['average_rating'] > 0): ?>
                    <div class="stat-value"><?php echo number_format($overview['average_rating'], 1); ?></div>
                    <div class="rating-emoji">
                        <?php
                        $avg_rating = round($overview['average_rating']);
                        echo match($avg_rating) {
                            1.0 => "😔",
                            2.0 => "😕",
                            3.0 => "😐",
                            4.0 => "😊",
                            5.0 => "🤩",
                            default => "😐"
                        };
                        ?>
                    </div>
                <?php else: ?>
                    <div class="stat-value">-</div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <h2 class="stat-title">Total Ratings</h2>
                <div class="stat-value">
                    <?php echo $overview ? $overview['total_ratings'] : '0'; ?>
                </div>
            </div>
        </div>

        <div class="ratings-list">
            <?php if (!empty($ratings)): ?>
                <?php foreach ($ratings as $rating): ?>
                    <div class="rating-item">
                        <div class="rating-header">
                            <div class="rating-score">
                                <?php
                                echo match($rating['rating']) {
                                    1 => "😔",
                                    2 => "😕",
                                    3 => "😐",
                                    4 => "😊",
                                    5 => "🤩",
                                    default => "😐"
                                };
                                ?> 
                                <?php echo htmlspecialchars($rating['rating']); ?>/5
                            </div>
                            <div class="rating-date">
                                <?php 
                                if (isset($rating['created_at']) && !empty($rating['created_at'])) {
                                    echo date('M d, Y', strtotime($rating['created_at']));
                                } else {
                                    echo 'Date not available';
                                }
                                ?>
                            </div>
                        </div>
                        <?php if (isset($rating['comment']) && !empty($rating['comment'])): ?>
                            <div class="rating-comment">
                                <?php echo htmlspecialchars($rating['comment']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-secondary);">No ratings yet</p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="back-link">Back to Home</a>
    </div>
</body>
</html>