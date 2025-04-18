<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'student') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get student_id
$student_sql = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_data = $student_stmt->get_result()->fetch_assoc();
$student_id = $student_data['student_id'];

// Get teachers list with existing ratings
$sql = "SELECT DISTINCT 
    t.user_id, 
    t.name, 
    c.course_code, 
    c.course_name,
    (SELECT rating FROM ratings WHERE rated_by = ? AND rated_for = t.user_id AND role = 'teacher') as existing_rating,
    (SELECT comment FROM ratings WHERE rated_by = ? AND rated_for = t.user_id AND role = 'teacher') as existing_comment
    FROM teachers t 
    INNER JOIN enrollments e ON t.teacher_id = e.teacher_id 
    INNER JOIN courses c ON e.course_code = c.course_code
    WHERE e.student_id = ?
    ORDER BY t.name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $user_id, $user_id, $student_id);
$stmt->execute();
$teachers = $stmt->get_result();

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacher_user_id = $_POST['teacher_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    $check_sql = "SELECT id FROM ratings WHERE rated_by = ? AND rated_for = ? AND role = 'teacher'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $teacher_user_id);
    $check_stmt->execute();
    $existing_rating = $check_stmt->get_result()->fetch_assoc();

    if ($existing_rating) {
        $sql = "UPDATE ratings SET rating = ?, comment = ? WHERE rated_by = ? AND rated_for = ? AND role = 'teacher'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isii", $rating, $comment, $user_id, $teacher_user_id);
    } else {
        $sql = "INSERT INTO ratings (rated_by, rated_for, rating, comment, role) VALUES (?, ?, ?, ?, 'teacher')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $user_id, $teacher_user_id, $rating, $comment);
    }
    
    if ($stmt->execute()) {
        $success = "Rating submitted successfully!";
    } else {
        $error = "Error submitting rating: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Teachers</title>
    <style>
        :root {
            --primary-color: #3b82f6;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --success-bg: #dcfce7;
            --success-text: #166534;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
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

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }

        .alert-danger {
            background-color: var(--error-bg);
            color: var(--error-text);
        }

        .teacher-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease;
        }

        .teacher-card:hover {
            transform: translateY(-2px);
        }

        .teacher-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .course-info {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .emoji-rating {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .emoji-rating span {
            font-size: 2.5rem;
            cursor: pointer;
            opacity: 0.3;
            transition: all 0.2s ease;
            transform-origin: center;
        }

        .emoji-rating span:hover {
            transform: scale(1.1);
        }

        .emoji-rating span.selected {
            opacity: 1;
            transform: scale(1.2);
        }

        .comment-box {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            margin: 1rem 0;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            transition: border-color 0.2s ease;
        }

        .comment-box:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.2s ease;
        }

        .submit-btn:hover {
            background-color: #2563eb;
        }

        .back-link {
            display: inline-block;
            text-align: center;
            margin-top: 2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--primary-color);
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .no-teachers {
            text-align: center;
            color: var(--text-secondary);
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        @media (max-width: 640px) {
            .emoji-rating span {
                font-size: 2rem;
            }

            .teacher-card {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Rate Your Teachers</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($teachers->num_rows > 0): ?>
            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                <div class="teacher-card">
                    <h2 class="teacher-name"><?php echo htmlspecialchars($teacher['name']); ?></h2>
                    <p class="course-info">
                        <?php echo htmlspecialchars($teacher['course_code'] . ' - ' . $teacher['course_name']); ?>
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['user_id']; ?>">
                        
                        <div class="emoji-rating">
                            <?php 
                            $emojis = ["😢", "😕", "😐", "😊", "🤩"];
                            for($i = 1; $i <= 5; $i++): 
                            ?>
                                <span data-rating="<?php echo $i; ?>" 
                                      class="<?php echo ($teacher['existing_rating'] == $i) ? 'selected' : ''; ?>">
                                    <?php echo $emojis[$i-1]; ?>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" value="<?php echo $teacher['existing_rating'] ?? ''; ?>" required>
                        
                        <textarea name="comment" 
                                  class="comment-box" 
                                  placeholder="Share your thoughts about this teacher..." 
                                  required><?php echo htmlspecialchars($teacher['existing_comment'] ?? ''); ?></textarea>
                        
                        <button type="submit" class="submit-btn">Submit Rating</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-teachers">
                <p>No teachers found in your courses.</p>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="back-link">Back to Dashboard</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                const emojis = form.querySelectorAll('.emoji-rating span');
                const ratingInput = form.querySelector('input[name="rating"]');
                
                emojis.forEach(emoji => {
                    emoji.addEventListener('click', () => {
                        emojis.forEach(e => e.classList.remove('selected'));
                        emoji.classList.add('selected');
                        ratingInput.value = emoji.dataset.rating;
                    });
                });
            });
        });
    </script>
</body>
</html>