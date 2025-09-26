<?php
// autoload.php
session_start();
require __DIR__ . '/api/db.php';
require __DIR__ . '/api/functions.php';

if (isset($_SESSION['player_id'])) {
    $player = applyRegeneration($_SESSION['player_id'], $pdo);

    if (!$player) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $GLOBALS['PLAYER'] = $player;
}
