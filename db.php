<?php
/**
 * Archivo de conexión a la base de datos.
 * Utiliza PDO para una conexión segura y flexible.
 */

// Configuración de credenciales
$host = 'localhost';
$usuario = 'root';
$password = ''; // Por defecto en XAMPP suele estar vacío
$base_datos = 'biblioteca_manjon';

try {
    // Crear instancia de PDO
    // Se establece el charset a utf8mb4 para soportar caracteres especiales y emojis
    $dsn = "mysql:host=$host;dbname=$base_datos;charset=utf8mb4";
    $pdo = new PDO($dsn, $usuario, $password);

    // Configurar el modo de errores a Excepción para facilitar la depuración
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Configurar el modo de fetch por defecto a Array Asociativo
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si falla la conexión, mostramos un mensaje de error y detenemos el script
    // En producción, esto debería registrarse en un log y mostrar un mensaje genérico al usuario
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}

/**
 * Función helper para ejecutar consultas preparadas de forma segura.
 * 
 * @param string $sql La consulta SQL
 * @param array $parametros Los parámetros para vincular
 * @return PDOStatement El objeto statement resultante
 */
function ejecutarConsulta($sql, $parametros = [])
{
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt;
    } catch (PDOException $e) {
        die("Error en la consulta: " . $e->getMessage());
    }
}
?>