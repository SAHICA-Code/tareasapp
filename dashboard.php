<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Añadir nueva tarea (principal o subtarea)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["nueva_tarea"]);
    $parent_id = isset($_POST["parent_id"]) && $_POST["parent_id"] !== "" ? (int)$_POST["parent_id"] : null;

    if (!empty($titulo)) {
        $stmt = $pdo->prepare("INSERT INTO tareas (usuario_id, titulo, parent_id) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $titulo, $parent_id]);
    }

    header("Location: dashboard.php");
    exit;
}

// Completar tarea (con sincronización de subtareas y padres)
if (isset($_GET["completar"])) {
    $id = (int)$_GET["completar"];

    $stmt = $pdo->prepare("SELECT completada, parent_id FROM tareas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $user_id]);
    $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tarea) {
        $nuevo_estado = $tarea["completada"] ? 0 : 1;

        // Actualizar tarea
        $pdo->prepare("UPDATE tareas SET completada = ? WHERE id = ? AND usuario_id = ?")
            ->execute([$nuevo_estado, $id, $user_id]);

        if ($tarea["parent_id"] === null) {
            // Si es principal → marcar todas sus subtareas
            $pdo->prepare("UPDATE tareas SET completada = ? WHERE parent_id = ? AND usuario_id = ?")
                ->execute([$nuevo_estado, $id, $user_id]);
        } else {
            // Si es subtarea → comprobar si todas están completas
            $parent_id = $tarea["parent_id"];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas WHERE parent_id = ? AND completada = 0");
            $stmt->execute([$parent_id]);
            $incompletas = $stmt->fetchColumn();

            $pdo->prepare("UPDATE tareas SET completada = ? WHERE id = ? AND usuario_id = ?")
                ->execute([$incompletas == 0 ? 1 : 0, $parent_id, $user_id]);
        }
    }

    header("Location: dashboard.php");
    exit;
}

// Eliminar tarea (y sus subtareas)
if (isset($_GET["eliminar"])) {
    $id = (int)$_GET["eliminar"];
    $pdo->prepare("DELETE FROM tareas WHERE parent_id = ? AND usuario_id = ?")->execute([$id, $user_id]);
    $pdo->prepare("DELETE FROM tareas WHERE id = ? AND usuario_id = ?")->execute([$id, $user_id]);
    header("Location: dashboard.php");
    exit;
}

// Obtener todas las tareas
$stmt = $pdo->prepare("SELECT * FROM tareas WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar principales y subtareas
$principales = array_filter($tareas, fn($t) => $t["parent_id"] === null);
$subtareas = [];
foreach ($tareas as $t) {
    if ($t["parent_id"] !== null) {
        $subtareas[$t["parent_id"]][] = $t;
    }
}

// Progreso
$total = count($tareas);
$completadas = count(array_filter($tareas, fn($t) => $t["completada"]));
$porcentaje = $total > 0 ? round(($completadas / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tareas</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="container">
    <header>
        <div id="user-info" class="logo-title">
            <img src="assets/logo.png" alt="Logo" class="logo">
            <div>
                <h1>Mis tareas</h1>
                <p id="user-name">Hola <?= htmlspecialchars($user_name) ?> 👋</p>
            </div>
        </div>
        <a href="logout.php" class="btn-logout <?= $porcentaje >= 100 && $total > 0 ? 'btn-success' : '' ?>">Cerrar sesión</a>
    </header>

    <p class="delete-account">
        <a href="delete_account.php" onclick="return confirm('¿Seguro que deseas eliminar tu cuenta? Esta acción no se puede deshacer.');">
            Eliminar cuenta
        </a>
    </p>

    <div class="progress-header">
        <p><?= $completadas ?> de <?= $total ?> tareas completadas</p>
        <p><?= $porcentaje ?>%</p>
    </div>

    <div class="progress-container">
        <div class="progress-bar <?= $porcentaje >= 100 && $total > 0 ? 'completo' : '' ?>" style="width: <?= $porcentaje ?>%;"></div>
    </div>

    <?php if ($porcentaje >= 100 && $total > 0): ?>
        <p id="progress-message" class="visible">¡Todas las tareas finalizadas!</p>
    <?php endif; ?>

    <form method="POST" class="task-input nueva-tarea-container">
        <input type="text" name="nueva_tarea" placeholder="Escribe una nueva tarea..." required>
        <button type="submit" class="add-task-btn">+</button>
    </form>

    <ul id="task-list">
        <?php if (empty($principales)): ?>
            <li>No hay tareas</li>
        <?php else: ?>
            <?php foreach ($principales as $t): ?>
                <?php $tiene_subtareas = isset($subtareas[$t["id"]]); ?>

                <li class="<?= $t["completada"] ? "completed" : "" ?> <?= $tiene_subtareas ? 'tiene-subtareas' : '' ?>">
                    <div class="task-header">
                        <?php if ($tiene_subtareas): ?>
                            <span class="toggle-subtasks" data-id="<?= $t["id"] ?>">▶</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($t["titulo"]) ?>
                    </div>
                    <div class="task-actions">
                        <a href="?completar=<?= $t["id"] ?>">✔</a>
                        <a href="?eliminar=<?= $t["id"] ?>">🗑</a>
                    </div>
                </li>

                <?php if ($tiene_subtareas): ?>
                    <ul class="subtask-list hidden" id="subtasks-<?= $t["id"] ?>">
                        <?php foreach ($subtareas[$t["id"]] as $st): ?>
                            <li class="subtask <?= $st["completada"] ? "completed" : "" ?>">
                                <?= htmlspecialchars($st["titulo"]) ?>
                                <div>
                                    <a href="?completar=<?= $st["id"] ?>">✔</a>
                                    <a href="?eliminar=<?= $st["id"] ?>">🗑</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="POST" class="add-subtask-form" id="form-sub-<?= $t["id"] ?>">
                    <input type="hidden" name="parent_id" value="<?= $t["id"] ?>">
                    <input type="text" name="nueva_tarea" placeholder="Nueva subtarea...">
                    <button type="submit">+</button>
                </form>

            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <footer class="footer">
        <p>Web hecha por <strong>Sara Hidalgo Caro</strong> · 2025</p>
    </footer>
</div>

<script>
document.querySelectorAll('.toggle-subtasks').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        const list = document.getElementById('subtasks-' + id);
        list.classList.toggle('hidden');
        btn.classList.toggle('abierto');
        btn.textContent = btn.classList.contains('abierto') ? '▼' : '▶';
    });
});
</script>

</body>
</html>
