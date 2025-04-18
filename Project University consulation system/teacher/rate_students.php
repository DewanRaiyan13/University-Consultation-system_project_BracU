<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

$sql = "SELECT DISTINCT s.student_id, s.user_id, s.name, c.course_code, c.course_name,
    (SELECT rating FROM ratings WHERE rated_by = ? AND rated_for = s.user_id AND role = 'student' LIMIT 1) as existing_rating,
    (SELECT comment FROM ratings WHERE rated_by = ? AND rated_for = s.user_id AND role = 'student' LIMIT 1) as existing_comment
    FROM students s 
    INNER JOIN enrollments e ON s.student_id = e.student_id 
    INNER JOIN courses c ON e.course_code = c.course_code 
    INNER JOIN teachers t ON e.teacher_id = t.teacher_id AND t.user_id = ?
    ORDER BY s.name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$stmt->execute();
$students = $stmt->get_result();



// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_user_id = $_POST['student_user_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    // Check if rating exists
    $check_sql = "SELECT id FROM ratings WHERE rated_by = ? AND rated_for = ? AND role = 'student'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $teacher_id, $student_user_id);
    $check_stmt->execute();
    $existing_rating = $check_stmt->get_result()->fetch_assoc();

    if ($existing_rating) {
        $sql = "UPDATE ratings SET rating = ?, comment = ? WHERE rated_by = ? AND rated_for = ? AND role = 'student'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isii", $rating, $comment, $teacher_id, $student_user_id);
    } else {
        $sql = "INSERT INTO ratings (rated_by, rated_for, rating, comment, role) VALUES (?, ?, ?, ?, 'student')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $teacher_id, $student_user_id, $rating, $comment);
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
    <title>Rate Students</title>
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

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            font-size: 1.875rem;
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
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .student-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        .student-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .course-info {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .emoji-rating {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .emoji-rating span {
            font-size: 2rem;
            cursor: pointer;
            opacity: 0.3;
            transition: opacity 0.2s ease;
        }

        .emoji-rating span.selected {
            opacity: 1;
        }

        .comment-box {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin: 1rem 0;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            transition: background-color 0.2s ease;
        }

        .submit-btn:hover {
            background-color: #2563eb;
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
            .emoji-rating span {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Rate Your Students</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($students->num_rows > 0): ?>
            <?php while ($student = $students->fetch_assoc()): ?>
                <div class="student-card">
                    <h2 class="student-name"><?php echo htmlspecialchars($student['name']); ?></h2>
                    <p class="course-info">
                        <?php echo htmlspecialchars($student['course_code'] . ' - ' . $student['course_name']); ?>
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="student_user_id" value="<?php echo $student['user_id']; ?>">
                        
                        <div class="emoji-rating">
                            <?php 
                            $emojis = ["😔", "😕", "😐", "😊", "🤩"];
                            for($i = 1; $i <= 5; $i++): 
                            ?>
                                <span data-rating="<?php echo $i; ?>" 
                                      class="<?php echo ($student['existing_rating'] == $i) ? 'selected' : ''; ?>">
                                    <?php echo $emojis[$i-1]; ?>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" value="<?php echo $student['existing_rating'] ?? ''; ?>" required>
                        
                        <textarea name="comment" 
                                  class="comment-box" 
                                  placeholder="Share your feedback about this student's performance..." 
                                  required><?php echo htmlspecialchars($student['existing_comment'] ?? ''); ?></textarea>
                        
                        <button type="submit" class="submit-btn">Submit Rating</button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="student-card">
                <p style="text-align: center; color: var(--text-secondary);">No students found in your courses.</p>
            </div>
        <?php endif; ?>

        <a href="./dashboard.php" class="back-link">Back to Dashboard</a>
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