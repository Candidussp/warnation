<?php
require __DIR__ . '/api/db.php';
session_start();

// Test credentials
$username = "TestPlayer";
$password = "MySecret123";

// Step 1: Delete previous test account (if exists)
$pdo->prepare("DELETE FROM players WHERE username = ?")->execute([$username]);

// Step 2: Register new account
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$defaultArmy = json_encode([
    "infantry" => 50,
    "archers" => 20,
    "tanks" => 5
]);

$pdo->prepare("INSERT INTO players (username, password, army) VALUES (?, ?, ?)")
    ->execute([$username, $hashedPassword, $defaultArmy]);

echo "<h2>Registration completed.</h2>";
echo "Username: $username<br>";
echo "Password (plain): $password<br>";
echo "Password (hash stored in DB): $hashedPassword<br>";

// Step 3: Attempt login
$stmt = $pdo->prepare("SELECT * FROM players WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Login attempt</h2>";

if ($user) {
    echo "<pre>";
    print_r($user);
    echo "</pre>";

    if (password_verify($password, $user['password'])) {
        echo "<p style='color:green;'>Password matches! Login successful.</p>";
        // Set session
        unset($user['password']);
        $_SESSION['player'] = $user;
    } else {
        echo "<p style='color:red;'>Password does NOT match!</p>";
    }
} else {
    echo "<p style='color:red;'>Username not found in DB!</p>";
}

?>
