<?php
session_start();
include('includes/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // check user credentials
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Directly compare the plaintext password
        if ($password === $user['password']) { 
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: ' . $user['role'] . '/dashboard.php');
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No user found with that email!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<style>
form {
    width: 100%;
    max-width: 600px;
    margin: 20px auto;
    padding: 25px;
    background-color: rgb(0, 0, 0) ;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgb(0, 0, 0);
}

label {
    font-size: 16px;
    margin: 10px 0;
    display: block;
    color: #2c3e50;
}


</style>
<body>
    <h1 style="text-align: center; color: #035496;">University Consultation System</h1>
    <div class="login-wrap">
        <div class="login-html">
            <input type="radio" name="tab" class="sign-in" checked>
            <label for="tab-1" class="tab">Sign In</label>
            <!-- Removed Sign-Up Tab -->
            <div class="login-form">
                <!-- Sign In Form -->
                <form method="POST" action="index.php" class="sign-in-htm">
                    <div class="group">
                        <label for="email" class="label">Email</label>
                        <input id="email" name="email" type="email" class="input" required>
                    </div>
                    <div class="group">
                        <label for="password" class="label">Password</label>
                        <input id="password" name="password" type="password" class="input" required>
                    </div>
                    <div class="group">
                        <input id="check" type="checkbox" class="check" checked>
                        <label for="check"><span class="icon"></span> Keep me Signed in</label>
                    </div>
                    <div class="group">
                        <input type="submit" class="button" value="Sign In">
                    </div>
                    <div class="hr"></div>
                    <?php if (isset($error)) { echo "<p style='color: red; text-align: center;'>$error</p>"; } ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

