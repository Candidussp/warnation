<?php
// Always start session at the top
session_start();

// Connect to database
require 'api/db.php';

// Check if player is logged in
if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

// (Optional) Load player data from DB
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch();
?>
<!DOCTYPE html><html>
<head><script src="PlayerBrain.js"></script>

    <title>WAR PAGE - Battle History</title>
    <style>
        body {
            background-color: darkblue;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        h1 {
            text-align: center;
            text-decoration: underline;
        }
        a {
            color: yellow;
            font-weight: bold;
            text-decoration: none;
            display: block;
            margin-bottom: 20px;
        }
        .history-log {
            border: 1px solid white;
            padding: 10px;
            max-width: 600px;
            margin: 0 auto;
            background-color: rgba(0,0,0,0.5);
        }
        .history-entry {
            margin-bottom: 10px;
            padding: 10px;
            border-bottom: 1px solid gray;
        }
    </style>
</head>
<body><a href="HomePage.php">← Back to Home</a> <a href="war.php">← Back to War Page</a>

<h1>Battle History</h1><div class="history-log" id="historyLog">
    <p>Loading battle history...</p>
</div><script>
    function loadBattleHistory() {
        const logDiv = document.getElementById('historyLog');
        let history = JSON.parse(localStorage.getItem('battleHistory')) || [];

        if (history.length === 0) {
            logDiv.innerHTML = '<p>No battle history yet.</p>';
            return;
        }

        logDiv.innerHTML = '';
        history.forEach(entry => {
            const div = document.createElement('div');
            div.className = 'history-entry';
            div.innerHTML = `<strong>${entry.result}</strong> vs ${entry.bot}<br>
                             Damage Dealt: ${entry.playerDamage} | Damage Taken: ${entry.botDamage}<br>
                             Cash Earned: $${entry.cash} | XP Earned: ${entry.xp}<br>
                             <small>${entry.time}</small>`;
            logDiv.appendChild(div);
        });
    }

    function saveBattleResult(result, botName, playerDamage, botDamage, cashEarned, xpEarned) {
        let history = JSON.parse(localStorage.getItem('battleHistory')) || [];
        history.unshift({
            result: result,
            bot: botName,
            playerDamage: playerDamage,
            botDamage: botDamage,
            cash: cashEarned,
            xp: xpEarned,
            time: new Date().toLocaleString()
        });
        if (history.length > 20) history.pop();  // Keep only last 20 battles
        localStorage.setItem('battleHistory', JSON.stringify(history));
    }

    loadBattleHistory();
</script></body>
</html>