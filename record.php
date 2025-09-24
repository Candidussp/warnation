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
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Player Records</title>
<style>
    body {
        background-color: #001f3f;
        color: white;
        font-family: Arial, sans-serif;
        padding: 20px;
    }
    h1 { text-align: center; }
    table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(255,255,255,0.05);
    }
    th, td {
        padding: 10px;
        border: 1px solid #555;
        text-align: left;
    }
    th {
        background-color: rgba(255,255,255,0.1);
    }
    a {
        color: cyan;
        text-decoration: none;
    }
</style>
<script src="PlayerBrain.js"></script>
</head>
<body>

<a href="HomePage.php">&larr; Back to Homepage</a>
<h1>Player Records</h1>
<table id="recordsTable">
    <thead>
        <tr>
            <th>Record</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<script>
    function loadRecords() {
        const ach = getAchievements();
        const records = [
            ["Kills", ach.kills],
            ["Deaths", ach.deaths],
            ["Quests Completed", ach.questsCompleted],
            ["Ears Cut", ach.earsCut],
            ["Players Pardoned", ach.playersPardoned],
            ["Reinforcements Sent", ach.reinforcementsSent], // ✅ NEW
            ["Victories", ach.victories],
            ["XP Earned", ach.xpEarned],
            ["Sanctions Executed", ach.sanctionsExecuted],
            ["Days in Game", ach.daysInGame]
        ];

        const tbody = document.querySelector("#recordsTable tbody");
        tbody.innerHTML = "";
        records.forEach(([name, value]) => {
            let row = document.createElement("tr");
            row.innerHTML = `<td>${name}</td><td>${value}</td>`;
            tbody.appendChild(row);
        });
    }
    loadRecords();
</script>

</body>
</html>
