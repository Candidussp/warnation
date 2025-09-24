<?php
session_start();
require __DIR__ . '/api/db.php';

// --- Check if player is logged in ---
if (empty($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

$playerId = (int) $_SESSION['player_id'];

// --- Load player from DB ---
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// --- Calculate max stats if missing ---
$level = isset($player['level']) ? (int)$player['level'] : 1;
$calcMaxHealth = 300 + (($level - 1) * 5);
$calcMaxEnergy = 500 + (($level - 1) * 5);
$calcMaxAmmo   = 20 + (($level - 1) * 1);

if (empty($player['maxHealth']) || $player['maxHealth'] < $calcMaxHealth) $player['maxHealth'] = $calcMaxHealth;
if (empty($player['maxEnergy']) || $player['maxEnergy'] < $calcMaxEnergy) $player['maxEnergy'] = $calcMaxEnergy;
if (empty($player['maxAmmo'])   || $player['maxAmmo']   < $calcMaxAmmo)   $player['maxAmmo']   = $calcMaxAmmo;

// --- Regen Logic ---
$now = time();
$lastRegen = strtotime($player['last_regen'] ?? date('Y-m-d H:i:s'));
$elapsed = $now - $lastRegen;
$interval = 180; // 3 minutes

if ($elapsed >= $interval) {
    $ticks = floor($elapsed / $interval);

    // Regen rates per tick
    $regenHealth = 10 * $ticks;
    $regenEnergy = 10 * $ticks;
    $regenAmmo   = 1 * $ticks;

    // Apply regen, capped at max
    $player['health'] = min($player['health'] + $regenHealth, $player['maxHealth']);
    $player['energy'] = min($player['energy'] + $regenEnergy, $player['maxEnergy']);
    $player['ammo']   = min($player['ammo']   + $regenAmmo,   $player['maxAmmo']);

    // Save updated stats + reset last_regen
    $stmt = $pdo->prepare("UPDATE players SET health=?, energy=?, ammo=?, last_regen=NOW() WHERE id=?");
    $stmt->execute([$player['health'], $player['energy'], $player['ammo'], $playerId]);
}

// --- Regen countdown for next tick ---
$elapsed = $now - strtotime($player['last_regen'] ?? date('Y-m-d H:i:s'));
$remaining = $interval - ($elapsed % $interval);
if ($remaining == $interval) $remaining = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>🏠 WARNATION - Homepage</title>
    <style>
        body {
            background-color: darkblue;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        h1 { text-align: center; color: gold; }
        .stat-box { border: 2px solid white; padding: 10px; margin: 10px 0; }
        .stat { margin: 5px 0; }
        .menu a { display: block; margin: 10px 0; color: skyblue; text-decoration: none; font-weight: bold; }
        .critical { color: red; font-weight: bold; }
        .timer { color: yellow; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<h1>⚔️ WARNATION</h1>

<div class="stat-box" id="statsBox">
    <div class="stat">❤️ Health: <span id="health"><?= htmlspecialchars($player['health']) ?></span> / <span id="maxHealth"><?= htmlspecialchars($player['maxHealth']) ?></span></div>
    <div class="stat">⚡ Energy: <span id="energy"><?= htmlspecialchars($player['energy']) ?></span> / <span id="maxEnergy"><?= htmlspecialchars($player['maxEnergy']) ?></span></div>
    <div class="stat">🔫 Ammo: <span id="ammo"><?= htmlspecialchars($player['ammo']) ?></span> / <span id="maxAmmo"><?= htmlspecialchars($player['maxAmmo']) ?></span></div>
    <div class="stat">💰 Cash: $<span id="cash"><?= htmlspecialchars($player['cash']) ?></span></div>
    <div class="stat">🪙 Gold: <span id="gold"><?= htmlspecialchars($player['gold']) ?></span></div>
    <div class="stat">🎖️ Level: <span id="level"><?= htmlspecialchars($player['level']) ?></span></div>
    <div class="stat">⭐ XP: <span id="xp"><?= htmlspecialchars($player['xp']) ?></span>/<span id="xpNeeded"><?php
        $xpNeeded = floor(1000 * pow($player['level'], 2.3));
        echo $xpNeeded;
    ?></span><strong> <h4>👤 Welcome, <?= htmlspecialchars($player['username']) ?></h4></strong></div>

<div id="menuContainer">
    <div class="menu" id="mainMenu">
        <hr>
        <p><a href="war.php">WAR</a></p>
        <p><a href="missions.php">MISSIONS</a></p>
        <p><a href="production.php">PRODUCTION</a></p>
        <p><a href="units.php">UNITS</a></p>
        <p><a href="structures.php">STRUCTURES</a></p>
        <p><a href="blackmarket.php">BLACK MARKET</a></p>
        <p><a href="officersmess.php">OFFICERS MESS</a></p>
        <p><a href="profile.php">PROFILE</a></p>
        <p><a href="alliance.php">ALLIANCE</a></p>
        <p><a href="halloffame.php">HALL OF FAME</a></p>
        <p><a href="chats.php">CHATS</a></p>
        <p><a href="events.php">EVENTS</a></p>
        <p><a href="settings.php">SETTINGS</a></p>
        <button class="menu-button" onclick="alert('Contacting Support...')">Contact SP/Support</button>
    </div>
</div>

<div id="clock">Time: 00:00:00</div>
<div id="date">Date: YYYY-MM-DD</div>

<script>
    // Clock + Date
    function updateTimeAndDate() {
        const now = new Date();
        document.getElementById('clock').textContent = "Time: " + now.toTimeString().slice(0, 8);
        document.getElementById('date').textContent = "Date: " + now.toISOString().slice(0, 10);
    }
    setInterval(updateTimeAndDate, 1000);
    updateTimeAndDate();

    // Regen countdown
    let remaining = <?= $remaining ?>;
    function updateRegenTimer() {
        if (remaining <= 0) {
            document.getElementById("regen-countdown").textContent = "Regen now!";
            return;
        }
        let mins = Math.floor(remaining / 60);
        let secs = remaining % 60;
        document.getElementById("regen-countdown").textContent =
            (mins > 0 ? mins + "m " : "") + secs + "s";
        remaining--;
    }
    setInterval(updateRegenTimer, 1000);
    updateRegenTimer();
</script>
</body>
</html>
