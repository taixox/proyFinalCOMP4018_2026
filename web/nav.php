<nav>
    <a href="index.php">Inicio</a>
    <a href="tracks.php">Tracks</a>
    <a href="playlists.php">Playlists</a>
    <a href="queries.php">Queries</a>
    <a href="diagrams.php">Diagramas</a>
    <span class="nav-user">
        <?= htmlspecialchars($_SESSION['username']) ?> |
        <a href="logout.php">Cerrar sesion</a>
    </span>
</nav>