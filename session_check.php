<?php
// auth.php - Include this at the top of every restricted page

session_start();

// Prevent browser caching of restricted pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if player is logged in
if (empty($_SESSION['player_id'])) {
    header("Location: login.php");
    exit;
}

// --- Load database ---
$dbPath = __DIR__ . '/db.php';
if (!file_exists($dbPath)) {
    // Try one folder up (if page includes from root)
    $dbPath = __DIR__ . '/../api/db.php';
}
require $dbPath;

// --- Load latest player data ---
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

// If somehow the player no longer exists, logout
if (!$player) {
    header("Location: logout.php");
    exit;
}
?>
<!-- Back-button prevention -->
<script>
// Redirect if page is loaded from browser history (back/forward)
window.addEventListener('pageshow', function(event) {
    if (event.persisted || (performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
        window.location.href = 'login.php';
    }
});
</script>
