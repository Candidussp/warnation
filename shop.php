<?php
session_start();
require 'api/db.php';

if (!isset($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}
$playerId = (int) $_SESSION['player_id'];

// --- Load player ---
$stmt = $pdo->prepare("SELECT id, level, cash, gold FROM players WHERE id = ?");
$stmt->execute([$playerId]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$player) die("Player not found");

// --- Shop Items ---
$unitsData = [
    "jet1" => ["name"=>"Jet Level 1", "atk"=>150, "def"=>30, "cost"=>10, "levelReq"=>0],
    "jet2" => ["name"=>"Jet Level 2", "atk"=>300, "def"=>60, "cost"=>16, "levelReq"=>10],
    "jet3" => ["name"=>"Jet Level 3", "atk"=>600, "def"=>120, "cost"=>25, "levelReq"=>20],

    "tank1"=> ["name"=>"Tank Level 1", "atk"=>30, "def"=>150, "cost"=>20, "levelReq"=>0],
    "tank2"=> ["name"=>"Tank Level 2", "atk"=>60, "def"=>300, "cost"=>32, "levelReq"=>12],
    "tank3"=> ["name"=>"Tank Level 3", "atk"=>120, "def"=>600, "cost"=>51, "levelReq"=>20],

    "ship1"=> ["name"=>"Ship Level 1", "atk"=>100, "def"=>100, "cost"=>15, "levelReq"=>0],
    "ship2"=> ["name"=>"Ship Level 2", "atk"=>200, "def"=>200, "cost"=>24, "levelReq"=>15],
    "ship3"=> ["name"=>"Ship Level 3", "atk"=>400, "def"=>400, "cost"=>38, "levelReq"=>20],
];

// --- Load owned units ---
$stmt = $pdo->prepare("SELECT unit_type, quantity FROM player_units WHERE player_id = ?");
$stmt->execute([$playerId]);
$owned = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Handle Purchase ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unit'], $_POST['qty'])) {
    $unit = $_POST['unit'];
    $qty  = max(1, (int)$_POST['qty']);
    if (isset($unitsData[$unit])) {
        $u = $unitsData[$unit];
        $cost = $u['cost'] * $qty;

        if ($player['level'] < $u['levelReq']) {
            $msg = "❌ Requires level {$u['levelReq']}";
        } elseif ($player['cash'] < $cost) {
            $msg = "❌ Not enough cash!";
        } else {
            // Deduct cash
            $stmt = $pdo->prepare("UPDATE players SET cash = cash - ? WHERE id = ?");
            $stmt->execute([$cost, $playerId]);

            // Add/Update units
            $stmt = $pdo->prepare("INSERT INTO player_units (player_id, unit_type, quantity)
                                   VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
            $stmt->execute([$playerId, $unit, $qty]);

            $msg = "✅ Bought {$qty} x {$u['name']}";

            // Refresh player & owned
            $stmt = $pdo->prepare("SELECT cash FROM players WHERE id = ?");
            $stmt->execute([$playerId]);
            $player['cash'] = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT unit_type, quantity FROM player_units WHERE player_id = ?");
            $stmt->execute([$playerId]);
            $owned = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>UNITS SHOP - WARNATION</title>
<style>
body { background:#001f3f; color:#e6e6e6; font-family:Arial; text-align:center; padding:20px; }
a { color:#ffd34d; text-decoration:none; font-weight:bold; }
h1 { color:gold; }
.shop-grid { display:flex; flex-wrap:wrap; justify-content:center; gap:20px; }
.card { background:#070707; border:1px solid #333; border-radius:8px; width:250px; padding:12px; text-align:left; }
.card h2 { color:#ffd34d; font-size:18px; margin:0 0 8px 0; }
.row { display:flex; justify-content:space-between; margin:4px 0; }
button { background:#ffd34d; border:0; padding:6px 10px; border-radius:5px; cursor:pointer; font-weight:bold; }
button:disabled { background:#444; color:#999; cursor:not-allowed; }
#msg { margin-top:15px; font-weight:bold; color:cyan; }
</style>
</head>
<body>

<a href="Homepage.php">← HOME</a>
<h1>Units Shop</h1>

<div>
  Level: <b><?= $player['level'] ?></b> | 
  💵 Cash: <b><?= number_format($player['cash']) ?></b>
</div>

<?php if ($msg): ?>
<div id="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST">
<div class="shop-grid">
<?php foreach ($unitsData as $id=>$u): ?>
  <div class="card">
    <h2><?= $u['name'] ?></h2>
    <div class="row"><span>ATK:</span><span><?= $u['atk'] ?></span></div>
    <div class="row"><span>DEF:</span><span><?= $u['def'] ?></span></div>
    <div class="row"><span>Cost:</span><span><?= $u['cost'] ?> Cash</span></div>
    <div class="row"><span>Owned:</span><span><?= $owned[$id] ?? 0 ?></span></div>
    <?php if ($player['level'] < $u['levelReq']): ?>
      <div style="color:#f55;">Requires Lvl <?= $u['levelReq'] ?></div>
      <button disabled>Locked</button>
    <?php else: ?>
      <input type="number" name="qty" value="1" min="1" style="width:60px;">
      <button name="unit" value="<?= $id ?>">Buy</button>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
</form>

</body>
</html>
