<?php
require __DIR__ . '/../api/db.php';

$now = time();

// Regen rules
$regenRules = [
    'health' => ['amount' => 10, 'interval' => 180],
    'energy' => ['amount' => 10, 'interval' => 180],
    'ammo'   => ['amount' => 1,  'interval' => 180],
];

// Load all players (for multiplayer, optional: only online players to reduce load)
$players = $pdo->query("SELECT * FROM players")->fetchAll(PDO::FETCH_ASSOC);

foreach ($players as $p) {
    $last = strtotime($p['last_regen'] ?? '1970-01-01 00:00:00');
    $elapsed = $now - $last;

    if ($elapsed >= $regenRules['health']['interval']) {
        $ticks = floor($elapsed / $regenRules['health']['interval']);
        $newHealth = min($p['health'] + $regenRules['health']['amount'] * $ticks, $p['maxHealth']);
        $newEnergy = min($p['energy'] + $regenRules['energy']['amount'] * $ticks, $p['maxEnergy']);
        $newAmmo   = min($p['ammo']   + $regenRules['ammo']['amount']   * $ticks, $p['maxAmmo']);

        $stmt = $pdo->prepare("UPDATE players SET health=?, energy=?, ammo=?, last_regen=NOW() WHERE id=?");
        $stmt->execute([$newHealth, $newEnergy, $newAmmo, $p['id']]);
    }
}

// You can do similar for bots if needed
?>
