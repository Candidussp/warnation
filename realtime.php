<?php
function applyRealTimeUpdates($playerId, $pdo) {
    // Fetch player
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id=?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) return;

    $now = new DateTime();
    $lastUpdate = new DateTime($player['last_update'] ?? $player['last_regen'] ?? 'now');

    $diffSeconds = $now->getTimestamp() - $lastUpdate->getTimestamp();

    if ($diffSeconds <= 0) return;

    // -------------------------
    // 1. Regen (every 3 minutes)
    // -------------------------
    $regenTicks = floor($diffSeconds / (3 * 60));

    if ($regenTicks > 0) {
        $newHealth = min($player['health'] + ($regenTicks * 10), $player['max_health']);
        $newEnergy = min($player['energy'] + ($regenTicks * 10), $player['max_energy']);
        $newAmmo   = min($player['ammo'] + ($regenTicks * 1), $player['max_ammo']);

        $stmt = $pdo->prepare("UPDATE players 
            SET health=?, energy=?, ammo=? WHERE id=?");
        $stmt->execute([$newHealth, $newEnergy, $newAmmo, $playerId]);
    }

    // -------------------------
    // 2. Buildings construction
    // -------------------------
    $stmt = $pdo->prepare("SELECT * FROM buildings WHERE player_id=? AND upgrade_finish IS NOT NULL");
    $stmt->execute([$playerId]);
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($buildings as $building) {
        if (strtotime($building['upgrade_finish']) <= $now->getTimestamp()) {
            $stmt = $pdo->prepare("UPDATE buildings 
                SET level = level + 1, upgrade_start=NULL, upgrade_finish=NULL 
                WHERE id=?");
            $stmt->execute([$building['id']]);
        }
    }

    // -------------------------
    // 3. Research / upgrades
    // -------------------------
    $stmt = $pdo->prepare("SELECT * FROM upgrades WHERE player_id=? AND upgrade_finish IS NOT NULL");
    $stmt->execute([$playerId]);
    $upgrades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($upgrades as $upgrade) {
        if (strtotime($upgrade['upgrade_finish']) <= $now->getTimestamp()) {
            $stmt = $pdo->prepare("UPDATE upgrades 
                SET level = level + 1, upgrade_start=NULL, upgrade_finish=NULL 
                WHERE id=?");
            $stmt->execute([$upgrade['id']]);
        }
    }

    // -------------------------
    // 4. Army training (example)
    // -------------------------
    $stmt = $pdo->prepare("SELECT * FROM training_queue WHERE player_id=?");
    $stmt->execute([$playerId]);
    $trainings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($trainings as $train) {
        if (strtotime($train['finish_time']) <= $now->getTimestamp()) {
            // Training complete → move units to army
            $stmt = $pdo->prepare("UPDATE army SET count = count + ? WHERE player_id=? AND unit_id=?");
            $stmt->execute([$train['quantity'], $playerId, $train['unit_id']]);

            // Clear queue
            $stmt = $pdo->prepare("DELETE FROM training_queue WHERE id=?");
            $stmt->execute([$train['id']]);
        }
    }

    // -------------------------
    // 5. Save last update time
    // -------------------------
    $stmt = $pdo->prepare("UPDATE players SET last_update=NOW() WHERE id=?");
    $stmt->execute([$playerId]);
}
