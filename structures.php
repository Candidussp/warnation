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
<script src="PlayerBrain.js"></script>
<script>
    // Restrict direct access by level
    if (typeof checkLevelRequirement === 'function') {
        if (!checkLevelRequirement(2)) {
            throw new Error('Level restriction: redirecting to homepage');
        }
    }
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Structures - WARNATION</title>
<style>
    body {
        background-color: #001f3f;
        color: white;
        font-family: Arial, sans-serif;
        padding: 20px;
    }
    h1 {
        text-align: center;
        color: gold;
        margin-bottom: 30px;
    }
    .structure {
        background-color: #002966;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
    }
    .structure p {
        margin: 5px 0;
    }
    .structure button {
        background-color: #ffcc00;
        color: #001f3f;
        border: none;
        padding: 8px 12px;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 5px;
    }
    .structure button:disabled {
        background-color: grey;
        cursor: not-allowed;
    }
</style>
</head>
<body>
    <a href="Homepage.php" style="color: gold; font-weight: bold; text-decoration: none; display: block; margin-bottom: 15px;">&larr; Back to Homepage</a>
    <h1>Structures</h1>
    <div id="structuresContainer"></div>
    ...

<h1>Structures</h1>
<div id="structuresContainer"></div>

<script>
function getPlayer() {
    return JSON.parse(localStorage.getItem('playerStats')) || {};
}
function savePlayer(player) {
    localStorage.setItem('playerStats', JSON.stringify(player));
}
function getStructures() {
    return JSON.parse(localStorage.getItem('playerStructures')) || {};
}
function saveStructures(structures) {
    localStorage.setItem('playerStructures', JSON.stringify(structures));
}

function getUpgradeCost(structure) {
    if (structure.level >= 10) return null;
    if (structure.level === 9) return { gold: 2000 };
    let cost = 200000 * Math.pow(1.3, structure.level);
    return { cash: Math.round(cost) };
}

function upgradeStructure(structureId) {
    let player = getPlayer();
    let structures = getStructures();
    let s = structures[structureId];
    if (!s || s.level >= 10) return false;

    let cost = getUpgradeCost(s);
    if (cost.cash && player.cash >= cost.cash) {
        player.cash -= cost.cash;
        s.level++;
    } else if (cost.gold && player.gold >= cost.gold) {
        player.gold -= cost.gold;
        s.level++;
    } else return false;

    savePlayer(player);
    structures[structureId] = s;
    saveStructures(structures);
    renderStructures();
    return true;
}

function renderStructures() {
    const container = document.getElementById('structuresContainer');
    container.innerHTML = '';
    const structures = getStructures();
    const achievements = JSON.parse(localStorage.getItem('achievements')) || {};
    const player = getPlayer();

    Object.values(structures).forEach(s => {
        const div = document.createElement('div');
        div.className = 'structure';

        const reqKey = Object.keys(s.requirement)[0];
        const reqVal = s.requirement[reqKey];
        const currentVal = (reqKey === 'level') ? player.level : achievements[reqKey] || 0;
        const unlocked = s.unlocked || currentVal >= reqVal;

        // Friendly requirement text
        let reqText = '';
        switch(s.id){
            case 'predator': reqText = 'Kill players'; break;
            case 'ironDome': reqText = 'Send reinforcements'; break;
            case 'razor': reqText = 'Cut enemy ears'; break;
            case 'tripWire': reqText = 'Sanction players'; break;
            case 'blackMarketDepot': reqText = 'Reach player level'; break;
        }

        // Effect text
        let effectText = '';
        switch(s.id){
            case 'predator': effectText = `Increase all attack by ${s.level * 2.5}%`; break;
            case 'ironDome': effectText = `Increase all defense by ${s.level * 2.5}%`; break;
            case 'razor': effectText = `Chance to cut both enemy ears ${s.level * 3}%`; break;
            case 'tripWire': effectText = `Chance to blow up attacking troops ${s.level * 3.5}%`; break;
            case 'blackMarketDepot': effectText = `Increase cash earnings by ${s.level * 5}%`; break;
        }

        div.innerHTML = `
            <p><strong>${s.name}</strong></p>
            <p>Level: ${s.level}</p>
            <p>Status: ${unlocked ? 'Unlocked ✅' : 'Locked ❌'}</p>
            <p>Requirement: ${reqText}</p>
            <p>Effect: ${effectText}</p>
        `;

        if(unlocked && s.level < 10){
            const btn = document.createElement('button');
            btn.textContent = 'Upgrade';
            btn.onclick = () => upgradeStructure(s.id);

            const cost = getUpgradeCost(s);
            if((cost.cash && player.cash < cost.cash) || (cost.gold && player.gold < cost.gold)) btn.disabled = true;

            div.appendChild(btn);
        }

        container.appendChild(div);
    });
}

renderStructures();
</script>
</body>
</html>
