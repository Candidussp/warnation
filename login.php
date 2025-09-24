<?php
// login.php
session_start();
require __DIR__ . '/api/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Both fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM players WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $player = $stmt->fetch();

        if ($player && password_verify($password, $player['password'])) {
            $_SESSION['player_id'] = $player['id'];
            $_SESSION['username'] = $player['username'];

            header("Location: homepage.php");
            exit;
        } else {
            $message = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - WARNATION</title>
    <style>
        body {
            background: darkblue;
            color: white;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }
        input, button {
            padding: 10px;
            margin: 5px;
            border-radius: 8px;
        }
        button {
            background: yellow;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Login to WARNATION</h1>
    <?php if (!empty($message)): ?>
        <p style="color: red;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Enter Username" required><br>
        <input type="password" id="password" name="password" placeholder="Enter Password" required>
        <br>
        <input type="checkbox" onclick="togglePassword()"> Show Password
        <br><br>
        <button type="submit">Login</button>
    </form>
    <p><a href="register.php" style="color:yellow;">Don’t have an account? Register here</a></p>

    <script>
        function togglePassword() {
            let pwField = document.getElementById("password");
            pwField.type = pwField.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>
