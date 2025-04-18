<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'student') {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the student_id from the students table
$student_sql = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("i", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_data = $student_result->fetch_assoc();
$student_id = $student_data['student_id'];

// Get list of teachers the student can rate
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
    
    // Check if rating already exists
    $check_sql = "SELECT id FROM ratings WHERE rated_by = ? AND rated_for = ? AND role = 'teacher'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $teacher_user_id);
    $check_stmt->execute();
    $existing_rating = $check_stmt->get_result()->fetch_assoc();

    if ($existing_rating) {
        // Update existing rating
        $sql = "UPDATE ratings SET rating = ?, comment = ? WHERE rated_by = ? AND rated_for = ? AND role = 'teacher'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isii", $rating, $comment, $user_id, $teacher_user_id);
    } else {
        // Insert new rating
        $sql = "INSERT INTO ratings (rated_by, rated_for, rating, comment, role) VALUES (?, ?, ?, ?, 'teacher')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $user_id, $teacher_user_id, $rating, $comment);
    }
    
    if ($stmt->execute()) {
        $success = "Rating submitted successfully!";
        // Refresh the page to show updated ratings
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
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
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .teacher-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(48, 0, 0, 0.1);
        }
        .emoji-rating {
            font-size: 2em;
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 15px 0;
        }
        .emoji-rating span {
            cursor: pointer;
            opacity: 0.3;
            transition: opacity 0.3s;
        }
        .emoji-rating span.selected {
            opacity: 1;
        }
        .comment-box {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color:rgb(91, 145, 103);
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Rate Your Teachers</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="teacher-list">
            <?php if ($teachers->num_rows > 0): ?>
                <?php while ($teacher = $teachers->fetch_assoc()): ?>
                    <div class="teacher-card">
                        <h3><?php echo htmlspecialchars($teacher['name']); ?></h3>
                        <p class="course-info">
                            Course: <?php echo htmlspecialchars($teacher['course_code'] . ' - ' . $teacher['course_name']); ?>
                        </p>
                        
                        <form method="POST" class="rating-form">
                            <input type="hidden" name="teacher_id" value="<?php echo $teacher['user_id']; ?>">
                            
                            <div class="emoji-rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span data-rating="<?php echo $i; ?>" 
                                          class="<?php echo ($teacher['existing_rating'] == $i) ? 'selected' : ''; ?>">
                                        <?php echo match($i) {
                                            1 => "😢",
                                            2 => "😕",
                                            3 => "😐",
                                            4 => "😊",
                                            5 => "😄"
                                        }; ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" value="<?php echo $teacher['existing_rating'] ?? ''; ?>" required>
                            
                            <textarea name="comment" class="comment-box" 
                                      placeholder="Leave a comment about this teacher..." 
                                      required><?php echo htmlspecialchars($teacher['existing_comment'] ?? ''); ?></textarea>
                            
                            <button type="submit" class="btn">Submit Rating</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No teachers found in your courses.</p>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ratingForms = document.querySelectorAll('.rating-form');
            
            ratingForms.forEach(form => {
                const emojis = form.querySelectorAll('.emoji-rating span');
                const ratingInput = form.querySelector('input[name="rating"]');
                
                emojis.forEach(emoji => {
                    emoji.addEventListener('click', () => {
                        // Remove selected class from all emojis in this form
                        form.querySelectorAll('.emoji-rating span').forEach(e => e.classList.remove('selected'));
                        // Add selected class to clicked emoji
                        emoji.classList.add('selected');
                        // Update hidden input value
                        ratingInput.value = emoji.dataset.rating;
                    });
                });
            });
        });
    </script>
</body>
</html>