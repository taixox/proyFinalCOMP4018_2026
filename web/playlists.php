<?php
require 'db.php';
session_start();
 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
 
$action  = $_GET['action'] ?? 'list';
$message = '';
$error   = '';
 
//se crea playlist
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $playlist_name = trim($_POST['playlist_name']);
    $playlist_type = $_POST['playlist_type'];
    $description   = trim($_POST['description'] ?? '');
 
    if (empty($playlist_name)) {
        $error  = "El nombre de la playlist es obligatorio.";
        $action = 'create';
    } else {
        try {
            $playlist_id = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO Playlist (playlist_id, playlist_name, playlist_type, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$playlist_id, $playlist_name, $playlist_type, $_SESSION['user_id']]);
 
            if ($playlist_type === 'public') {
                $stmt = $pdo->prepare("INSERT INTO PublicPlaylist (playlist_id, description) VALUES (?, ?)");
                $stmt->execute([$playlist_id, $description]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO PrivatePlaylist (playlist_id) VALUES (?)");
                $stmt->execute([$playlist_id]);
            }
            $message = "Playlist creada exitosamente.";
            $action  = 'list';
        } catch (PDOException $e) {
            $error  = "Error al crear playlist: " . $e->getMessage();
            $action = 'create';
        }
    }
}
 
//para eliminar playlists
if (isset($_GET['delete'])) {
    $playlist_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT playlist_id FROM Playlist WHERE playlist_id = ? AND user_id = ?");
        $stmt->execute([$playlist_id, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM PlaylistTrack WHERE playlist_id = ?")->execute([$playlist_id]);
            $pdo->prepare("DELETE FROM PublicPlaylist WHERE playlist_id = ?")->execute([$playlist_id]);
            $pdo->prepare("DELETE FROM PrivatePlaylist WHERE playlist_id = ?")->execute([$playlist_id]);
            $pdo->prepare("DELETE FROM Playlist WHERE playlist_id = ?")->execute([$playlist_id]);
            $message = "Playlist eliminada.";
        }
    } catch (PDOException $e) {
        $error = "Error al eliminar: " . $e->getMessage();
    }
}
 

$stmt = $pdo->prepare("
    SELECT p.playlist_id, p.playlist_name, p.playlist_type,
           pp.description,
           COUNT(pt.track_id) as track_count
    FROM Playlist p
    LEFT JOIN PublicPlaylist pp ON p.playlist_id = pp.playlist_id
    LEFT JOIN PlaylistTrack pt ON p.playlist_id = pt.playlist_id
    WHERE p.user_id = ?
    GROUP BY p.playlist_id, p.playlist_name, p.playlist_type, pp.description
    ORDER BY p.playlist_name
");
$stmt->execute([$_SESSION['user_id']]);
$playlists = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Playlists - Spotify DB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <h1>Mis Playlists</h1>
 
    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
 
    <?php if ($action === 'create'): ?>
    <h2>Crear nueva playlist</h2>
    <form method="POST" action="playlists.php?action=create">
        <label>Nombre *</label>
        <input type="text" name="playlist_name" required>
 
        <label>Tipo</label>
        <select name="playlist_type" id="playlist_type" onchange="toggleDescription()">
            <option value="public">Publica</option>
            <option value="private">Privada</option>
        </select>
 
        <div id="description_div">
            <label>Descripcion (solo playlists publicas)</label>
            <textarea name="description"></textarea>
        </div>
 
        <button type="submit">Crear playlist</button>
        <a href="playlists.php" class="btn btn-danger">Cancelar</a>
    </form>
    <script>
        function toggleDescription() {
            const type = document.getElementById('playlist_type').value;
            document.getElementById('description_div').style.display = type === 'public' ? 'block' : 'none';
        }
    </script>
 
    <?php else: ?>
    <a href="playlists.php?action=create" class="btn">+ Crear playlist</a>
 
    <?php if (empty($playlists)): ?>
        <p style="margin-top:20px;">No tienes playlists todavia. Crea una!</p>
    <?php else: ?>
    <div class="card-grid" style="margin-top:20px;">
        <?php foreach ($playlists as $pl): ?>
        <div class="card">
            <h3><?= htmlspecialchars($pl['playlist_name']) ?></h3>
            <p><?= $pl['playlist_type'] === 'public' ? 'Publica' : 'Privada' ?> &bull; <?= $pl['track_count'] ?> tracks</p>
            <?php if ($pl['description']): ?>
                <p style="margin-top:6px; font-style:italic;"><?= htmlspecialchars($pl['description']) ?></p>
            <?php endif; ?>
            <div style="display:flex; gap:8px; margin-top:10px;">
                <a href="playlist_track.php?id=<?= urlencode($pl['playlist_id']) ?>">Ver</a>
                <a href="playlists.php?delete=<?= urlencode($pl['playlist_id']) ?>"
                   onclick="return confirm('Eliminar esta playlist?')"
                   style="color:#e74c3c;">Eliminar</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>