<?php
/**
 * PANEL DE GESTIÓN - BIBLIOTECA ANDRÉS MANJÓN
 */

require_once 'db.php';
session_start();

// 1. Control de Acceso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$rol_usuario = $_SESSION['rol']; // 'directiva' o 'profesor'
$nombre_usuario = $_SESSION['nombre'];
$mensaje_accion = '';
$tipo_mensaje = '';

// Leer mensajes desde GET (para redirecciones PRG)
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje_accion = $_GET['mensaje'];
    $tipo_mensaje = $_GET['tipo'];
}

// ==========================================================================
// LÓGICA DE BORRADO (GET) 
// ==========================================================================
if (isset($_GET['borrar_libro']) && $rol_usuario === 'directiva') {
    $id_libro = $_GET['borrar_libro'];
    try {
        $sql = "DELETE FROM libros WHERE id = :id";
        ejecutarConsulta($sql, [':id' => $id_libro]);
        header("Location: gestion.php?mensaje=Libro eliminado correctamente&tipo=exito");
        exit;
    } catch (Exception $e) {
        // En caso de error, no redirigimos para mostrarlo, o redirigimos con error
        $mensaje_accion = "Error al eliminar: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

if (isset($_GET['borrar_alumno']) && $rol_usuario === 'directiva') {
    $id = $_GET['borrar_alumno'];
    try {
        $sql = "DELETE FROM alumnos WHERE id = :id";
        ejecutarConsulta($sql, [':id' => $id]);
        header("Location: gestion.php?mensaje=Alumno eliminado correctamente&tipo=exito");
        exit;
    } catch (Exception $e) {
        $mensaje_accion = "Error al eliminar alumno: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

if (isset($_GET['borrar_usuario']) && $rol_usuario === 'directiva') {
    $id = $_GET['borrar_usuario'];
    // Evitar que se borre a sí mismo
    if ($id == $_SESSION['usuario_id']) {
        $mensaje_accion = "No puedes eliminar tu propia cuenta.";
        $tipo_mensaje = "error";
    } else {
        try {
            $sql = "DELETE FROM usuarios WHERE id = :id";
            ejecutarConsulta($sql, [':id' => $id]);
            header("Location: gestion.php?mensaje=Usuario eliminado correctamente&tipo=exito");
            exit;
        } catch (Exception $e) {
            $mensaje_accion = "Error al eliminar usuario: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// ==========================================================================
//LÓGICA DE REGISTROS
// ==========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Acción: Registrar Nuevo Préstamo ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'nuevo_prestamo') {
        $id_libro = $_POST['id_libro'];
        $id_alumno = $_POST['id_alumno'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];

        try {
            // Iniciar transacción
            $pdo->beginTransaction();

            // 1. Crear préstamo en activos
            $sql = "INSERT INTO prestamos_activos (id_libro, id_alumno, id_usuario_presta, fecha_salida, fecha_vencimiento) 
                    VALUES (:libro, :alumno, :usuario, CURDATE(), :vencimiento)";
            ejecutarConsulta($sql, [
                ':libro' => $id_libro,
                ':alumno' => $id_alumno,
                ':usuario' => $_SESSION['usuario_id'],
                ':vencimiento' => $fecha_vencimiento
            ]);

            // 2. Actualizar estado del libro a 'prestado'
            $sqlUpdate = "UPDATE libros SET estado = 'prestado' WHERE id = :id";
            ejecutarConsulta($sqlUpdate, [':id' => $id_libro]);

            $pdo->commit();
            // Redirección PRG para evitar duplicados al recargar página
            header("Location: gestion.php?mensaje=Préstamo registrado correctamente&tipo=exito");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje_accion = "Error al registrar préstamo: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // --- Acción: Devolver Libro ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'devolver_libro') {
        $id_prestamo = $_POST['id_prestamo'];

        try {
            $pdo->beginTransaction();

            // 1. Obtener datos del préstamo antes de borrarlo
            $sqlGet = "SELECT * FROM prestamos_activos WHERE id = :id";
            $prestamo = ejecutarConsulta($sqlGet, [':id' => $id_prestamo])->fetch();

            if ($prestamo) {
                // 2. Mover a historial
                $sqlHist = "INSERT INTO historial_prestamos (id_libro, id_alumno, id_usuario_presta, fecha_salida, fecha_devolucion_real) 
                            VALUES (:libro, :alumno, :usuario, :salida, CURDATE())";
                ejecutarConsulta($sqlHist, [
                    ':libro' => $prestamo['id_libro'],
                    ':alumno' => $prestamo['id_alumno'],
                    ':usuario' => $prestamo['id_usuario_presta'],
                    ':salida' => $prestamo['fecha_salida']
                ]);

                // 3. Borrar de activos
                $sqlDel = "DELETE FROM prestamos_activos WHERE id = :id";
                ejecutarConsulta($sqlDel, [':id' => $id_prestamo]);

                // 4. Actualizar estado del libro a 'disponible'
                $sqlUpdate = "UPDATE libros SET estado = 'disponible' WHERE id = :id";
                ejecutarConsulta($sqlUpdate, [':id' => $prestamo['id_libro']]);

                $pdo->commit();
                $mensaje_accion = "Libro devuelto correctamente.";
                $tipo_mensaje = "exito";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje_accion = "Error al devolver libro: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
    // --- Acción: Eliminar Libro (GET) ---

    // --- Acción: Guardar/Editar Libro (POST) ---
    // Nota: 'accion' debe ser 'guardar_libro' según el formulario que vamos a corregir
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_libro' && $rol_usuario === 'directiva') {
        $id_libro = isset($_POST['id_libro']) && !empty($_POST['id_libro']) ? $_POST['id_libro'] : null;
        $titulo = $_POST['titulo'];
        $autor = $_POST['autor'];
        $isbn = $_POST['isbn'];
        $color = $_POST['color'];

        try {
            if ($id_libro) {
                // UPDATE: Actualizar libro existente
                $sql = "UPDATE libros SET titulo = :t, autor = :a, isbn = :i, color_categoria = :c WHERE id = :id";
                ejecutarConsulta($sql, [':t' => $titulo, ':a' => $autor, ':i' => $isbn, ':c' => $color, ':id' => $id_libro]);
                $mensaje_accion = "Libro actualizado correctamente.";
            } else {
                // INSERT: Nuevo libro
                $sql = "INSERT INTO libros (titulo, autor, isbn, color_categoria) VALUES (:t, :a, :i, :c)";
                ejecutarConsulta($sql, [':t' => $titulo, ':a' => $autor, ':i' => $isbn, ':c' => $color]);
                $mensaje_accion = "Libro añadido al catálogo.";
            }
            $tipo_mensaje = "exito";
        } catch (Exception $e) {
            $mensaje_accion = "Error al guardar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // --- Acción: Guardar Alumno (POST) ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_alumno' && $rol_usuario === 'directiva') {
        $id = isset($_POST['id_alumno']) && !empty($_POST['id_alumno']) ? $_POST['id_alumno'] : null;
        $nombre = $_POST['nombre'];
        $curso = $_POST['curso'];

        try {
            if ($id) {
                $sql = "UPDATE alumnos SET nombre = :n, curso = :c WHERE id = :id";
                ejecutarConsulta($sql, [':n' => $nombre, ':c' => $curso, ':id' => $id]);
                $mensaje_accion = "Datos del alumno actualizados.";
            } else {
                $sql = "INSERT INTO alumnos (nombre, curso) VALUES (:n, :c)";
                ejecutarConsulta($sql, [':n' => $nombre, ':c' => $curso]);
                $mensaje_accion = "Alumno registrado correctamente.";
            }
            $tipo_mensaje = "exito";
        } catch (Exception $e) {
            $mensaje_accion = "Error al guardar alumno: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // --- Acción: Guardar Docente/Usuario (POST) ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_usuario' && $rol_usuario === 'directiva') {
        $id = isset($_POST['id_usuario']) && !empty($_POST['id_usuario']) ? $_POST['id_usuario'] : null;
        $nombre = $_POST['nombre'];
        $usuario = $_POST['usuario'];
        $rol = $_POST['rol'];
        $password = $_POST['password']; // Puede venir vacío en edición

        try {
            if ($id) {
                // TODA ESTA PARTE ES PARA AÑADIR NUEVO USUARIO
                if (!empty($password)) {
                    // Si hay password nuevo, lo hasheamos y actualizamos todo
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET nombre = :n, usuario = :u, password = :p, rol = :r WHERE id = :id";
                    ejecutarConsulta($sql, [':n' => $nombre, ':u' => $usuario, ':p' => $hash, ':r' => $rol, ':id' => $id]);
                } else {
                    // Si no hay password, actualizamos solo datos
                    $sql = "UPDATE usuarios SET nombre = :n, usuario = :u, rol = :r WHERE id = :id";
                    ejecutarConsulta($sql, [':n' => $nombre, ':u' => $usuario, ':r' => $rol, ':id' => $id]);
                }
                $mensaje_accion = "Usuario actualizado correctamente.";
            } else {
                // ERRORES POSIBLES A LA HORA DE AÑADIRLO
                if (empty($password)) {
                    throw new Exception("La contraseña es obligatoria para nuevos usuarios.");
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (:n, :u, :p, :r)";
                ejecutarConsulta($sql, [':n' => $nombre, ':u' => $usuario, ':p' => $hash, ':r' => $rol]);
                $mensaje_accion = "Usuario registrado correctamente.";
            }
            $tipo_mensaje = "exito";
        } catch (Exception $e) {
            $mensaje_accion = "Error al guardar usuario: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    // --- Acción: Guardar Incidencia (POST) ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_incidencia') {
        $id_libro = !empty($_POST['id_libro']) ? $_POST['id_libro'] : null;
        $id_alumno = !empty($_POST['id_alumno']) ? $_POST['id_alumno'] : null;
        $descripcion = $_POST['descripcion'] ?? '';
        $sancion = $_POST['sancion'] ?? '';

        if (!$id_libro) {
            $mensaje_accion = "El libro es obligatorio para registrar una incidencia.";
            $tipo_mensaje = "error";
        } else {
            try {
                // Asegurar columna id_libro si no existe
                try {
                    $pdo->exec("ALTER TABLE incidencias ADD COLUMN id_libro INT AFTER id");
                } catch (Exception $e) {
                }

                $sql = "INSERT INTO incidencias (id_libro, id_alumno, id_usuario, descripcion, sancion, estado, fecha) 
                        VALUES (:libro, :alumno, :usuario, :desc, :sancion, 'pendiente', CURDATE())";
                ejecutarConsulta($sql, [
                    ':libro' => $id_libro,
                    ':alumno' => $id_alumno,
                    ':usuario' => $_SESSION['usuario_id'],
                    ':desc' => $descripcion,
                    ':sancion' => $sancion
                ]);
                $mensaje_accion = "Incidencia registrada correctamente.";
                $tipo_mensaje = "exito";
            } catch (Exception $e) {
                $mensaje_accion = "Error al registrar incidencia: " . $e->getMessage();
                $tipo_mensaje = "error";
            }
        }
    }

    // --- Acción: Resolver Incidencia (POST) ---
    if (isset($_POST['accion']) && $_POST['accion'] === 'resolver_incidencia') {
        $id_incidencia = $_POST['id_incidencia'];

        try {
            $sql = "UPDATE incidencias SET estado = 'resuelta' WHERE id = :id";
            ejecutarConsulta($sql, [':id' => $id_incidencia]);
            $mensaje_accion = "Incidencia marcada como resuelta.";
            $tipo_mensaje = "exito";
        } catch (Exception $e) {
            $mensaje_accion = "Error al resolver incidencia: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// ==========================================================================
// 3. RECUPERACIÓN DE DATOS
// ==========================================================================

// Libros disponibles (para selectores)
$libros_disponibles = ejecutarConsulta("SELECT * FROM libros WHERE estado = 'disponible' ORDER BY titulo ASC")->fetchAll();

// Alumnos (para selectores)
$alumnos = ejecutarConsulta("SELECT * FROM alumnos ORDER BY nombre ASC")->fetchAll();

// Préstamos Activos (para devoluciones y calendario)
$queryPrestamos = "
    SELECT pa.id, pa.fecha_vencimiento, pa.fecha_salida,
           l.titulo, l.isbn, l.color_categoria,
           a.nombre as nombre_alumno, a.curso
    FROM prestamos_activos pa
    JOIN libros l ON pa.id_libro = l.id
    JOIN alumnos a ON pa.id_alumno = a.id
    ORDER BY pa.fecha_vencimiento ASC
";
$prestamos_activos = ejecutarConsulta($queryPrestamos)->fetchAll();
$eventos_calendario = json_encode($prestamos_activos);

// Historial (Limitado a los últimos 50 para no cargar demasiado)
$historial = ejecutarConsulta("
    SELECT h.*, l.titulo, a.nombre as nombre_alumno 
    FROM historial_prestamos h
    JOIN libros l ON h.id_libro = l.id
    JOIN alumnos a ON h.id_alumno = a.id
    ORDER BY h.fecha_devolucion_real DESC LIMIT 50
")->fetchAll();

// --- GESTIÓN DATOS COMPLETOS (Solo Directiva) ---
$todos_libros = [];
$todos_usuarios = [];
$todos_alumnos_gestion = [];

if ($rol_usuario === 'directiva') {
    $todos_libros = ejecutarConsulta("SELECT * FROM libros ORDER BY titulo ASC")->fetchAll();
    $todos_usuarios = ejecutarConsulta("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();
    $todos_alumnos_gestion = ejecutarConsulta("SELECT * FROM alumnos ORDER BY curso ASC, nombre ASC")->fetchAll();
}

// --- INCIDENCIAS (Recuperación segura) ---
$incidencias_pendientes = [];
$historial_incidencias = [];

try {
    // Asegurar estructura de tabla (Silencioso pero efectivo)
    try {
        $pdo->exec("ALTER TABLE incidencias ADD COLUMN id_libro INT AFTER id");
    } catch (Exception $e) {
    }

    // Pendientes: LEFT JOIN para asegurar que se vean incluso si no tienen libro asignado (old data)
    $sqlPendientes = "
        SELECT i.*, COALESCE(a.nombre, 'General') as nombre_alumno, a.curso, COALESCE(l.titulo, 'Libro no especificado') as titulo_libro
        FROM incidencias i
        LEFT JOIN libros l ON i.id_libro = l.id
        LEFT JOIN alumnos a ON i.id_alumno = a.id
        WHERE i.estado = 'pendiente'
        ORDER BY i.fecha DESC
    ";
    $incidencias_pendientes = ejecutarConsulta($sqlPendientes)->fetchAll();

    // Historial: Resueltas
    $sqlHistorialInc = "
        SELECT i.*, COALESCE(a.nombre, 'General') as nombre_alumno, u.nombre as nombre_usuario, COALESCE(l.titulo, 'Libro no especificado') as titulo_libro
        FROM incidencias i
        LEFT JOIN libros l ON i.id_libro = l.id
        LEFT JOIN alumnos a ON i.id_alumno = a.id
        JOIN usuarios u ON i.id_usuario = u.id
        WHERE i.estado = 'resuelta'
        ORDER BY i.fecha DESC
    ";
    $historial_incidencias = ejecutarConsulta($sqlHistorialInc)->fetchAll();
} catch (Exception $e) {
    error_log("Fallo al cargar incidencias: " . $e->getMessage());
}

// Libros para búsqueda de incidencias (TODOS, no solo disponibles)
$todos_libros_busqueda = ejecutarConsulta("SELECT id, titulo, autor, isbn, imagen_portada FROM libros ORDER BY titulo ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Gestión - Biblioteca</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" href="img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <!-- CABECERA DE GESTIÓN -->
    <header class="cabecera-gestion">
        <div class="cabecera-contenido">

            <a href="index.php" class="logo-sitio">
                <img src="img/logo.png" alt="Logo" class="logo-imagen">
                <span>Panel de Gestión</span>
            </a>

            <div class="info-usuario">
                <span style="margin-right: 15px;">
                    Hola, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                </span>
                <a href="index.php" class="boton">
                    <i class="fa-solid fa-arrow-left"></i> Volver al Catálogo</a>
                <a href="logout.php" class="boton-header boton-rojo"><i class="fa-solid fa-right-from-bracket"></i>Cerrar Sesión </a>
                <a href="perfil.php" class="boton-perfil-redondo">
                <img src="img/icono-perfil.png" alt="Perfil" class="icono-img">
                </a>
    
            </div>

        </div>
    </header>

    <main class="contenedor contenedor-pestanas">

        <!-- NOTIFICACIONES TOAST (Se activan por PHP) -->
        <?php if ($mensaje_accion): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // 1. Muestra el mensaje
                    mostrarNotificacion('<?php echo addslashes(htmlspecialchars($mensaje_accion)); ?>', '<?php echo $tipo_mensaje; ?>');

                    // 2. Limpia la URL inmediatamente para que F5 no repita el mensaje
                    if (window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.pathname);
                    }
                });
            </script>
        <?php endif; ?>

        <!-- NAVEGACIÓN DE PESTAÑAS -->
        <nav class="navegacion-pestanas">

            <button class="boton-pestana activo" data-tab="tab-prestamos">
                <i class="fa-solid fa-circle-plus" style="font-size: 1.1rem;"></i>
                Nuevo Préstamo
            </button>

            <button class="boton-pestana" data-tab="tab-devoluciones">
                <i class="fa-solid fa-rotate-left" style="font-size: 1.1rem;"></i>
                Devoluciones
            </button>

            <button class="boton-pestana" data-tab="tab-calendario">
                <i class="fa-regular fa-calendar-days" style="font-size: 1.1rem;"></i>
                Calendario
                <button class="boton-pestana" data-tab="tab-incidencias">
                    <i class="fa-solid fa-triangle-exclamation"></i> Incidencias
                </button>

                <?php if ($rol_usuario === 'directiva'): ?>

                    <div style="width: 1px; height: 18px; background: #cbd5e1; margin: 0 8px; opacity: 0.6;"></div>

                    <button class="boton-pestana" data-tab="tab-gestion-libros">
                        <i class="fa-solid fa-book-open" style="font-size: 1rem;"></i>
                        Libros
                    </button>

                    <button class="boton-pestana" data-tab="tab-gestion-usuarios">
                        <i class="fa-solid fa-user-group" style="font-size: 1rem;"></i>
                        Usuarios
                    </button>


                <?php endif; ?>

        </nav>
        <!-- CONTENIDO: NUEVO PRÉSTAMO (REDISEÑO VISUAL) -->
        <section id="tab-prestamos" class="contenido-pestana activo">
            <h2 class="titulo-seccion-prestamo">Registrar Nuevo Préstamo</h2>

            <form method="POST" action="gestion.php" id="formulario-prestamo">
                <input type="hidden" name="accion" value="nuevo_prestamo">

                <!-- Contenedor de las dos tarjetas -->
                <div class="contenedor-tarjetas-prestamo">

                    <!-- Tarjeta Izquierda: LIBRO -->
                    <div class="tarjeta-seleccion">
                        <h3> <span class="icono-titulo"><i class="fa-solid fa-book"></i></span> Seleccionar Libro</h3>

                        <div class="contenedor-buscador-visual">
                            <input type="text" id="input-busqueda-libro" class="buscador-tarjeta"
                                placeholder="Buscar libro por título o ISBN..." autocomplete="off" required>
                            <input type="hidden" name="id_libro" id="id_libro_seleccionado" required>
                            <div id="resultados-libros" class="resultados-flotantes"></div>
                        </div>

                        <!-- Área de ficha del libro seleccionado -->
                        <div id="ficha-libro" class="area-ficha vacia">
                            <span>Ningún libro seleccionado</span>
                        </div>
                    </div>

                    <!-- Tarjeta Derecha: ALUMNO -->
                    <div class="tarjeta-seleccion">
                        <h3><span class="icono-titulo"><i class="fa-solid fa-user"></i></span> Seleccionar Alumno</h3>

                        <div class="contenedor-buscador-visual">
                            <input type="text" id="input-busqueda-alumno" class="buscador-tarjeta"
                                placeholder="Buscar alumno por nombre..." autocomplete="off" required>
                            <input type="hidden" name="id_alumno" id="id_alumno_seleccionado" required>
                            <div id="resultados-alumnos" class="resultados-flotantes"></div>
                        </div>

                        <!-- Área de ficha del alumno seleccionado -->
                        <div id="ficha-alumno" class="area-ficha vacia">
                            <span>Ningún alumno seleccionado</span>
                        </div>
                    </div>
                </div>

                <!-- Selector de Fecha estilo píldora -->
                <div class="contenedor-fecha-pilula">
                    <div class="selector-fecha-pilula">
                        <label>Fecha de devolución:</label>
                        <input type="date" name="fecha_vencimiento"
                            value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                    </div>
                </div>

                <!-- Botón de confirmación grande y verde -->
                <button type="submit" class="boton-confirmar-grande">
                    <span class="icono-confirmar"></span>
                    Registrar Préstamo
                </button>
            </form>
        </section>

        <!-- CONTENIDO: DEVOLUCIONES (ESTILO EDITORIAL) -->
        <section id="tab-devoluciones" class="contenido-pestana">
            <div class="cabecera-devoluciones">
                <h2>Préstamos Activos</h2>
                <button class="boton-historial" onclick="alternarVisibilidad('tabla-historial')">
                    <i class="fa-solid fa-scroll"></i> Ver Historial
                </button>
            </div>

            <div class="cuadricula-editorial">
                <?php foreach ($prestamos_activos as $prestamo): ?>
                    <article class="tarjeta-editorial">
                        <?php
                        // Validación robusta del ISBN
                        $isbn = isset($prestamo['isbn']) ? trim($prestamo['isbn']) : '';

                        if (!empty($isbn)) {
                            // ISBN válido - usar API de OpenLibrary
                            $portadaSrc = "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg?default=false";
                        } else {
                            // Sin ISBN - usar placeholder
                            $portadaSrc = "img/sin_portada.png";
                        }
                        ?>
                        <img src="<?php echo $portadaSrc; ?>" alt="<?php echo htmlspecialchars($prestamo['titulo']); ?>"
                            class="portada-editorial"
                            style="width: 100%; height: 320px; object-fit: cover; background: #f0f0f0;"
                            onerror="this.onerror=null; this.src='img/sin_portada.png';">

                        <div class="contenido-editorial">
                            <h3 class="titulo-editorial">
                                <?php echo htmlspecialchars($prestamo['titulo']); ?>
                            </h3>
                            <p class="alumno-editorial">
                                <?php echo htmlspecialchars($prestamo['nombre_alumno']); ?> ·
                                <?php echo htmlspecialchars($prestamo['curso']); ?>
                            </p>

                            <form method="POST" action="gestion.php">
                                <input type="hidden" name="accion" value="devolver_libro">
                                <input type="hidden" name="id_prestamo" value="<?php echo $prestamo['id']; ?>">
                                <button type="submit" class="boton-devolver-editorial">Devolver</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($prestamos_activos)): ?>
                    <p class="mensaje-vacio-editorial">No hay préstamos activos en este momento.</p>
                <?php endif; ?>
            </div>

            <!-- ============================================================
                 HISTORIAL DE DEVOLUCIONES (Oculto por defecto)
                 - Mini calendario para filtrar por mes/año
                 - Por defecto muestra mes actual
                 ============================================================ -->
            <div id="tabla-historial" style="display: none; margin-top: 30px; width: 100%;">
                <h3>Historial de Devoluciones</h3>

                <!-- Mini Calendario de Filtrado -->
                <div class="mini-calendario-filtro" id="minical-devoluciones">
                    <button type="button" class="minical-flecha" onclick="cambiarMesHistorial('devoluciones', -1)">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="minical-centro">
                        <span class="minical-mes" id="minical-mes-devoluciones">Enero</span>
                        <span class="minical-anio" id="minical-anio-devoluciones">2026</span>
                    </div>
                    <button type="button" class="minical-flecha" onclick="cambiarMesHistorial('devoluciones', 1)">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <!-- Contador de resultados -->
                    <span id="contador-devoluciones" class="minical-contador"></span>
                </div>

                <!-- Tabla de historial -->
                <table id=" tabla-historial-devoluciones"
                    style="width: 100%; border-collapse: collapse; background: white;">
                    <thead>
                        <tr style="background: #eee; text-align: left;">
                            <th style="padding: 10px;">Libro</th>
                            <th style="padding: 10px;">Alumno</th>
                            <th style="padding: 10px;">Devuelto el</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h):
                            // Extraemos año y mes para filtrado
                            $fechaDevolucion = $h['fecha_devolucion_real'];
                            $anioDevolucion = date('Y', strtotime($fechaDevolucion));
                            $mesDevolucion = date('m', strtotime($fechaDevolucion));
                            ?>
                            <tr class="fila-historial-devolucion" data-anio="<?php echo $anioDevolucion; ?>"
                                data-mes="<?php echo $mesDevolucion; ?>" style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($h['titulo']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo htmlspecialchars($h['nombre_alumno']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo date('d/m/Y', strtotime($h['fecha_devolucion_real'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Mensaje cuando no hay resultados -->
                <p id="sin-resultados-devoluciones"
                    style="display: none; text-align: center; color: #7f8c8d; padding: 20px; font-style: italic;">
                    No hay devoluciones registradas en este mes.
                </p>
            </div>
        </section>

        <!-- CONTENIDO: CALENDARIO (REDISEÑO MODERNO) -->
        <section id="tab-calendario" class="contenido-pestana">
            <div class="contenedor-calendario-moderno">
                <!-- Cabecera Moderna del Calendario -->
                <div class="cabecera-calendario">
                    <button class="flecha-calendario" onclick="cambiarMes(-1)">‹</button>
                    <h2 id="titulo-mes" class="titulo-mes-calendario">Enero 2026</h2>
                    <button class="flecha-calendario" onclick="cambiarMes(1)">›</button>
                </div>

                <!-- Grid del calendario generado por JS -->
                <div id="calendario-grid" class="cuadricula-calendario-moderna"></div>
            </div>
        </section>

        <!-- CONTENIDO: INCIDENCIAS (REDISEÑO PROFESIONAL CON AUTOCOMPLETE) -->
        <section id="tab-incidencias" class="contenido-pestana">
            

            <div class="cabecera-con-boton">
                <h2 style="margin:0; color: #2c3e50;">Centro de Incidencias</h2>
                <button class="boton-historial" onclick="alternarVisibilidad('historial-incidencias-completo')">
                    <i class="fa-solid fa-scroll"></i> Ver Historial
                </button>
            </div>

            <div class="layout-incidencias-moderno">

                <!-- Columna Izquierda: Reportar -->
                <div class="columna-reportar">
                    <div class="panel-incidencia">
                        <h3 style="margin-top:0;"></span> Reportar Suceso</h3>
                        <form method="POST" action="gestion.php">
                            <input type="hidden" name="accion" value="guardar_incidencia">

                            <!-- Buscador Libros (Obligatorio) -->
                            <div class="grupo-buscador-incidencia">
                                <label style="display:block; margin-bottom:5px; font-weight:600;">Libro Relacionado
                                    *</label>
                                <input type="text" id="incidencia-libro" class="entrada-incidencia-moderna"
                                    placeholder="Buscar por título o ISBN..." required autocomplete="off">
                                <input type="hidden" name="id_libro" id="id_libro_incidencia" required>
                                <div id="resultados-incidencia-libro" class="resultados-autocompletado"></div>

                                <div id="ficha-incidencia-libro" class="ficha-visual-incidencia vacia">
                                    <span>Ningún libro seleccionado</span>
                                </div>
                            </div>

                            <!-- Buscador Alumnos (Opcional) -->
                            <div class="grupo-buscador-incidencia">
                                <label style="display:block; margin-bottom:5px; font-weight:600;">Alumno
                                    (Opcional)</label>
                                <input type="text" id="incidencia-alumno" class="entrada-incidencia-moderna"
                                    placeholder="Buscar por nombre..." autocomplete="off">
                                <input type="hidden" name="id_alumno" id="id_alumno_incidencia">
                                <div id="resultados-incidencia-alumno" class="resultados-autocompletado"></div>

                                <div id="ficha-incidencia-alumno" class="ficha-visual-incidencia vacia">
                                    <span>Incidencia General (Sin alumno)</span>
                                </div>
                            </div>

                            <div class="grupo-formulario">
                                <label style="display:block; margin-bottom:5px; font-weight:600;">Descripción del
                                    Suceso</label>
                                <textarea name="descripcion" class="entrada-incidencia-moderna"
                                    style="min-height:100px;" required placeholder="¿Qué ocurrió?"></textarea>
                            </div>

                            <div class="grupo-formulario" style="margin-top:15px;">
                                <label style="display:block; margin-bottom:5px; font-weight:600;">Sanción
                                    Aplicada</label>
                                <input type="text" name="sancion" class="entrada-incidencia-moderna"
                                    placeholder="Ej: 3 días sin préstamo">
                            </div>

                            <button type="submit" class="boton-devolver-editorial"
                                style="background: var(--cat-verde); width: 100%; margin-top:20px; padding:15px;">
                                <span class="icono-confirmar"></span> REGISTRAR INCIDENCIA
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Columna Derecha: Pendientes -->
                <div class="columna-pendientes">
                    <h3 style="margin-top:0; color:#c0392b;">Incidencias Pendientes
                        (<?php echo count($incidencias_pendientes); ?>)</h3>
                    <div class="cuadricula-incidencias">
                        <?php foreach ($incidencias_pendientes as $inc): ?>
                            <article class="tarjeta-incidencia-elegante">
                                <div class="info-superior">
                                    <div class="info-principal-incidencia">
                                        <h4><?php echo htmlspecialchars($inc['nombre_alumno']); ?></h4>
                                        <p style="margin:2px 0; font-size:0.9rem; color:#2c3e50;">
                                            <?php echo htmlspecialchars($inc['titulo_libro']); ?>
                                        </p>
                                    </div>
                                    <div class="info-secundaria-incidencia">
                                        <span> <?php echo date('d/m/Y', strtotime($inc['fecha'])); ?></span>
                                        <?php if ($inc['curso']): ?>
                                            <span>• <?php echo htmlspecialchars($inc['curso']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="texto-descripcion-incidencia">
                                        "<?php echo htmlspecialchars($inc['descripcion']); ?>"</p>

                                    <?php if ($inc['sancion']): ?>
                                        <div class="etiqueta-sancion-destacada">
                                            Sanción: <?php echo htmlspecialchars($inc['sancion']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <form method="POST" action="gestion.php">
                                    <input type="hidden" name="accion" value="resolver_incidencia">
                                    <input type="hidden" name="id_incidencia" value="<?php echo $inc['id']; ?>">
                                    <button type="submit" class="boton-resolver-verde">MARCAR COMO RESUELTA</button>
                                </form>
                            </article>
                        <?php endforeach; ?>

                        <?php if (empty($incidencias_pendientes)): ?>
                            <div
                                style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #fff; border-radius: 12px; border: 2px dashed #eee;">
                                <p style="color: #999; font-style: italic;">No hay incidencias por resolver. ¡Buen trabajo!
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ============================================================
                 HISTORIAL DE INCIDENCIAS RESUELTAS (Oculto por defecto)
                 - Mini calendario para filtrar por mes/año
                 - Por defecto muestra mes actual
                 ============================================================ -->
            <div id="historial-incidencias-completo" style="display: none; margin-top:30px;" class="panel-incidencia">
                <h3>Historial de Incidencias Resueltas</h3>

                <!-- Mini Calendario de Filtrado -->
                <div class="mini-calendario-filtro" id="minical-incidencias">
                    <button type="button" class="minical-flecha" onclick="cambiarMesHistorial('incidencias', -1)">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="minical-centro">
                        <span class="minical-mes" id="minical-mes-incidencias">Enero</span>
                        <span class="minical-anio" id="minical-anio-incidencias">2026</span>
                    </div>
                    <button type="button" class="minical-flecha" onclick="cambiarMesHistorial('incidencias', 1)">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <!-- Contador de resultados -->
                    <span id="contador-incidencias" class="minical-contador"></span>
                </div>

                <div style="overflow-x: auto;">
                    <table id="tabla-historial-incidencias" class="tabla-incidencias-datos">
                        <thead>
                            <tr style="background:#f8f9fa;">
                                <th>Fecha</th>
                                <th>Alumno</th>
                                <th>Libro</th>
                                <th>Sanción</th>
                                <th>Reportado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_incidencias as $h_inc):
                                // Extraemos año y mes para filtrado
                                $fechaIncidencia = $h_inc['fecha'];
                                $anioIncidencia = date('Y', strtotime($fechaIncidencia));
                                $mesIncidencia = date('m', strtotime($fechaIncidencia));
                                ?>
                                <tr class="fila-historial-incidencia" data-anio="<?php echo $anioIncidencia; ?>"
                                    data-mes="<?php echo $mesIncidencia; ?>">
                                    <td><?php echo date('d/m/Y', strtotime($h_inc['fecha'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($h_inc['nombre_alumno']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($h_inc['titulo_libro']); ?></td>
                                    <td style="color:#c53030;"><?php echo htmlspecialchars($h_inc['sancion']); ?></td>
                                    <td style="color:#7f8c8d;"><?php echo htmlspecialchars($h_inc['nombre_usuario']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje cuando no hay resultados -->
                <p id="sin-resultados-incidencias"
                    style="display: none; text-align: center; color: #7f8c8d; padding: 20px; font-style: italic;">
                    No hay incidencias resueltas en este mes.
                </p>
            </div>
        </section>


        <?php if ($rol_usuario === 'directiva'): ?>












            <!-- GESTION DE LIBROS TODO -->

            <section id="tab-gestion-libros" class="contenido-pestana">

                <section class="layout-inventario">

                    <aside class="panel-formulario-libro">

                        <h3 id="titulo-form-libro">
                            <i class="fa-solid fa-book-open"></i> Gestión de Libros
                        </h3>

                        <form method="POST" action="gestion.php" id="formLibros">
                            <input type="hidden" id="accion_libro" name="accion" value="guardar_libro">
                            <input type="hidden" id="libro_id" name="id_libro" value="">

                            <p class="grupo-formulario">
                                <label for="titulo"><i class="fa-solid fa-heading"></i> Título</label>
                                <input type="text" name="titulo" id="titulo" class="input-formulario" required>
                            </p>

                            <p class="grupo-formulario">
                                <label for="autor"><i class="fa-solid fa-user-pen"></i> Autor</label>
                                <input type="text" name="autor" id="autor" class="input-formulario" required>
                            </p>

                            <p class="grupo-formulario">
                                <label for="isbn"><i class="fa-solid fa-barcode"></i> ISBN</label>
                                <input type="text" name="isbn" id="isbn" class="input-formulario"
                                    placeholder="Ej: 9788424922580">
                            </p>

                            <p class="grupo-formulario">
                                <label for="categoria"><i class="fa-solid fa-palette"></i> Categoría</label>
                                <select name="color" id="categoria" class="select-formulario" required>
                                    <option value="azul">🔵 Naturaleza</option>
                                    <option value="rojo">🔴 Valores</option>
                                    <option value="verde">🟢 Infantil </option>
                                    <option value="amarillo">🟡 Inglés</option>
                                    <option value="rosa">🟣 Emociones</option>
                                    <option value="naranja">🟠 2º y 3º</option>
                                </select>
                            </p>

                            <button type="submit" class="boton-guardar-libro" id="btn-form-libro">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Libro
                            </button>
                        </form>
                    </aside>

                    <section class="panel-lista-libros">

                        <header class="cabecera-lista-libros">

                            <section class="titulo-y-contador">
                                <h3><i class="fa-solid fa-list"></i> Catálogo</h3>
                                <span class="contador-libros"><?php echo count($todos_libros); ?> libros</span>
                            </section>

                            <section class="barra-herramientas-busqueda"
                                style="margin-top: 10px; display: flex; gap: 10px;">
                                
                                <div style="position: relative; flex-grow: 1;">
                                    <input type="text" id="busqueda-libros" class="input-formulario"
                                        placeholder=" Buscar por título, autor o ISBN..." style="width: 100%;">
                                </div>

                                <select id="filtro-rapido-color" class="select-formulario" style="width: auto;">
                                    <option value="">Todos</option>
                                    <option value="azul">Azul</option>
                                    <option value="rojo">Rojo</option>
                                    <option value="verde">Verde</option>
                                    <option value="amarillo">Amarillo</option>
                                </select>
                            </section>
                        </header>

                        <?php if (empty($todos_libros)): ?>

                            <p class="mensaje-vacio-libros">
                                <i class="fa-regular fa-folder-open"></i> No hay libros registrados.
                            </p>

                        <?php else: ?>

                            <section class="lista-fichas-libros" id="contenedor-libros">

                                <?php foreach ($todos_libros as $libro): ?>
                                    <?php
                                    $isbn = trim($libro['isbn'] ?? '');
                                    $img_local = $libro['imagen_portada'] ?? null;
                                    $url_portada = '';

                                    // Lógica de Prioridad: 1. Local -> 2. OpenLibrary -> 3. Fallback
                                    if (!empty($img_local) && file_exists(__DIR__ . '/uploads/portadas/' . basename($img_local))) {
                                        // Usamos basename para seguridad, aunque en BD debería estar limpio
                                        $url_portada = 'uploads/portadas/' . basename($img_local) . '?v=' . filemtime(__DIR__ . '/uploads/portadas/' . basename($img_local));
                                    } elseif (!empty($isbn)) {
                                        $url_portada = "https://covers.openlibrary.org/b/isbn/{$isbn}-S.jpg?default=false";
                                    } else {
                                        $url_portada = "img/sin_portada.png";
                                    }
                                    ?>

                                    <article class="ficha-libro-horizontal"
                                        data-titulo="<?php echo strtolower($libro['titulo']); ?>"
                                        data-autor="<?php echo strtolower($libro['autor']); ?>"
                                        data-color="<?php echo $libro['color_categoria']; ?>">

                                        <img src="<?php echo $portadaUrl; ?>" class="mini-portada-libro" alt="Portada"
                                            onerror="this.src='img/sin_portada.png'">

                                        <section class="info-ficha-libro">
                                            <span
                                                class="titulo-ficha-libro"><?php echo htmlspecialchars($libro['titulo']); ?> <span style="display:none;"><?php echo htmlspecialchars($isbn); ?></span></span>
                                            <span class="autor-ficha-libro">
                                                <i class="fa-solid fa-feather"></i> <?php echo htmlspecialchars($libro['autor']); ?>
                                            </span>
                                        </section>

                                        <span class="punto-color-categoria"
                                            style="display:inline-block; background-color: var(--cat-<?php echo $libro['color_categoria']; ?>);"
                                            title="Categoría: <?php echo $libro['color_categoria']; ?>">
                                        </span>

                                        <footer class="acciones-ficha-libro">

                                            <button type="button" class="btn-editar-libro" title="Editar"
                                                onclick="editarLibro('<?php echo $libro['id']; ?>', '<?php echo addslashes(htmlspecialchars($libro['titulo'])); ?>', '<?php echo addslashes(htmlspecialchars($libro['autor'])); ?>', '<?php echo $libro['isbn']; ?>', '<?php echo $libro['color_categoria']; ?>')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <a href="gestion.php?borrar_libro=<?php echo $libro['id']; ?>"
                                                class="btn-eliminar-libro" title="Eliminar"
                                                onclick="return confirm('¿Seguro que quieres borrar este libro?');"
                                                style="text-decoration: none; display: inline-block; text-align: center; color: #dc3545;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>

                                        </footer>
                                    </article> <?php endforeach; ?>
                            </section>
                        <?php endif; ?>
                    </section>
                </section>
            </section>























            <!-- GESTION DE USUARIOS TODO -->


            <section id="tab-gestion-usuarios" class="contenido-pestana">

                <article class="bloque-gestion">

                    <aside class="panel-formulario tarjeta-sombra">
                        <header class="cabecera-formulario">
                            <h3><i class="fa-solid fa-user-graduate"></i> Nuevo Alumno</h3>
                        </header>

                        <form method="POST" action="gestion.php" id="formAlumno" class="form-gestion">
                            <input type="hidden" name="accion" id="accion_alumno" value="guardar_alumno">
                            <input type="hidden" name="id_alumno" id="id_alumno" value="">

                            <div class="grupo-input">
                                <label for="nombre_alumno">Nombre Completo</label>
                                <input type="text" name="nombre" id="nombre_alumno" required placeholder="Ej. Juan Pérez">
                            </div>

                            <div class="grupo-input">
                                <label for="curso_alumno">Curso</label>
                                <input type="text" name="curso" id="curso_alumno" required placeholder="Ej. 4º ESO B">
                            </div>

                            <div class="acciones-form">
                                <button type="submit" id="btn-form-alumno" class="btn-principal">
                                    Guardar Alumno
                                </button>
                                <button type="button" id="btn-cancelar-alumno" class="btn-secundario"
                                    onclick="cancelarEdicionAlumno()">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </aside>

                    <div class="panel-lista tarjeta-sombra">
                        <header class="cabecera-lista">
                            <h3>Listado de Alumnos</h3>
                        </header>

                        <div class="barra-herramientas">
                            <div class="buscador-icono">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="busqueda-alumno" class="input-busqueda"
                                    placeholder="Buscar alumno...">
                            </div>
                        </div>

                        <div class="contenedor-tabla-scroll">
                            <?php if (empty($todos_alumnos_gestion)): ?>
                                <div class="mensaje-vacio">No hay alumnos registrados.</div>
                            <?php else: ?>
                                <table class="tabla-gestion" id="tabla-alumnos">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Curso</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todos_alumnos_gestion as $alu): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($alu['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($alu['curso']); ?></td>
                                                <td class="acciones-celda">
                                                    <button type="button" class="btn-icono editar"
                                                        onclick="editarAlumno('<?php echo $alu['id']; ?>', '<?php echo addslashes(htmlspecialchars($alu['nombre'])); ?>', '<?php echo addslashes(htmlspecialchars($alu['curso'])); ?>')"
                                                        title="Editar">
                                                        <i class="fa-solid fa-pencil"></i>
                                                    </button>
                                                    <a href="gestion.php?borrar_alumno=<?php echo $alu['id']; ?>"
                                                        class="btn-icono eliminar"
                                                        onclick="return confirm('¿Eliminar a <?php echo addslashes(htmlspecialchars($alu['nombre'])); ?>?');"
                                                        title="Eliminar">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>

                <article class="bloque-gestion">

                    <aside class="panel-formulario tarjeta-sombra">
                        <header class="cabecera-formulario">
                            <h3 id="titulo-form-docente"><i class="fa-solid fa-chalkboard-user"></i> Nuevo Docente</h3>
                        </header>

                        <form method="POST" action="gestion.php" id="formDocente" class="form-gestion">
                            <input type="hidden" name="accion" value="guardar_usuario">
                            <input type="hidden" name="id_usuario" id="id_usuario" value="">

                            <div class="grupo-input">
                                <label for="nombre_docente">Nombre Completo</label>
                                <input type="text" name="nombre" id="nombre_docente" required
                                    placeholder="Ej. María García">
                            </div>

                            <div class="grupo-input">
                                <label for="usuario_docente">Usuario (Login)</label>
                                <input type="text" name="usuario" id="usuario_docente" required
                                    placeholder="usuario@cole.com">
                            </div>

                            <div class="grupo-input">
                                <label for="password_docente">Contraseña</label>
                                <input type="password" name="password" id="password_docente"
                                    placeholder="(Dejar vacío si no cambia)">
                            </div>

                            <div class="grupo-input">
                                <label for="rol_docente">Rol</label>
                                <select name="rol" id="rol_docente">
                                    <option value="profesor">Profesor</option>
                                    <option value="directiva">Directiva</option>
                                </select>
                            </div>

                            <div class="acciones-form">
                                <button type="submit" id="btn-form-docente" class="btn-principal oscuro">
                                    Guardar Docente
                                </button>
                                <button type="button" id="btn-cancelar-docente" class="btn-secundario"
                                    onclick="cancelarEdicionDocente()">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </aside>

                    <div class="panel-lista tarjeta-sombra">
                        <header class="cabecera-lista">
                            <h3>Listado de Personal</h3>
                        </header>

                        <div class="barra-herramientas">
                            <div class="buscador-icono">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="busqueda-docente" class="input-busqueda"
                                    placeholder="Buscar docente...">
                            </div>
                        </div>

                        <div class="contenedor-tabla-scroll">
                            <?php if (empty($todos_usuarios)): ?>
                                <div class="mensaje-vacio">No hay usuarios registrados.</div>
                            <?php else: ?>
                                <table class="tabla-gestion" id="tabla-docentes">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Usuario</th>
                                            <th>Rol</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todos_usuarios as $usr): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($usr['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($usr['usuario']); ?></td>
                                                <td>
                                                    <span class="badge-rol <?php echo $usr['rol']; ?>">
                                                        <?php echo ucfirst($usr['rol']); ?>
                                                    </span>
                                                </td>
                                                <td class="acciones-celda">
                                                    <button type="button" class="btn-icono editar"
                                                        onclick="editarDocente('<?php echo $usr['id']; ?>', '<?php echo addslashes(htmlspecialchars($usr['nombre'])); ?>', '<?php echo addslashes(htmlspecialchars($usr['usuario'])); ?>', '<?php echo $usr['rol']; ?>')"
                                                        title="Editar">
                                                        <i class="fa-solid fa-pencil"></i>
                                                    </button>
                                                    <?php if ($usr['id'] != $_SESSION['usuario_id']): ?>
                                                        <a href="gestion.php?borrar_usuario=<?php echo $usr['id']; ?>"
                                                            class="btn-icono eliminar"
                                                            onclick="return confirm('¿Eliminar usuario <?php echo addslashes(htmlspecialchars($usr['nombre'])); ?>?');"
                                                            title="Eliminar">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </section>
        <?php endif; ?>

    </main>


    <script src="assets/main.js"></script>
    <script>
        // DATOS PARA BUSCADOR VISUAL Y CALENDARIO
        const dbLibros = <?php echo json_encode($libros_disponibles); ?>;
        const dbAlumnos = <?php echo json_encode($alumnos); ?>;
        const dbLibrosIncidencias = <?php echo json_encode($todos_libros_busqueda); ?>;
        const prestamosCargados = <?php echo $eventos_calendario ?: '[]'; ?>;

        // Inicializar componentes
        document.addEventListener('DOMContentLoaded', () => {
            // Inicializar calendario
            renderizarCalendario(new Date(), prestamosCargados);
            cargarPortadasLibros();

            // Buscadores Préstamos
            iniciarBuscadorVisual('input-busqueda-libro', 'resultados-libros', 'id_libro_seleccionado', dbLibros, 'libro');
            iniciarBuscadorVisual('input-busqueda-alumno', 'resultados-alumnos', 'id_alumno_seleccionado', dbAlumnos, 'alumno');

            // Buscadores Incidencias
            iniciarBuscadorIncidencias('incidencia-libro', 'resultados-incidencia-libro', 'id_libro_incidencia', dbLibrosIncidencias, 'libro', 'ficha-incidencia-libro');
            iniciarBuscadorIncidencias('incidencia-alumno', 'resultados-incidencia-alumno', 'id_alumno_incidencia', dbAlumnos, 'alumno', 'ficha-incidencia-alumno');
        });

        function iniciarBuscadorIncidencias(idInput, idContenedor, idHidden, datos, tipo, idFicha) {
            const input = document.getElementById(idInput);
            const contenedor = document.getElementById(idContenedor);
            const hidden = document.getElementById(idHidden);
            const ficha = document.getElementById(idFicha);

            if (!input) return;

            input.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                contenedor.innerHTML = '';
                if (query.length < 2) { contenedor.style.display = 'none'; return; }

                const resultados = datos.filter(item => {
                    if (tipo === 'libro') {
                        const titulo = (item.titulo || "").toLowerCase();
                        const isbn = (item.isbn || "").toLowerCase();
                        return titulo.includes(query) || isbn.includes(query);
                    } else {
                        return (item.nombre || "").toLowerCase().includes(query);
                    }
                });

                if (resultados.length > 0) {
                    contenedor.style.display = 'block';
                    resultados.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'item-resultado';
                        div.innerHTML = tipo === 'libro' ? `<strong>${item.titulo}</strong><br><small>${item.isbn}</small>` : `<strong>${item.nombre}</strong><br><small>${item.curso}</small>`;

                        div.onclick = () => {
                            input.value = tipo === 'libro' ? item.titulo : item.nombre;
                            hidden.value = item.id;
                            contenedor.style.display = 'none';
                            actualizarFichaVisual(item, tipo, ficha);
                        };
                        contenedor.appendChild(div);
                    });
                } else { contenedor.style.display = 'none'; }
            });
        }

        function actualizarFichaVisual(item, tipo, ficha) {
            ficha.classList.remove('vacia');
            ficha.classList.add('activa');
            if (tipo === 'libro') {
                // Usamos el helper global definido en main.js
                const url = obtenerRutaPortada(item);
                
                ficha.innerHTML = `
                    <div class="ficha-libro-seleccionado">
                        <img src="${url}" class="portada-ficha" onerror="this.src='img/sin_portada.png'">
                        <div class="datos-ficha">
                            <div class="titulo-ficha">${item.titulo}</div>
                            <div class="autor-ficha">${item.autor}</div>
                        </div>
                    </div>`;
            } else {
                ficha.innerHTML = `
                    <div class="ficha-alumno-seleccionado">
                        <div class="icono-usuario-grande">👤</div>
                        <div class="datos-ficha">
                            <div class="nombre-ficha">${item.nombre}</div>
                            <div class="curso-ficha">${item.curso}</div>
                        </div>
                    </div>`;
            }
        }

        // ==========================================
        // LÓGICA CALENDARIO
        // ==========================================
        let fechaActual = new Date();

        function cambiarMes(delta) {
            fechaActual.setMonth(fechaActual.getMonth() + delta);
            renderizarCalendario(fechaActual, prestamosCargados);
        }

        function renderizarCalendario(fecha, eventos) {
            const grid = document.getElementById('calendario-grid');
            const titulo = document.getElementById('titulo-mes');

            // Nombres de meses en español
            const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

            titulo.textContent = `${meses[fecha.getMonth()]} ${fecha.getFullYear()}`;
            grid.innerHTML = ''; // Limpiar

            // Cabeceras días de la semana
            const diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
            diasSemana.forEach(dia => {
                const header = document.createElement('div');
                header.className = 'cabecera-dia-semana';
                header.textContent = dia;
                grid.appendChild(header);
            });

            // Primer día del mes y total de días
            const primerDia = new Date(fecha.getFullYear(), fecha.getMonth(), 1).getDay();
            const diasEnMes = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0).getDate();

            // Fecha de hoy para comparar
            const hoy = new Date();
            const esHoy = (d) => d === hoy.getDate() &&
                fecha.getMonth() === hoy.getMonth() &&
                fecha.getFullYear() === hoy.getFullYear();

            // Relleno días vacíos
            for (let i = 0; i < primerDia; i++) {
                const vacio = document.createElement('div');
                vacio.className = 'dia-vacio';
                grid.appendChild(vacio);
            }

            // Días del mes
            for (let dia = 1; dia <= diasEnMes; dia++) {
                const celda = document.createElement('div');
                celda.className = 'celda-dia-moderna';

                // Marcar día actual
                if (esHoy(dia)) {
                    celda.classList.add('es-hoy');
                }

                // Número del día
                const numDia = document.createElement('span');
                numDia.className = 'numero-dia';
                numDia.textContent = dia;
                celda.appendChild(numDia);

                // Contenedor de eventos
                const eventosContainer = document.createElement('div');
                eventosContainer.className = 'eventos-dia';

                // Buscar eventos para este día
                const fechaString = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;

                eventos.forEach(ev => {
                    if (ev.fecha_vencimiento === fechaString) {
                        const pill = document.createElement('div');

                        // Lógica de colores por urgencia
                        const fechaVenc = new Date(ev.fecha_vencimiento);
                        const diffDias = Math.ceil((fechaVenc - hoy) / (1000 * 60 * 60 * 24));

                        let claseUrgencia = 'evento-verde';
                        if (diffDias < 0) claseUrgencia = 'evento-rojo';
                        else if (diffDias <= 3) claseUrgencia = 'evento-naranja';
                        else if (diffDias <= 7) claseUrgencia = 'evento-amarillo';

                        pill.className = `pilula-evento ${claseUrgencia}`;

                        // Mostrar nombre del alumno truncado
                        const nombreAlumno = ev.nombre_alumno || 'Alumno';
                        pill.textContent = nombreAlumno.length > 12 ? nombreAlumno.substring(0, 12) + '...' : nombreAlumno;
                        pill.title = `${ev.titulo} - ${nombreAlumno}`;

                        // Evento click para mostrar toast con información del préstamo
                        pill.style.cursor = 'pointer';
                        pill.addEventListener('click', function () {
                            const tituloLibro = ev.titulo || 'Libro desconocido';
                            const cursoAlumno = ev.curso || '';
                            const mensaje = `<i class="fa-solid fa-book"></i>  ${tituloLibro}\n <br> <i class="fa-solid fa-graduation-cap"></i>   ${nombreAlumno}${cursoAlumno ? ' - ' + cursoAlumno : ''}`;
                            mostrarNotificacion(mensaje, 'info', 4000);
                        });

                        eventosContainer.appendChild(pill);
                    }
                });

                celda.appendChild(eventosContainer);
                grid.appendChild(celda);
            }
        }

        // ==========================================
        // FUNCIONES DE FILTRADO DE HISTORIAL POR MES/AÑO
        // ==========================================

        /**
         * Inicializa los selectores de fecha con el mes y año actual
         * Se ejecuta al cargar la página
         */
        // ==========================================
        // FUNCIONES DE FILTRADO DE HISTORIAL (MINI CALENDARIO)
        // ==========================================

        // Estado global de los filtros
        const estadoFiltros = {
            devoluciones: { mes: new Date().getMonth() + 1, anio: new Date().getFullYear() },
            incidencias: { mes: new Date().getMonth() + 1, anio: new Date().getFullYear() }
        };

        const NOMBRES_MESES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

        /**
         * Inicializa los mini calendarios con el mes actual
         */
        function inicializarFiltrosHistorial() {
            actualizarVisualMinical('devoluciones');
            actualizarVisualMinical('incidencias');
            filtrarTablas('devoluciones');
            filtrarTablas('incidencias');
        }

        /**
         * Cambia el mes del historial
         * @param {string} tipo - 'devoluciones' o 'incidencias'
         * @param {number} delta - +1 (siguiente) o -1 (anterior)
         */
        function cambiarMesHistorial(tipo, delta) {
            let estado = estadoFiltros[tipo];

            // Calcular nuevo mes
            let nuevoMes = estado.mes + delta;

            if (nuevoMes > 12) {
                estado.mes = 1;
                estado.anio++;
            } else if (nuevoMes < 1) {
                estado.mes = 12;
                estado.anio--;
            } else {
                estado.mes = nuevoMes;
            }

            actualizarVisualMinical(tipo);
            filtrarTablas(tipo);
        }

        /**
         * Actualiza el texto del mini calendario (Mes y Año)
         */
        function actualizarVisualMinical(tipo) {
            const estado = estadoFiltros[tipo];
            const elMes = document.getElementById(`minical-mes-${tipo}`);
            const elAnio = document.getElementById(`minical-anio-${tipo}`);

            if (elMes) elMes.textContent = NOMBRES_MESES[estado.mes - 1];
            if (elAnio) elAnio.textContent = estado.anio;
        }

        /**
         * Filtra las filas de la tabla correspondiente
         */
        function filtrarTablas(tipo) {
            const estado = estadoFiltros[tipo];
            const mesStr = String(estado.mes).padStart(2, '0');
            const anioStr = String(estado.anio);

            // Selectores dinámicos según el tipo
            const selectorFilas = tipo === 'devoluciones' ? '.fila-historial-devolucion' : '.fila-historial-incidencia';
            const idContador = tipo === 'devoluciones' ? 'contador-devoluciones' : 'contador-incidencias';
            const idMensajeVacio = tipo === 'devoluciones' ? 'sin-resultados-devoluciones' : 'sin-resultados-incidencias';

            const filas = document.querySelectorAll(selectorFilas);
            const contador = document.getElementById(idContador);
            const mensajeVacio = document.getElementById(idMensajeVacio);

            let visibles = 0;

            filas.forEach(fila => {
                const anioFila = fila.getAttribute('data-anio');
                const mesFila = fila.getAttribute('data-mes');

                if (anioFila === anioStr && mesFila === mesStr) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            // Actualizar UI
            if (contador) {
                contador.textContent = visibles > 0 ? `(${visibles} registros)` : '';
            }
            if (mensajeVacio) {
                mensajeVacio.style.display = visibles === 0 ? 'block' : 'none';
            }
        }

        // Inicializar al cargar
        document.addEventListener('DOMContentLoaded', inicializarFiltrosHistorial);
    </script>

</body>

</html>