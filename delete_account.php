<?php
session_start();
require_once "includes/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION["user_id"];

// Eliminar usuario y sus tareas
$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);

// Cerrar sesión
session_unset();
session_destroy();

header("Location: index.php?deleted=1");
exit;
