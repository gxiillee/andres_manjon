<?php
/**
 * PERFIL DE USUARIO - BIBLIOTECA ANDRÉS MANJÓN
 * Diseño completo arreglado
 */

require_once 'db.php'; 
session_start();

// 1. SEGURIDAD
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$mensaje = '';
$tipo_mensaje = '';

// 2. LÓGICA: CAMBIAR CONTRASEÑA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_cambiar_pass'])) {
    $nueva_pass = trim($_POST['nueva_password']);
    $confirma_pass = trim($_POST['confirmar_password']);
    
    if (empty($nueva_pass) || empty($confirma_pass)) {
        $mensaje = "Por favor, rellena ambos campos.";
        $tipo_mensaje = "error";
    } elseif ($nueva_pass !== $confirma_pass) {
        $mensaje = "❌ Las contraseñas NO coinciden.";
        $tipo_mensaje = "error";
    } else {
        $pass_encriptada = password_hash($nueva_pass, PASSWORD_DEFAULT);
        
        if (isset($pdo)) {
            try {
                $sql_update = "UPDATE usuarios SET password = ? WHERE id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                
                if ($stmt_update->execute([$pass_encriptada, $id_usuario])) {
                    $mensaje = "¡Contraseña actualizada correctamente!";
                    $tipo_mensaje = "exito";
                } else {
                    $mensaje = "Error en base de datos.";
                    $tipo_mensaje = "error";
                }
            } catch (Exception $e) {
                $mensaje = "Error: " . $e->getMessage();
                $tipo_mensaje = "error";
            }
        }
    }
}

// 3. OBTENER DATOS COMPLETOS (Nombre, Usuario y Rol)
$datos_usuario = [
    'nombre' => 'Usuario',
    'usuario' => '---',
    'rol' => 'profesor'
];

if (isset($pdo)) {
    $stmt = $pdo->prepare("SELECT nombre, usuario, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila) {
        $datos_usuario = $fila;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - Biblioteca</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: var(--color-fondo-principal, #f4f6f9);">

    <header class="cabecera-gestion">
        <div class="cabecera-contenido">
            <a href="gestion.php" class="logo-sitio">
                <img src="img/logo.png" alt="Logo" class="logo-imagen" onerror="this.style.display='none'">
                <span>Panel de Gestión</span>
            </a>
            <div class="info-usuario">
                <span style="margin-right: 15px;">
                    Hola, <strong><?php echo htmlspecialchars($datos_usuario['nombre']); ?></strong>
                </span>
                <a href="gestion.php" class="boton">
                    <i class="fa-solid fa-arrow-left"></i> Volver al Panel
                </a>
                <a href="logout.php" class="boton-header boton-rojo">Cerrar Sesión</a>
                
                <div class="boton-perfil-redondo" title="Ya estás en tu perfil">
                    <img src="img/icono-perfil.png" alt="Perfil" class="icono-img" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </header>

    <main class="contenedor-perfil">
        
        <?php if($mensaje): ?>
            <div class="alerta <?php echo $tipo_mensaje; ?>" style="margin-bottom: 20px;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="perfil-layout">
            
            <div class="tarjeta-perfil tarjeta-identidad">
                <div class="avatar-grande">
                    <?php echo strtoupper(substr($datos_usuario['nombre'], 0, 1)); ?>
                </div>
                <h2 class="nombre-grande"><?php echo htmlspecialchars($datos_usuario['nombre']); ?></h2>
                
                <div class="datos-extra">
                    <div class="dato-fila">
                        <i class="fa-solid fa-user-tag"></i>
                        <span class="etiqueta-rol <?php echo $datos_usuario['rol']; ?>">
                            <?php echo ucfirst($datos_usuario['rol']); ?>
                        </span>
                    </div>
                    <div class="dato-fila">
                        <i class="fa-solid fa-envelope"></i>
                        <span><?php echo htmlspecialchars($datos_usuario['usuario']); ?></span>
                    </div>
                </div>
            </div>

            <div class="tarjeta-perfil tarjeta-seguridad">
                <h2 class="titulo-perfil"><i class="fa-solid fa-lock"></i> Seguridad</h2>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">Actualiza tu contraseña.</p>

                <form method="POST" action="perfil.php">
                    <label for="pass1">Nueva contraseña:</label>
                    <div class="input-group">
                        <input type="password" name="nueva_password" id="pass1" class="input-perfil" placeholder="Mínimo 4 caracteres..." required>
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePass('pass1', this)"></i>
                    </div>

                    <label for="pass2">Repetir contraseña:</label>
                    <div class="input-group">
                        <input type="password" name="confirmar_password" id="pass2" class="input-perfil" placeholder="Confirma la contraseña..." required>
                        <i class="fa-solid fa-eye toggle-password" onclick="togglePass('pass2', this)"></i>
                    </div>
                    
                    <button type="submit" name="btn_cambiar_pass" class="btn-guardar" style="width: 100%; margin-top: 15px;">
                        Actualizar Contraseña
                    </button>
                </form>
            </div>

        </div>
    </main>

    <script>
        function togglePass(inputId, iconElement) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove("fa-eye");
                iconElement.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                iconElement.classList.remove("fa-eye-slash");
                iconElement.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>