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
<html>
<head>
    <title>BATTLE - WARNATION</title>
    <script src="PlayerBrain.js"></script>
    <style>
        body {
            background-color: black;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
            text-align: center;
        }
        a {
            color: yellow;
            font-weight: bold;
            text-decoration: none;
            display: block;
            margin-bottom: 20px;
        }
        .health-bar {
            width: 300px;
            height: 25px;
            background-color: gray;
            margin: 10px auto;
            position: relative;
        }
        .health-fill {
            height: 100%;
            background-color: green;
            width: 100%;
            transition: width 0.5s;
        }
        .battle-log {
            border: 1px solid white;
            height: 140px;
            width: 90%;
            margin: 20px auto;
            overflow-y: auto;
            text-align: center;
            padding: 10px;
            font-size: 16px;
        }
        .attack-button {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            background-color: red;
            border: none;
            color: white;
            cursor: pointer;
        }
        .attack-button:disabled {
            background-color: gray;
            cursor: not-allowed;
        }
        .stats {
            text-align: left;
            margin: 20px auto;
            width: 300px;
            font-size: 14px;
            border: 1px solid white;
            padding: 10px;
        }
        button.fatality-btn {
            margin: 5px;
            padding: 8px 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            border: none;
        }
        #cutEarBtn {
            background-color: darkred;
            color: white;
        }
        #pardonBtn {
            background-color: darkgreen;
            color: white;
        }
    </style>
</head>
<body>

<a href="HomePage.php">← Back to Home</a>
<h1>BATTLE COMMENCED!</h1>
<h2>You (Player) VS <span id="botName">Enemy Bot</span></h2>

<div class="stats">
    <p>Health: <span id="playerHealthDisplay">300</span>/300</p>
    <p>Energy: <span id="energyDisplay">500</span>/500</p>
    <p>Ammo: <span id="ammoDisplay">20</span>/20</p>
    <p>Cash: $<span id="cashDisplay">0</span></p>
    <p>Level: <span id="levelDisplay">1</span></p>
    <p>Gold: <span id="goldDisplay">0</span></p>
    <p>XP: <span id="xpDisplay">0</span> / <span id="xpNeededDisplay">1500</span></p>
</div>

<div>
    <p>Your Health: <span id="playerHealthNum">0</span>/<span id="playerMaxHealth">0</span></p>
    <div class="health-bar">
        <div id="playerHealthBar" class="health-fill"></div>
    </div>
</div>

<button class="attack-button" id="attackBtn" onclick="attack()">ATTACK</button>

<div class="battle-log" id="battleLog">
    <p>Battle log will appear here...</p>
</div>

<script>
    // --- setup / state ---
    const urlParams = new URLSearchParams(window.location.search);
    const botName = urlParams.get('bot') || 'Enemy Bot';
    const botType = urlParams.get('type') || 'bot';  // 'bot' or 'player'
    document.getElementById('botName').textContent = botName;

    const botHPKey = `bot_${botName}_hp`;
    const botTimeKey = `bot_${botName}_lastTime`;
    const maxBotHealth = 300;

    function regenerateBotHealth() {
        const lastDamaged = parseInt(localStorage.getItem(botTimeKey)) || 0;
        const now = Date.now();
        if (now - lastDamaged >= 3 * 60 * 1000) {
            localStorage.setItem(botHPKey, maxBotHealth);
        }
    }

    regenerateBotHealth();
    let botHealth = parseInt(localStorage.getItem(botHPKey)) || maxBotHealth;

    // load player stats
    let playerStats = JSON.parse(localStorage.getItem('playerStats')) || {
        health: 300, energy: 500, ammo: 20, cash: 10000, gold: 0,
        level: 1, xp: 0, xpNeeded: 1500, crit: 5, dodge: 5, skillPoints: 5,
        army: 1000
    };

    // load bot stats (simulate stored bot army & cash)
    let botArmyKey = `bot_${botName}_army`;
    let botCashKey = `bot_${botName}_cash`;
    let botArmy = parseInt(localStorage.getItem(botArmyKey)) || 800;
    let botCash = parseInt(localStorage.getItem(botCashKey)) || 5000;

    let {
        health: playerHealth,
        energy,
        ammo,
        cash,
        gold,
        level,
        xp,
        xpNeeded,
        crit: playerCritChance,
        dodge: playerDodgeChance,
        skillPoints,
        army: playerArmy
    } = playerStats;

    const playerMaxHealth = 300;

    // accumulate rewards for this current battle (used in final save)
    let battleCashEarned = 0;
    let battleXPEarned = 0;

    // --- UI helpers ---
    function updateStats() {
        document.getElementById('playerHealthDisplay').textContent = playerHealth;
        document.getElementById('energyDisplay').textContent = energy;
        document.getElementById('ammoDisplay').textContent = ammo;
        document.getElementById('cashDisplay').textContent = cash;
        document.getElementById("goldDisplay").textContent = gold;
        document.getElementById("levelDisplay").textContent = level;
        document.getElementById('xpDisplay').textContent = xp;
        document.getElementById('xpNeededDisplay').textContent = xpNeeded;
        document.getElementById('playerHealthNum').textContent = playerHealth;
        document.getElementById('playerMaxHealth').textContent = playerMaxHealth;
        document.getElementById('playerHealthBar').style.width = (playerHealth / playerMaxHealth * 100) + "%";
    }

    function saveStats() {
        playerStats = {
            health: playerHealth,
            energy,
            ammo,
            cash,
            gold,
            level,
            xp,
            xpNeeded,
            crit: playerCritChance,
            dodge: playerDodgeChance,
            skillPoints,
            army: playerArmy
        };
        localStorage.setItem('playerStats', JSON.stringify(playerStats));

        // also save bot army and cash
        localStorage.setItem(botArmyKey, botArmy);
        localStorage.setItem(botCashKey, botCash);
    }

    function saveBattleResult(type, result, botName, playerDamage, botDamage, cashEarned, xpEarned) {
        let history = JSON.parse(localStorage.getItem('battleHistory')) || [];
        history.unshift({
            type: type,
            result: result,
            bot: botName,
            playerDamage: playerDamage,
            botDamage: botDamage,
            cash: cashEarned,
            xp: xpEarned,
            time: new Date().toLocaleString()
        });
        if (history.length > 20) history.pop();
        localStorage.setItem('battleHistory', JSON.stringify(history));
    }

    // Achievement helpers for fatality actions
    function cutEar() {
        let ach = JSON.parse(localStorage.getItem('achievements')) || {};
        ach.earsCut = (ach.earsCut || 0) + 1;
        localStorage.setItem('achievements', JSON.stringify(ach));
        appendLog(`✂️ You performed a Cut Ear on ${botName}!`);
    }

    function pardonPlayer() {
        let ach = JSON.parse(localStorage.getItem('achievements')) || {};
        ach.playersPardoned = (ach.playersPardoned || 0) + 1;
        localStorage.setItem('achievements', JSON.stringify(ach));
        appendLog(`🕊️ You pardoned ${botName}.`);
    }

    // --- attack logic ---
    function attack() {
        if (playerHealth <= 0) return;
        if (ammo <= 0) {
            appendLog(`<em>No ammo left.</em>`);
            return;
        }
        if (botHealth <= 0) {
            appendLog(`<em>This opponent is already dead.</em>`);
            return;
        }

        document.getElementById('attackBtn').disabled = true;
        setTimeout(() => {
            if (playerHealth > 0 && botHealth > 0) {
                document.getElementById('attackBtn').disabled = false;
            }
        }, 5000);

        ammo--;

        let playerDamage = Math.floor(Math.random() * 30) + 10;
        let botDamage = Math.floor(playerHealth * (Math.random() * (5 - 2) + 2) / 100);

        const playerDodged = Math.random() < (playerDodgeChance / 100);
        const botDodged = Math.random() < 0.05;

        let isCritical = false;
        if (!botDodged && Math.random() < (playerCritChance / 100)) {
            isCritical = true;
            playerDamage = Math.floor(playerDamage * ((Math.random() * 0.5) + 2));
        }

        if (playerDodged) playerDamage = 0;
        if (botDodged) botDamage = 0;

        botHealth -= playerDamage;
        playerHealth -= botDamage;
        if (botHealth < 0) botHealth = 0;
        if (playerHealth < 0) playerHealth = 0;

        // army losses
        let defenderArmyLossPercent = (Math.random() * (8 - 3)) + 3;
        let defenderArmyLost = Math.floor(botArmy * (defenderArmyLossPercent / 100));
        botArmy -= defenderArmyLost;
        if (botArmy < 0) botArmy = 0;

        let attackerArmyLossPercent = Math.random() * 3;
        let attackerArmyLost = Math.floor(playerArmy * (attackerArmyLossPercent / 100));
        playerArmy -= attackerArmyLost;
        if (playerArmy < 0) playerArmy = 0;

        // cash transfer
        let cashStolen = 0;
        if (botCash > 0) {
            let cashLossPercent = (Math.random() * (5 - 1)) + 1;
            cashStolen = Math.floor(botCash * (cashLossPercent / 100));
            botCash -= cashStolen;
            cash += cashStolen;
        }

        localStorage.setItem(botHPKey, botHealth);
        localStorage.setItem(botTimeKey, Date.now());

        let perHitCash = 0;
        let perHitXP = 0;
        if (playerDamage > 0) {
            perHitCash = Math.floor(Math.random() * (300 - 90 + 1)) + 90;
            perHitXP = Math.floor(Math.random() * (50 - 25 + 1)) + 25;

            cash += perHitCash;
            xp += perHitXP;
            battleCashEarned += perHitCash;
            battleXPEarned += perHitXP;
        }

        let levelUpMessages = "";
        while (xp >= xpNeeded) {
            xp -= xpNeeded;
            level++;
            skillPoints += 5;
            xpNeeded = 1500 + (level - 1) * 500;
            levelUpMessages += `<br>📈 LEVEL UP! You are now Level ${level} and gained 5 skill points!`;
        }

        let roundMessage = "";

        if (botHealth <= 0 && playerHealth > 0) {
            let finalCash = Math.floor(Math.random() * 4000) + 6000;
            let finalXP = Math.floor(Math.random() * 300) + 200;

            cash += finalCash;
            xp += finalXP;
            battleCashEarned += finalCash;
            battleXPEarned += finalXP;

            while (xp >= xpNeeded) {
                xp -= xpNeeded;
                level++;
                skillPoints += 5;
                xpNeeded = 1500 + (level - 1) * 500;
                levelUpMessages += `<br>📈 LEVEL UP! You are now Level ${level} and gained 5 skill points!`;
            }

            roundMessage = `<strong>💥 YOU KILLED ${botName.toUpperCase()}!</strong><br>
                            You hit ${botName} for <strong>${playerDamage}</strong> and took <strong>${botDamage}</strong> damage.` +
                           (perHitCash > 0 ? `<br>+ $${perHitCash} &nbsp; +${perHitXP} XP (hit reward)` : "") +
                           `<br>💰 Stole $${cashStolen} from ${botName}` +
                           `<br>🪖 ${botName} lost ${defenderArmyLost} soldiers (${defenderArmyLossPercent.toFixed(2)}%)` +
                           `<br>🪖 You lost ${attackerArmyLost} soldiers (${attackerArmyLossPercent.toFixed(2)}%)` +
                           `<br>🏆 Final bonus: +$${finalCash} &nbsp; +${finalXP} XP.` +
                           `<br>🏁 Total this battle: +$${battleCashEarned} &nbsp; +${battleXPEarned} XP.` +
                           levelUpMessages;

            // Show fatality buttons only if killed a real player
            if (botType === 'player') {
                roundMessage += `
                    <br><br>
                    <button id="cutEarBtn" class="fatality-btn">✂️ Cut Ear (Fatality)</button>
                    <button id="pardonBtn" class="fatality-btn">🕊️ Pardon</button>
                `;
            }

            saveBattleResult("Attack", "WIN", botName, playerDamage, botDamage, battleCashEarned, battleXPEarned);
            document.getElementById('attackBtn').disabled = true;

            setTimeout(() => {
                if (botType === 'player') {
                    document.getElementById('cutEarBtn').onclick = () => {
                        cutEar();
                        disableFatalityButtons();
                    };
                    document.getElementById('pardonBtn').onclick = () => {
                        pardonPlayer();
                        disableFatalityButtons();
                    };
                }
            }, 100);

        } else if (playerHealth <= 0 && botHealth > 0) {
            roundMessage = `<strong>💀 YOU WERE KILLED BY ${botName.toUpperCase()}!</strong><br>
                            You hit ${botName} for <strong>${playerDamage}</strong> and took <strong>${botDamage}</strong> damage.` +
                           (perHitCash > 0 ? `<br>+ $${perHitCash} &nbsp; +${perHitXP} XP (hit reward)` : "") +
                           `<br>💰 Stole $${cashStolen} from ${botName}` +
                           `<br>🪖 ${botName} lost ${defenderArmyLost} soldiers (${defenderArmyLossPercent.toFixed(2)}%)` +
                           `<br>🪖 You lost ${attackerArmyLost} soldiers (${attackerArmyLossPercent.toFixed(2)}%)` +
                           `<br>🏁 Total this battle: +$${battleCashEarned} &nbsp; +${battleXPEarned} XP.` +
                           levelUpMessages;

            saveBattleResult("Attack", "LOSS", botName, playerDamage, botDamage, battleCashEarned, battleXPEarned);
            document.getElementById('attackBtn').disabled = true;

        } else if (playerHealth <= 0 && botHealth <= 0) {
            roundMessage = `<strong>⚔️ YOU AND ${botName.toUpperCase()} BOTH FELL!</strong><br>
                            You hit ${botName} for <strong>${playerDamage}</strong> and took <strong>${botDamage}</strong> damage.` +
                          (perHitCash > 0 ? `<br>+ $${perHitCash} &nbsp; +${perHitXP} XP (hit reward)` : "") +
                          `<br>💰 Stole $${cashStolen} from ${botName}` +
                          `<br>🪖 ${botName} lost ${defenderArmyLost} soldiers (${defenderArmyLossPercent.toFixed(2)}%)` +
                          `<br>🪖 You lost ${attackerArmyLost} soldiers (${attackerArmyLossPercent.toFixed(2)}%)` +
                          `<br>🏁 Total this battle: +$${battleCashEarned} &nbsp; +${battleXPEarned} XP.` +
                          levelUpMessages;

            saveBattleResult("Attack", "DRAW", botName, playerDamage, botDamage, battleCashEarned, battleXPEarned);
            document.getElementById('attackBtn').disabled = true;

        } else {
            if (playerDamage === 0 && botDamage === 0) {
                roundMessage = `Both you and ${botName} DODGED! No hits landed.`;
            } else if (playerDamage === 0) {
                roundMessage = `YOU dealt no damage. ${botName} hit you for ${botDamage}.` +
                               (perHitCash > 0 ? `<br>+ $${perHitCash} &nbsp; +${perHitXP} XP (hit reward)` : "");
            } else if (botDamage === 0) {
                roundMessage = `🌀 You DODGED. You hit ${botName} for ${playerDamage}.` +
                               (perHitCash > 0 ? `<br>+ $${perHitCash} &nbsp; +${perHitXP} XP (hit reward)` : "");
            } else {
                roundMessage = `You hit ${botName} for ${playerDamage} and took ${botDamage} damage.` +
                               (perHitCash > 0 ? `<br>+ $${perHitCash} &nbsp; +${perHitXP} XP (hit reward)` : "");
            }
        }

        appendLog(roundMessage);
        updateStats();
        saveStats();
    }

    function disableFatalityButtons() {
        const cutBtn = document.getElementById('cutEarBtn');
        const pardonBtn = document.getElementById('pardonBtn');
        if (cutBtn) cutBtn.disabled = true;
        if (pardonBtn) pardonBtn.disabled = true;
    }

    // Append to battle log with auto-scroll
    function appendLog(message) {
        const log = document.getElementById('battleLog');
        log.innerHTML = message + "<br><br>" + log.innerHTML;
        log.scrollTop = 0;
    }

    // Initialize UI
    updateStats();
</script>

</body>
</html>