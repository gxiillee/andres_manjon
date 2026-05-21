/**
 * ============================================================================
 * ARCHIVO PRINCIPAL DE JAVASCRIPT (MAIN.JS)
 * Sistema de Gestión Bibliotecaria
 * * ÍNDICE DE CONTENIDOS:
 * 1. UTILIDADES Y HELPERS GLOBALES
 * 2. SISTEMA DE NOTIFICACIONES (TOASTS)
 * 3. SISTEMA DE PESTAÑAS (TABS)
 * 4. GESTIÓN DE MODALES Y API DE GOOGLE BOOKS
 * 5. GESTIÓN DE IMÁGENES Y PORTADAS (OPEN LIBRARY)
 * 6. BUSCADOR VISUAL Y AUTOCOMPLETADO
 * 7. GESTIÓN DE FORMULARIOS (EDICIÓN DE DATOS)
 * 8. LOGICA DE FILTRADO (EVENT LISTENERS Y DOM)
 * ============================================================================
 */

/* ==========================================================================
   1. UTILIDADES Y HELPERS GLOBALES
   Funciones de ayuda general para la interfaz.
   ========================================================================== */

/**
 * Devuelve la URL de la portada de un libro con la prioridad correcta:
 * 1. Imagen Local (subida manualmente)
 * 2. OpenLibrary (si tiene ISBN)
 * 3. Fallback (sin_portada.png)
 * 
 * @param {Object} libro - Objeto libro con propiedades: imagen_portada, isbn
 * @param {string} size - Tamaño de la imagen OpenLibrary: 'S' (Small), 'M' (Medium), 'L' (Large). Default: 'L'.
 * @returns {string} URL de la imagen
 */
function obtenerRutaPortada(libro, size = 'L') {
    if (libro.imagen_portada) {
        // Cache busting para imágenes locales
        return `uploads/portadas/${libro.imagen_portada}?t=${new Date().getTime()}`;
    }

    if (libro.isbn) {
        // Limpiar ISBN por seguridad
        const isbnLimpio = libro.isbn.replace(/[^0-9X]/g, '');
        if (isbnLimpio) {
            return `https://covers.openlibrary.org/b/isbn/${isbnLimpio}-${size}.jpg?default=false`;
        }
    }

    return 'img/sin_portada.png';
}

/**
 * Alterna la visibilidad de un elemento HTML (Show/Hide).
 * @param {string} idElemento - ID del elemento a alternar.
 */
function alternarVisibilidad(idElemento) {
    // Obtenemos el elemento del DOM
    const elemento = document.getElementById(idElemento);
    // Verificamos si existe para evitar errores
    if (elemento) {
        // Si está oculto o vacío, lo mostramos como 'table' (para filas), si no, lo ocultamos
        elemento.style.display = (elemento.style.display === 'none' || elemento.style.display === '') ? 'table' : 'none';
    }
}

/* ==========================================================================
   2. SISTEMA DE NOTIFICACIONES (TOASTS)
   Maneja las alertas flotantes (éxito, error, info).
   ========================================================================== */

/**
 * Crea el contenedor HTML para los toasts si no existe en el DOM.
 */
function inicializarContenedorToasts() {
    // Buscamos si ya existe el div contenedor
    if (!document.querySelector('.contenedor-toasts')) {
        // Si no existe, lo creamos
        const contenedor = document.createElement('div');
        contenedor.className = 'contenedor-toasts';
        // Lo añadimos al final del body
        document.body.appendChild(contenedor);
    }
}

/**
 * Muestra una notificación visual temporal.
 * @param {string} mensaje - Texto a mostrar.
 * @param {string} tipo - Clase CSS ('exito', 'error', 'info', 'advertencia').
 * @param {number} duracion - Tiempo en ms antes de desaparecer (default: 3000).
 */
function mostrarNotificacion(mensaje, tipo = 'info', duracion = 3000) {
    // 1. Aseguramos que el contenedor exista
    inicializarContenedorToasts();
    const contenedor = document.querySelector('.contenedor-toasts');

    // 2. Creamos el elemento del toast
    const toast = document.createElement('div');
    toast.className = `notificacion-toast ${tipo}`; // Asignamos clases dinámicas

    // 3. Definimos iconos según el tipo de mensaje
    const iconos = {
        'exito': '✓',
        'error': '✕',
        'info': 'ℹ',
        'advertencia': '⚠'
    };

    // 4. Inyectamos el HTML interno del toast
    toast.innerHTML = `
        <div class="toast-icono">${iconos[tipo] || 'ℹ'}</div>
        <span class="toast-mensaje">${mensaje}</span>
        <button class="toast-cerrar" onclick="cerrarToast(this.parentElement)">×</button>
        <div class="toast-progreso"></div>
    `;

    // 5. Añadimos el toast al contenedor visual
    contenedor.appendChild(toast);

    // 6. Programamos el auto-cierre
    setTimeout(() => {
        cerrarToast(toast);
    }, duracion);

    return toast;
}

/**
 * Elimina el toast con una animación de salida.
 * @param {HTMLElement} toast - El elemento DOM del toast.
 */
function cerrarToast(toast) {
    // Si no hay toast o ya se está cerrando, salimos
    if (!toast || toast.classList.contains('saliendo')) return;

    // Añadimos clase para animación CSS
    toast.classList.add('saliendo');

    // Esperamos a que termine la animación (300ms) para eliminar del DOM
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

/* ==========================================================================
   3. SISTEMA DE PESTAÑAS (TABS)
   Controla la navegación entre secciones sin recargar la página.
   ========================================================================== */
/* ==========================================================================
INICIALIZACIÓN DEL SISTEMA DE PESTAÑAS
IMPORTANTE: Esto hace que todo lo anterior funcione al cargar la página.
========================================================================== */
document.addEventListener('DOMContentLoaded', function () {
    console.log("🔧 Iniciando sistema bibliotecario...");

    // 1. Arrancar Pestañas (CRÍTICO: Esto arregla tu navegación)
    iniciarSistemaPestanas();

    // 2. Arrancar Buscadores de las tablas (Alumnos/Docentes)
    if (document.getElementById('busqueda-alumno')) {
        inicializarBuscadorTabla('busqueda-alumno', 'filtro-alumno', 'tabla-alumnos');
    }
    if (document.getElementById('busqueda-docente')) {
        inicializarBuscadorTabla('busqueda-docente', 'filtro-docente', 'tabla-docentes');
    }

    // 3. Arrancar Notificaciones
    inicializarContenedorToasts();
});

function iniciarSistemaPestanas() {
    // Seleccionamos todos los botones y contenidos de pestañas
    const botones = document.querySelectorAll('.boton-pestana');
    const contenidos = document.querySelectorAll('.contenido-pestana');

    // Si no hay pestañas, no hacemos nada
    if (botones.length === 0) return;

    // Helper interno para cambiar la clase 'activo'
    function cambiarPestana(tabId) {
        // 1. Desactivar todo visualmente
        botones.forEach(b => b.classList.remove('activo'));
        contenidos.forEach(c => c.classList.remove('activo'));

        // 2. Buscar el botón y contenido específicos
        const botonActivo = document.querySelector(`.boton-pestana[data-tab="${tabId}"]`);
        const contenidoActivo = document.getElementById(tabId);

        // 3. Activar los elementos encontrados
        if (botonActivo && contenidoActivo) {
            botonActivo.classList.add('activo');
            contenidoActivo.classList.add('activo');
            // 4. Guardar preferencia en localStorage para recordar tras recarga
            localStorage.setItem('pestañaActiva', tabId);
        }
    }

    // Asignar evento click a cada botón
    botones.forEach(boton => {
        boton.addEventListener('click', () => {
            const tabId = boton.getAttribute('data-tab'); // Obtenemos el ID destino
            cambiarPestana(tabId);
        });
    });

    // Al cargar la página: Recuperar última pestaña visitada
    const pestanaGuardada = localStorage.getItem('pestañaActiva');
    if (pestanaGuardada) {
        // Verificar que la pestaña guardada realmente existe
        if (document.getElementById(pestanaGuardada)) {
            cambiarPestana(pestanaGuardada);
        }
    }
}


// Carga diferida de imágenes (para que la web vaya rápida)
window.addEventListener('load', function () {
    setTimeout(cargarPortadasLibros, 200);
});





/* ==========================================================================
   4. GESTIÓN DE MODALES Y SINOPSIS (CON EDICIÓN MANUAL Y AJAX)
   ========================================================================== */

// Variable global
let libroIdActual = null;

function abrirModalDesdeBoton(boton) {
    if (!boton) return;

    // ============================================================
    // 1. RECOGIDA DE DATOS (PÚBLICO)
    // ============================================================
    libroIdActual = boton.getAttribute('data-id');
    const titulo = boton.getAttribute('data-titulo') || "Sin título";
    const autor = boton.getAttribute('data-autor') || "Autor desconocido";
    const isbn = boton.getAttribute('data-isbn') || "";
    const estado = boton.getAttribute('data-estado') || "desconocido";
    const desc = boton.getAttribute('data-desc') || "";
    const imagenLocal = boton.getAttribute('data-imagen-local') || "";

    // Elementos del DOM
    const elTitulo = document.getElementById('modal-titulo');
    const elAutor = document.getElementById('modal-autor');
    const elEstado = document.getElementById('modal-estado');
    const elImg = document.getElementById('modal-img');
    const elDesc = document.getElementById('modal-desc');
    const elModal = document.getElementById('modal-sinopsis');

    if (!elModal) return;

    // ============================================================
    // 2. RENDERIZADO VISUAL (PÚBLICO - TODOS VEN ESTO)
    // ============================================================

    // A) Cabecera y metadatos
    elTitulo.innerText = titulo;
    elAutor.innerText = autor;

    if (elEstado) {
        elEstado.innerText = estado.toUpperCase();
        elEstado.className = 'etiqueta-estado ' + (estado.toLowerCase() === 'disponible' ? 'etiqueta-disponible' : 'etiqueta-prestado');
    }

    // B) Imagen de Portada — PRIORIDAD: Local → OpenLibrary → Fallback
    // Usamos el helper centralizado
    if (elImg) {
        const libroTemp = {
            imagen_portada: imagenLocal ? imagenLocal.replace('uploads/portadas/', '') : null, // Ajuste sucio porque el atributo ya trae la ruta
            isbn: isbn
        };

        // Si imagenLocal ya es una ruta completa (ej: uploads/portadas/...), la usamos directamente
        // Pero el helper espera solo el nombre de archivo en libro.imagen_portada
        // O podemos adaptar la lógica.

        // MEJOR: Si ya tenemos la ruta local en el atributo, la usamos directo.
        if (imagenLocal) {
            elImg.src = imagenLocal + '?v=' + Date.now();
        } else {
            elImg.src = obtenerRutaPortada({ isbn: isbn });
        }

        elImg.onerror = function () { this.src = 'img/sin_portada.png'; };
    }

    // C) Descripción / Sinopsis
    elDesc.innerHTML = ''; // Limpiamos contenido previo
    const divLectura = document.createElement('div');
    divLectura.className = 'zona-lectura';
    divLectura.style.lineHeight = "1.6";
    elDesc.appendChild(divLectura);

    // Lógica del contenido de lectura
    if (desc && desc.length > 20 && desc !== 'Sin descripción disponible.') {
        // Opción 1: Tenemos sinopsis en BD (Local) -> La mostramos
        console.log("📖 Usando sinopsis local (BD).");
        divLectura.innerHTML = desc;
    } else {
        // Opción 2: No tenemos sinopsis -> Buscamos en OpenLibrary
        console.log("🌐 Sinopsis local vacía. Buscando en OpenLibrary para ISBN:", isbn);
        divLectura.innerHTML = '<p style="color: #666;"><i class="fa-solid fa-spinner fa-spin"></i> Buscando reseñas automáticas...</p>';
        if (typeof buscarEnOpenLibrary === 'function') {
            buscarEnOpenLibrary(isbn, divLectura);
        }
    }

    // ============================================================
    // 3. ZONA PRIVADA (SOLO DIRECTIVA/ADMIN)
    // ============================================================
    // Verificamos variable inyectada por PHP en el footer
    const esDirectiva = (typeof ES_DIRECTIVA !== 'undefined' && ES_DIRECTIVA === true);

    if (esDirectiva) {
        console.log("🛡️ Modo: Directiva/Admin habilitado.");
        const divEditor = document.createElement('div');
        divEditor.className = 'zona-editor-admin';
        divEditor.style.marginTop = '25px';
        divEditor.style.borderTop = '2px dashed #ddd';
        divEditor.style.paddingTop = '15px';

        divEditor.innerHTML = `
            <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9em; color: #856404;">
                <i class="fa-solid fa-user-shield"></i> <strong>Panel Directiva:</strong> Edita la sinopsis y/o sube una portada personalizada.
            </div>

            <label style="font-weight:bold; display:block; margin-bottom:5px; font-size:0.9em; color:#555;">
                <i class="fa-solid fa-image"></i> Portada personalizada (JPG/PNG, máx 2MB)
            </label>
            <input type="file" id="input-portada-manual" accept="image/jpeg,image/png"
                   style="margin-bottom:10px; font-size:0.9em;">
            <div id="preview-portada" style="margin-bottom:15px; display:none;">
                <img id="preview-portada-img" src="" alt="Preview"
                     style="max-height:150px; border-radius:6px; border:2px solid #2c3e50; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
            </div>

            <label style="font-weight:bold; display:block; margin-bottom:5px; font-size:0.9em; color:#555;">
                <i class="fa-solid fa-pen-to-square"></i> Sinopsis
            </label>
            <textarea id="texto-sinopsis-manual" 
                      style="width:100%; height:120px; padding:10px; border:1px solid #ccc; border-radius:5px; font-family:inherit; resize: vertical;"
                      placeholder="Escribe aquí la sinopsis...">${desc || ''}</textarea>
            
            <button id="btn-guardar-ajax" 
                    onclick="guardarEdicionAvanzada()" 
                    style="margin-top:10px; background:#2c3e50; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer; font-weight:bold; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-save"></i> Guardar Cambios
            </button>
        `;
        elDesc.appendChild(divEditor);

        // Preview en vivo al seleccionar archivo
        const inputFile = document.getElementById('input-portada-manual');
        inputFile.addEventListener('change', function () {
            const archivo = this.files[0];
            const previewDiv = document.getElementById('preview-portada');
            const previewImg = document.getElementById('preview-portada-img');

            if (archivo) {
                // Validación rápida en cliente
                if (!['image/jpeg', 'image/png'].includes(archivo.type)) {
                    mostrarNotificacion('Solo se permiten archivos JPG y PNG.', 'error');
                    this.value = '';
                    previewDiv.style.display = 'none';
                    return;
                }
                if (archivo.size > 2 * 1024 * 1024) {
                    mostrarNotificacion('La imagen es demasiado grande. Máximo 2MB.', 'error');
                    this.value = '';
                    previewDiv.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewDiv.style.display = 'block';
                };
                reader.readAsDataURL(archivo);
            } else {
                previewDiv.style.display = 'none';
            }
        });
    }

    // 4. Mostrar modal final
    elModal.style.display = 'flex';
}

// ============================================================
// APIS + EDITOR
// ============================================================

// 1. Función auxiliar: Si existe el editor, le mete el texto encontrado
function actualizarEditorAdmin(texto) {
    // Buscamos el textarea del admin
    const editor = document.getElementById('texto-sinopsis-manual');

    // Si existe (porque somos directiva), le metemos el texto
    if (editor) {
        editor.value = texto;

        // Efecto visual: parpadeo verde para avisar de que se ha rellenado
        editor.style.transition = "background-color 0.5s";
        editor.style.backgroundColor = "#d4edda";
        setTimeout(() => editor.style.backgroundColor = "#fff", 1000);
    }
}

// 2. BUSCADOR DE SINOPSIS (SOLO OPEN LIBRARY)
// Se han eliminado las integraciones con Google Books para evitar solapamientos.

// 3. RECUPERACIÓN ÚNICA (OPEN LIBRARY)
function buscarEnOpenLibrary(isbn, elementoDestino) {
    if (!isbn || isbn.length < 10) {
        mensajeNoEncontrado(elementoDestino);
        return;
    }
    const key = `ISBN:${isbn}`;

    fetch(`https://openlibrary.org/api/books?bibkeys=${key}&jscmd=details&format=json`)
        .then(res => res.json())
        .then(data => {
            if (data[key]?.details?.description) {
                let desc = data[key].details.description;
                if (typeof desc === 'object' && desc.value) desc = desc.value;

                elementoDestino.innerHTML = `<p>${desc}</p>`;
                actualizarEditorAdmin(desc); // <--- ¡AQUÍ AUTO-RELLENA!
            } else {
                mensajeNoEncontrado(elementoDestino);
            }
        })
        .catch(() => mensajeNoEncontrado(elementoDestino));
}

// 5. MENSAJE FINAL SI FALLA TODO
function mensajeNoEncontrado(elementoDestino) {
    elementoDestino.innerHTML = `
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #f1c40f; color: #555;">
            <h3>¡Ups! No tenemos la sinopsis.</h3>
            <p>Pregunta a tu profe para saber de qué va este libro.</p>
        </div>`;

    // Si fallan todas, ponemos un placeholder invitando a escribir
    const editor = document.getElementById('texto-sinopsis-manual');
    if (editor) {
        editor.value = ""; // Limpiamos por si acaso
        editor.placeholder = "Las APIs no encontraron nada... Te toca escribir la sinopsis a mano aquí.";
    }
}
// --- FUNCIONES DE VISUALIZACIÓN ---

function mostrarSinopsis(texto, fuente, elemento) {
    // Mostramos el texto encontrado
    elemento.innerHTML = `${texto}<br><br><small style="color:#888;">Fuente: ${fuente}</small>`;
}

/**
 * Muestra el formulario para añadir sinopsis manualmente cuando no se encuentra nada
 */
function mostrarFormularioManual(elemento) {
    elemento.innerHTML = `
        <div style="background: #fdf2f2; padding: 15px; border-radius: 8px; border: 1px dashed #e74c3c;">
            <p style="margin-top:0; color: #c0392b;"><strong><i class="fa-solid fa-triangle-exclamation"></i> Sinopsis no encontrada.</strong></p>
            <p style="font-size: 0.9em; margin-bottom: 10px;">No se encontró información automática. Puedes añadirla manualmente:</p>
            
            <textarea id="texto-sinopsis-manual" class="input-formulario" 
                style="width: 100%; height: 100px; margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" 
                placeholder="Escribe aquí el resumen del libro..."></textarea>
            
            <button type="button" id="btn-guardar-ajax" class="boton-guardar-libro" onclick="guardarSinopsisAjax()" style="width: 100%; cursor: pointer;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Sinopsis
            </button>
        </div>
    `;
}

















/**
 * Envía la sinopsis y/o portada a index.php mediante AJAX (FormData)
 * Combina la edición de texto y subida de imagen en un solo envío.
 */
function guardarEdicionAvanzada() {
    const textoInput = document.getElementById('texto-sinopsis-manual');
    const fileInput = document.getElementById('input-portada-manual');
    const boton = document.getElementById('btn-guardar-ajax');
    const texto = textoInput ? textoInput.value : '';
    const archivo = fileInput && fileInput.files.length > 0 ? fileInput.files[0] : null;

    // Validar que hay al menos algo que guardar
    if ((!texto || texto.trim().length < 5) && !archivo) {
        mostrarNotificacion('Escribe una sinopsis (mín. 5 caracteres) o selecciona una imagen.', 'advertencia');
        return;
    }
    if (!libroIdActual) {
        mostrarNotificacion('Error: ID de libro desconocido.', 'error');
        return;
    }

    // Feedback visual
    const textoOriginal = boton.innerHTML;
    boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
    boton.disabled = true;

    // Construir FormData
    const formData = new FormData();
    formData.append('accion', 'guardar_edicion_avanzada');
    formData.append('id_libro', libroIdActual);

    if (texto && texto.trim().length >= 5) {
        formData.append('sinopsis', texto);
    }
    if (archivo) {
        formData.append('portada', archivo);
    }

    // ENVIAMOS A index.php (sin Content-Type header → el navegador pone multipart/form-data)
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(textoRespuesta => {
            console.log("📢 RESPUESTA DEL SERVIDOR (RAW):", textoRespuesta);

            try {
                const data = JSON.parse(textoRespuesta);

                if (data.status === 'success') {
                    mostrarNotificacion('¡Cambios guardados correctamente!', 'exito');

                    // Actualizar data-desc en el botón original
                    const btnOriginal = document.querySelector(`button[data-id="${libroIdActual}"]`);
                    if (texto && texto.trim().length >= 5) {
                        if (btnOriginal) btnOriginal.setAttribute('data-desc', texto);
                    }

                    // Si se subió imagen, actualizar todo instantáneamente
                    if (data.imagen_url) {
                        const urlConCacheBust = data.imagen_url + '?v=' + Date.now();

                        // A) Actualizar imagen del modal
                        const elImg = document.getElementById('modal-img');
                        if (elImg) elImg.src = urlConCacheBust;

                        // B) Actualizar data-imagen-local del botón
                        if (btnOriginal) btnOriginal.setAttribute('data-imagen-local', data.imagen_url);

                        // C) Actualizar la tarjeta en la cuadrícula
                        const tarjeta = btnOriginal ? btnOriginal.closest('.tarjeta-libro') : null;
                        if (tarjeta) {
                            const imgTarjeta = tarjeta.querySelector('.portada-libro');
                            if (imgTarjeta) imgTarjeta.src = urlConCacheBust;
                        }
                    }

                    // Resetear el input de archivo
                    if (fileInput) fileInput.value = '';
                    const previewDiv = document.getElementById('preview-portada');
                    if (previewDiv) previewDiv.style.display = 'none';

                    boton.innerHTML = '<i class="fa-solid fa-check"></i> ¡Guardado!';
                    setTimeout(() => {
                        boton.innerHTML = textoOriginal;
                        boton.disabled = false;
                    }, 2000);

                } else {
                    mostrarNotificacion('⚠️ ' + data.message, 'error');
                    boton.innerHTML = textoOriginal;
                    boton.disabled = false;
                }
            } catch (e) {
                console.error("❌ Error al parsear JSON:", e);
                mostrarNotificacion('Error en la respuesta del servidor. Ver consola (F12).', 'error');
                boton.innerHTML = textoOriginal;
                boton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error de red:', error);
            mostrarNotificacion('Error de conexión con el servidor.', 'error');
            boton.innerHTML = textoOriginal;
            boton.disabled = false;
        });
}

// Mantener compatibilidad con guardarSinopsisAjax (redirige a la nueva función)
function guardarSinopsisAjax() {
    guardarEdicionAvanzada();
}

// Cierre de modal
function cerrarModal() {
    const modal = document.getElementById('modal-sinopsis');
    if (modal) modal.style.display = 'none';
    libroIdActual = null;
}

// Cerrar al hacer clic fuera
window.onclick = function (event) {
    const modal = document.getElementById('modal-sinopsis');
    if (event.target == modal) cerrarModal();
}



/* ==========================================================================
   5. GESTIÓN DE IMÁGENES Y PORTADAS (OPEN LIBRARY)
   Carga diferida de imágenes para no saturar la red.
   ========================================================================== */

// Evento global de carga
window.addEventListener('load', function () {
    console.log(" Página lista. Iniciando scripts...");
    // Retrasamos la carga de portadas 500ms para priorizar la interfaz principal
    setTimeout(cargarPortadasLibros, 500);
});

/**
 * Carga las portadas de los libros de forma escalonada (una a una).
 */
function cargarPortadasLibros() {
    // Seleccionar todas las imágenes que tienen un data-isbn
    const imagenes = document.querySelectorAll('.portada-libro[data-isbn]');
    if (imagenes.length === 0) return;

    console.log(`📚 Procesando ${imagenes.length} portadas...`);

    imagenes.forEach((img, index) => {
        setTimeout(() => {
            const fallbackImg = 'img/sin_portada.png';

            // PRIORIDAD 1: Si la imagen ya tiene src local (cargado por PHP), saltar
            // Las imágenes locales contienen "uploads/portadas/" en su src
            if (img.src && img.src.includes('uploads/portadas/')) {
                img.style.opacity = "1";
                return; // Ya tiene imagen local, no consultar OpenLibrary
            }

            // PRIORIDAD 2: Intentar OpenLibrary
            const isbnRaw = img.getAttribute('data-isbn');
            if (!isbnRaw) {
                img.src = fallbackImg;
                return;
            }

            const isbn = isbnRaw.replace(/[^0-9X]/g, '');
            if (isbn.length < 10) {
                img.src = fallbackImg;
                return;
            }

            // URL estricta de OpenLibrary usando el helper (Tamaño 'M')
            const urlPortada = obtenerRutaPortada({ isbn: isbn }, 'M');

            // Verificación previa de imagen
            const testImg = new Image();
            testImg.src = urlPortada;

            testImg.onload = function () {
                // Éxito: La imagen existe en OpenLibrary
                if (this.width > 1) {
                    img.src = urlPortada;
                    img.style.opacity = "1";
                } else {
                    img.src = fallbackImg;
                }
            };

            testImg.onerror = function () {
                // PRIORIDAD 3: Fallback
                img.src = fallbackImg;
            };

        }, index * 50); // Carga escalonada
    });
}

/* ==========================================================================
   6. BUSCADOR VISUAL Y AUTOCOMPLETADO
   Lógica compleja para los inputs que muestran resultados desplegables.
   ========================================================================== */

/**
 * Inicializa un buscador con autocompletado visual (Fichas).
 * @param {string} idInput - ID del campo de texto.
 * @param {string} idContenedor - ID del div donde se muestran resultados.
 * @param {string} idHidden - ID del input oculto que guardará el ID seleccionado.
 * @param {Array} datos - Array de objetos JSON con los datos (libros o alumnos).
 * @param {string} tipo - 'libro' o 'alumno'.
 */
function iniciarBuscadorVisual(idInput, idContenedor, idHidden, datos, tipo) {
    const input = document.getElementById(idInput);
    const contenedor = document.getElementById(idContenedor);
    const hidden = document.getElementById(idHidden);

    if (!input || !contenedor) return;

    // Escuchar evento mientras se escribe
    input.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        contenedor.innerHTML = ''; // Limpiar lista previa

        // Si hay menos de 2 letras, ocultar y salir
        if (query.length < 2) {
            contenedor.style.display = 'none';
            return;
        }

        // Filtrar el array de datos
        const resultados = datos.filter(item => {
            if (tipo === 'libro') {
                return item.titulo.toLowerCase().includes(query) || (item.isbn && item.isbn.includes(query));
            } else {
                return item.nombre.toLowerCase().includes(query) || (item.curso && item.curso.toLowerCase().includes(query));
            }
        });

        // Si hay coincidencias, construir el HTML
        if (resultados.length > 0) {
            contenedor.style.display = 'block';
            resultados.forEach(item => {
                const div = document.createElement('div');
                div.className = 'item-resultado';

                // Renderizado específico según tipo
                if (tipo === 'libro') {
                    // Lógica de miniatura centralizada (Tamaño 'S')
                    const url = obtenerRutaPortada(item, 'S');
                    let imgHtml = `<img src="${url}" class="miniatura-libro" onerror="this.src='img/sin_portada.png'">`;

                    div.innerHTML = `
                        ${imgHtml}
                        <div class="info-resultado">
                            <h4>${item.titulo}</h4>
                            <p>${item.autor} ${item.isbn ? '| ISBN: ' + item.isbn : ''}</p>
                        </div>
                    `;

                    // Click en resultado LIBRO
                    div.addEventListener('click', () => {
                        input.value = item.titulo;
                        hidden.value = item.id;
                        contenedor.style.display = 'none';
                        actualizarFichaLibro(item); // Mostrar tarjeta grande
                    });

                } else { // Tipo Alumno
                    div.innerHTML = `
                         <div class="info-resultado">
                            <h4>${item.nombre}</h4>
                            <p>Curso: ${item.curso}</p>
                        </div>
                    `;

                    // Click en resultado ALUMNO
                    div.addEventListener('click', () => {
                        input.value = item.nombre + " (" + item.curso + ")";
                        hidden.value = item.id;
                        contenedor.style.display = 'none';
                        actualizarFichaAlumno(item); // Mostrar tarjeta grande
                    });
                }

                contenedor.appendChild(div);
            });
        } else {
            contenedor.style.display = 'none';
        }
    });

    // Cerrar sugerencias si se hace clic fuera
    document.addEventListener('click', function (e) {
        if (e.target !== input && e.target !== contenedor) {
            contenedor.style.display = 'none';
        }
    });
}

/**
 * Renderiza la ficha grande del libro seleccionado en el formulario.
 */
function actualizarFichaLibro(libro) {
    const fichaLibro = document.getElementById('ficha-libro');
    if (!fichaLibro) return;

    const urlPortada = obtenerRutaPortada(libro);

    fichaLibro.innerHTML = `
        <div class="ficha-libro-seleccionado">
            <img src="${urlPortada}" 
                 class="portada-ficha" 
                 alt="Portada"
                 onerror="this.src='img/sin_portada.png'">
            <div class="datos-ficha">
                <div class="titulo-ficha">${libro.titulo}</div>
                <div class="autor-ficha">${libro.autor || 'Autor desconocido'}</div>
                ${libro.isbn ? `<div class="isbn-ficha">ISBN: ${libro.isbn}</div>` : ''}
            </div>
        </div>
    `;

    fichaLibro.classList.remove('vacia');
    fichaLibro.classList.add('activa');
}

/**
 * Renderiza la ficha grande del alumno seleccionado en el formulario.
 */
function actualizarFichaAlumno(alumno) {
    const fichaAlumno = document.getElementById('ficha-alumno');
    if (!fichaAlumno) return;

    fichaAlumno.innerHTML = `
        <div class="ficha-alumno-seleccionado">
            <div class="icono-usuario-grande">👤</div>
            <div class="datos-ficha">
                <div class="nombre-ficha">${alumno.nombre}</div>
                <div class="curso-ficha">${alumno.curso}</div>
            </div>
        </div>
    `;

    fichaAlumno.classList.remove('vacia');
    fichaAlumno.classList.add('activa');
}

/**
 * Buscador simple para la página Index (Búsqueda en tarjetas).
 */
function iniciarBuscadorLibrosIndex() {
    const inputBuscador = document.getElementById('buscador-libros');
    if (!inputBuscador) return;

    inputBuscador.addEventListener('input', function (e) {
        const termino = e.target.value.toLowerCase();
        const tarjetas = document.querySelectorAll('.tarjeta-libro');

        tarjetas.forEach(tarjeta => {
            const titulo = tarjeta.querySelector('.titulo-libro').textContent.toLowerCase();
            const autor = tarjeta.querySelector('.autor-libro').textContent.toLowerCase();

            // Mostrar si coincide título O autor
            if (titulo.includes(termino) || autor.includes(termino)) {
                tarjeta.style.display = 'flex';
            } else {
                tarjeta.style.display = 'none';
            }
        });
    });
}

// Filtros auxiliares antiguos (Mantener por compatibilidad)
function filtrarPorColor(color) {
    const libros = document.querySelectorAll('.tarjeta-libro');
    if (event && event.currentTarget) {
        document.querySelectorAll('.filtro-pill').forEach(btn => btn.classList.remove('activo'));
        event.currentTarget.classList.add('activo');
    }
    libros.forEach(libro => {
        const colorLibro = libro.getAttribute('data-color');
        libro.style.display = (color === 'todos' || colorLibro === color) ? 'flex' : 'none';
    });
}

function filtrarLibrosGlobal() {
    const input = document.getElementById('buscador-libros');
    if (!input) return;
    const busqueda = input.value.toLowerCase();
    document.querySelectorAll('.tarjeta-libro').forEach(libro => {
        const tituloEl = libro.querySelector('.titulo-libro');
        const autorEl = libro.querySelector('.autor-libro');

        if (tituloEl && autorEl) {
            const titulo = tituloEl.textContent.toLowerCase();
            const autor = autorEl.textContent.toLowerCase();
            const isbn = (libro.getAttribute('data-isbn') || '').toLowerCase();

            libro.style.display = (titulo.includes(busqueda) || autor.includes(busqueda) || isbn.includes(busqueda)) ? 'flex' : 'none';
        }
    });
}

/* ==========================================================================
   7. GESTIÓN DE FORMULARIOS (EDICIÓN DE DATOS)
   Funciones para rellenar formularios al hacer clic en "Editar".
   ========================================================================== */

/**
 * Prepara el formulario de libros para modo edición.
 */
function editarLibro(id, titulo, autor, isbn, color) {
    // 1. Rellenar inputs con los datos recibidos
    document.getElementById('libro_id').value = id;
    document.getElementById('titulo').value = titulo;
    document.getElementById('autor').value = autor;
    document.getElementById('isbn').value = isbn;
    document.getElementById('categoria').value = color;

    // 2. Cambiar UI para indicar edición
    document.getElementById('accion_libro').value = 'guardar_libro';
    const btn = document.getElementById('btn-form-libro');
    btn.innerText = 'Actualizar Libro';
    btn.style.backgroundColor = '#ff9800'; // Color Naranja

    // 3. Llevar al usuario al formulario
    document.getElementById('titulo-form-libro').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Prepara el formulario de alumnos para modo edición.
 */
function editarAlumno(id, nombre, curso) {
    document.getElementById('id_alumno').value = id;
    document.getElementById('nombre_alumno').value = nombre;
    document.getElementById('curso_alumno').value = curso;

    document.getElementById('titulo-form-alumno').innerText = '✏️ Editar Alumno';
    const btn = document.getElementById('btn-form-alumno');
    btn.innerText = 'Actualizar Alumno';
    btn.style.backgroundColor = '#ff9800';

    document.getElementById('btn-cancelar-alumno').style.display = 'block';
    document.getElementById('titulo-form-alumno').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Cancela la edición de alumno y resetea el formulario.
 */
function cancelarEdicionAlumno() {
    document.getElementById('formAlumno').reset();
    document.getElementById('id_alumno').value = '';

    document.getElementById('titulo-form-alumno').innerText = '🎓 Nuevo Alumno';
    const btn = document.getElementById('btn-form-alumno');
    btn.innerText = 'Guardar Alumno';
    btn.style.backgroundColor = '#3498db'; // Azul original

    document.getElementById('btn-cancelar-alumno').style.display = 'none';
}

/**
 * Prepara el formulario de docentes para modo edición.
 */
function editarDocente(id, nombre, usuario, rol) {
    document.getElementById('id_usuario').value = id;
    document.getElementById('nombre_docente').value = nombre;
    document.getElementById('usuario_docente').value = usuario;
    document.getElementById('rol_docente').value = rol;
    document.getElementById('password_docente').value = ''; // La contraseña no se muestra por seguridad

    document.getElementById('titulo-form-docente').innerText = '✏️ Editar Docente';
    const btn = document.getElementById('btn-form-docente');
    btn.innerText = 'Actualizar Docente';
    btn.style.backgroundColor = '#ff9800';

    document.getElementById('btn-cancelar-docente').style.display = 'block';
    document.getElementById('titulo-form-docente').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Cancela la edición de docente y resetea el formulario.
 */
function cancelarEdicionDocente() {
    document.getElementById('formDocente').reset();
    document.getElementById('id_usuario').value = '';

    document.getElementById('titulo-form-docente').innerText = '🧑‍🏫 Nuevo Docente';
    const btn = document.getElementById('btn-form-docente');
    btn.innerText = 'Guardar Docente';
    btn.style.backgroundColor = '#2c3e50'; // Color original

    document.getElementById('btn-cancelar-docente').style.display = 'none';
}

/* ==========================================================================
   8. LÓGICA DE FILTRADO (EVENT LISTENERS Y DOM)
   Bloques de código que se ejecutan al cargar la página para filtros.
   ========================================================================== */

/**
 * BLOQUE 1: FILTRADO DINÁMICO DE LIBROS (Filtro rápido color + texto)
 */
document.addEventListener('DOMContentLoaded', function () {
    // Selección de elementos
    const inputBusqueda = document.getElementById('busqueda-libros');
    const selectFiltro = document.getElementById('filtro-rapido-color');
    const listaLibros = document.querySelectorAll('.ficha-libro-horizontal');
    const contadorLibros = document.querySelector('.contador-libros');

    // Validación de existencia
    if (!inputBusqueda || !listaLibros) return;

    // Función local para normalizar texto (quitar tildes y mayúsculas)
    const normalizarTexto = (texto) => {
        return texto
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    };

    // Función principal de filtrado
    const filtrarLibros = () => {
        const textoBusqueda = normalizarTexto(inputBusqueda.value);
        const colorSeleccionado = selectFiltro.value;
        let cantidadVisibles = 0;

        listaLibros.forEach(libro => {
            // Datos del libro
            const tituloLibro = normalizarTexto(libro.dataset.titulo || '');
            const autorLibro = normalizarTexto(libro.dataset.autor || '');
            const colorLibro = libro.dataset.color || '';
            const contenidoTexto = normalizarTexto(libro.textContent);

            // Comprobar coincidencias
            const matchTexto = tituloLibro.includes(textoBusqueda) ||
                autorLibro.includes(textoBusqueda) ||
                contenidoTexto.includes(textoBusqueda);

            const matchColor = (colorSeleccionado === "") || (colorLibro === colorSeleccionado);

            // Aplicar visibilidad
            if (matchTexto && matchColor) {
                libro.style.display = '';
                cantidadVisibles++;
            } else {
                libro.style.display = 'none';
            }
        });

        // Actualizar contador visual
        if (contadorLibros) {
            contadorLibros.textContent = `${cantidadVisibles} libros encontrados`;
        }
    };

    // Listeners
    inputBusqueda.addEventListener('input', filtrarLibros);
    selectFiltro.addEventListener('change', filtrarLibros);
});

/**
 * BLOQUE 2: FILTRADO DE ALUMNOS Y DOCENTES
 */
document.addEventListener('DOMContentLoaded', function () {

    // Helper local de normalización
    const normalizar = (str) => {
        return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    };

    // --- SUB-LOGICA: FILTRO DE ALUMNOS ---
    const inputBusquedaAlu = document.getElementById('busqueda-alumnos');
    const selectFiltroCurso = document.getElementById('filtro-curso-alumno');
    const filasAlumnos = document.querySelectorAll('#tabla-cuerpo-alumnos tr');

    const filtrarAlumnos = () => {
        if (!inputBusquedaAlu) return;

        const texto = normalizar(inputBusquedaAlu.value);
        const cursoSeleccionado = selectFiltroCurso.value.toLowerCase();

        filasAlumnos.forEach(fila => {
            const nombre = normalizar(fila.dataset.nombre || '');
            const curso = normalizar(fila.dataset.curso || '');

            const matchTexto = nombre.includes(texto) || curso.includes(texto);
            const matchCurso = (cursoSeleccionado === "") || curso.includes(cursoSeleccionado);

            if (matchTexto && matchCurso) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    };

    if (inputBusquedaAlu) {
        inputBusquedaAlu.addEventListener('input', filtrarAlumnos);
        selectFiltroCurso.addEventListener('change', filtrarAlumnos);
    }

    // --- SUB-LOGICA: FILTRO DE DOCENTES ---
    const inputBusquedaDoc = document.getElementById('busqueda-docentes');
    const selectFiltroRol = document.getElementById('filtro-rol-docente');
    const filasDocentes = document.querySelectorAll('#tabla-cuerpo-docentes tr');

    const filtrarDocentes = () => {
        if (!inputBusquedaDoc) return;

        const texto = normalizar(inputBusquedaDoc.value);
        const rolSeleccionado = selectFiltroRol.value.toLowerCase();

        filasDocentes.forEach(fila => {
            const nombre = normalizar(fila.dataset.nombre || '');
            const usuario = normalizar(fila.dataset.usuario || '');
            const rol = normalizar(fila.dataset.rol || '');

            const matchTexto = nombre.includes(texto) || usuario.includes(texto);
            const matchRol = (rolSeleccionado === "") || (rol === rolSeleccionado);

            if (matchTexto && matchRol) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    };

    if (inputBusquedaDoc) {
        inputBusquedaDoc.addEventListener('input', filtrarDocentes);
        selectFiltroRol.addEventListener('change', filtrarDocentes);
    }
});

/**
 * BLOQUE 3: INICIALIZACIÓN DE BUSCADORES DE TABLA GENÉRICOS
 */
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar buscador de Alumnos
    inicializarBuscadorTabla('busqueda-alumno', 'filtro-alumno', 'tabla-alumnos');

    // Inicializar buscador de Docentes
    inicializarBuscadorTabla('busqueda-docente', 'filtro-docente', 'tabla-docentes');
});

/**
 * Función reutilizable para conectar un input y select a una tabla HTML estándar.
 * Filtra filas basándose en el contenido de texto de las celdas.
 */
function inicializarBuscadorTabla(idInput, idSelect, idTabla) {
    const input = document.getElementById(idInput);
    const select = document.getElementById(idSelect);
    const tabla = document.getElementById(idTabla);

    if (!input || !tabla) return;

    const ejecutarFiltro = () => {
        const filtroTexto = input.value.toLowerCase();
        const columnaIndice = select ? select.value : 'todos'; // Puede ser 'todos' o índice numérico
        const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const celdas = fila.getElementsByTagName('td');
            let mostrarFila = false;

            if (columnaIndice === 'todos') {
                // Revisar todas las celdas (menos la última que suele ser acciones)
                for (let j = 0; j < celdas.length - 1; j++) {
                    if (celdas[j] && celdas[j].textContent.toLowerCase().indexOf(filtroTexto) > -1) {
                        mostrarFila = true;
                        break;
                    }
                }
            } else {
                // Revisar columna específica
                const celdaObjetivo = celdas[parseInt(columnaIndice)];
                if (celdaObjetivo && celdaObjetivo.textContent.toLowerCase().indexOf(filtroTexto) > -1) {
                    mostrarFila = true;
                }
            }

            fila.style.display = mostrarFila ? '' : 'none';
        }
    };

    // Listeners
    input.addEventListener('keyup', ejecutarFiltro);
    if (select) {
        select.addEventListener('change', ejecutarFiltro);
    }
}


/* ==========================================================================
   8. LOGICA DE ESCÁNER (AUTO-SELECCIÓN)
   ========================================================================== */

/**
 * Inicializa la lógica para detectar escaneos de códigos de barras (ISBN).
 * Si hay coincidencia exacta, selecciona el libro automáticamente.
 */
function inicializarEscanerLibros() {
    // --------------------------------------------------------
    // A) SECCIÓN PRÉSTAMOS
    // --------------------------------------------------------
    const inputPrestamo = document.getElementById('input-busqueda-libro');

    if (inputPrestamo) {
        inputPrestamo.addEventListener('input', function () {
            const valor = this.value.trim();
            if (!valor) return;

            // Buscamos coincidencia EXACTA de ISBN en dbLibros (Disponibles)
            if (typeof dbLibros !== 'undefined') {
                const libroEncontrado = dbLibros.find(libro => libro.isbn === valor);

                if (libroEncontrado) {
                    // 1. Ejecutar selección visual
                    actualizarFichaLibro(libroEncontrado);

                    // 2. Rellenar input oculto ID
                    const hiddenInput = document.getElementById('id_libro_seleccionado');
                    if (hiddenInput) hiddenInput.value = libroEncontrado.id;

                    // 3. Ocultar lista de sugerencias
                    const resultados = document.getElementById('resultados-libros');
                    if (resultados) resultados.style.display = 'none';

                    // 4. Cerrar foco (Blur)
                    this.blur();

                    // Feedback visual opcional en consola
                    console.log("🔫 Escáner: Libro detectado y seleccionado:", libroEncontrado.titulo);
                }
            }
        });
    }

    // --------------------------------------------------------
    // B) SECCIÓN INCIDENCIAS
    // --------------------------------------------------------
    const inputIncidencia = document.getElementById('incidencia-libro');

    if (inputIncidencia) {
        inputIncidencia.addEventListener('input', function () {
            const valor = this.value.trim();
            if (!valor) return;

            // Buscamos coincidencia EXACTA de ISBN en dbLibrosIncidencias (Todos)
            // Nota: dbLibrosIncidencias debe estar definido en gestion.php
            if (typeof dbLibrosIncidencias !== 'undefined') {
                const libroEncontrado = dbLibrosIncidencias.find(libro => libro.isbn === valor);

                if (libroEncontrado) {
                    // 1. Ejecutar selección visual (Reutilizamos lógica de incidencias)
                    const fichaElemento = document.getElementById('ficha-incidencia-libro');
                    if (fichaElemento) {
                        actualizarFichaVisual(libroEncontrado, 'libro', fichaElemento);
                    }

                    // 2. Rellenar input oculto ID
                    const hiddenInput = document.getElementById('id_libro_incidencia');
                    if (hiddenInput) hiddenInput.value = libroEncontrado.id;

                    // 3. Ocultar lista de sugerencias
                    const resultados = document.getElementById('resultados-incidencia-libro');
                    if (resultados) resultados.style.display = 'none';

                    // 4. Cerrar foco (Blur)
                    this.blur();

                    console.log("🔫 Escáner Incidencia: Libro detectado:", libroEncontrado.titulo);
                }
            }
        });
    }
}

// Inicializamos la función cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    inicializarEscanerLibros();

    // NUEVO: Prevenir envío de formulario al pulsar Enter en los buscadores (Corrección Scanner)
    const inputsBuscadores = [
        document.getElementById('input-busqueda-libro'), // Préstamos
        document.getElementById('incidencia-libro')      // Incidencias
    ];

    inputsBuscadores.forEach(input => {
        if (input) {
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.keyCode === 13) {
                    event.preventDefault(); // Detiene el envío del formulario
                    console.log("🛑 Enter capturado para prevenir envío del formulario (Scanner).");
                    return false;
                }
            });
        }
    });

    // NUEVO: Inicializar botones de limpieza "X"
    inicializarBotonesLimpieza();
});


/**
 * Inicializa la funcionalidad de "Limpiar Selección" (Botón X).
 * Inyecta un botón visual para resetear campos de búsqueda y fichas.
 */
function inicializarBotonesLimpieza() {
    // Definimos los objetivos: { idInput, idHidden (opcional), idFicha (opcional), tipo }
    const objetivos = [
        // --- PRÉSTAMOS ---
        { inputId: 'input-busqueda-libro', hiddenId: 'id_libro_seleccionado', fichaId: 'ficha-libro', tipo: 'libro' },
        { inputId: 'input-busqueda-alumno', hiddenId: 'id_alumno_seleccionado', fichaId: 'ficha-alumno', tipo: 'alumno' },

        // --- INCIDENCIAS ---
        { inputId: 'incidencia-libro', hiddenId: 'id_libro_incidencia', fichaId: 'ficha-incidencia-libro', tipo: 'libro' },
        { inputId: 'incidencia-alumno', hiddenId: 'id_alumno_incidencia', fichaId: 'ficha-incidencia-alumno', tipo: 'alumno' },

        // --- GESTIÓN (FILTROS) ---
        { inputId: 'busqueda-libros', esFiltro: true },  // Catálogo Público
        { inputId: 'busqueda-alumno', esFiltro: true },  // Gestión Alumnos
        { inputId: 'busqueda-docente', esFiltro: true }  // Gestión Docentes
    ];

    objetivos.forEach(obj => {
        const input = document.getElementById(obj.inputId);
        if (!input) return;

        // 1. Crear el botón (si no existe ya)
        // Usamos el parentElement que debe tener position: relative (por CSS)
        const contenedor = input.parentElement;
        if (!contenedor) return;

        // Evitar duplicados
        if (contenedor.querySelector('.btn-limpiar')) return;

        const btn = document.createElement('button');
        btn.type = 'button'; // Importante para no enviar forms
        btn.className = 'btn-limpiar';
        btn.innerHTML = '<i class="fa-solid fa-xmark"></i>'; // Icono de FontAwesome
        btn.title = "Limpiar selección";

        // Insertar en el DOM
        contenedor.appendChild(btn);

        // 2. Lógica de visibilidad (Solo mostrar si hay texto)
        const actualizarVisibilidad = () => {
            btn.style.display = input.value.trim() !== '' ? 'block' : 'none';
        };

        // Escuchar cambios en el input
        input.addEventListener('input', actualizarVisibilidad);
        input.addEventListener('change', actualizarVisibilidad);

        // Ejecutar una vez al inicio (por si el navegador rellena datos)
        actualizarVisibilidad();

        // 3. Acción al hacer clic (Limpiar)
        btn.addEventListener('click', function (e) {
            e.preventDefault(); // Por seguridad

            // A) Limpiar Input Visible
            input.value = '';
            input.focus(); // Devolver foco al usuario

            // B) Si es una búsqueda con ficha visual (Préstamos/Incidencias)
            if (obj.hiddenId && obj.fichaId) {
                // Limpiar Input Oculto (ID)
                const hidden = document.getElementById(obj.hiddenId);
                if (hidden) hidden.value = '';

                // Resetear Ficha Visual
                const ficha = document.getElementById(obj.fichaId);
                if (ficha) {
                    ficha.classList.remove('activa');
                    ficha.classList.add('vacia');

                    // Restaurar texto placeholder original según tipo
                    if (obj.tipo === 'libro') {
                        ficha.innerHTML = '<span>Ningún libro seleccionado</span>';
                    } else {
                        // Para incidencias puede ser "Incidencia General"
                        if (obj.inputId === 'incidencia-alumno') {
                            ficha.innerHTML = '<span>Incidencia General (Sin alumno)</span>';
                        } else {
                            ficha.innerHTML = '<span>Ningún alumno seleccionado</span>';
                        }
                    }
                }

                // Ocultar lista de resultados si estaba abierta
                const idResultados = input.nextElementSibling?.nextElementSibling?.id; // Aprox
                // Es más seguro buscar por clase o ID conocido si la estructura es fija
                // Pero como tenemos el contenedor, buscamos .resultados-flotantes dentro
                const listaResultados = contenedor.querySelector('.resultados-flotantes, .resultados-autocompletado');
                if (listaResultados) listaResultados.style.display = 'none';
            }

            // C) Si es un filtro de tabla
            if (obj.esFiltro) {
                // Disparar evento 'input' manual para que el filtro detecte el cambio a vacío
                const eventoInput = new Event('input', { bubbles: true });
                input.dispatchEvent(eventoInput);

                // También Keyup por si acaso usas ese listener
                const eventoKeyup = new Event('keyup', { bubbles: true });
                input.dispatchEvent(eventoKeyup);
            }

            // Actualizar visibilidad del propio botón (ocultarse)
            actualizarVisibilidad();

            console.log(`🧹 Campo ${obj.inputId} limpiado.`);
        });
    });
}
















