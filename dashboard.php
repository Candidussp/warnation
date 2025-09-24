<?php
// TEMPORARY DEBUG: enable detailed errors (remove in production)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Ensure session is started before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'api/db.php';

// Simple authentication guard (adjust session key to your app)
if (empty($_SESSION['user_id']) && empty($_SESSION['username'])) {
    // not logged in — redirect to login
    header('Location: login.php');
    exit;
}

// Load player data
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch();

// Decode JSON army
$player['army'] = $player['army'] ? json_decode($player['army'], true) : [];
?>

<h1>Welcome, <?php echo htmlspecialchars($player['username']); ?></h1>
<p>Health: <?php echo $player['health']; ?></p>
<p>Cash: <?php echo $player['cash']; ?></p>

<h2>Army:</h2>
<ul>
<?php foreach ($player['army'] as $unit => $count): ?>
    <li><?php echo htmlspecialchars($unit) . ": " . htmlspecialchars($count); ?></li>
<?php endforeach; ?>
</ul>
