<?php
// Include session check for login + back-button redirect
require __DIR__ . '/api/session_check.php';

$message = "";

// Change name (costs 200 gold)
if (isset($_POST['change_name'])) {
    $newName = trim($_POST['new_name']);
    if (!empty($newName)) {
        if ($player['gold'] >= 200) {
            $stmt = $pdo->prepare("UPDATE players SET username = :username, gold = gold - 200 WHERE id = :id");
            $stmt->execute([
                ':username' => $newName,
                ':id' => $player['id']
            ]);
            $player['username'] = $newName;
            $player['gold'] -= 200;
            $message = "Username changed successfully!";
        } else {
            $message = "Not enough gold to change name.";
        }
    } else {
        $message = "New name cannot be empty.";
    }
}

// Change password
if (isset($_POST['change_password'])) {
    $oldPass = $_POST['old_password'];
    $newPass = $_POST['new_password'];

    if (password_verify($oldPass, $player['password'])) {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE players SET password = :password WHERE id = :id");
        $stmt->execute([':password' => $hashed, ':id' => $player['id']]);
        $message = "Password changed successfully!";
    } else {
        $message = "Old password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Settings - WARNATION</title>
    <style>
        body {
            background: darkblue;
            color: white;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }
        input, button {
            padding: 10px;
            margin: 5px;
            border-radius: 8px;
        }
        button {
            background: yellow;
            font-weight: bold;
            cursor: pointer;
        }
        a {
            color: yellow;
            font-weight: bold;
            text-decoration: none;
        }
        .box {
            border: 1px solid white;
            padding: 20px;
            margin: 20px auto;
            width: 300px;
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <p><a href="homepage.php">&larr; Back to Homepage</a></p>

    <h1>Settings</h1>
    <p><strong>Current Gold: <?= htmlspecialchars($player['gold']) ?></strong></p>

    <?php if (!empty($message)): ?>
        <p><strong><?= htmlspecialchars($message) ?></strong></p>
    <?php endif; ?>

    <div class="box">
        <h2>Change Username (Cost: 200 Gold)</h2>
        <form method="POST">
            <input type="text" name="new_name" placeholder="Enter new username" required><br>
            <button type="submit" name="change_name">Change Username</button>
        </form>
    </div>

    <div class="box">
        <h2>Change Password</h2>
        <form method="POST">
            <input type="password" name="old_password" placeholder="Old Password" required><br>
            <input type="password" name="new_password" id="settings-password" placeholder="New Password" required><br>
            <button type="submit" name="change_password">Change Password</button>
        </form>
        <script>
        const settingsPwd = document.getElementById('settings-password');
        let settingsPwdTimeout;
        settingsPwd.addEventListener('input', function() {
            clearTimeout(settingsPwdTimeout);
            settingsPwd.type = 'text';
            settingsPwdTimeout = setTimeout(() => {
                settingsPwd.type = 'password';
            }, 1000);
        });
        </script>
    </div>

    <div class="box">
        <h2>Logout</h2>
        <a href="logout.php">Click here to logout</a>
    </div>
</body>
</html>