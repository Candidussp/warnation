<?php
require __DIR__ . '/api/db.php';

$now = time();
$regenRules = [
    'health' => ['amount' => 10, 'interval' => 180],
    'energy' => ['amount' => 10, 'interval' => 180],
    'ammo'   => ['amount' => 1,  'interval' => 180],
];

// --- Players regen ---
$players = $pdo->query("SELECT * FROM players")->fetchAll(PDO::FETCH_ASSOC);
foreach ($players as $p) {
    $last = strtotime($p['last_regen'] ?? '1970-01-01 00:00:00');
    $elapsed = $now - $last;
    if ($elapsed >= $regenRules['health']['interval']) {
        $ticks = floor($elapsed / $regenRules['health']['interval']);
        $newHealth = min($p['health'] + $regenRules['health']['amount'] * $ticks, $p['max_health']);
        $newEnergy = min($p['energy'] + $regenRules['energy']['amount'] * $ticks, $p['max_energy']);
        $newAmmo   = min($p['ammo']   + $regenRules['ammo']['amount']   * $ticks, $p['max_ammo']);

        $stmt = $pdo->prepare("UPDATE players SET health=?, energy=?, ammo=?, last_regen=NOW(), updated_at=NOW() WHERE id=?");
        $stmt->execute([$newHealth, $newEnergy, $newAmmo, $p['id']]);
    }
}

// --- Bots regen ---
$bots = $pdo->query("SELECT * FROM bots")->fetchAll(PDO::FETCH_ASSOC);
foreach ($bots as $b) {
    $last = strtotime($b['last_regen'] ?? '1970-01-01 00:00:00');
    $elapsed = $now - $last;
    if ($elapsed >= $regenRules['health']['interval']) {
        $ticks = floor($elapsed / $regenRules['health']['interval']);
        $newHealth = min($b['health'] + $regenRules['health']['amount'] * $ticks, 300); // Max bot HP = 300
        $stmt = $pdo->prepare("UPDATE bots SET health=?, last_regen=NOW() WHERE id=?");
        $stmt->execute([$newHealth, $b['id']]);
    }
}
