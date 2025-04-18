<?php
include('../includes/session_handler.php');
include('../includes/db_connect.php');

if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}


$courses = $conn->query("SELECT * FROM courses");
$students = $conn->query("SELECT * FROM students");
$teachers = $conn->query("SELECT * FROM teachers");


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_course'])) {
    $course_code = $_POST['course_code'];
    $section = $_POST['section'];
    $student_id = $_POST['student_id'];
    $teacher_id = $_POST['teacher_id'];
    $class_time = $_POST['class_time']; // Added class_time


    $student_check = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
    $student_check->bind_param("i", $student_id);
    $student_check->execute();
    $student_check->bind_result($exists);
    $student_check->fetch();
    $student_check->close();

    if ($exists == 0) {
   
        echo "The selected student does not exist!";
    } else {
      
        $sql = "INSERT INTO enrollments (course_code, section, student_id, teacher_id, class_time, role) VALUES (?, ?, ?, ?, ?, 'student')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $course_code, $section, $student_id, $teacher_id, $class_time); // Added class_time
        $stmt->execute();
        $stmt->close();

        echo "Course assigned successfully!";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_assignment'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $course_code = $_POST['course_code'];
    $section = $_POST['section'];
    $student_id = $_POST['student_id'];
    $teacher_id = $_POST['teacher_id'];
    $class_time = $_POST['class_time']; // Added class_time

  
    $student_check = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_id = ?");
    $student_check->bind_param("i", $student_id);
    $student_check->execute();
    $student_check->bind_result($exists);
    $student_check->fetch();
    $student_check->close();

    if ($exists == 0) {
       
        echo "The selected student does not exist!";
    } else {
        $sql = "UPDATE enrollments SET course_code = ?, section = ?, student_id = ?, teacher_id = ?, class_time = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $course_code, $section, $student_id, $teacher_id, $class_time, $enrollment_id); // Added class_time
        $stmt->execute();
        $stmt->close();

        echo "Assignment updated successfully!";
    }
}


if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Delete the enrollment record
    $sql = "DELETE FROM enrollments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    echo "Assignment deleted successfully!";
}

// Fetch all enrollments (current assignments)
$enrollments = $conn->query("SELECT e.id, e.course_code, e.section, e.student_id, e.teacher_id, e.class_time, s.name AS student_name, t.name AS teacher_name 
                            FROM enrollments e 
                            JOIN students s ON e.student_id = s.student_id 
                            JOIN teachers t ON e.teacher_id = t.teacher_id");

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Courses</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
      
        .content-container {
            max-width: 800px;
            margin: auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        table th {
            background: rgba(17, 97, 238, 0.8);
        }

        table td {
            background: rgba(0, 0, 0, 0.84);
        }

        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }

            .group label, .group input, .group select, .group button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <div class="content-container">
                <h1 style="color: white; text-align: center; margin-bottom: 20px;">Assign Students and Teachers to Courses</h1>

                <!-- Back to Dashboard -->
                <nav class="foot-lnk">
                    <a href="dashboard.php" style="color: #fff; text-decoration: underline;">Back to Dashboard</a>
                </nav>

                <!-- Form for Assigning New Courses -->
                <form method="POST" action="assign_courses.php" style="margin-top: 20px;">
                    <div class="group">
                        <label for="course_code" class="label">Course</label>
                        <select name="course_code" id="course_code" class="input" required>
                            <?php while ($row = $courses->fetch_assoc()) { ?>
                                <option value="<?= $row['course_code'] ?>">
                                    <?= $row['course_name'] ?> - <?= $row['course_code'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="group">
                        <label for="section" class="label">Section</label>
                        <input type="text" name="section" id="section" class="input" required>
                    </div>

                    <div class="group">
                        <label for="student_id" class="label">Student</label>
                        <select name="student_id" id="student_id" class="input" required>
                            <?php while ($row = $students->fetch_assoc()) { ?>
                                <option value="<?= $row['student_id'] ?>">
                                    <?= $row['name'] ?> - <?= $row['student_id'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="group">
                        <label for="teacher_id" class="label">Teacher</label>
                        <select name="teacher_id" id="teacher_id" class="input" required>
                            <?php while ($row = $teachers->fetch_assoc()) { ?>
                                <option value="<?= $row['teacher_id'] ?>">
                                    <?= $row['name'] ?> - <?= $row['teacher_id'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="group">
                        <label for="class_time" class="label">Class Time</label>
                        <select name="class_time" id="class_time" class="input" required>
                            <?php
                            $courses->data_seek(0);
                            while ($row = $courses->fetch_assoc()) {
                                echo "<option value='{$row['class_time']}'>{$row['class_time']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="group">
                        <button type="submit" name="assign_course" class="button">Assign</button>
                    </div>
                </form>

                <!-- Display Current Assignments -->
                <h2 style="color: white; text-align: center; margin-top: 30px;">Current Assigned and Enrolled Courses</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Section</th>
                            <th>Student Name</th>
                            <th>Teacher Name</th>
                            <th>Class Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $enrollments->fetch_assoc()) { ?>
                            <tr>
                                <td><?= $row['course_code'] ?></td>
                                <td><?= $row['section'] ?></td>
                                <td><?= $row['student_name'] ?></td>
                                <td><?= $row['teacher_name'] ?></td>
                                <td><?= $row['class_time'] ?></td>
                                <td>
                                    <a href="?delete_id=<?= $row['id'] ?>" style="color: red; text-decoration: underline;" onclick="return confirm('Are you sure you want to delete this assignment?')">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

