
<!DOCTYPE html>
<html>
  <body>
    <h2>Login</h2>
    <form method="POST">
      Username: <input type="text" name="username" required><br>
      Password: <input type="password" name="password" required><br>
      <input type="submit" value="Login">
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["username"]) && isset($_POST["password"])) {
        $conn = new mysqli("db", "root", "root", "wow_db");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $username = $_POST["username"];
        $password = $_POST["password"];
        $hashed = hash("sha256", $password);

        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
        $stmt->bind_param("ss", $username, $hashed);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            echo "<p>✅ Login successful!</p>";
        } else {
            echo "<p>❌ Invalid credentials.</p>";
        }

        $stmt->close();
        $conn->close();
    }
    ?>
  </body>
</html>