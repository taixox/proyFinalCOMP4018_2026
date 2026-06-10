<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

//estadisticas generales
$total_tracks  = $pdo->query("SELECT COUNT(*) FROM Track")->fetchColumn();
$total_artists = $pdo->query("SELECT COUNT(*) FROM Artist")->fetchColumn();
$total_albums  = $pdo->query("SELECT COUNT(*) FROM Album")->fetchColumn();
$total_genres  = $pdo->query("SELECT COUNT(*) FROM Genre")->fetchColumn();
$total_playlists = $pdo->prepare("SELECT COUNT(*) FROM Playlist WHERE user_id = ?");
$total_playlists->execute([$_SESSION['user_id']]);
$total_playlists = $total_playlists->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Spotify DB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>

    <h2>Estadisticas de la base de datos</h2>
    <div class="card-grid">
        <div class="card">
            <h3>Tracks</h3>
            <p><?= number_format($total_tracks) ?> canciones</p>
            <a href="tracks.php">Ver tracks</a>
        </div>
        <div class="card">
            <h3>Artistas</h3>
            <p><?= number_format($total_artists) ?> artistas</p>
        </div>
        <div class="card">
            <h3>Albums</h3>
            <p><?= number_format($total_albums) ?> albums</p>
        </div>
        <div class="card">
            <h3>Generos</h3>
            <p><?= number_format($total_genres) ?> generos</p>
        </div>
        <div class="card">
            <h3>Mis Playlists</h3>
            <p><?= number_format($total_playlists) ?> playlists</p>
            <a href="playlists.php">Ver playlists</a>
        </div>
    </div>

    <h2>Accesos rapidos</h2>
    <div class="card-grid">
        <div class="card">
            <h3>Consultas</h3>
            <p>Ver los queries del proyecto</p>
            <a href="queries.php">Ver queries</a>
        </div>
        <div class="card">
            <h3>Insertar Track</h3>
            <p>Anadir una nueva cancion</p>
            <a href="tracks.php?action=insert">Insertar</a>
        </div>
        <div class="card">
            <h3>Crear Playlist</h3>
            <p>Crear una nueva playlist</p>
            <a href="playlists.php?action=create">Crear</a>
        </div>
    </div>
</div>
</body>
</html>