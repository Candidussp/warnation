<?php
// /api/session_check.php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if logged in
if (empty($_SESSION['player_id'])) {
    header("Location: ../login.php");
    exit;
}

// Load latest player data
require __DIR__ . '/db.php';
$stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
$stmt->execute([$_SESSION['player_id']]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

// If somehow the player no longer exists, force logout
if (!$player) {
    header("Location: ../logout.php");
    exit;
}
?>
<!-- Back-button prevention -->
<script>
// Redirect if page is loaded from browser history (back/forward)
window.addEventListener('pageshow', function(event) {
    if (event.persisted || window.performance.getEntriesByType('navigation')[0].type === 'back_forward') {
        window.location.href = '../login.php';
    }
});
</script>
