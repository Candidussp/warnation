<?php
session_start();
require 'api/db.php';

// --- Check login ---
if (empty($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

$playerId = (int) $_SESSION['player_id'];

// --- Load attacker (player) ---
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$player) die("Player not found");

// --- Regen logic for player ---
$level = isset($player['level']) ? (int)$player['level'] : 1;
$player['maxHealth'] = $player['maxHealth'] ?: (300 + (($level - 1) * 5));
$player['maxEnergy'] = $player['maxEnergy'] ?: (500 + (($level - 1) * 5));
$player['maxAmmo']   = $player['maxAmmo']   ?: (20 + (($level - 1) * 1));

$interval = 180; // 3 minutes
$now = time();
$lastRegen = strtotime($player['last_regen'] ?? date('Y-m-d H:i:s'));
$elapsed = $now - $lastRegen;

if ($elapsed >= $interval) {
    $ticks = floor($elapsed / $interval);

    $regenHealth = 10 * $ticks;
    $regenEnergy = 10 * $ticks;
    $regenAmmo   = 1 * $ticks;

    $player['health'] = min($player['health'] + $regenHealth, $player['maxHealth']);
    $player['energy'] = min($player['energy'] + $regenEnergy, $player['maxEnergy']);
    $player['ammo']   = min($player['ammo']   + $regenAmmo,   $player['maxAmmo']);

    $stmt = $pdo->prepare("UPDATE players SET health=?, energy=?, ammo=?, last_regen=NOW() WHERE id=?");
    $stmt->execute([$player['health'], $player['energy'], $player['ammo'], $playerId]);
}

// --- Determine target ---
$targetPlayerId = isset($_GET['target']) ? (int) $_GET['target'] : 0;
$targetBotId    = isset($_GET['bot']) ? (int) $_GET['bot'] : 0;

if ($targetPlayerId <= 0 && $targetBotId <= 0) {
    die("Invalid battle participants");
}

// --- Load target ---
if ($targetPlayerId > 0) {
    $stmt = $pdo->prepare("SELECT id, username, health, maxHealth, cash, level FROM players WHERE id = ?");
    $stmt->execute([$targetPlayerId]);
    $enemy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$enemy) die("Invalid battle participants");
    $enemyType = 'player';
} else {
    $stmt = $pdo->prepare("SELECT * FROM bots WHERE id = ?");
    $stmt->execute([$targetBotId]);
    $enemy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$enemy) die("Bot not found");

    $enemyType = 'bot';
    $regenInterval = 180;
    $regenAmount = 10;
    $last = strtotime($enemy['last_regen']);
    $diff = time() - $last;
    if ($diff >= $regenInterval && $enemy['health'] < $enemy['max_health']) {
        $regenTimes = floor($diff / $regenInterval);
        $newHealth = min($enemy['max_health'], $enemy['health'] + ($regenAmount * $regenTimes));
        $stmt = $pdo->prepare("UPDATE bots SET health = ?, last_regen = NOW() WHERE id = ?");
        $stmt->execute([$newHealth, $enemy['id']]);
        $enemy['health'] = $newHealth;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BATTLE - WARNATION</title>
    <style>
        body { background-color: black; color: white; font-family: Arial, sans-serif; text-align: center; padding: 20px; }
        a { color: yellow; font-weight: bold; text-decoration: none; display: block; margin-bottom: 20px; }
        .health-bar { width: 300px; height: 25px; background-color: gray; margin: 10px auto; position: relative; }
        .health-fill { height: 100%; background-color: green; width: 100%; transition: width 0.5s; }
        .battle-log { border: 1px solid white; height: 140px; width: 90%; margin: 20px auto; overflow-y: auto; text-align: center; padding: 10px; font-size: 16px; }
        .attack-button { padding: 10px 20px; font-size: 16px; font-weight: bold; background-color: red; border: none; color: white; cursor: pointer; }
        .attack-button:disabled { background-color: gray; cursor: not-allowed; }
        .stats { text-align: left; margin: 20px auto; width: 300px; font-size: 14px; border: 1px solid white; padding: 10px; }
    </style>
</head>
<body>

<a href="HomePage.php">← Back to Home</a>
<h1>BATTLE COMMENCED!</h1>
<h2><?= htmlspecialchars($player['username']) ?> VS <?= htmlspecialchars($enemy['name'] ?? $enemy['username']) ?></h2>

<div class="stats">
    <p>Health: <span id="playerHealthDisplay"><?= $player['health'] ?></span>/<?= $player['maxHealth'] ?></p>
    <p>Ammo: <span id="ammoDisplay"><?= $player['ammo'] ?></span></p>
    <p>Cash: $<span id="cashDisplay"><?= $player['cash'] ?></span></p>
    <p>Level: <span id="levelDisplay"><?= $player['level'] ?></span></p>
    <p>XP: <span id="xpDisplay"><?= $player['xp'] ?></span></p>
</div>

<div class="stats">
    <p>Enemy Health: <span id="enemyHealthDisplay"><?= $enemy['health'] ?></span>/<?= $enemyType === 'player' ? $enemy['maxHealth'] : $enemy['max_health'] ?></p>
    <?php if ($enemyType === 'player'): ?>
        <p>Cash: $<span id="enemyCashDisplay"><?= $enemy['cash'] ?></span></p>
    <?php else: ?>
        <p>Cash: Unlimited</p>
    <?php endif; ?>
</div>

<button class="attack-button" id="attackBtn">ATTACK</button>
<div class="battle-log" id="battleLog">
    <p>Battle log will appear here...</p>
</div>

<script>
const attackBtn = document.getElementById('attackBtn');
const logDiv = document.getElementById('battleLog');
const enemyId = <?= $enemy['id'] ?>;
const enemyType = "<?= $enemyType ?>";

function appendLog(message) {
    logDiv.innerHTML = "<p>" + message + "</p>" + logDiv.innerHTML;
}

attackBtn.addEventListener('click', () => {
    attackBtn.disabled = true;
    appendLog("➡ Attacking enemy...");

    fetch("api/battle.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `enemy_id=${enemyId}&enemy_type=${enemyType}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            appendLog(data.message);
            document.getElementById('playerHealthDisplay').textContent = data.player_hp;
            document.getElementById('ammoDisplay').textContent = data.ammo;
            document.getElementById('cashDisplay').textContent = data.cash;
            document.getElementById('xpDisplay').textContent = data.xp;
            document.getElementById('enemyHealthDisplay').textContent = data.enemy_hp;
            if (enemyType === 'player' && data.enemy_cash !== undefined) {
                document.getElementById('enemyCashDisplay').textContent = data.enemy_cash;
            }
        } else {
            appendLog("❌ " + data.message);
        }
    })
    .catch(err => appendLog("⚠️ Error: " + err))
    .finally(() => {
        setTimeout(() => attackBtn.disabled = false, 3000);
    });
});
</script>

</body>
</html>
