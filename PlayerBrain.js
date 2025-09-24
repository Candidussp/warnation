// Returns an object with menu item lock status for homepage
function getHomepageMenuLocks() {
    const player = getPlayer();
    const locked = {};
    const menuItems = [
        'war.php', 'missions.php', 'production.php', 'units.php', 'structures.php',
        'blackmarket.php', 'officersmess.php', 'profile.php', 'alliance.php',
        'halloffame.php', 'chats.php', 'events.php', 'settings.php', 'support'
    ];
    menuItems.forEach(item => {
        if (item === 'war.php' || item === 'missions.php') {
            locked[item] = false;
        } else if (['production.php', 'blackmarket.php', 'officersmess.php'].includes(item)) {
            locked[item] = player.level < 3;
        } else {
            locked[item] = player.level < 2;
        }
    });
    return locked;
}
// WARNATION - Player Brain (Full Merged Version)

function initializePlayer() {
    if (!localStorage.getItem('playerStats')) {
        const defaultStats = {
            health: 300,
            maxHealth: 300,
            energy: 500,
            maxEnergy: 500,
            ammo: 20,
            maxAmmo: 20,
            cash: 10000,
            gold: 0,
            xp: 0,
            level: 1,
            skillPoints: 5,
            critChance: 0,
            dodgeChance: 0,
            army: 1000,
            totalAttack: 0,
            totalDefense: 0
        };
        localStorage.setItem('playerStats', JSON.stringify(defaultStats));
    }

    const exampleBots = ['Enemy Bot', 'Bot Alpha', 'Bot Bravo'];
    exampleBots.forEach(bot => {
        if (!localStorage.getItem(`bot_${bot}_army`)) localStorage.setItem(`bot_${bot}_army`, 800);
        if (!localStorage.getItem(`bot_${bot}_cash`)) localStorage.setItem(`bot_${bot}_cash`, 5000);
    });

    let ach = JSON.parse(localStorage.getItem('achievements')) || {};
    const defaultAchievements = {
        kills: 0,
        deaths: 0,
        questsCompleted: 0,
        earsCut: 0,
        playersPardoned: 0,
        reinforcementsSent: 0,
        victories: 0,
        xpEarned: 0,
        sanctionsExecuted: 0,
        totalCashEarned: 0,
        firstLoginDate: new Date().toISOString().split("T")[0],
        daysInGame: 1
    };
    ach = { ...defaultAchievements, ...ach };

    if (ach.firstLoginDate) {
        let first = new Date(ach.firstLoginDate);
        let now = new Date();
        ach.daysInGame = Math.floor((now - first) / (1000 * 60 * 60 * 24)) + 1;
    }

    localStorage.setItem('achievements', JSON.stringify(ach));
    initializeStructures();
}

initializePlayer();

// ================= PLAYER FUNCTIONS =================
function getPlayer() {
    return JSON.parse(localStorage.getItem('playerStats')) || {};
}
function savePlayer(player) {
    localStorage.setItem('playerStats', JSON.stringify(player));
}

function getAchievements() {
    return JSON.parse(localStorage.getItem('achievements')) || {};
}
function saveAchievements(ach) {
    localStorage.setItem('achievements', JSON.stringify(ach));
    checkStructureUnlocks();
}

// ================= BOT FUNCTIONS =================
function getBotArmy(botName) {
    return parseInt(localStorage.getItem(`bot_${botName}_army`)) || 0;
}
function saveBotArmy(botName, amount) {
    localStorage.setItem(`bot_${botName}_army`, amount);
}
function getBotCash(botName) {
    return parseInt(localStorage.getItem(`bot_${botName}_cash`)) || 0;
}
function saveBotCash(botName, amount) {
    localStorage.setItem(`bot_${botName}_cash`, amount);
}

const exampleBots = ['Enemy Bot', 'Bot Alpha', 'Bot Bravo'];
setInterval(() => {
    exampleBots.forEach(bot => {
        let hp = getBotArmy(bot);
        hp += 10;
        saveBotArmy(bot, hp);
    });
}, 180000);

// ================= ACHIEVEMENT FUNCTIONS =================
function addKill() { let ach = getAchievements(); ach.kills++; saveAchievements(ach); }
function addDeath() { let ach = getAchievements(); ach.deaths++; saveAchievements(ach); }
function completeQuest() { let ach = getAchievements(); ach.questsCompleted++; saveAchievements(ach); }
function cutEar() { let ach = getAchievements(); ach.earsCut++; saveAchievements(ach); }
function sendReinforcement() { let ach = getAchievements(); ach.reinforcementsSent++; saveAchievements(ach); }
function addVictory() { let ach = getAchievements(); ach.victories++; saveAchievements(ach); }
function addXpRecord(amount) { let ach = getAchievements(); ach.xpEarned += amount; saveAchievements(ach); }
function executeSanction() { let ach = getAchievements(); ach.sanctionsExecuted++; saveAchievements(ach); }
function addCashEarned(amount) { let ach = getAchievements(); ach.totalCashEarned += amount; saveAchievements(ach); }

// ================= LEVEL FUNCTIONS =================
// XP curve: level 50 requires ~12M XP
function calculateXpNeeded(level) {
    return Math.floor(1000 * (level ** 2.3));
}

function checkLevelUp() {
    let player = getPlayer();
    let xp = player.xp ?? 0;
    let level = player.level ?? 1;
    let leveledUp = false;

    while (xp >= calculateXpNeeded(level)) {
        xp -= calculateXpNeeded(level);
        level++;
        player.skillPoints = (player.skillPoints ?? 0) + 5;

        // Recalculate max stats per level
        player.maxHealth = 300 + ((level - 1) * 5);
        player.maxEnergy = 500 + ((level - 1) * 5);
        player.maxAmmo = 20 + ((level - 1) * 1);

    // Ensure current stats grow with max
    player.health = Math.min(player.health, player.maxHealth);
    player.energy = Math.min(player.energy, player.maxEnergy);
    player.ammo = Math.min(player.ammo, player.maxAmmo);

        leveledUp = true;
    }

    player.xp = xp;
    player.level = level;
    savePlayer(player);
    if (leveledUp) {
        localStorage.setItem("levelUpNotice", "true");
        setTimeout(() => {
            localStorage.removeItem("levelUpNotice");
        }, 5000); // Clear after 5 seconds
    }
}

function gainXP(amount) {
    let player = getPlayer();
    player.xp = (player.xp || 0) + amount;
    savePlayer(player);
    addXpRecord(amount);
    checkLevelUp();
}

// ================= STRUCTURES SYSTEM =================
function initializeStructures() {
    const randomReq = (key, min, max) => {
        const saved = localStorage.getItem(`unlock_${key}`);
        if (saved) return parseInt(saved);
        const val = Math.floor(Math.random() * (max - min + 1)) + min;
        localStorage.setItem(`unlock_${key}`, val);
        return val;
    };

    const defaultStructures = [
        { id: "predator", name: "Predator", level: 0, unlocked: false, requirement: { kills: randomReq('predatorKills',30,50) }, effectPerLevel: { attack: 2.5 } },
        { id: "ironDome", name: "Iron Dome", level: 0, unlocked: false, requirement: { reinforcementsSent: randomReq('ironDomeReinforcements',50,100) }, effectPerLevel: { defense: 2.5 } },
        { id: "razor", name: "Razor", level: 0, unlocked: false, requirement: { earsCut: randomReq('razorEarsCut',10,50) }, effectPerLevel: { earCutChance: 3 } },
        { id: "tripWire", name: "Trip Wire", level: 0, unlocked: false, requirement: { sanctionsExecuted: randomReq('tripWireSanctions',20,50) }, effectPerLevel: { trapChance: 3.5 } },
        { id: "blackMarketDepot", name: "Black Market Depot", level: 0, unlocked: false, requirement: { level: randomReq('blackMarketDepotLevel',15,20) }, effectPerLevel: { cashBonus: 5 } }
    ];

    let structures = JSON.parse(localStorage.getItem('playerStructures')) || {};
    defaultStructures.forEach(s => { if (!structures[s.id]) structures[s.id] = s; });
    localStorage.setItem('playerStructures', JSON.stringify(structures));

    checkStructureUnlocks();
}

function checkStructureUnlocks() {
    let ach = getAchievements();
    let player = getPlayer();
    let structures = JSON.parse(localStorage.getItem('playerStructures')) || {};
    let changed = false;

    for (let id in structures) {
        let s = structures[id];
        if (!s.unlocked) {
            let reqKey = Object.keys(s.requirement)[0];
            let reqVal = s.requirement[reqKey];
            let currentVal = (reqKey === 'level') ? player.level : ach[reqKey] || 0;
            if (currentVal >= reqVal) {
                s.unlocked = true;
                changed = true;
            }
        }
    }

    if (changed) localStorage.setItem('playerStructures', JSON.stringify(structures));
}

function getUpgradeCost(structure) {
    if (structure.level >= 10) return null;
    if (structure.level === 9) return { gold: 2000 };
    let cost = 200000 * Math.pow(1.3, structure.level);
    return { cash: Math.round(cost) };
}

function upgradeStructure(structureId) {
    let player = getPlayer();
    let structures = JSON.parse(localStorage.getItem('playerStructures')) || {};
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
    localStorage.setItem('playerStructures', JSON.stringify(structures));
    return true;
}

// ================= REGEN SYSTEM =================
setInterval(() => {
    let player = getPlayer();
    // Always recalculate max caps based on current level
    player.maxHealth = 300 + ((player.level - 1) * 5);
    player.maxEnergy = 500 + ((player.level - 1) * 5);
    player.maxAmmo = 20 + ((player.level - 1) * 1);

    // Regen 10 health, 10 energy, 1 ammo every 3 minutes until max
    if (player.health < player.maxHealth) player.health = Math.min(player.health + 10, player.maxHealth);
    if (player.energy < player.maxEnergy) player.energy = Math.min(player.energy + 10, player.maxEnergy);
    if (player.ammo < player.maxAmmo) player.ammo = Math.min(player.ammo + 1, player.maxAmmo);

    savePlayer(player);
}, 180000);

// ================= PAGE ACCESS CONTROL =================
function checkLevelRequirement(requiredLevel) {
    let player = getPlayer();
    if (player.level < requiredLevel) {
        alert("⚠️ You need to be at least Level " + requiredLevel + " to access this page.");
        window.location.href = "Homepage.php";
        return false;
    }
    return true;
}

// ================= COMBINED ATTACK/DEFENSE HELPERS =================
// Scan localStorage for module totals and sum them into combinedTotalAttack/combinedTotalDefense.
// Priority: keys starting with "warnation_" and ending with "totalAttack"/"totalDefense".
// Fallback: any key ending with "totalAttack"/"totalDefense".
function computeCombinedTotals() {
    const keys = Object.keys(localStorage);
    // find attack/def keys (prefer warnation_ prefixed keys)
    let atkKeys = keys.filter(k => /^warnation_.*totalAttack$/i.test(k));
    let defKeys = keys.filter(k => /^warnation_.*totalDefense$/i.test(k));

    if (atkKeys.length === 0) atkKeys = keys.filter(k => /totalAttack$/i.test(k));
    if (defKeys.length === 0) defKeys = keys.filter(k => /totalDefense$/i.test(k));

    // sum unique numeric values to reduce obvious duplicates
    const seenAtkVals = new Set();
    const seenDefVals = new Set();
    let totalAtk = 0;
    let totalDef = 0;

    atkKeys.forEach(k => {
        try {
            const raw = localStorage.getItem(k);
            const val = Number(raw === null ? 0 : (raw.trim().startsWith('{') || raw.trim().startsWith('[') ? JSON.parse(raw) : raw));
            if (!isNaN(val) && val !== 0 && !seenAtkVals.has(val)) {
                totalAtk += val;
                seenAtkVals.add(val);
            }
        } catch (e) { /* ignore parse errors */ }
    });

    defKeys.forEach(k => {
        try {
            const raw = localStorage.getItem(k);
            const val = Number(raw === null ? 0 : (raw.trim().startsWith('{') || raw.trim().startsWith('[') ? JSON.parse(raw) : raw));
            if (!isNaN(val) && val !== 0 && !seenDefVals.has(val)) {
                totalDef += val;
                seenDefVals.add(val);
            }
        } catch (e) { /* ignore parse errors */ }
    });

    localStorage.setItem('combinedTotalAttack', String(totalAtk));
    localStorage.setItem('combinedTotalDefense', String(totalDef));
    return { combinedTotalAttack: totalAtk, combinedTotalDefense: totalDef };
}

// Convenience getter (reads cached combined values or computes if missing)
function getCombinedTotals() {
    const atkRaw = localStorage.getItem('combinedTotalAttack');
    const defRaw = localStorage.getItem('combinedTotalDefense');
    if (atkRaw !== null && defRaw !== null) {
        return { combinedTotalAttack: Number(atkRaw) || 0, combinedTotalDefense: Number(defRaw) || 0 };
    }
    return computeCombinedTotals();
}

// Alias for external callers/pages to trigger recompute
function updateCombinedTotals() {
    return computeCombinedTotals();
}
