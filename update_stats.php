<?php
// /api/update_stats.php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

$username = trim($_POST['username'] ?? '');
$statsJson = $_POST['stats'] ?? '';
if ($username === '' || $statsJson === '') {
    echo json_encode(['success' => false, 'message' => 'username & stats required']);
    exit;
}

$stats = json_decode($statsJson, true);
if (!is_array($stats)) {
    echo json_encode(['success' => false, 'message' => 'invalid stats']);
    exit;
}

// map JS keys -> DB columns
$map = [
    'health'    => 'health',
    'maxHealth' => 'max_health',
    'energy'    => 'energy',
    'maxEnergy' => 'max_energy',
    'ammo'      => 'ammo',
    'maxAmmo'   => 'max_ammo',
    'cash'      => 'cash',
    'gold'      => 'gold',
    'xp'        => 'xp',
    'level'     => 'level',
];

$fields = [];
$params = [];
foreach ($map as $jsKey => $dbCol) {
    if (isset($stats[$jsKey])) {
        $fields[] = "$dbCol = ?";
        $params[] = $stats[$jsKey];
    }
}

if (!$fields) {
    echo json_encode(['success' => false, 'message' => 'no updatable fields']);
    exit;
}

// get player id
$stmt = $pdo->prepare('SELECT id FROM players WHERE username=? LIMIT 1');
$stmt->execute([$username]);
$player = $stmt->fetch();
if (!$player) {
    echo json_encode(['success' => false, 'message' => 'player not found']);
    exit;
}
$params[] = (int)$player['id'];

$sql = 'UPDATE stats SET ' . implode(', ', $fields) . ' WHERE player_id = ? LIMIT 1';
$pdo->prepare($sql)->execute($params);

echo json_encode(['success' => true]);