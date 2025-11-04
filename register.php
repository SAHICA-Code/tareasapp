<?php
session_start();
require_once "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $error = "Este correo ya está registrado.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $email, $password]);
        $_SESSION["user_id"] = $pdo->lastInsertId();
        $_SESSION["user_name"] = $nombre;
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Crear cuenta</title>
        <link rel="stylesheet" href="assets/styles.css">
    </head>
    <body>
    <div class="container">
        <section id="register-section" class="active">
            <h1>Crear cuenta</h1>
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="nombre" placeholder="Nombre completo" required>
                <input type="email" name="email" placeholder="Correo electrónico" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <button type="submit">Registrarse</button>
            </form>
            <p>¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a></p>
        </section>
        <footer class="footer">
            <p>Web hecha por <strong>Sara Hidalgo Caro </strong>· 2025</p>
        </footer>
    </div>
    </body>
</html>
