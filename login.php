<?php
/**
 * PÁGINA DE LOGIN - SISTEMA BIBLIOTECARIO
 * Maneja la autenticación de usuarios (profesores y directiva).
 */

require_once 'db.php';
session_start();

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['usuario_id'])) {
    header('Location: gestion.php');
    exit;
}

$error = '';

// Procesar el formulario al enviar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar entradas
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        try {
            // Buscar usuario en la base de datos
            $sql = "SELECT id, nombre, password, rol FROM usuarios WHERE usuario = :usuario LIMIT 1";
            $stmt = ejecutarConsulta($sql, [':usuario' => $usuario]);
            $user = $stmt->fetch();

            // Verificar contraseña usando password_verify para hashes seguros
            if ($user && password_verify($password, $user['password'])) {
                // Credenciales correctas: Iniciar sesión
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];

                // Redirigir al panel de gestión
                header('Location: gestion.php');
                exit;
            } else {
                // Credenciales incorrectas
                $error = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error = "Error del sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Profesores - Biblioteca Andrés Manjón</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="img/favicon.ico">
</head>

<div class="cuerpo-login-split">
    <div class="tarjeta-login-split">

        <!-- COLUMNA IZQUIERDA: BRANDING (AZUL) -->
        <div class="login-izquierda">
            <div class="contenido-brand">
                <img src="img/logo.png" alt="Logo Colegio" class="logo-login-brand" onerror="this.style.display='none'">
                <h2>Biblioteca</h2>
                <p>Colegio Andrés Manjón</p>
            </div>
            <!-- Decoración de fondo opcional -->
            <div class="decoracion-circulo"></div>
        </div>

        <!-- COLUMNA DERECHA: FORMULARIO (BLANCO) -->
        <div class="login-derecha">
            <div class="cabecera-form-login">
                <h2>Hola de nuevo</h2>
                <p>Introduce tus credenciales para acceder.</p>
            </div>

                <?php if (!empty($error)): ?>
                <div class="alerta-error-login">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="form-login-moderno">
                <div class="grupo-input-moderno">
                    <label for="usuario">USUARIO</label>
                    <input type="text" id="usuario" name="usuario" placeholder="usuario@cole.com" required autofocus>
                </div>

                <div class="grupo-input-moderno">
                    <label for="password">CONTRASEÑA</label>
                    <input type="password" id="password" name="password" placeholder="••••" required>
                </div>

                <button type="submit" class="boton-login-moderno">Iniciar Sesión</button>

                <div class="pie-form-login">
                    <a href="index.php">Cancelar y volver</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>

</html>