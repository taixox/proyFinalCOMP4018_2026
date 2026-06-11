<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagramas - Spotify DB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .diagram-img {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 12px;
        }
        .attr-table th { background: #222; color: #fff; }
        .section { margin-bottom: 40px; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <h1>Diagramas y Modelo de Datos</h1>

    <!-- diagrama ER -->
    <div class="section">
        <h2>Diagrama Entidad-Relacion (E/R) Conceptual</h2>
        <p>Muestra las entidades principales, sus relaciones y la herencia ISA en Playlist (total, disjunta).</p>
        <img src="img/er_conceptual.jpeg" alt="Diagrama E/R Conceptual" class="diagram-img">
    </div>

    <!-- modelo relacional -->
    <div class="section">
        <h2>Modelo Relacional</h2>
        <p>Traduccion del diagrama E/R a tablas relacionales con claves primarias, foraneas y tablas intermedias.</p>
        <img src="img/modelo_relacional.jpeg" alt="Modelo Relacional" class="diagram-img">
    </div>

    <!-- Atributos -->
    <div class="section">
        <h2>Descripcion de Atributos por Tabla</h2>

        <!-- Track -->
        <h3 style="margin-top:20px;">Track</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>track_id</td><td>VARCHAR(50) PK</td><td>Identificador unico del track en Spotify</td></tr>
                <tr><td>track_name</td><td>VARCHAR(255)</td><td>Nombre de la cancion</td></tr>
                <tr><td>duration_ms</td><td>INT</td><td>Duracion de la cancion en milisegundos</td></tr>
                <tr><td>explicit</td><td>TINYINT(1)</td><td>Indica si la cancion tiene contenido explicito (1=si, 0=no)</td></tr>
                <tr><td>popularity</td><td>INT</td><td>Popularidad de 0 a 100 basada en reproducciones recientes</td></tr>
                <tr><td>time_signature</td><td>INT</td><td>Compas estimado de la cancion (3 a 7)</td></tr>
                <tr><td>album_id</td><td>VARCHAR(36) FK</td><td>Referencia al album al que pertenece el track</td></tr>
            </tbody>
        </table>

        <!-- AudioFeatures -->
        <h3 style="margin-top:20px;">AudioFeatures</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>track_id</td><td>VARCHAR(50) PK FK</td><td>Referencia al track correspondiente</td></tr>
                <tr><td>danceability</td><td>FLOAT</td><td>Que tan bailable es el track (0.0 a 1.0)</td></tr>
                <tr><td>energy</td><td>FLOAT</td><td>Intensidad y actividad perceptual del track (0.0 a 1.0)</td></tr>
                <tr><td>key</td><td>INT</td><td>Tonalidad del track usando notacion Pitch Class (-1 a 11)</td></tr>
                <tr><td>loudness</td><td>FLOAT</td><td>Volumen general del track en decibeles (dB)</td></tr>
                <tr><td>mode</td><td>TINYINT(1)</td><td>Modalidad del track: 1=mayor, 0=menor</td></tr>
                <tr><td>speechiness</td><td>FLOAT</td><td>Presencia de palabras habladas (0.0 a 1.0)</td></tr>
                <tr><td>acousticness</td><td>FLOAT</td><td>Confianza de que el track es acustico (0.0 a 1.0)</td></tr>
                <tr><td>instrumentalness</td><td>FLOAT</td><td>Probabilidad de que el track no tenga vocales (0.0 a 1.0)</td></tr>
                <tr><td>liveness</td><td>FLOAT</td><td>Probabilidad de que el track fue grabado en vivo (0.0 a 1.0)</td></tr>
                <tr><td>valence</td><td>FLOAT</td><td>Positividad musical del track (0.0 a 1.0)</td></tr>
                <tr><td>tempo</td><td>FLOAT</td><td>Tempo estimado en beats por minuto (BPM)</td></tr>
            </tbody>
        </table>

        <!-- Album -->
        <h3 style="margin-top:20px;">Album</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>album_id</td><td>VARCHAR(36) PK</td><td>Identificador unico del album</td></tr>
                <tr><td>album_name</td><td>VARCHAR(255)</td><td>Nombre del album</td></tr>
            </tbody>
        </table>

        <!-- Artist -->
        <h3 style="margin-top:20px;">Artist</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>artist_id</td><td>VARCHAR(36) PK</td><td>Identificador unico del artista</td></tr>
                <tr><td>artist_name</td><td>VARCHAR(255)</td><td>Nombre del artista</td></tr>
            </tbody>
        </table>

        <!-- Genre -->
        <h3 style="margin-top:20px;">Genre</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>genre_id</td><td>VARCHAR(36) PK</td><td>Identificador unico del genero</td></tr>
                <tr><td>genre_name</td><td>VARCHAR(100)</td><td>Nombre del genero musical</td></tr>
            </tbody>
        </table>

        <!-- User -->
        <h3 style="margin-top:20px;">User</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>user_id</td><td>VARCHAR(36) PK</td><td>Identificador unico del usuario</td></tr>
                <tr><td>username</td><td>VARCHAR(100) UNIQUE</td><td>Nombre de usuario unico para autenticacion</td></tr>
                <tr><td>password_hash</td><td>VARCHAR(255)</td><td>Contrasena encriptada con bcrypt</td></tr>
            </tbody>
        </table>

        <!-- Playlist -->
        <h3 style="margin-top:20px;">Playlist</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>playlist_id</td><td>VARCHAR(36) PK</td><td>Identificador unico de la playlist</td></tr>
                <tr><td>playlist_name</td><td>VARCHAR(255)</td><td>Nombre de la playlist</td></tr>
                <tr><td>playlist_type</td><td>ENUM('public','private')</td><td>Tipo de playlist: publica o privada</td></tr>
                <tr><td>user_id</td><td>VARCHAR(36) FK</td><td>Referencia al usuario dueno de la playlist</td></tr>
            </tbody>
        </table>

        <!-- PublicPlaylist -->
        <h3 style="margin-top:20px;">PublicPlaylist (subtipo de Playlist)</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>playlist_id</td><td>VARCHAR(36) PK FK</td><td>Referencia a la playlist padre</td></tr>
                <tr><td>description</td><td>TEXT</td><td>Descripcion visible para otros usuarios</td></tr>
            </tbody>
        </table>

        <!-- PrivatePlaylist -->
        <h3 style="margin-top:20px;">PrivatePlaylist (subtipo de Playlist)</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>playlist_id</td><td>VARCHAR(36) PK FK</td><td>Referencia a la playlist padre. Solo visible para el dueno.</td></tr>
            </tbody>
        </table>

        <!-- Tablas intermedias -->
        <h3 style="margin-top:20px;">TrackArtist (relacion M:N)</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>track_id</td><td>VARCHAR(50) PK FK</td><td>Referencia al track</td></tr>
                <tr><td>artist_id</td><td>VARCHAR(36) PK FK</td><td>Referencia al artista</td></tr>
            </tbody>
        </table>

        <h3 style="margin-top:20px;">TrackGenre (relacion M:N)</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>track_id</td><td>VARCHAR(50) PK FK</td><td>Referencia al track</td></tr>
                <tr><td>genre_id</td><td>VARCHAR(36) PK FK</td><td>Referencia al genero</td></tr>
            </tbody>
        </table>

        <h3 style="margin-top:20px;">PlaylistTrack (relacion M:N)</h3>
        <table class="attr-table">
            <thead><tr><th>Atributo</th><th>Tipo</th><th>Descripcion</th></tr></thead>
            <tbody>
                <tr><td>playlist_id</td><td>VARCHAR(36) PK FK</td><td>Referencia a la playlist</td></tr>
                <tr><td>track_id</td><td>VARCHAR(50) PK FK</td><td>Referencia al track</td></tr>
                <tr><td>added_at</td><td>TIMESTAMP</td><td>Fecha y hora en que se añadio el track a la playlist</td></tr>
                <tr><td>position</td><td>INT</td><td>Posicion del track dentro de la playlist</td></tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>