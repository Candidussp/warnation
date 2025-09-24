<?php
// register.php
session_start();
require __DIR__ . '/api/db.php'; // connect to database

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "Username and password are required.";
    } else {
        try {
            // hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // insert new player
            $stmt = $pdo->prepare("
                INSERT INTO players (username, password) 
                VALUES (:username, :password)
            ");
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashedPassword
            ]);

            // get new player id
            $playerId = $pdo->lastInsertId();

            // log player in
            $_SESSION['player_id'] = $playerId;
            $_SESSION['username'] = $username;

            // redirect to homepage
            header("Location: homepage.php");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // duplicate username
                $message = "That username is already taken.";
            } else {
                throw $e;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - WARNATION</title>
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
    <h1>Register to WARNATION</h1>
    <?php if (!empty($message)): ?>
        <p style="color: red;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Enter Username" required><br>
        <input type="password" id="password" name="password" placeholder="Enter Password" required>
        <br>
        <input type="checkbox" onclick="togglePassword()"> Show Password
        <br><br>
        <button type="submit">Create Account</button>
    </form>
    <p><a href="login.php" style="color:yellow;">Already have an account? Login here</a></p>

    <script>
        function togglePassword() {
            let pwField = document.getElementById("password");
            pwField.type = pwField.type === "password" ? "text" : "password";
        }
    </script>
</body>
</html>
