<?php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

session_start();
if (empty($_SESSION['player_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$pid = (int) $_SESSION['player_id'];

// --- Get player stats ---
$stmt = $pdo->prepare('SELECT health, max_health, energy, max_energy, ammo, max_ammo, cash, gold, xp, level, username FROM players WHERE id=? LIMIT 1');
$stmt->execute([$pid]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    echo json_encode(['success' => false, 'message' => 'Player not found']);
    exit;
}

// --- XP needed for next level ---
$xpNeeded = floor(1000 * pow($player['level'], 2.3));

echo json_encode([
    'success' => true,
    'health' => $player['health'],
    'maxHealth' => $player['max_health'],
    'energy' => $player['energy'],
    'maxEnergy' => $player['max_energy'],
    'ammo' => $player['ammo'],
    'maxAmmo' => $player['max_ammo'],
    'cash' => $player['cash'],
    'gold' => $player['gold'],
    'xp' => $player['xp'],
    'level' => $player['level'],
    'username' => $player['username'],
    'xpNeeded' => $xpNeeded
]);
