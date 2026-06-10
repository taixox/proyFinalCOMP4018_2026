<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$playlist_id = $_GET['id'] ?? '';
$message = '';
$error   = '';

if (!$playlist_id) {
    header("Location: playlists.php");
    exit;
}

//Verificar que playlist existe y pertenece al usuario
$stmt = $pdo->prepare("SELECT p.*, pp.description FROM Playlist p
                       LEFT JOIN PublicPlaylist pp ON p.playlist_id = pp.playlist_id
                       WHERE p.playlist_id = ? AND p.user_id = ?");
$stmt->execute([$playlist_id, $_SESSION['user_id']]);
$playlist = $stmt->fetch();

if (!$playlist) {
    header("Location: playlists.php");
    exit;
}

//anadir tracks a playlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_id'])) {
    $track_id = trim($_POST['track_id']);
    $position = (int)$_POST['position'];
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO PlaylistTrack (playlist_id, track_id, position) VALUES (?, ?, ?)");
        $stmt->execute([$playlist_id, $track_id, $position]);
        $message = "Track añadido exitosamente.";
    } catch (PDOException $e) {
        $error = "Error al añadir track: " . $e->getMessage();
    }
}

// Eliminar track de playlist
if (isset($_GET['remove'])) {
    $track_id = $_GET['remove'];
    $pdo->prepare("DELETE FROM PlaylistTrack WHERE playlist_id = ? AND track_id = ?")->execute([$playlist_id, $track_id]);
    $message = "Track eliminado de la playlist.";
}

//obtener tracks de playlist
$tracks_in_playlist = $pdo->prepare("
    SELECT t.track_id, t.track_name, t.popularity, t.duration_ms,
           a.album_name, pt.position, pt.added_at,
           GROUP_CONCAT(ar.artist_name SEPARATOR ', ') as artists
    FROM PlaylistTrack pt
    JOIN Track t ON pt.track_id = t.track_id
    JOIN Album a ON t.album_id = a.album_id
    LEFT JOIN TrackArtist ta ON t.track_id = ta.track_id
    LEFT JOIN Artist ar ON ta.artist_id = ar.artist_id
    WHERE pt.playlist_id = ?
    GROUP BY t.track_id, t.track_name, t.popularity, t.duration_ms, a.album_name, pt.position, pt.added_at
    ORDER BY pt.position ASC
");
$tracks_in_playlist->execute([$playlist_id]);
$playlist_tracks = $tracks_in_playlist->fetchAll();

//buscar tracks
$search  = trim($_GET['search'] ?? '');
$results = [];
if ($search) {
    $stmt = $pdo->prepare("
        SELECT t.track_id, t.track_name, t.popularity, a.album_name,
               GROUP_CONCAT(ar.artist_name SEPARATOR ', ') as artists
        FROM Track t
        JOIN Album a ON t.album_id = a.album_id
        LEFT JOIN TrackArtist ta ON t.track_id = ta.track_id
        LEFT JOIN Artist ar ON ta.artist_id = ar.artist_id
        WHERE t.track_name LIKE ?
        GROUP BY t.track_id, t.track_name, t.popularity, a.album_name
        ORDER BY t.popularity DESC
        LIMIT 20
    ");
    $stmt->execute(["%$search%"]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($playlist['playlist_name']) ?> - Spotify DB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <h1><?= htmlspecialchars($playlist['playlist_name']) ?></h1>
    <p><?= $playlist['playlist_type'] === 'public' ? 'Playlist Publica' : 'Playlist Privada' ?></p>
    <?php if ($playlist['description']): ?>
        <p style="font-style:italic; color:#666;"><?= htmlspecialchars($playlist['description']) ?></p>
    <?php endif; ?>

    <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <!--buscar y anadir tracks -->
    <h2>Añadir tracks</h2>
    <form method="GET" action="playlist_track.php" style="display:flex; gap:8px;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($playlist_id) ?>">
        <input type="text" name="search" placeholder="Buscar track por nombre..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
        <button type="submit">Buscar</button>
    </form>

    <?php if (!empty($results)): ?>
    <table>
        <thead>
            <tr><th>Nombre</th><th>Artistas</th><th>Album</th><th>Popularidad</th><th>Accion</th></tr>
        </thead>
        <tbody>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['track_name']) ?></td>
                <td><?= htmlspecialchars($r['artists'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['album_name']) ?></td>
                <td><?= $r['popularity'] ?></td>
                <td>
                    <form method="POST" action="playlist_track.php?id=<?= urlencode($playlist_id) ?>">
                        <input type="hidden" name="track_id" value="<?= htmlspecialchars($r['track_id']) ?>">
                        <input type="number" name="position" value="<?= count($playlist_tracks) + 1 ?>" style="width:60px;">
                        <button type="submit">Añadir</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- tracks en playlist -->
    <h2>Tracks en la playlist (<?= count($playlist_tracks) ?>)</h2>
    <?php if (empty($playlist_tracks)): ?>
        <p>No hay tracks en esta playlist todavia.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>#</th><th>Nombre</th><th>Artistas</th><th>Album</th><th>Duracion</th><th>Accion</th></tr>
        </thead>
        <tbody>
            <?php foreach ($playlist_tracks as $t): ?>
            <tr>
                <td><?= $t['position'] ?></td>
                <td><?= htmlspecialchars($t['track_name']) ?></td>
                <td><?= htmlspecialchars($t['artists'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['album_name']) ?></td>
                <td><?= round($t['duration_ms'] / 60000, 2) ?> min</td>
                <td>
                    <a href="playlist_track.php?id=<?= urlencode($playlist_id) ?>&remove=<?= urlencode($t['track_id']) ?>"
                       onclick="return confirm('Eliminar de la playlist?')"
                       style="color:#e74c3c;">Quitar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <a href="playlists.php" class="btn btn-danger">Volver a playlists</a>
    </div>
</div>
</body>
</html>