-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-02-2026 a las 10:57:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `biblioteca_manjon`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `curso` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`id`, `nombre`, `curso`, `created_at`, `updated_at`) VALUES
(1, 'Ana Martínez', '1º ESO A', '2026-01-13 16:32:56', '2026-01-13 16:32:56'),
(2, 'Pedro Sánchez', '1º ESO B', '2026-01-13 16:32:56', '2026-01-13 16:32:56'),
(3, 'Laura García', '2º ESO A', '2026-01-13 16:32:56', '2026-01-13 16:32:56'),
(4, 'Miguel Rodríguez', '3º ESO B', '2026-01-13 16:32:56', '2026-01-13 16:32:56'),
(5, 'Sofía Hernández', '4º ESO A', '2026-01-13 16:32:56', '2026-01-13 16:32:56'),
(6, 'Sofía García López', '1º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(7, 'Mateo Rodríguez Ruiz', '1º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(8, 'Valentina Martínez Gil', '1º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(9, 'Hugo Fernández Torres', '1º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(10, 'Lucía González Díaz', '1º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(11, 'Martín Pérez Romero', '1º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(12, 'Alejandro Sánchez Muñoz', '2º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(13, 'Valeria Romero Serrano', '2º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(14, 'Daniel Jiménez Navarro', '2º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(15, 'María Ruiz Molina', '2º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(16, 'Pablo Díaz Castillo', '2º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(17, 'Julia Serrano Ortíz', '2º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(18, 'Adrián Muñoz Marín', '3º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(19, 'Paula Gil Ramos', '3º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(20, 'Álvaro Navarro Ibáñez', '3º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(21, 'Emma Torres Castro', '3º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(22, 'Leo Molina Garrido', '3º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(23, 'Carla Castillo Rubio', '3º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(24, 'Diego Ortíz Sáez', '4º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(25, 'Daniela Marín Santos', '4º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(26, 'Javier Ramos Cano', '4º ESO A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(27, 'Sara Ibáñez Flores', '4º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(28, 'Mario Castro Méndez', '4º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(29, 'Claudia Garrido Cruz', '4º ESO B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(30, 'Lucas Rubio Gallego', '1º Bach A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(31, 'Elena Sáez Calvo', '1º Bach A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(32, 'Manuel Santos Vidal', '1º Bach B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(33, 'Lara Cano Reyes', '1º Bach B', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(34, 'Marcos Flores Prieto', '2º Bach A', '2026-01-21 17:57:57', '2026-01-21 17:57:57'),
(35, 'Carmen Méndez León', '2º Bach B', '2026-01-21 17:57:57', '2026-01-21 17:57:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_prestamos`
--

CREATE TABLE `historial_prestamos` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_usuario_presta` int(11) NOT NULL,
  `fecha_salida` date NOT NULL,
  `fecha_devolucion_real` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_prestamos`
--

INSERT INTO `historial_prestamos` (`id`, `id_libro`, `id_alumno`, `id_usuario_presta`, `fecha_salida`, `fecha_devolucion_real`, `created_at`) VALUES
(19, 32, 2, 1, '2026-01-28', '2026-02-02', '2026-02-02 09:55:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) DEFAULT NULL,
  `id_alumno` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `sancion` text DEFAULT NULL,
  `estado` enum('pendiente','resuelta') DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incidencias`
--

INSERT INTO `incidencias` (`id`, `id_libro`, `id_alumno`, `id_usuario`, `descripcion`, `sancion`, `estado`, `fecha`) VALUES
(1, NULL, 1, 1, 'El alumno ha devuelto el libro \"Harry Potter\" con la portada arrancada.', 'Reponer el libro o pagar su coste (15€).', 'resuelta', '2025-05-10 08:30:00'),
(2, NULL, 2, 2, 'Gritos y comportamiento disruptivo en la zona de estudio silencioso.', 'Aviso preventivo. A la próxima, 1 semana sin acceso.', 'pendiente', '2025-05-12 09:15:00'),
(3, NULL, 5, 1, 'Intento de llevarse un libro sin pasar por el mostrador de préstamo.', 'Suspensión de préstamo por 1 mes.', 'resuelta', '2025-05-14 07:00:00'),
(4, NULL, NULL, 1, 'Se ha detectado una humedad en la estantería de la sección de Poesía.', 'Aviso enviado a mantenimiento.', 'pendiente', '2025-05-15 06:45:00'),
(5, NULL, NULL, 2, 'Faltan 3 sillas en la mesa redonda del fondo. Posible traslado al aula de música.', 'Localizar y devolver las sillas.', 'pendiente', '2025-05-16 10:30:00'),
(6, NULL, 8, 1, 'Retraso de más de 20 días en la devolución de 3 libros.', 'Multa de 2€ y tutoría informada.', 'resuelta', '2025-04-20 14:00:00'),
(7, NULL, 10, 2, 'Pérdida del carnet de biblioteca por segunda vez.', 'Cobro de 1€ por emisión de nuevo carnet.', 'resuelta', '2025-04-25 08:20:00'),
(8, NULL, NULL, 1, 'El ordenador de consulta nº 3 no enciende.', 'Fuente de alimentación cambiada por el servicio técnico.', 'resuelta', '2025-05-01 12:00:00'),
(9, NULL, 3, 1, 'Manchas de comida en las páginas del libro \"El Quijote\".', 'Limpieza realizada y advertencia verbal.', 'resuelta', '2025-05-05 11:10:00'),
(10, NULL, 12, 2, 'Uso inapropiado de los ordenadores (juegos en horas de estudio).', 'Bloqueo de usuario por 3 días.', 'resuelta', '2025-05-08 15:45:00'),
(11, 7, 2, 1, 'Fue mongolo  yse le rompio', '', 'resuelta', '2026-01-21 23:00:00'),
(12, 16, NULL, 1, 'Fue mongurr', 'quien', 'resuelta', '2026-01-21 23:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `autor` varchar(150) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `color_categoria` enum('rojo','azul','verde','amarillo','rosa','naranja') NOT NULL DEFAULT 'azul',
  `estado` enum('disponible','prestado','reservado','mantenimiento') NOT NULL DEFAULT 'disponible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sinopsis` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `autor`, `isbn`, `color_categoria`, `estado`, `created_at`, `updated_at`, `sinopsis`) VALUES
(1, '¡Qué Cosas!', 'Edith Schreiber-Wicke', '9788434836778', 'azul', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:55:17', NULL),
(2, '¡Una de Piratas!', 'José Luis Alonso de Santos', '9788434870628', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:54:51', NULL),
(3, '4 años, 6 meses y 3 días después', 'Emmanuel Bourdier', '9788426366948', 'rosa', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:55:27', ''),
(4, 'A vueltas con mi nombre', 'Alice Vieira', '9788434830905', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 12:39:28', NULL),
(5, 'Abdel', 'Enrique Páez', '9788467577853', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:55:21', NULL),
(6, 'Madera ¡Desechos!', 'Veronica Bonar', '9788426326362', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:31', NULL),
(7, 'Asterix, El Galo', 'René Goscinny y Albert Uderzo', '8475100260', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 08:01:51', NULL),
(8, 'Belfy y Lillibit 4', 'Pepe Gálvez y Manuel Vázquez', '8485604601', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-01-28 20:58:22', NULL),
(9, 'Belfy y Lillibit 6', 'Pepe Gálvez y Manuel Vázquez', '9788466650178', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:02', NULL),
(10, 'Breve Historia de Aragón', 'José Antonio Parrilla y José Antonio Muñiz', '8450097584', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-01-28 20:58:54', NULL),
(11, 'Musicando con... Rossini y la Cenicienta', 'Montse Sanuy', '9788430545841', 'verde', 'prestado', '2026-01-26 11:53:05', '2026-02-02 08:46:20', NULL),
(12, 'Musicando con... Beethoven y Fidelio', 'Montse Sanuy', '9788430545827', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:48', NULL),
(13, 'Musicando con... Chopin y Las Sílfides', 'Montse Sanuy', '9788430566877', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:53', NULL),
(14, 'Musicando con... Verdi y Aida', 'Montse Sanuy', '9788430561353', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:55:04', NULL),
(15, 'Musicando con... Strauss y El Murciélago', 'Montse Sanuy', '9788430566860', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:58', NULL),
(16, '¡Buenos Días!', 'Asunción Lissón', '9788424606596', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(17, '¡Caramba con los amigos!', 'Ricardo Alcántara', '9788478644322', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:48:26', NULL),
(18, '¡Cómo brilla el mar!', 'Mercè Company Gonzalez', '9788434836631', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(19, '¡Crea!', 'Román Belmonte Andújar', '9788494808579', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(20, '¡Cuánto me quieren!', 'Alejandra Vallejo-Nágera', '9788420449517', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:52:27', NULL),
(21, '¡¡¡PAPÁÁÁ!!!', 'Carles Cano', '9788469885611', 'naranja', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(22, '¡Cómo molo!', 'Elvira Lindo', '9788420458564', 'naranja', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(23, '¡Corre, Sebastián, Corre!', 'Juan Kruz Igerabide', '8467221542', 'naranja', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:51:53', NULL),
(24, '¡Cumpleaños feliz!', 'Carmen Vázquez-Vigo', '9788421620816', 'naranja', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(25, '¡Encerrados en clase!', 'Miquel Capó y Haizea M. Zubieta', '9788418318917', 'naranja', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(26, 'Descubrir el mundo: La Selva', 'Varios Autores', '9788482986128', 'azul', 'prestado', '2026-01-26 11:53:05', '2026-02-02 09:55:36', NULL),
(27, 'El Sistema Solar', 'Gaby Goldsack', '8450043190', 'azul', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:46:14', ''),
(28, 'La capa de Ozono', 'Tony Hare', '9789580421863', 'azul', 'disponible', '2026-01-26 11:53:05', '2026-01-26 12:32:00', NULL),
(29, 'Animales desaparecidos', 'Claude Delafosse', '9781851034086', 'azul', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:57:26', NULL),
(30, 'Salvemos la Tierra', 'Jonathon Porritt', '9681901169', 'azul', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:46:50', NULL),
(31, 'Ecoeducación', 'Mario Gomboli', '9788421632871', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(32, 'Cuentos de Todos los Colores', 'Aro Sáinz de la Maza y Josep M. Hernández Ripoll', '9788478711239', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:55:53', NULL),
(33, 'Tu primer VOX de Cuentos del Mundo', 'Marie-Pierre Levallois', '9788483326053', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(34, 'Niños y Niñas del Mundo', 'Núria Roca', '9780764121425', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:45:32', NULL),
(35, 'Mandela', 'Alain Blondel', '9788492197781', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(36, 'Adiós, tristeza. ¡Hola, alegría!', 'Ana Serna Vara', '9788467774221', 'rosa', 'disponible', '2026-01-26 11:53:05', '2026-02-02 08:50:20', NULL),
(37, 'Miedo', 'Ana Serna Vara', '9788467774269', 'rosa', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(38, 'Alegría', 'Ana Serna Vara', '9788467774221', 'rosa', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(39, 'Adiós, Enfado ¡Hola, calma!', 'Ana Serna Vara', '9788467774252', 'rosa', 'disponible', '2026-01-26 11:53:05', '2026-02-02 08:14:45', NULL),
(40, 'Sinceridad', 'Violeta Monreal', '9788439208907', 'rosa', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(41, 'Las chicas son guerreras', 'Irene Cívico y Sergio Parra', '9788490436547', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:19', NULL),
(42, 'Inventoras y sus inventos', 'Aitziber López-Lozano', '9788494743238', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:12', NULL),
(43, 'Luchadoras', 'Cristina Serret Alonso', '9788413610115', 'rojo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:25', NULL),
(44, 'No me cuentes cuentos', 'Varios Autores', '9788417922290', 'azul', 'disponible', '2026-01-26 11:53:05', '2026-01-28 20:56:29', ''),
(45, 'Mujeres exploradoras', 'Riccardo Francaviglia y Margherita Sgarlata', '9788468269719', 'verde', 'disponible', '2026-01-26 11:53:05', '2026-02-02 09:54:40', NULL),
(46, 'Students in space', 'Craig Wright', '9780194400992', 'amarillo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(47, 'Best friends in Fairyland', 'Daisy Meadows', '9780545222938', 'amarillo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL),
(48, 'Monster party!', 'Annie Bach', '9781454910510', 'amarillo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 08:38:52', NULL),
(49, 'The Birthday Cake', 'Alex Lane', '9780198470588', 'amarillo', 'disponible', '2026-01-26 11:53:05', '2026-02-02 07:47:44', NULL),
(50, 'Billy the Kid', 'Ruth Miskin y Gill Munton', '9780198386797', 'amarillo', 'disponible', '2026-01-26 11:53:05', '2026-01-26 11:53:05', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamos_activos`
--

CREATE TABLE `prestamos_activos` (
  `id` int(11) NOT NULL,
  `id_libro` int(11) NOT NULL,
  `id_alumno` int(11) NOT NULL,
  `id_usuario_presta` int(11) NOT NULL,
  `fecha_salida` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `prestamos_activos`
--

INSERT INTO `prestamos_activos` (`id`, `id_libro`, `id_alumno`, `id_usuario_presta`, `fecha_salida`, `fecha_vencimiento`, `created_at`) VALUES
(32, 11, 2, 1, '2026-01-28', '2026-02-12', '2026-01-28 20:55:06'),
(33, 26, 2, 1, '2026-02-02', '2026-02-17', '2026-02-02 09:55:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('profesor','directiva') NOT NULL DEFAULT 'profesor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `password`, `rol`, `created_at`, `updated_at`) VALUES
(1, 'Director García', 'admin', '$2y$10$9o/yBD9SSVuV41nLhDpoa.neQIHrofGXEbL/CDr7mNtJR/1dZ6Ceu', 'directiva', '2026-01-13 16:32:56', '2026-01-13 16:43:56'),
(2, 'María López', 'profesor1', '$2y$10$rayRbhEDVqgo2tmqxsJ0Juj13CHA2CvpWjAh8fOci8s9/yzLXVqvC', 'profesor', '2026-01-13 16:32:56', '2026-01-13 16:43:56'),
(3, 'Carlos Fernández', 'profesor2', '$2y$10$rayRbhEDVqgo2tmqxsJ0Juj13CHA2CvpWjAh8fOci8s9/yzLXVqvC', 'profesor', '2026-01-13 16:32:56', '2026-01-13 16:43:56'),
(4, 'guille', 'guille', '$2y$10$pYz7/5P7jHsOjJQIkQUsLegBMmEve4EV8KrJwbux1uDAhwxWY2P8i', 'profesor', '2026-01-20 11:58:53', '2026-01-20 11:58:53');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_prestamos`
--
ALTER TABLE `historial_prestamos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_presta` (`id_usuario_presta`),
  ADD KEY `idx_fechas` (`fecha_salida`,`fecha_devolucion_real`),
  ADD KEY `idx_libro_hist` (`id_libro`),
  ADD KEY `idx_alumno_hist` (`id_alumno`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_alumno` (`id_alumno`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_color` (`color_categoria`),
  ADD KEY `idx_isbn` (`isbn`);

--
-- Indices de la tabla `prestamos_activos`
--
ALTER TABLE `prestamos_activos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario_presta` (`id_usuario_presta`),
  ADD KEY `idx_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_libro` (`id_libro`),
  ADD KEY `idx_alumno` (`id_alumno`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `historial_prestamos`
--
ALTER TABLE `historial_prestamos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `prestamos_activos`
--
ALTER TABLE `prestamos_activos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `historial_prestamos`
--
ALTER TABLE `historial_prestamos`
  ADD CONSTRAINT `historial_prestamos_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_prestamos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_prestamos_ibfk_3` FOREIGN KEY (`id_usuario_presta`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `incidencias_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `prestamos_activos`
--
ALTER TABLE `prestamos_activos`
  ADD CONSTRAINT `prestamos_activos_ibfk_1` FOREIGN KEY (`id_libro`) REFERENCES `libros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prestamos_activos_ibfk_2` FOREIGN KEY (`id_alumno`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prestamos_activos_ibfk_3` FOREIGN KEY (`id_usuario_presta`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
