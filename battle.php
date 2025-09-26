<?php
session_start();
require __DIR__ . '/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['player_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$playerId = (int) $_SESSION['player_id'];
$enemyId  = isset($_POST['enemy_id']) ? (int) $_POST['enemy_id'] : 0;
$enemyType = isset($_POST['enemy_type']) ? $_POST['enemy_type'] : 'player';

if ($enemyId <= 0 || ($enemyType === 'player' && $enemyId === $playerId)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid battle participants.']);
    exit;
}

// --- Load attacker ---
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$player) {
    echo json_encode(['status' => 'error', 'message' => 'Player not found']);
    exit;
}

// --- Load defender ---
if ($enemyType === 'player') {
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$enemyId]);
    $enemy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$enemy) {
        echo json_encode(['status' => 'error', 'message' => 'Enemy not found']);
        exit;
    }
} else {
    // Load bot
    $stmt = $pdo->prepare("SELECT * FROM bots WHERE id = ?");
    $stmt->execute([$enemyId]);
    $enemy = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$enemy) {
        echo json_encode(['status' => 'error', 'message' => 'Bot not found']);
        exit;
    }
    // Bot HP regen: 10 HP every 3 mins
    $regenInterval = 180; // seconds
    $regenAmount   = 10;
    $lastRegen     = strtotime($enemy['last_regen'] ?? 'now');
    $diff          = time() - $lastRegen;
    if ($diff >= $regenInterval && $enemy['health'] < $enemy['max_health']) {
        $times = floor($diff / $regenInterval);
        $enemy['health'] = min($enemy['max_health'], $enemy['health'] + ($regenAmount * $times));
        $stmt = $pdo->prepare("UPDATE bots SET health=?, last_regen=NOW() WHERE id=?");
        $stmt->execute([$enemy['health'], $enemy['id']]);
    }
}

// --- Player regen: 1% per minute ---
function regenHealth($currentHP, $maxHP, $lastUpdate) {
    $minutes = floor((time() - strtotime($lastUpdate)) / 60);
    return min($currentHP + floor($maxHP * 0.01 * $minutes), $maxHP);
}

$player['health'] = regenHealth($player['health'], $player['maxHealth'], $player['last_update'] ?? 'now');
if ($enemyType === 'player') {
    $enemy['health'] = regenHealth($enemy['health'], $enemy['maxHealth'], $enemy['last_update'] ?? 'now');
}

// --- Check ammo ---
if ($player['ammo'] <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No ammo left!',
        'player_hp' => $player['health'],
        'ammo' => $player['ammo'],
        'cash' => $player['cash'],
        'xp' => $player['xp']
    ]);
    exit;
}

// --- Battle logic ---
$playerDamage = rand(10, 30);
$enemyDamage  = rand(5, 20);

// Apply damage
$player['health'] = max(0, $player['health'] - $enemyDamage);
$enemy['health']  = max(0, $enemy['health'] - $playerDamage);

// Consume ammo
$player['ammo'] -= 1;

// Rewards
if ($enemyType === 'bot') {
    $cashGained = rand(200, 500); // unlimited cash
} else {
    $cashGained = rand(50, 200);
}
$xpGained = rand(10, 30);

// Determine messages
$message = '';
if ($enemy['health'] <= 0) {
    $cashGained += rand(500, 1000);
    $xpGained   += rand(50, 100);
    $message = $enemyType === 'bot'
        ? "{$enemy['name']} has been defeated! You looted cash!"
        : "{$enemy['username']} has been defeated!";
} elseif ($player['health'] <= 0) {
    $message = $enemyType === 'bot'
        ? "You were defeated by {$enemy['name']}!"
        : "You were defeated by {$enemy['username']}!";
}

// --- Update DB ---
$now = date('Y-m-d H:i:s');

// Update player
$stmt = $pdo->prepare("UPDATE players SET health=?, ammo=?, cash=cash+?, xp=xp+?, last_update=? WHERE id=?");
$stmt->execute([$player['health'], $player['ammo'], $cashGained, $xpGained, $now, $playerId]);

// Update enemy if real player
if ($enemyType === 'player') {
    $stmt = $pdo->prepare("UPDATE players SET health=?, last_update=? WHERE id=?");
    $stmt->execute([$enemy['health'], $now, $enemyId]);
} else {
    // Bot HP saved
    $stmt = $pdo->prepare("UPDATE bots SET health=?, last_regen=NOW() WHERE id=?");
    $stmt->execute([$enemy['health'], $enemyId]);
}

// --- Response ---
$response = [
    'status' => 'ok',
    'damage_dealt' => $playerDamage,
    'damage_taken' => $enemyDamage,
    'player_hp' => $player['health'],
    'enemy_hp'  => $enemy['health'],
    'ammo' => $player['ammo'],
    'cash' => $player['cash'] + $cashGained,
    'xp' => $player['xp'] + $xpGained,
];

if ($enemyType === 'player') {
    $response['enemy_cash'] = $enemy['cash'];
}

if ($message !== '') {
    $response['message'] = $message;
}

echo json_encode($response);
exit;
