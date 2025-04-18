<?php
require '../includes/session_handler.php';
require '../includes/db_connect.php';


if ($_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}


$teachers = $conn->query("SELECT id, name FROM users WHERE role = 'teacher'");
$consultation_hours = $conn->query("
    SELECT ch.id, ch.teacher_id, ch.day, ch.time_from, ch.time_to, u.name AS teacher_name 
    FROM consultation_hours ch 
    JOIN users u ON ch.teacher_id = u.id 
    WHERE u.role = 'teacher'
");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'];
    $day = $_POST['day'];
    $time_from = $_POST['time_from'];
    $time_to = $_POST['time_to'];

    if (isset($_POST['add_consultation'])) {
        $stmt = $conn->prepare("INSERT INTO consultation_hours (teacher_id, day, time_from, time_to) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $teacher_id, $day, $time_from, $time_to);
        $stmt->execute();
        $stmt->close();
        $message = "Consultation hour added successfully!";
        // Redirect to refresh the page and display new data
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['update_consultation'])) {
        $consultation_id = $_POST['consultation_id'];
        $stmt = $conn->prepare("UPDATE consultation_hours SET teacher_id = ?, day = ?, time_from = ?, time_to = ? WHERE id = ?");
        $stmt->bind_param("isssi", $teacher_id, $day, $time_from, $time_to, $consultation_id);
        $stmt->execute();
        $stmt->close();
        $message = "Consultation hour updated successfully!";
        // Redirect to refresh the page and display updated data
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM consultation_hours WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_id']);
    $stmt->execute();
    $stmt->close();
    $message = "Consultation hour deleted successfully!";
    // Redirect to refresh the page after deletion
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Schedule</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            background: url('images/New_campus2.jpg') no-repeat center center fixed; 
            background-size: cover;
            color: #333;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 20px; 
        }
        table, th, td { 
            border: 1px solid #ddd; 
            text-align: center; 
        }
        th, td { 
            padding: 10px; 
        }
        form { 
            margin-top: 20px; 
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        select, input, button { 
            padding: 10px; 
            margin: 5px 0; 
            border-radius: 5px; 
            width: 100%; 
            max-width: 300px;
        }
        button { 
            background-color: #007bff; 
            color: white; 
            border: none; 
        }
        button:hover { 
            background-color: #0056b3; 
        }
        a { 
            color: #007bff; 
            text-decoration: none;
            font-size: 18px;
        }
        a:hover { 
            text-decoration: underline; 
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            form {
                width: 100%;
            }
            table {
                font-size: 14px;
            }
            select, input, button {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
   
    <a href="dashboard.php">&larr; Back to Dashboard</a>

    <h1 style = "color:rgb(241, 241, 241)">Consultation Schedule</h1>

  
    <?php if (isset($message)) echo "<p style='color: green;'>$message</p>"; ?>

   
    <form method="POST">
        <h3>Add Consultation Hour</h3>
        <label>Teacher:</label>
        <select name="teacher_id" required>
            <option value="">Select Teacher</option>
            <?php while ($teacher = $teachers->fetch_assoc()) { ?>
                <option value="<?= $teacher['id'] ?>"><?= $teacher['name'] ?></option>
            <?php } ?>
        </select>
        <label>Day:</label>
        <select name="day" required>
            <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) { ?>
                <option value="<?= $day ?>"><?= $day ?></option>
            <?php } ?>
        </select>
        <label>From:</label>
        <input type="time" name="time_from" required>
        <label>To:</label>
        <input type="time" name="time_to" required>
        <button type="submit" name="add_consultation">Add</button>
    </form>

    
    <h1 style = "color:rgb(236, 238, 240)">Existing Consultation Hours</h1>
    <table>
        <thead>
            <tr style = "color:rgb(18, 24, 29)">
                <th>Teacher</th>
                <th>Day</th>
                <th>From</th>
                <th>To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $consultation_hours->fetch_assoc()) { ?>
                <tr>
                    <form method="POST">
                        <td>
                            <select name="teacher_id" required>
                                <?php 
                                $teachers->data_seek(0); // Reset teacher pointer
                                while ($teacher = $teachers->fetch_assoc()) {
                                    $selected = ($teacher['id'] == $row['teacher_id']) ? 'selected' : '';
                                    echo "<option value='{$teacher['id']}' $selected>{$teacher['name']}</option>";
                                } ?>
                            </select>
                        </td>
                        <td>
                            <select name="day" required>
                                <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) { ?>
                                    <option value="<?= $day ?>" <?= ($row['day'] == $day) ? 'selected' : '' ?>><?= $day ?></option>
                                <?php } ?>
                            </select>
                        </td>
                        <td><input type="time" name="time_from" value="<?= $row['time_from'] ?>" required></td>
                        <td><input type="time" name="time_to" value="<?= $row['time_to'] ?>" required></td>
                        <td>
                            <input type="hidden" name="consultation_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="update_consultation">Update</button>
                            <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this consultation hour?')">Delete</a>
                        </td>
                    </form>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>