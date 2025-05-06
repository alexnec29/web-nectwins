<?php
session_start();

if (isset($_SESSION["username"])) {
    header("Location: index.php");
    exit();
}

$success = null;
$error = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = new mysqli("db", "root", "root", "wow_db");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $email = trim($_POST["email"]);

    if (empty($username) || empty($password) || empty($email)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists.";
        }
        elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match("/[a-z]/", $password)) {
            $error = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match("/[0-9]/", $password)) {
            $error = "Password must contain at least one number.";
        } 
        else {
            $hashed = hash("sha256", $password);
            $insert = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $hashed, $email);
            if ($insert->execute()) {
                $success = "✅ Registration successful! You can now <a href='login.php'>log in</a>.";
            } else {
                $error = "Something went wrong. Try again.";
            }
            $insert->close();
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Register</title>
        <link rel="stylesheet" href="css/styles.css">
        <link rel="stylesheet" href="css/register.css">
    </head>
    <body>
        <div class="register-card">
            <h2>Register</h2>

            <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
            <?php if ($success) echo "<p style='color:green;'>$success</p>"; ?>

            <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="submit" value="Register">
            </form>

            <p>Already have an account? <a href="login.php">Log in here</a>.</p>
        </div>
    </body>
</html>