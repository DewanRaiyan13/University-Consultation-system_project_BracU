<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'teacher') {
    header('Location: ../index.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Modified query to include user_id from students table
$sql = "SELECT DISTINCT s.student_id, s.user_id, s.name, c.course_code, c.course_name,
    (SELECT rating FROM ratings WHERE rated_by = ? AND rated_for = s.user_id AND role = 'student') as existing_rating,
    (SELECT comment FROM ratings WHERE rated_by = ? AND rated_for = s.user_id AND role = 'student') as existing_comment
    FROM students s 
    INNER JOIN enrollments e ON s.student_id = e.student_id 
    INNER JOIN courses c ON e.course_code = c.course_code 
    WHERE e.teacher_id = (SELECT teacher_id FROM teachers WHERE user_id = ?)
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
    
    // Check if rating already exists
    $check_sql = "SELECT id FROM ratings WHERE rated_by = ? AND rated_for = ? AND role = 'student'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $teacher_id, $student_user_id);
    $check_stmt->execute();
    $existing_rating = $check_stmt->get_result()->fetch_assoc();

    if ($existing_rating) {
        // Update existing rating
        $sql = "UPDATE ratings SET rating = ?, comment = ? WHERE rated_by = ? AND rated_for = ? AND role = 'student'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isii", $rating, $comment, $teacher_id, $student_user_id);
    } else {
        // Insert new rating
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
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .student-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Rate Your Students</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="student-list">
            <?php if ($students->num_rows > 0): ?>
                <?php while ($student = $students->fetch_assoc()): ?>
                    <div class="student-card">
                        <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                        <p class="course-info">
                            Course: <?php echo htmlspecialchars($student['course_code'] . ' - ' . $student['course_name']); ?>
                        </p>
                        
                        <form method="POST" class="rating-form">
                            <input type="hidden" name="student_user_id" value="<?php echo $student['user_id']; ?>">
                            
                            <div class="emoji-rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span data-rating="<?php echo $i; ?>" 
                                          class="<?php echo ($student['existing_rating'] == $i) ? 'selected' : ''; ?>">
                                        <?php echo match($i) {
                                            1 => "😢",
                                            2 => "😕",
                                            3 => "😐",
                                            4 => "😊",
                                            5 => "😍"
                                        }; ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" value="<?php echo $student['existing_rating'] ?? ''; ?>" required>
                            
                            <textarea name="comment" class="comment-box" 
                                      placeholder="Leave a comment about this student's performance..." 
                                      required><?php echo htmlspecialchars($student['existing_comment'] ?? ''); ?></textarea>
                            
                            <button type="submit" class="btn">Submit Rating</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No students found in your courses.</p>
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