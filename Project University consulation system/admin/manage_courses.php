<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $section = $_POST['section'];
    $class_time = $_POST['class_time'];  

   
    $sql = "INSERT INTO courses (course_code, course_name, section, class_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $course_code, $course_name, $section, $class_time);  
    $stmt->execute();
    $stmt->close();

    
    $enrollment_sql = "INSERT INTO enrollments (course_code, section, class_time) 
                       SELECT ?, ?, ? FROM students";  
    $enrollment_stmt = $conn->prepare($enrollment_sql);
    $enrollment_stmt->bind_param("sss", $course_code, $section, $class_time); 
    $enrollment_stmt->execute();
    $enrollment_stmt->close();

    echo "Course added successfully!";
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $section = $_POST['section'];
    $class_time = $_POST['class_time'];  // Added class_time

    
    $sql = "UPDATE courses SET course_name = ?, section = ?, class_time = ? WHERE course_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $course_name, $section, $class_time, $course_code);  // Added class_time
    $stmt->execute();
    $stmt->close();

    
    $update_enrollment_sql = "UPDATE enrollments SET class_time = ? WHERE course_code = ?";
    $update_enrollment_stmt = $conn->prepare($update_enrollment_sql);
    $update_enrollment_stmt->bind_param("ss", $class_time, $course_code);
    $update_enrollment_stmt->execute();
    $update_enrollment_stmt->close();

    echo "Course updated successfully!";
}


if (isset($_GET['delete'])) {
    $course_code = $_GET['delete'];
    $sql = "DELETE FROM courses WHERE course_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $course_code);
    $stmt->execute();
    $stmt->close();

    
    $delete_enrollment_sql = "DELETE FROM enrollments WHERE course_code = ?";
    $delete_enrollment_stmt = $conn->prepare($delete_enrollment_sql);
    $delete_enrollment_stmt->bind_param("s", $course_code);
    $delete_enrollment_stmt->execute();
    $delete_enrollment_stmt->close();

    header('Location: manage_courses.php');
    exit;
}


$result = $conn->query("SELECT * FROM courses");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title >Manage Courses</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <h1 style = "color:rgb(226, 235, 241)">Manage Courses</h1>

           
            <a href="dashboard.php" class="button">Back to Dashboard</a>

    
            <form method="POST" action="manage_courses.php" class="login-form">
                <h2 style = "color:rgb(226, 235, 241)">Add New Course</h2>
                <div class="group">
                    <label for="course_code" class="label">Course Code:</label>
                    <input type="text" id="course_code" name="course_code" class="input" required>
                </div>
                <div class="group">
                    <label for="course_name" class="label">Course Name:</label>
                    <input type="text" id="course_name" name="course_name" class="input" required>
                </div>
                <div class="group">
                    <label for="section" class="label">Section:</label>
                    <input type="text" id="section" name="section" class="input" required>
                </div>
                <div class="group">
                    <label for="class_time" class="label">Class Time:</label>
                    <input type="text" id="class_time" name="class_time" class="input" required placeholder="e.g. 9:00 AM - 10:50 AM, SUNDAY-TUESDAY">
                </div>
                <div class="group">
                    <button type="submit" name="add_course" class="button">Add Course</button>
                </div>
            </form>

            <h2 style = "color:rgb(226, 235, 241)">Existing Courses</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Class Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        
                        <form method="POST" action="manage_courses.php">
                            <td>
                                <input type="text" name="course_code" class="input" value="<?= $row['course_code'] ?>" readonly>
                            </td>
                            <td>
                                <input type="text" name="course_name" class="input" value="<?= $row['course_name'] ?>" required>
                            </td>
                            <td>
                                <input type="text" name="section" class="input" value="<?= $row['section'] ?>" required>
                            </td>
                            <td>
                                <input type="text" name="class_time" class="input" value="<?= $row['class_time'] ?>" required>
                            </td>
                            <td>
                                <button type="submit" name="update_course" class="button">Update</button>
                                <a href="manage_courses.php?delete=<?= $row['course_code'] ?>" onclick="return confirm('Are you sure you want to delete this course?');" class="button">Delete</a>
                            </td>
                        </form>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
