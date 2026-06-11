<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$popularity_filter = isset($_GET['popularity']) ? (int)$_GET['popularity'] : 80;
$energy_filter = isset($_GET['energy']) ? (float)$_GET['energy'] : 0.6;


// Query 1 - JOIN 3+ tablas: Tracks con artistas y album
$q1 = $pdo->prepare("
    SELECT t.track_name, t.popularity, a.album_name,
           GROUP_CONCAT(DISTINCT ar.artist_name ORDER BY ar.artist_name SEPARATOR ', ') as artists
    FROM Track t
    JOIN Album a ON t.album_id = a.album_id
    JOIN TrackArtist ta ON t.track_id = ta.track_id
    JOIN Artist ar ON ta.artist_id = ar.artist_id
    WHERE t.popularity > ?
    GROUP BY t.track_id, t.track_name, t.popularity, a.album_name
    ORDER BY t.popularity DESC
    LIMIT 15
");
$q1->execute([$popularity_filter]);
$results_q1 = $q1->fetchAll();


// Query 2 - GROUP BY / HAVING: Generos con energia promedio alta
$q2 = $pdo->prepare("
    SELECT g.genre_name,
           COUNT(DISTINCT tg.track_id) as total_tracks,
           ROUND(AVG(af.energy), 3) as avg_energy,
           ROUND(AVG(af.danceability), 3) as avg_danceability,
           ROUND(AVG(t.popularity), 1) as avg_popularity
    FROM Genre g
    JOIN TrackGenre tg ON g.genre_id = tg.genre_id
    JOIN AudioFeatures af ON tg.track_id = af.track_id
    JOIN Track t ON tg.track_id = t.track_id
    GROUP BY g.genre_id, g.genre_name
    HAVING AVG(af.energy) > ?
    ORDER BY avg_energy DESC
");
$q2->execute([$energy_filter]);
$results_q2 = $q2->fetchAll();


// Query 3 - WITH (CTE): Tracks mas populares que promedio de su genero
$q3 = $pdo->query("
    WITH GenreAvg AS (
        SELECT tg.genre_id,
               AVG(t.popularity) as avg_popularity
        FROM TrackGenre tg
        JOIN Track t ON tg.track_id = t.track_id
        GROUP BY tg.genre_id
    )
    SELECT t.track_name, t.popularity,
           g.genre_name,
           ROUND(ga.avg_popularity, 1) as genre_avg,
           ROUND(t.popularity - ga.avg_popularity, 1) as above_avg
    FROM Track t
    JOIN TrackGenre tg ON t.track_id = tg.track_id
    JOIN Genre g ON tg.genre_id = g.genre_id
    JOIN GenreAvg ga ON tg.genre_id = ga.genre_id
    WHERE t.popularity > ga.avg_popularity
    ORDER BY above_avg DESC
    LIMIT 15
");
$results_q3 = $q3->fetchAll();


//Query 4 - Agregacion: Top 10 artistas con mas tracks
$q4 = $pdo->query("
    SELECT ar.artist_name,
           COUNT(ta.track_id) as total_tracks,
           ROUND(AVG(t.popularity), 1) as avg_popularity,
           MAX(t.popularity) as max_popularity
    FROM Artist ar
    JOIN TrackArtist ta ON ar.artist_id = ta.artist_id
    JOIN Track t ON ta.track_id = t.track_id
    GROUP BY ar.artist_id, ar.artist_name
    ORDER BY total_tracks DESC
    LIMIT 10
");
$results_q4 = $q4->fetchAll();


// query 5 - Albums con tracks explicitos
$q5 = $pdo->query("
    SELECT a.album_name,
           COUNT(t.track_id) as total_tracks,
           SUM(t.explicit) as explicit_tracks,
           ROUND(AVG(t.popularity), 1) as avg_popularity
    FROM Album a
    JOIN Track t ON a.album_id = t.album_id
    GROUP BY a.album_id, a.album_name
    HAVING SUM(t.explicit) > 0
    ORDER BY explicit_tracks DESC
    LIMIT 15
");
$results_q5 = $q5->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Queries - Spotify DB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .query-section {
            margin-bottom: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
        }
        .query-sql {
            background: #222;
            color: #1db954;
            padding: 14px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            margin: 12px 0;
        }
        .query-label {
            display: inline-block;
            background: #1db954;
            color: #fff;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <h1>Queries</h1>
    <p style="color:#666; margin-bottom:20px;">5 consultas SQL sobre la base de datos de Spotify.</p>

    <!-- query 1 -->
    <div class="query-section">
        <span class="query-label">JOIN 3+ tablas</span>
        <h2>Query 1 — Tracks con artistas y album</h2>
        <p>Tracks con popularidad mayor al umbral, junto a sus artistas y album. JOIN entre Track, Album, Artist y TrackArtist.</p>
        <div class="query-sql">SELECT t.track_name, t.popularity, a.album_name,
       GROUP_CONCAT(DISTINCT ar.artist_name) as artists
FROM Track t
JOIN Album a ON t.album_id = a.album_id
JOIN TrackArtist ta ON t.track_id = ta.track_id
JOIN Artist ar ON ta.artist_id = ar.artist_id
WHERE t.popularity > [umbral]
GROUP BY t.track_id
ORDER BY t.popularity DESC
LIMIT 15</div>
        <form method="GET" action="queries.php" style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
            <label>Popularidad minima:</label>
            <input type="number" name="popularity" min="0" max="100" value="<?= $popularity_filter ?>" style="width:80px;">
            <input type="hidden" name="energy" value="<?= $energy_filter ?>">
            <button type="submit">Filtrar</button>
        </form>
        <?php if (empty($results_q1)): ?>
            <p>No hay resultados para ese filtro.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Track</th><th>Artistas</th><th>Album</th><th>Popularidad</th></tr></thead>
            <tbody>
                <?php foreach ($results_q1 as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['track_name']) ?></td>
                    <td><?= htmlspecialchars($r['artists']) ?></td>
                    <td><?= htmlspecialchars($r['album_name']) ?></td>
                    <td><?= $r['popularity'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Query 2 -->
    <div class="query-section">
        <span class="query-label">GROUP BY / HAVING</span>
        <h2>Query 2 — Generos con energia promedio alta</h2>
        <p>Agrupa tracks por genero y filtra los que tienen energia promedio mayor al umbral. Incluye danceability y popularidad promedio.</p>
        <div class="query-sql">SELECT g.genre_name,
       COUNT(DISTINCT tg.track_id) as total_tracks,
       ROUND(AVG(af.energy), 3) as avg_energy,
       ROUND(AVG(af.danceability), 3) as avg_danceability,
       ROUND(AVG(t.popularity), 1) as avg_popularity
FROM Genre g
JOIN TrackGenre tg ON g.genre_id = tg.genre_id
JOIN AudioFeatures af ON tg.track_id = af.track_id
JOIN Track t ON tg.track_id = t.track_id
GROUP BY g.genre_id, g.genre_name
HAVING AVG(af.energy) > [umbral]
ORDER BY avg_energy DESC</div>
        <form method="GET" action="queries.php" style="display:flex; gap:8px; align-items:center; margin-bottom:10px;">
            <label>Energia minima (0.0 - 1.0):</label>
            <input type="number" name="energy" step="0.1" min="0" max="1" value="<?= $energy_filter ?>" style="width:80px;">
            <input type="hidden" name="popularity" value="<?= $popularity_filter ?>">
            <button type="submit">Filtrar</button>
        </form>
        <?php if (empty($results_q2)): ?>
            <p>No hay resultados para ese filtro.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Genero</th><th>Total Tracks</th><th>Avg Energia</th><th>Avg Danceability</th><th>Avg Popularidad</th></tr></thead>
            <tbody>
                <?php foreach ($results_q2 as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['genre_name']) ?></td>
                    <td><?= $r['total_tracks'] ?></td>
                    <td><?= $r['avg_energy'] ?></td>
                    <td><?= $r['avg_danceability'] ?></td>
                    <td><?= $r['avg_popularity'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- query 3 -->
    <div class="query-section">
        <span class="query-label">Subconsulta con WITH</span>
        <h2>Query 3 — Tracks mas populares que el promedio de su genero</h2>
        <p>Usa una CTE (WITH) para calcular popularidad promedio por genero, luego filtra tracks que superan ese promedio.</p>
        <div class="query-sql">WITH GenreAvg AS (
    SELECT tg.genre_id,
           AVG(t.popularity) as avg_popularity
    FROM TrackGenre tg
    JOIN Track t ON tg.track_id = t.track_id
    GROUP BY tg.genre_id
)
SELECT t.track_name, t.popularity,
       g.genre_name,
       ROUND(ga.avg_popularity, 1) as genre_avg,
       ROUND(t.popularity - ga.avg_popularity, 1) as above_avg
FROM Track t
JOIN TrackGenre tg ON t.track_id = tg.track_id
JOIN Genre g ON tg.genre_id = g.genre_id
JOIN GenreAvg ga ON tg.genre_id = ga.genre_id
WHERE t.popularity > ga.avg_popularity
ORDER BY above_avg DESC
LIMIT 15</div>
        <?php if (empty($results_q3)): ?>
            <p>No hay resultados.</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Track</th><th>Popularidad</th><th>Genero</th><th>Promedio Genero</th><th>Por encima</th></tr></thead>
            <tbody>
                <?php foreach ($results_q3 as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['track_name']) ?></td>
                    <td><?= $r['popularity'] ?></td>
                    <td><?= htmlspecialchars($r['genre_name']) ?></td>
                    <td><?= $r['genre_avg'] ?></td>
                    <td><?= $r['above_avg'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- query 4 -->
    <div class="query-section">
        <span class="query-label">Agregacion</span>
        <h2>Query 4 — Top 10 artistas con mas tracks</h2>
        <p>Agrupa por artista y cuenta sus tracks, con popularidad promedio y maxima.</p>
        <div class="query-sql">SELECT ar.artist_name,
       COUNT(ta.track_id) as total_tracks,
       ROUND(AVG(t.popularity), 1) as avg_popularity,
       MAX(t.popularity) as max_popularity
FROM Artist ar
JOIN TrackArtist ta ON ar.artist_id = ta.artist_id
JOIN Track t ON ta.track_id = t.track_id
GROUP BY ar.artist_id, ar.artist_name
ORDER BY total_tracks DESC
LIMIT 10</div>
        <table>
            <thead><tr><th>Artista</th><th>Total Tracks</th><th>Popularidad Promedio</th><th>Popularidad Maxima</th></tr></thead>
            <tbody>
                <?php foreach ($results_q4 as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['artist_name']) ?></td>
                    <td><?= $r['total_tracks'] ?></td>
                    <td><?= $r['avg_popularity'] ?></td>
                    <td><?= $r['max_popularity'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- query 5 -->
    <div class="query-section">
        <span class="query-label">Albums con tracks explícitos</span>
        <h2>Query 5 — Albums con tracks explicitos</h2>
        <p>Albums que tienen al menos un track explicito, con conteo de tracks totales, explicitos y popularidad promedio.</p>
        <div class="query-sql">SELECT a.album_name,
       COUNT(t.track_id) as total_tracks,
       SUM(t.explicit) as explicit_tracks,
       ROUND(AVG(t.popularity), 1) as avg_popularity
FROM Album a
JOIN Track t ON a.album_id = t.album_id
GROUP BY a.album_id, a.album_name
HAVING SUM(t.explicit) > 0
ORDER BY explicit_tracks DESC
LIMIT 15</div>
        <table>
            <thead><tr><th>Album</th><th>Total Tracks</th><th>Explicitos</th><th>Avg Popularidad</th></tr></thead>
            <tbody>
                <?php foreach ($results_q5 as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['album_name']) ?></td>
                    <td><?= $r['total_tracks'] ?></td>
                    <td><?= $r['explicit_tracks'] ?></td>
                    <td><?= $r['avg_popularity'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>