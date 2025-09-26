<?php
// api/functions.php

/**
 * Apply regeneration logic for player stats.
 */
function applyRegeneration($playerId, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        return false;
    }

    $now = new DateTime();
    $lastRegen = new DateTime($player['last_regen']);

    $diffMinutes = floor(($now->getTimestamp() - $lastRegen->getTimestamp()) / 60);

    if ($diffMinutes >= 3) {
        $ticks = floor($diffMinutes / 3);

        $newHealth = min($player['health'] + ($ticks * 10), $player['max_health']);
        $newEnergy = min($player['energy'] + ($ticks * 10), $player['max_energy']);
        $newAmmo   = min($player['ammo'] + ($ticks * 1), $player['max_ammo']);

        $stmt = $pdo->prepare("UPDATE players 
            SET health=?, energy=?, ammo=?, last_regen=NOW() 
            WHERE id=?");
        $stmt->execute([$newHealth, $newEnergy, $newAmmo, $playerId]);

        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$playerId]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $player;
}

/**
 * Universal getter for player stats.
 */
function getPlayer($key = null) {
    if (!isset($GLOBALS['PLAYER'])) {
        return null;
    }

    if ($key === null) {
        return $GLOBALS['PLAYER']; // full array
    }

    return $GLOBALS['PLAYER'][$key] ?? null;
}

/**
 * Universal updater for player stats.
 * Example: updatePlayer('cash', getPlayer('cash') + 500);
 */
function updatePlayer($key, $value, $pdo) {
    if (!isset($GLOBALS['PLAYER']) || !isset($_SESSION['player_id'])) {
        return false;
    }

    $playerId = $_SESSION['player_id'];

    // Update DB
    $stmt = $pdo->prepare("UPDATE players SET {$key} = ? WHERE id = ?");
    $stmt->execute([$value, $playerId]);

    // Update global cache
    $GLOBALS['PLAYER'][$key] = $value;

    return true;
}
