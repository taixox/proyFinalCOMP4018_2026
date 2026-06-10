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

//insertar track
if ($action === 'insert' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_id       = trim($_POST['track_id']);
    $track_name     = trim($_POST['track_name']);
    $duration_ms    = (int)$_POST['duration_ms'];
    $explicit       = isset($_POST['explicit']) ? 1 : 0;
    $popularity     = (int)$_POST['popularity'];
    $time_signature = (int)$_POST['time_signature'];
    $album_id       = trim($_POST['album_id']);

    //validaciones de integridad
    if (empty($track_id) || empty($track_name) || empty($album_id)) {
        $error = "Error: track_id, nombre y album son obligatorios.";
    } elseif ($popularity < 0 || $popularity > 100) {
        $error = "Violacion de integridad: popularidad debe estar entre 0 y 100.";
    } elseif ($duration_ms <= 0) {
        $error = "Violacion de integridad: duracion debe ser mayor a 0.";
    } else {
        //Verificar que el track_id no exista ya
        $check = $pdo->prepare("SELECT track_id FROM Track WHERE track_id = ?");
        $check->execute([$track_id]);
        if ($check->fetch()) {
            $error = "Violacion de integridad: ya existe un track con ese ID (clave primaria duplicada).";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Track (track_id, track_name, duration_ms, explicit, popularity, time_signature, album_id)
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$track_id, $track_name, $duration_ms, $explicit, $popularity, $time_signature, $album_id]);
                $message = "Track insertado exitosamente.";
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Error de base de datos: " . $e->getMessage();
                $action = 'insert';
            }
        }
    }
    if ($error) $action = 'insert';
}

//modificar track
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_id       = trim($_POST['track_id']);
    $track_name     = trim($_POST['track_name']);
    $duration_ms    = (int)$_POST['duration_ms'];
    $explicit       = isset($_POST['explicit']) ? 1 : 0;
    $popularity     = (int)$_POST['popularity'];
    $time_signature = (int)$_POST['time_signature'];

    //validaciones de integrdad
    if (empty($track_name)) {
        $error = "Error: el nombre del track es obligatorio.";
    } elseif ($popularity < 0 || $popularity > 100) {
        $error = "Violacion de integridad: popularidad debe estar entre 0 y 100.";
    } elseif ($duration_ms <= 0) {
        $error = "Violacion de integridad: duracion debe ser mayor a 0.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Track SET track_name=?, duration_ms=?, explicit=?, popularity=?, time_signature=?
                                   WHERE track_id=?");
            $stmt->execute([$track_name, $duration_ms, $explicit, $popularity, $time_signature, $track_id]);
            $message = "Track actualizado exitosamente.";
            $action = 'list';
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
    if ($error) $action = 'edit';
}

//obtener track para editar
$edit_track = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Track WHERE track_id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_track = $stmt->fetch();
    if (!$edit_track) {
        $error  = "Track no encontrado.";
        $action = 'list';
    }
}

//busqueda y paginacion
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 20;
$offset  = ($page - 1) * $limit;

if ($search) {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM Track WHERE track_name LIKE ?");
    $count_stmt->execute(["%$search%"]);
    $total = $count_stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT t.track_id, t.track_name, t.popularity, t.duration_ms, t.explicit, a.album_name
                           FROM Track t
                           JOIN Album a ON t.album_id = a.album_id
                           WHERE t.track_name LIKE ?
                           ORDER BY t.popularity DESC
                           LIMIT ? OFFSET ?");
    $stmt->execute(["%$search%", $limit, $offset]);
} else {
    $total = $pdo->query("SELECT COUNT(*) FROM Track")->fetchColumn();
    $stmt  = $pdo->prepare("SELECT t.track_id, t.track_name, t.popularity, t.duration_ms, t.explicit, a.album_name
                            FROM Track t
                            JOIN Album a ON t.album_id = a.album_id
                            ORDER BY t.popularity DESC
                            LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
}
$tracks     = $stmt->fetchAll();
$total_pages = ceil($total / $limit);

// Obtener albums para el formulario de insertar
$albums = $pdo->query("SELECT album_id, album_name FROM Album ORDER BY album_name LIMIT 500")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tracks - Spotify DB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <h1>Tracks</h1>

    <?php if ($message): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($action === 'insert' || $action === 'edit'): ?>
    <!-- para insertar y editar -->
    <h2><?= $action === 'insert' ? 'Insertar nuevo track' : 'Editar track' ?></h2>
    <form method="POST" action="tracks.php?action=<?= $action ?><?= $edit_track ? '&id=' . urlencode($edit_track['track_id']) : '' ?>">
        <?php if ($action === 'edit'): ?>
            <input type="hidden" name="track_id" value="<?= htmlspecialchars($edit_track['track_id']) ?>">
            <label>Track ID</label>
            <input type="text" value="<?= htmlspecialchars($edit_track['track_id']) ?>" disabled>
        <?php else: ?>
            <label>Track ID *</label>
            <input type="text" name="track_id" placeholder="ID unico del track" required>
        <?php endif; ?>

        <label>Nombre del track *</label>
        <input type="text" name="track_name" value="<?= htmlspecialchars($edit_track['track_name'] ?? '') ?>" required>

        <label>Duracion (ms)</label>
        <input type="number" name="duration_ms" value="<?= htmlspecialchars($edit_track['duration_ms'] ?? '180000') ?>">

        <label>Popularidad (0-100)</label>
        <input type="number" name="popularity" min="0" max="100" value="<?= htmlspecialchars($edit_track['popularity'] ?? '50') ?>">

        <label>Time Signature</label>
        <input type="number" name="time_signature" value="<?= htmlspecialchars($edit_track['time_signature'] ?? '4') ?>">

        <label>
            <input type="checkbox" name="explicit" <?= !empty($edit_track['explicit']) ? 'checked' : '' ?>>
            Explicit
        </label>

        <?php if ($action === 'insert'): ?>
        <label>Album *</label>
        <select name="album_id" required>
            <option value="">-- Selecciona un album --</option>
            <?php foreach ($albums as $album): ?>
                <option value="<?= htmlspecialchars($album['album_id']) ?>">
                    <?= htmlspecialchars($album['album_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <button type="submit"><?= $action === 'insert' ? 'Insertar' : 'Guardar cambios' ?></button>
        <a href="tracks.php" class="btn btn-danger">Cancelar</a>
    </form>

    <?php else: ?>
    <!-- lista de tracks -->
    <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
        <form method="GET" action="tracks.php" style="display:flex; gap:8px; flex:1;">
            <input type="text" name="search" placeholder="Buscar por nombre..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
            <button type="submit">Buscar</button>
        </form>
        <a href="tracks.php?action=insert" class="btn">+ Insertar track</a>
    </div>

    <p>Mostrando <?= count($tracks) ?> de <?= number_format($total) ?> tracks</p>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Album</th>
                <th>Popularidad</th>
                <th>Duracion</th>
                <th>Explicit</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tracks as $track): ?>
            <tr>
                <td><?= htmlspecialchars($track['track_name']) ?></td>
                <td><?= htmlspecialchars($track['album_name']) ?></td>
                <td><?= $track['popularity'] ?></td>
                <td><?= round($track['duration_ms'] / 60000, 2) ?> min</td>
                <td><?= $track['explicit'] ? 'Si' : 'No' ?></td>
                <td>
                    <a href="tracks.php?action=edit&id=<?= urlencode($track['track_id']) ?>" class="btn">Editar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- paginacion -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="tracks.php?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Anterior</a>
        <?php endif; ?>
        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <a href="tracks.php?page=<?= $i ?>&search=<?= urlencode($search) ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="tracks.php?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Siguiente</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>