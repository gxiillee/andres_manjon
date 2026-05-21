<?php
/**
 * BIBLIOTECA "ANDRÉS MANJÓN" - Catálogo Público
 * Versión: HTML5 Semántico + Edición de Sinopsis (Todo en uno)
 */

// 1. INICIO DE SESIÓN Y BUFFER (Para evitar errores de JSON)
ob_start(); // Iniciamos el buffer para limpiar cualquier error visual previo
session_start();
require_once 'db.php';

// =================================================================================
// 👑 BLOQUE 1: LÓGICA PARA GUARDAR SINOPSIS (SOLO DIRECTIVA)
// =================================================================================
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if ($input && isset($input['accion']) && $input['accion'] === 'guardar_sinopsis_index') {

    // Limpiamos cualquier salida previa (espacios, warnings) para responder solo JSON limpio
    ob_end_clean();
    header('Content-Type: application/json');

    // A) VERIFICACIÓN DE SEGURIDAD (DIRECTIVA)
    if (!isset($_SESSION['usuario_id']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'directiva')) {
        echo json_encode(['status' => 'error', 'message' => 'Permiso denegado. Solo personal de directiva.']);
        exit;
    }

    // B) VALIDACIÓN DE DATOS
    $idLibro = $input['id_libro'] ?? null;
    $sinopsis = $input['sinopsis'] ?? null;

    if (!$idLibro || empty(trim($sinopsis))) {
        echo json_encode(['status' => 'error', 'message' => 'Datos vacíos o sinopsis muy corta.']);
        exit;
    }

    // C) GUARDAR EN BASE DE DATOS
    try {
        $sql = "UPDATE libros SET sinopsis = :desc WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        // --- AQUÍ ESTABA EL ERROR: Faltaba ejecutar y guardar en $res ---
        $res = $stmt->execute([':desc' => $sinopsis, ':id' => $idLibro]);

        if ($res) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar en la BD.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $e->getMessage()]);
    }

    exit; // Fin de la petición AJAX.
}

// =================================================================================
// 🖼️ BLOQUE 1B: EDICIÓN AVANZADA (PORTADA + SINOPSIS) — FormData
// =================================================================================
if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_edicion_avanzada') {

    ob_end_clean();
    header('Content-Type: application/json');

    // A) SEGURIDAD
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'directiva') {
        echo json_encode(['status' => 'error', 'message' => 'Permiso denegado. Solo personal de directiva.']);
        exit;
    }

    $idLibro = $_POST['id_libro'] ?? null;
    $sinopsis = $_POST['sinopsis'] ?? null;

    if (!$idLibro) {
        echo json_encode(['status' => 'error', 'message' => 'ID de libro no proporcionado.']);
        exit;
    }

    $rutaImagenNueva = null; // Se mantiene NULL si no se sube imagen
    $dirPortadas = __DIR__ . '/uploads/portadas/';

    // Crear directorio si no existe
    if (!is_dir($dirPortadas)) {
        mkdir($dirPortadas, 0755, true);
    }

    // B) PROCESAR IMAGEN (si se envió)
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['portada'];

        // Validar tamaño (máx 2MB)
        if ($archivo['size'] > 2 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'La imagen es demasiado grande. Máximo 2MB.']);
            exit;
        }

        // Validar tipo MIME real
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($archivo['tmp_name']);
        $mimesPermitidos = ['image/jpeg', 'image/png'];

        if (!in_array($mimeReal, $mimesPermitidos)) {
            echo json_encode(['status' => 'error', 'message' => 'Formato no permitido. Solo JPG y PNG.']);
            exit;
        }

        // Extensión basada en MIME real
        $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $ext = $extensiones[$mimeReal];

        // Nombre seguro: libro_{id}_{timestamp}.ext
        $nombreArchivo = 'libro_' . intval($idLibro) . '_' . time() . '.' . $ext;
        $rutaCompleta = $dirPortadas . $nombreArchivo;
        $rutaImagenNueva = 'uploads/portadas/' . $nombreArchivo;

        // C) LIMPIEZA: Borrar imagen antigua si existe
        try {
            $sqlAntigua = "SELECT imagen_portada FROM libros WHERE id = :id";
            $stmtAntigua = $pdo->prepare($sqlAntigua);
            $stmtAntigua->execute([':id' => $idLibro]);
            $libroActual = $stmtAntigua->fetch();

            if ($libroActual && !empty($libroActual['imagen_portada'])) {
                $rutaAntigua = __DIR__ . '/' . $libroActual['imagen_portada'];
                if (file_exists($rutaAntigua)) {
                    unlink($rutaAntigua);
                }
            }
        } catch (Exception $e) {
            // No bloquear si falla la limpieza
        }

        // D) MOVER ARCHIVO
        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la imagen en el servidor.']);
            exit;
        }
    }

    // E) GUARDAR EN BASE DE DATOS
    try {
        if ($rutaImagenNueva !== null && $sinopsis !== null && trim($sinopsis) !== '') {
            // Ambos: sinopsis + imagen
            $sql = "UPDATE libros SET sinopsis = :desc, imagen_portada = :img WHERE id = :id";
            $params = [':desc' => $sinopsis, ':img' => $rutaImagenNueva, ':id' => $idLibro];
        } elseif ($rutaImagenNueva !== null) {
            // Solo imagen
            $sql = "UPDATE libros SET imagen_portada = :img WHERE id = :id";
            $params = [':img' => $rutaImagenNueva, ':id' => $idLibro];
        } elseif ($sinopsis !== null && trim($sinopsis) !== '') {
            // Solo sinopsis
            $sql = "UPDATE libros SET sinopsis = :desc WHERE id = :id";
            $params = [':desc' => $sinopsis, ':id' => $idLibro];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No hay datos para guardar.']);
            exit;
        }

        $stmt = $pdo->prepare($sql);
        $res = $stmt->execute($params);

        if ($res) {
            $respuesta = ['status' => 'success'];
            if ($rutaImagenNueva !== null) {
                $respuesta['imagen_url'] = $rutaImagenNueva;
            }
            echo json_encode($respuesta);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar en la BD.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $e->getMessage()]);
    }

    exit;
}

// Si no es petición AJAX, liberamos el buffer y mostramos la web
ob_end_flush();

// =================================================================================
// 📚 BLOQUE 2: CARGA NORMAL DE LA PÁGINA (VISUALIZACIÓN)
// =================================================================================

try {
    // Consulta optimizada: Agrupa por ISBN y cuenta disponibles
    $sql = "SELECT *, 
            COUNT(*) as total_copias,
            SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles
            FROM libros 
            GROUP BY isbn 
            ORDER BY titulo ASC";

    $stmt = $pdo->query($sql);
    $libros = $stmt->fetchAll();

    // Selección aleatoria para el libro destacado
    $libro_destacado = null;
    if (count($libros) > 0) {
        $indice_aleatorio = array_rand($libros);
        $libro_destacado = $libros[$indice_aleatorio];
    }

} catch (PDOException $e) {
    $error = "Error al cargar el catálogo: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca - Colegio Andrés Manjón</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="img/favicon.ico">
</head>

<body>

    <header class="cabecera-principal">
        <div class="cabecera-contenido">
            <figure class="logo-sitio">
                <img src="img/logo.png" alt="Escudo Colegio Andrés Manjón" class="logo-imagen"
                    onerror="this.style.display='none'">
                <figcaption>
                    <h2>Biblioteca: Andrés Manjón</h2>
                </figcaption>
            </figure>

            <nav class="navegacion-usuario">
                <?php if (isset($_SESSION['usuario_id'])): ?>

                    <a href="gestion.php" class="boton-header boton-dorado">
                        <i class="fa-solid fa-gear"></i> Panel de Gestión
                    </a>

                    <a href="logout.php" class="boton-header boton-rojo">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                    </a>

                <?php else: ?>

                    <a href="login.php" class="boton-header">
                        <i class="fa-solid fa-user-tie"></i> Acceso Profesores
                    </a>

                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="contenedor">

        <section class="seccion-destacada" aria-label="Libro recomendado">
            <?php if ($libro_destacado): ?>
                <?php
                $isbn_destacado = trim($libro_destacado['isbn'] ?? '');
                $img_local_dest = $libro_destacado['imagen_portada'] ?? null;
                // Prioridad: imagen local → OpenLibrary → fallback
                if (!empty($img_local_dest) && file_exists(__DIR__ . '/' . $img_local_dest)) {
                    $url_portada_destacada = $img_local_dest . '?v=' . filemtime(__DIR__ . '/' . $img_local_dest);
                } elseif (!empty($isbn_destacado)) {
                    $url_portada_destacada = "https://covers.openlibrary.org/b/isbn/{$isbn_destacado}-L.jpg?default=false";
                } else {
                    $url_portada_destacada = "img/sin_portada.png";
                }
                ?>
                <article class="banner-recomendacion">
                    <div class="contenido-banner">
                        <figure class="contenedor-imagen-banner">
                            <img class="portada-banner" src="<?php echo $url_portada_destacada; ?>"
                                alt="Portada de <?php echo htmlspecialchars($libro_destacado['titulo']); ?>"
                                onerror="this.onerror=null; this.src='img/sin_portada.png';">
                        </figure>

                        <div class="info-banner">
                            <span class="etiqueta-destacada">✨ RECOMENDACIÓN DEL DÍA</span>
                            <h2 class="titulo-destacado"><?php echo htmlspecialchars($libro_destacado['titulo']); ?></h2>
                            <p class="autor-destacado">Autor: <?php echo htmlspecialchars($libro_destacado['autor']); ?></p>

                            <button class="boton-sinopsis-destacado" type="button" onclick="abrirModalDesdeBoton(this)"
                                data-id="<?php echo $libro_destacado['id']; ?>"
                                data-titulo="<?php echo htmlspecialchars($libro_destacado['titulo']); ?>"
                                data-autor="<?php echo htmlspecialchars($libro_destacado['autor']); ?>"
                                data-desc="<?php echo htmlspecialchars($libro_destacado['sinopsis'] ?? ''); ?>"
                                data-isbn="<?php echo htmlspecialchars($libro_destacado['isbn']); ?>"
                                data-estado="<?php echo htmlspecialchars($libro_destacado['estado']); ?>"
                                data-imagen-local="<?php echo htmlspecialchars($libro_destacado['imagen_portada'] ?? ''); ?>">
                                LEER SINOPSIS →
                            </button>
                        </div>
                    </div>
                </article>
            <?php endif; ?>

            <div class="contenedor-buscador-flotante" role="search">
                <input type="text" id="buscador-libros" class="input-buscador-destacado"
                    placeholder="🔍 Buscar libro por título, autor..." onkeyup="filtrarLibrosGlobal()">
            </div>

            <nav class="contenedor-filtros-v1" aria-label="Filtros de libros">
                <button class="filtro-pill gris activo" onclick="filtrarPorColor('todos')">Todos</button>
                <button class="filtro-pill verde" onclick="filtrarPorColor('verde')">Infantil / 1º Ciclo</button>
                <button class="filtro-pill naranja" onclick="filtrarPorColor('naranja')">2º y 3º Ciclo</button>
                <button class="filtro-pill azul" onclick="filtrarPorColor('azul')">Naturaleza</button>
                <button class="filtro-pill rojo" onclick="filtrarPorColor('rojo')">Valores</button>
                <button class="filtro-pill rosa" onclick="filtrarPorColor('rosa')">Emociones</button>
                <button class="filtro-pill amarillo" onclick="filtrarPorColor('amarillo')">Inglés</button>
            </nav>
        </section>

        <section class="cuadricula-libros" id="contenedor-libros">
            <?php if (isset($error)): ?>
                <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
                <?php if (empty($libros)): ?>
                    <p style="text-align:center; width:100%; color:#666;">No se encontraron libros en la biblioteca.</p>
                <?php else: ?>

                    <?php foreach ($libros as $libro):
                        // Prioridad de imagen: local → OpenLibrary → fallback
                        $img_local = $libro['imagen_portada'] ?? null;
                        if (!empty($img_local) && file_exists(__DIR__ . '/' . $img_local)) {
                            $url_portada = $img_local . '?v=' . filemtime(__DIR__ . '/' . $img_local);
                        } elseif (!empty($libro['isbn'])) {
                            $url_portada = 'https://covers.openlibrary.org/b/isbn/' . $libro['isbn'] . '-M.jpg?default=false';
                        } else {
                            $url_portada = 'img/sin_portada.png';
                        }

                        $esta_disponible = $libro['disponibles'] > 0;
                        $clase_estado = $esta_disponible ? 'disponible' : 'prestado';
                        $texto_estado = $esta_disponible ? 'Disponible' : 'Prestado';
                        ?>

                        <article class="tarjeta tarjeta-libro"
                            data-color="<?php echo htmlspecialchars($libro['color_categoria']); ?>"
                            data-isbn="<?php echo htmlspecialchars($libro['isbn'] ?? ''); ?>">
                            <figure class="contenedor-portada">
                                <img class="portada-libro" loading="lazy" src="<?php echo $url_portada; ?>"
                                    alt="Portada de <?php echo htmlspecialchars($libro['titulo']); ?>"
                                    onerror="this.onerror=null; this.src='img/sin_portada.png';">
                            </figure>

                            <div class="info-libro">
                                <header>
                                    <h3 class="titulo-libro"><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                                    <p class="autor-libro"><?php echo htmlspecialchars($libro['autor']); ?></p>
                                </header>

                                <footer class="pie-tarjeta">
                                    <div class="estado-y-cat">
                                        <span class="etiqueta-estado <?php echo $clase_estado; ?>">
                                            <?php echo $texto_estado; ?>
                                        </span>

                                        <span class="punto-categoria"
                                            title="Categoría: <?php echo ucfirst($libro['color_categoria']); ?>"
                                            style="background-color: var(--cat-<?php echo $libro['color_categoria']; ?>)">
                                        </span>
                                    </div>

                                    <button class="boton-detalle" onclick="abrirModalDesdeBoton(this)"
                                        data-id="<?php echo $libro['id']; ?>"
                                        data-titulo="<?php echo htmlspecialchars($libro['titulo']); ?>"
                                        data-autor="<?php echo htmlspecialchars($libro['autor']); ?>"
                                        data-isbn="<?php echo htmlspecialchars($libro['isbn']); ?>"
                                        data-estado="<?php echo $esta_disponible ? 'disponible' : 'prestado'; ?>"
                                        data-desc="<?php echo htmlspecialchars($libro['sinopsis'] ?? ''); ?>"
                                        data-imagen-local="<?php echo htmlspecialchars($libro['imagen_portada'] ?? ''); ?>">
                                        Ver detalles
                                    </button>
                                </footer>
                            </div>
                        </article>

                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    </main>
    <!-- Modal de sinopsis -->
    <div id="modal-sinopsis" class="fondo-modal" style="display:none;" aria-hidden="true">
        <section class="contenido-modal" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <button class="cerrar-modal" onclick="cerrarModal()" aria-label="Cerrar ventana">×</button>

            <div class="rejilla-modal">
                <figure class="imagen-modal">
                    <img id="modal-img" src="" alt="Portada del libro">
                </figure>

                <article class="texto-modal">
                    <h2 id="modal-titulo">Título del Libro</h2>
                    <h4 id="modal-autor">Autor</h4>
                    <span id="modal-estado" class="etiqueta-estado">Disponible</span>

                    <hr class="separador-modal">

                    <div id="modal-desc" class="cuerpo-descripcion">
                    </div>
                </article>
            </div>
        </section>
    </div>
    <script>
        const ES_DIRECTIVA = <?php echo (isset($_SESSION['rol']) && $_SESSION['rol'] === 'directiva') ? 'true' : 'false'; ?>;
    </script>
    <script src="assets/main.js?v=<?php echo time(); ?>"></script>
</body>

</html>