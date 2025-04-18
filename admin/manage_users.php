<?php
require '../includes/session_handler.php';
require '../includes/db_connect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $role = $_POST['role'];

    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        if ($role === 'teacher') {
            $conn->query("INSERT INTO teachers (user_id, name) VALUES ($user_id, '$name')");
        } elseif ($role === 'student') {
            $conn->query("INSERT INTO students (student_id, user_id, name) VALUES ($user_id, $user_id, '$name')");
        }
        $success = "User added successfully.";
    } else {
        $error = "Failed to add user.";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $role = $_POST['role'];

    if (!empty($password)) {
        $sql = "UPDATE users SET name = ?, email = ?, password = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $email, $password, $role, $id);
    } else {
        $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }

    if ($stmt->execute()) {
        if ($role === 'teacher') {
            $conn->query("REPLACE INTO teachers (user_id, name) VALUES ($id, '$name')");
        } elseif ($role === 'student') {
            $conn->query("REPLACE INTO students (student_id, user_id, name) VALUES ($id, $id, '$name')");
        }
        $success = "User updated successfully.";
    } else {
        $error = "Failed to update user.";
    }
}


if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

 
    $conn->query("DELETE FROM ratings WHERE rated_by = $delete_id OR rated_for = $delete_id");

 
    $role = $conn->query("SELECT role FROM users WHERE id = $delete_id")->fetch_assoc()['role'];
    
    if ($role === 'teacher') {
        $conn->query("DELETE FROM teachers WHERE user_id = $delete_id");
    } elseif ($role === 'student') {
        $conn->query("DELETE FROM students WHERE user_id = $delete_id");
    }

    if ($conn->query("DELETE FROM users WHERE id = $delete_id")) {
        $success = "deleted successfully.";
    } else {
        $error = "Failed to delete user.";
    }
}


$result = $conn->query("SELECT id, name, email, password, role FROM users"); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="login-wrap">
        <div class="login-html">
            <h2 style="color:rgb(226, 235, 241)">Manage Users</h2>

            <a href="dashboard.php" class="button">Back to Dashboard</a>

            <?php if (isset($success)): ?>
                <p class="success-message"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

           
            <form action="" method="POST" class="login-form">
                <h3 style="color:rgb(226, 235, 241)">Add New User</h3>
                <div class="group">
                    <label for="name" class="label">Name:</label>
                    <input type="text" name="name" id="name" class="input" required>
                </div>
                <div class="group">
                    <label for="email" class="label">Email:</label>
                    <input type="email" name="email" id="email" class="input" required>
                </div>
                <div class="group">
                    <label for="password" class="label">Password:</label>
                    <input type="text" name="password" id="password" class="input" required>
                </div>
                <div class="group">
                    <label for="role" class="label">Role:</label>
                    <select name="role" id="role" class="input" required>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="group">
                    <button type="submit" name="add_user" class="button">Add User</button>
                </div>
            </form>

            <!-- User List Table -->
            <h3 style="color:rgb(226, 235, 241)">User List</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $result->fetch_assoc()): ?>
                        <tr>
                            <form action="" method="POST">
                                <td>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                    <?= htmlspecialchars($user['id']) ?>
                                </td>
                                <td>
                                    <input type="text" name="name" class="input" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </td>
                                <td>
                                    <input type="email" name="email" class="input" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </td>
                                <td>
                                    <input type="text" name="password" class="input" value="<?= htmlspecialchars($user['password']) ?>">
                                </td>
                                <td>
                                    <select name="role" class="input">
                                        <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="update_user" class="button">Update</button>
                                    <a href="?delete_id=<?= htmlspecialchars($user['id']) ?>" class="button" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>