<?php
session_start();
require 'api/db.php';

// Redirect if not logged in
if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

$playerId = (int) $_SESSION['player_id'];

// ---- Fetch 4 random real players (exclude self) ----
$stmt = $pdo->prepare("
    SELECT id, username, level 
    FROM players 
    WHERE id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$playerId]);
$realPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Fetch 6 random bots from database ----
$stmt = $pdo->query("SELECT id, name, max_health FROM bots ORDER BY RAND() LIMIT 6");
$botRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Merge entities ----
$entities = [];

// Push bots
foreach ($botRows as $b) {
    $entities[] = [
        'type'   => 'bot',
        'id'     => $b['id'],
        'name'   => $b['name'],
        'flag'   => "🤖",
        'level'  => rand(1, 5),
        'rating' => rand(200, 600),
    ];
}

// Push real players
foreach ($realPlayers as $rp) {
    $entities[] = [
        'type'   => 'player',
        'id'     => $rp['id'],
        'name'   => $rp['username'],
        'flag'   => "🟦",
        'level'  => $rp['level'],
        'rating' => max(200, $rp['level'] * 500),
    ];
}

// Shuffle all together
shuffle($entities);
?>
<!DOCTYPE html>
<html>
<head>
    <title>WAR PAGE - WARNATION</title>
    <style> 
        body { background-color: darkblue; color: white; font-family: Arial, sans-serif; padding: 20px; }
        h1, h2 { text-align: center; text-decoration: underline; }
        .section { margin-bottom: 30px; }
        .entity { margin: 10px 0; font-size: 18px; }
        .attack-link { color: cyan; font-weight: bold; text-decoration: none; margin-left: 20px; }
        .attack-link:hover { text-decoration: underline; }
        a { color: yellow; font-weight: bold; text-decoration: none; display: block; margin-bottom: 20px; }
        .player-name { color: yellow; font-weight: bold; }
    </style>
</head>
<body>

    <center><a href="HomePage.php">WARNATION</a></center>

    <div class="section">
        <h2>INVASION</h2>
        <div id="entityList">
            <?php foreach ($entities as $e): ?>
                <div class="entity">
                    <?= $e['flag'] ?>
                    <span class="player-name"><?= htmlspecialchars($e['name']) ?></span>
                    Lv.<?= (int)$e['level'] ?> | Rating <?= (int)$e['rating'] ?>
                    <?php if ($e['type'] === 'bot'): ?>
                        <a class="attack-link" href="Candidus.php?bot=<?= urlencode($e['id']) ?>&type=bot">Attack</a>
                    <?php else: ?>
                        <a class="attack-link" href="Candidus.php?target=<?= (int)$e['id'] ?>&type=player">Attack</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section">
        <h2>GROUP BATTLE</h2>
        <p>Coming Soon! Prepare to join forces with allies for epic group wars.</p>
    </div>

    <div class="section">
        <h2>SANCTION</h2>
        <p>Impose sanctions on enemy nations to cripple their resources. (Feature in development)</p>
    </div>

    <center>
        <u><a href="battlelog.php">Battle Log</a></u>
    </center>

</body>
</html>
