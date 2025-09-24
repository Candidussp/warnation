<?php
session_start();
require 'api/db.php';

if (!isset($_SESSION['player_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$playerId = (int) $_SESSION['player_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// --- Helper: fetch mine ---
function getMine($pdo, $playerId, $mineNumber) {
    $stmt = $pdo->prepare("SELECT * FROM player_mines WHERE player_id = ? AND mine_number = ?");
    $stmt->execute([$playerId, $mineNumber]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Enforce max 3 mines ---
function validateMineNumber($mineNumber) {
    return in_array($mineNumber, [1,2,3], true);
}

header("Content-Type: application/json");

// --- Get all mines ---
if ($action === "get_mines") {
    $stmt = $pdo->prepare("SELECT * FROM player_mines WHERE player_id = ? ORDER BY mine_number ASC");
    $stmt->execute([$playerId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- Purchase mine ---
if ($action === "purchase" && isset($_POST['mine'], $_POST['cost'])) {
    $mineNumber = (int) $_POST['mine'];
    $cost = (int) $_POST['cost'];

    if (!validateMineNumber($mineNumber)) {
        echo json_encode(["success" => false, "error" => "Invalid mine number"]);
        exit;
    }

    $player = $pdo->query("SELECT gold FROM players WHERE id = $playerId")->fetch(PDO::FETCH_ASSOC);

    if ($player && $player['gold'] >= $cost) {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE players SET gold = gold - ? WHERE id = ?")->execute([$cost, $playerId]);
        $pdo->prepare("UPDATE player_mines SET unlocked = 1 WHERE player_id = ? AND mine_number = ?")
            ->execute([$playerId, $mineNumber]);

        $pdo->commit();
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Not enough gold"]);
    }
    exit;
}

// --- Start mining ---
if ($action === "start" && isset($_POST['mine'])) {
    $mineNumber = (int) $_POST['mine'];
    if (!validateMineNumber($mineNumber)) {
        echo json_encode(["success" => false, "error" => "Invalid mine number"]);
        exit;
    }

    $mine = getMine($pdo, $playerId, $mineNumber);
    if (!$mine || !$mine['unlocked']) {
        echo json_encode(["success" => false, "error" => "Mine not unlocked"]);
        exit;
    }

    $pdo->prepare("UPDATE player_mines SET mining_start = NOW(), last_claim_date = NULL WHERE player_id = ? AND mine_number = ?")
        ->execute([$playerId, $mineNumber]);

    echo json_encode(["success" => true]);
    exit;
}

// --- Claim reward ---
if ($action === "claim" && isset($_POST['mine'])) {
    $mineNumber = (int) $_POST['mine'];
    if (!validateMineNumber($mineNumber)) {
        echo json_encode(["success" => false, "error" => "Invalid mine number"]);
        exit;
    }

    $mine = getMine($pdo, $playerId, $mineNumber);
    if (!$mine || !$mine['unlocked'] || !$mine['mining_start']) {
        echo json_encode(["success" => false, "error" => "Invalid mining state"]);
        exit;
    }

    $miningDuration = 30 * 60; // 30 min in seconds
    $elapsed = time() - strtotime($mine['mining_start']);
    if ($elapsed < $miningDuration) {
        echo json_encode(["success" => false, "error" => "Mining not completed"]);
        exit;
    }

    // Weighted reward
    $rand = mt_rand(1, 100);
    if ($rand <= 70) {
        $goldReward = rand(10, 30);
    } elseif ($rand <= 90) {
        $goldReward = rand(30, 40);
    } else {
        $goldReward = rand(40, 58);
    }

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE players SET gold = gold + ? WHERE id = ?")
        ->execute([$goldReward, $playerId]);
    $pdo->prepare("UPDATE player_mines SET mining_start = NULL, last_claim_date = CURDATE() WHERE player_id = ? AND mine_number = ?")
        ->execute([$playerId, $mineNumber]);

    $pdo->commit();

    echo json_encode(["success" => true, "goldReward" => $goldReward]);
    exit;
}

echo json_encode(["success" => false, "error" => "Invalid action"]);
