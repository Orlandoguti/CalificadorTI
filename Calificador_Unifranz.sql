-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.3.0 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla calificador_unifranz.areas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sede_id` int DEFAULT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `permite_csat` tinyint(1) NOT NULL DEFAULT '1',
  `permite_nps` tinyint(1) NOT NULL DEFAULT '0',
  `permite_fcr` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_areas_sede_id` (`sede_id`),
  CONSTRAINT `fk_areas_sede_id` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.areas: ~40 rows (aproximadamente)
INSERT INTO `areas` (`id`, `sede_id`, `nombre`, `codigo`, `descripcion`, `password`, `is_active`, `permite_csat`, `permite_nps`, `permite_fcr`, `created_at`, `updated_at`) VALUES
	(27, 1, 'Admision y Registro', 'ARCA', NULL, 'arca2025', 1, 0, 1, 1, '2025-10-27 22:12:08', '2025-11-06 21:28:07'),
	(28, 3, 'Admision y Registro', 'ARCA', NULL, 'arca2025', 1, 0, 1, 1, '2025-10-27 22:12:08', '2025-10-31 18:42:52'),
	(29, 2, 'Admision y Registro', 'ARCA', NULL, 'arca2025', 1, 0, 1, 1, '2025-10-27 22:12:08', '2025-10-31 18:42:58'),
	(30, 4, 'Admision y Registro', 'ARCA', NULL, 'arca2025', 1, 0, 1, 1, '2025-10-27 22:12:08', '2025-10-31 18:43:02'),
	(35, 1, 'Tecnologias de la Informacion', 'TI', NULL, 'ti2025', 1, 0, 0, 1, '2025-10-27 23:35:12', '2025-11-06 21:28:37'),
	(36, 3, 'Tecnologias de la Informacion', 'TI', NULL, 'ti2025', 1, 0, 0, 1, '2025-10-27 23:35:13', '2025-10-31 18:51:37'),
	(37, 2, 'Tecnologias de la Informacion', 'TI', NULL, 'ti2025', 1, 0, 0, 1, '2025-10-27 23:35:14', '2025-10-31 18:51:43'),
	(38, 4, 'Tecnologias de la Informacion', 'TI', NULL, 'ti2025', 1, 0, 0, 1, '2025-10-27 23:35:14', '2025-10-31 18:51:48'),
	(39, 1, 'Servicios Estudiantiles', 'SES', NULL, 'ses2025', 1, 1, 1, 1, '2025-10-28 00:00:25', '2025-11-06 21:00:21'),
	(40, 3, 'Servicios Estudiantiles', 'SES', NULL, 'ses2025', 1, 1, 0, 1, '2025-10-28 00:00:26', '2025-10-31 18:43:27'),
	(41, 4, 'Servicios Estudiantiles', 'SES', NULL, 'ses2025', 1, 1, 0, 1, '2025-10-28 00:00:26', '2025-10-31 18:43:31'),
	(42, 2, 'Servicios Estudiantiles', 'SES', NULL, 'ses2025', 1, 0, 0, 1, '2025-10-28 00:00:26', '2025-11-06 20:32:09'),
	(43, 1, 'Recepcion', 'REC', NULL, 'recepcion2025', 1, 1, 0, 0, '2025-10-28 01:45:08', '2025-10-31 18:52:28'),
	(44, 3, 'Recepcion', 'REC', NULL, 'recepcion2025', 1, 1, 0, 0, '2025-10-28 01:45:09', '2025-10-31 18:52:34'),
	(45, 4, 'Recepcion', 'REC', NULL, 'recepcion2025', 1, 1, 0, 0, '2025-10-28 01:45:09', '2025-10-31 18:52:38'),
	(46, 2, 'Recepcion', 'REC', NULL, 'recepcion2025', 1, 1, 0, 0, '2025-10-28 01:45:09', '2025-10-31 18:52:46'),
	(47, 1, 'Gestion Financiera', 'GFC', NULL, 'sg2025', 1, 1, 1, 1, '2025-10-29 19:16:06', '2025-11-17 17:59:58'),
	(48, 3, 'Gestion Financiera', 'GFC', NULL, 'sg2025', 1, 0, 1, 1, '2025-10-29 19:16:06', '2025-10-31 18:53:08'),
	(49, 4, 'Gestion Financiera', 'GFC', NULL, 'sg2025', 1, 0, 1, 1, '2025-10-29 19:16:07', '2025-10-31 18:53:13'),
	(50, 2, 'Gestion Financiera', 'GFC', NULL, 'sg2025', 1, 0, 1, 1, '2025-10-29 19:16:07', '2025-10-31 18:53:18'),
	(51, 1, 'Biblioteca', 'BIB', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:49:30', '2025-10-31 18:49:30'),
	(52, 4, 'Biblioteca', 'BIB', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(53, 2, 'Biblioteca', 'BIB', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(54, 3, 'Biblioteca', 'BIB', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:49:33', '2025-10-31 18:49:33'),
	(55, 4, 'Academica', 'ACA', NULL, 'admin', 1, 1, 0, 1, '2025-10-31 18:50:11', '2025-10-31 18:50:11'),
	(56, 2, 'Academica', 'ACA', NULL, 'admin', 1, 1, 0, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(57, 1, 'Academica', 'ACA', NULL, 'admin', 1, 1, 0, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(58, 3, 'Academica', 'ACA', NULL, 'admin', 1, 1, 0, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(59, 1, 'Admisiones', 'ADM', NULL, 'admin', 1, 1, 1, 0, '2025-10-31 18:50:48', '2025-10-31 18:50:48'),
	(60, 3, 'Admisiones', 'ADM', NULL, 'admin', 1, 1, 1, 0, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(61, 4, 'Admisiones', 'ADM', NULL, 'admin', 1, 1, 1, 0, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(62, 2, 'Admisiones', 'ADM', NULL, 'admin', 1, 1, 1, 0, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(63, 1, 'Cafeteria', 'CAF', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(64, 3, 'Cafeteria', 'CAF', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(65, 4, 'Cafeteria', 'CAF', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(66, 2, 'Cafeteria', 'CAF', NULL, 'admin', 1, 1, 0, 0, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(67, 1, 'Cajas', 'CAJ', NULL, 'admin', 1, 0, 0, 1, '2025-10-31 18:52:14', '2025-10-31 18:52:14'),
	(68, 2, 'Cajas', 'CAJ', NULL, 'admin', 1, 0, 0, 1, '2025-10-31 18:52:15', '2025-10-31 18:52:15'),
	(69, 3, 'Cajas', 'CAJ', NULL, 'admin', 1, 0, 0, 1, '2025-10-31 18:52:15', '2025-10-31 18:52:15'),
	(70, 4, 'Cajas', 'CAJ', NULL, 'admin', 1, 0, 0, 1, '2025-10-31 18:52:15', '2025-10-31 18:52:15');

-- Volcando estructura para tabla calificador_unifranz.area_pregunta
CREATE TABLE IF NOT EXISTS `area_pregunta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `area_id` int NOT NULL,
  `sede_id` int DEFAULT NULL,
  `pregunta_id` int NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `area_pregunta_sede_id_foreign` (`sede_id`),
  KEY `area_pregunta_area_id_foreign` (`area_id`),
  KEY `area_pregunta_pregunta_id_foreign` (`pregunta_id`),
  CONSTRAINT `area_pregunta_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_pregunta_pregunta_id_foreign` FOREIGN KEY (`pregunta_id`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_pregunta_sede_id_foreign` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=500 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.area_pregunta: ~140 rows (aproximadamente)
INSERT INTO `area_pregunta` (`id`, `area_id`, `sede_id`, `pregunta_id`, `is_active`, `created_at`, `updated_at`) VALUES
	(209, 28, 3, 3, 1, '2025-10-28 06:58:59', '2025-10-28 06:58:59'),
	(210, 29, 2, 10, 1, '2025-10-28 06:58:59', '2025-10-28 06:58:59'),
	(211, 30, 4, 3, 1, '2025-10-28 06:58:59', '2025-10-28 06:58:59'),
	(230, 39, 1, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(231, 40, 3, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(232, 41, 4, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(234, 43, 1, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(235, 44, 3, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(236, 45, 4, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(237, 46, 2, 4, 1, '2025-10-28 07:34:59', '2025-10-28 07:34:59'),
	(246, 39, 1, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(247, 40, 3, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(248, 41, 4, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(250, 43, 1, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(251, 44, 3, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(252, 45, 4, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(253, 46, 2, 5, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(261, 39, 1, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(262, 40, 3, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(263, 41, 4, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(265, 43, 1, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(266, 44, 3, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(267, 45, 4, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(268, 46, 2, 6, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(289, 28, 3, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(290, 29, 2, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(291, 30, 4, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(292, 35, 1, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(293, 36, 3, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(294, 38, 4, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(295, 39, 1, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(296, 40, 3, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(297, 41, 4, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(298, 42, 2, 8, 1, '2025-10-29 08:56:06', '2025-10-29 08:56:06'),
	(359, 39, 1, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(360, 40, 3, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(361, 41, 4, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(362, 43, 1, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(363, 44, 3, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(364, 45, 4, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(365, 46, 2, 9, 1, '2025-10-29 21:45:21', '2025-10-29 21:45:21'),
	(379, 42, 2, 4, 1, '2025-10-31 18:43:37', '2025-10-31 18:43:37'),
	(380, 42, 2, 5, 1, '2025-10-31 18:43:37', '2025-10-31 18:43:37'),
	(381, 42, 2, 6, 1, '2025-10-31 18:43:37', '2025-10-31 18:43:37'),
	(382, 42, 2, 9, 1, '2025-10-31 18:43:37', '2025-10-31 18:43:37'),
	(383, 51, 1, 4, 1, '2025-10-31 18:49:30', '2025-10-31 18:49:30'),
	(384, 51, 1, 5, 1, '2025-10-31 18:49:30', '2025-10-31 18:49:30'),
	(385, 51, 1, 6, 1, '2025-10-31 18:49:30', '2025-10-31 18:49:30'),
	(386, 51, 1, 9, 1, '2025-10-31 18:49:30', '2025-10-31 18:49:30'),
	(387, 52, 4, 4, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(388, 52, 4, 5, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(389, 52, 4, 6, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(390, 52, 4, 9, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(391, 53, 2, 4, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(392, 53, 2, 5, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(393, 53, 2, 6, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(394, 53, 2, 9, 1, '2025-10-31 18:49:32', '2025-10-31 18:49:32'),
	(395, 54, 3, 4, 1, '2025-10-31 18:49:33', '2025-10-31 18:49:33'),
	(396, 54, 3, 5, 1, '2025-10-31 18:49:33', '2025-10-31 18:49:33'),
	(397, 54, 3, 6, 1, '2025-10-31 18:49:33', '2025-10-31 18:49:33'),
	(398, 54, 3, 9, 1, '2025-10-31 18:49:33', '2025-10-31 18:49:33'),
	(399, 55, 4, 4, 1, '2025-10-31 18:50:11', '2025-10-31 18:50:11'),
	(400, 55, 4, 5, 1, '2025-10-31 18:50:11', '2025-10-31 18:50:11'),
	(401, 55, 4, 6, 1, '2025-10-31 18:50:11', '2025-10-31 18:50:11'),
	(402, 55, 4, 9, 1, '2025-10-31 18:50:11', '2025-10-31 18:50:11'),
	(403, 55, 4, 8, 1, '2025-10-31 18:50:11', '2025-10-31 18:50:11'),
	(404, 56, 2, 4, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(405, 56, 2, 5, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(406, 56, 2, 6, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(407, 56, 2, 9, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(408, 56, 2, 8, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(409, 57, 1, 4, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(410, 57, 1, 5, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(411, 57, 1, 6, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(412, 57, 1, 9, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(413, 57, 1, 8, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(414, 58, 3, 4, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(415, 58, 3, 5, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(416, 58, 3, 6, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(417, 58, 3, 9, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(418, 58, 3, 8, 1, '2025-10-31 18:50:12', '2025-10-31 18:50:12'),
	(419, 59, 1, 4, 1, '2025-10-31 18:50:48', '2025-10-31 18:50:48'),
	(420, 59, 1, 5, 1, '2025-10-31 18:50:48', '2025-10-31 18:50:48'),
	(421, 59, 1, 6, 1, '2025-10-31 18:50:48', '2025-10-31 18:50:48'),
	(422, 59, 1, 9, 1, '2025-10-31 18:50:48', '2025-10-31 18:50:48'),
	(423, 59, 1, 10, 1, '2025-10-31 18:50:48', '2025-10-31 18:50:48'),
	(424, 60, 3, 4, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(425, 60, 3, 5, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(426, 60, 3, 6, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(427, 60, 3, 9, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(428, 60, 3, 3, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(429, 61, 4, 4, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(430, 61, 4, 5, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(431, 61, 4, 6, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(432, 61, 4, 9, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(433, 61, 4, 3, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(434, 62, 2, 4, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(435, 62, 2, 5, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(436, 62, 2, 6, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(437, 62, 2, 9, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(438, 62, 2, 3, 1, '2025-10-31 18:50:49', '2025-10-31 18:50:49'),
	(439, 63, 1, 4, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(440, 63, 1, 5, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(441, 63, 1, 6, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(442, 63, 1, 9, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(443, 64, 3, 4, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(444, 64, 3, 5, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(445, 64, 3, 6, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(446, 64, 3, 9, 1, '2025-10-31 18:51:16', '2025-10-31 18:51:16'),
	(447, 65, 4, 4, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(448, 65, 4, 5, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(449, 65, 4, 6, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(450, 65, 4, 9, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(451, 66, 2, 4, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(452, 66, 2, 5, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(453, 66, 2, 6, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(454, 66, 2, 9, 1, '2025-10-31 18:51:17', '2025-10-31 18:51:17'),
	(455, 37, 2, 8, 1, '2025-10-31 18:51:43', '2025-10-31 18:51:43'),
	(456, 67, 1, 8, 1, '2025-10-31 18:52:14', '2025-10-31 18:52:14'),
	(457, 68, 2, 8, 1, '2025-10-31 18:52:15', '2025-10-31 18:52:15'),
	(458, 69, 3, 8, 1, '2025-10-31 18:52:15', '2025-10-31 18:52:15'),
	(459, 70, 4, 8, 1, '2025-10-31 18:52:15', '2025-10-31 18:52:15'),
	(460, 47, 1, 10, 1, '2025-10-31 18:53:02', '2025-10-31 18:53:02'),
	(461, 47, 1, 8, 1, '2025-10-31 18:53:02', '2025-10-31 18:53:02'),
	(462, 48, 3, 3, 1, '2025-10-31 18:53:08', '2025-10-31 18:53:08'),
	(463, 48, 3, 8, 1, '2025-10-31 18:53:08', '2025-10-31 18:53:08'),
	(464, 49, 4, 3, 1, '2025-10-31 18:53:13', '2025-10-31 18:53:13'),
	(465, 49, 4, 8, 1, '2025-10-31 18:53:13', '2025-10-31 18:53:13'),
	(466, 50, 2, 3, 1, '2025-10-31 18:53:18', '2025-10-31 18:53:18'),
	(467, 50, 2, 8, 1, '2025-10-31 18:53:18', '2025-10-31 18:53:18'),
	(490, 27, 1, 10, 1, '2025-11-06 19:35:57', '2025-11-06 19:35:57'),
	(491, 39, 1, 10, 1, '2025-11-06 21:58:03', '2025-11-06 21:58:03'),
	(492, 27, 1, 4, 1, '2025-11-07 00:26:08', '2025-11-07 00:26:08'),
	(493, 27, 1, 5, 1, '2025-11-07 00:26:08', '2025-11-07 00:26:08'),
	(494, 27, 1, 6, 1, '2025-11-07 00:26:08', '2025-11-07 00:26:08'),
	(495, 27, 1, 9, 1, '2025-11-07 00:26:08', '2025-11-07 00:26:08'),
	(496, 47, 1, 11, 1, '2025-11-17 17:59:58', '2025-11-17 17:59:58'),
	(497, 47, 1, 5, 1, '2025-11-17 17:59:58', '2025-11-17 17:59:58'),
	(498, 47, 1, 6, 1, '2025-11-17 17:59:58', '2025-11-17 17:59:58'),
	(499, 47, 1, 9, 1, '2025-11-17 17:59:58', '2025-11-17 17:59:58');

-- Volcando estructura para tabla calificador_unifranz.calificaciones
CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `area_id` int NOT NULL,
  `sede_id` int NOT NULL,
  `tipo_calificacion` enum('csat','nps','fcr') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de calificación: CSAT, NPS o FCR',
  `valor_principal` int DEFAULT NULL COMMENT 'CSAT: 1-4, NPS: 0-10, FCR: 0=Sí, 1=No',
  `nivel_calificacion_id` int DEFAULT NULL COMMENT 'NULL para FCR y NPS, 1-4 para CSAT',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `area_id` (`area_id`),
  KEY `sede_id` (`sede_id`),
  KEY `nivel_calificacion_id` (`nivel_calificacion_id`),
  KEY `idx_tipo_calificacion` (`tipo_calificacion`),
  KEY `idx_fecha` (`created_at`),
  CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_ibfk_3` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calificaciones_ibfk_4` FOREIGN KEY (`nivel_calificacion_id`) REFERENCES `niveles_calificacion` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.calificaciones: ~5 rows (aproximadamente)
INSERT INTO `calificaciones` (`id`, `user_id`, `area_id`, `sede_id`, `tipo_calificacion`, `valor_principal`, `nivel_calificacion_id`, `created_at`, `updated_at`) VALUES
	(1, NULL, 47, 1, 'csat', NULL, 1, '2025-11-17 18:02:38', '2025-11-17 18:02:38'),
	(2, NULL, 47, 1, 'csat', NULL, 2, '2025-11-17 18:03:26', '2025-11-17 18:03:26'),
	(3, NULL, 47, 1, 'csat', NULL, 1, '2025-11-17 18:14:19', '2025-11-17 18:14:19'),
	(4, NULL, 47, 1, 'nps', 7, NULL, '2025-11-17 18:14:30', '2025-11-17 18:14:30'),
	(5, NULL, 47, 1, 'fcr', NULL, NULL, '2025-11-17 18:14:33', '2025-11-17 18:14:33');

-- Volcando estructura para tabla calificador_unifranz.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.migrations: ~10 rows (aproximadamente)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '2014_10_12_000000_create_users_table', 1),
	(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
	(3, '2019_08_19_000000_create_failed_jobs_table', 1),
	(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
	(5, '2025_01_01_000000_add_indicators_to_areas_table', 2),
	(7, '2025_01_27_000001_add_tipo_pregunta_to_preguntas', 3),
	(8, '2025_01_27_000003_create_tipos_calificacion_table', 4),
	(9, '2025_10_27_190534_add_sede_id_to_area_pregunta_table', 5),
	(11, '2025_10_28_013133_add_descripcion_icono_color_to_areas_table', 6),
	(12, '2025_10_29_045436_make_niveles_calificacion_id_nullable_in_preguntas_table', 7);

-- Volcando estructura para tabla calificador_unifranz.niveles_calificacion
CREATE TABLE IF NOT EXISTS `niveles_calificacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emoji` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` int NOT NULL,
  `color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.niveles_calificacion: ~4 rows (aproximadamente)
INSERT INTO `niveles_calificacion` (`id`, `nombre`, `emoji`, `valor`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'Muy Insatisfecho', '?', 1, '#EF4444', 1, '2025-10-03 17:55:21', '2025-10-03 17:55:21'),
	(2, 'Insatisfecho', '?', 2, '#F59E0B', 1, '2025-10-03 17:55:21', '2025-10-03 17:55:21'),
	(3, 'Satisfecho', '?', 3, '#10B981', 1, '2025-10-03 17:55:21', '2025-10-03 17:55:21'),
	(4, 'Muy Satisfecho', '?', 4, '#3B82F6', 1, '2025-10-03 17:55:21', '2025-10-03 17:55:21');

-- Volcando estructura para tabla calificador_unifranz.opciones_pregunta
CREATE TABLE IF NOT EXISTS `opciones_pregunta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pregunta_id` int NOT NULL,
  `opcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tiene_subpreguntas` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pregunta_id` (`pregunta_id`),
  CONSTRAINT `opciones_pregunta_ibfk_1` FOREIGN KEY (`pregunta_id`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.opciones_pregunta: ~19 rows (aproximadamente)
INSERT INTO `opciones_pregunta` (`id`, `pregunta_id`, `opcion`, `created_at`, `updated_at`, `tiene_subpreguntas`) VALUES
	(79, 4, 'Mejorar amabilidad del personal', '2025-10-28 07:34:59', '2025-10-28 07:34:59', 0),
	(83, 5, 'Mejorar amabilidad del personal', '2025-10-28 07:50:08', '2025-10-28 07:50:08', 0),
	(84, 5, 'Proceso de atención (complicado/confuso)', '2025-10-28 07:50:08', '2025-10-28 07:50:08', 0),
	(85, 5, 'Claridad de la información proporcionada', '2025-10-28 07:50:08', '2025-10-28 07:50:08', 0),
	(86, 5, 'Otro - especifique', '2025-10-28 07:50:08', '2025-10-28 07:50:08', 0),
	(87, 6, 'Amabilidad del personal', '2025-10-28 07:51:12', '2025-10-28 07:51:12', 0),
	(88, 6, 'Rapidez del servicio/atención', '2025-10-28 07:51:12', '2025-10-28 07:51:12', 0),
	(89, 6, 'Claridad de la información recibida', '2025-10-28 07:51:12', '2025-10-28 07:51:12', 0),
	(90, 6, 'Otro - especifique', '2025-10-28 07:51:12', '2025-10-28 07:51:12', 0),
	(95, 8, 'Sí', '2025-10-29 08:56:06', '2025-10-30 01:20:42', 0),
	(96, 8, 'No', '2025-10-29 08:56:06', '2025-10-30 01:20:42', 1),
	(106, 4, 'Proceso de atención (complicado/confuso)', '2025-10-29 21:36:42', '2025-10-29 21:36:42', 0),
	(107, 4, 'Claridad de la información proporcionada', '2025-10-29 21:36:42', '2025-10-29 21:36:42', 0),
	(108, 4, 'Otro - especifique', '2025-10-29 21:36:42', '2025-10-29 21:36:42', 0),
	(109, 9, 'Amabilidad del personal', '2025-10-29 21:45:21', '2025-10-29 21:45:21', 0),
	(110, 9, 'Rapidez del servicio/atención', '2025-10-29 21:45:21', '2025-10-29 21:45:21', 0),
	(111, 9, 'Claridad de la información recibida', '2025-10-29 21:45:21', '2025-10-29 21:45:21', 0),
	(112, 9, 'Otro - especifique', '2025-10-29 21:45:21', '2025-10-29 21:45:21', 0),
	(113, 11, 'Proceso de atención (complicado/confuso)', '2025-10-29 21:36:42', '2025-10-29 21:36:42', 0);

-- Volcando estructura para tabla calificador_unifranz.preguntas
CREATE TABLE IF NOT EXISTS `preguntas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pregunta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('opcion_unica','opcion_multiple','texto_libre','indicador_0_10','opcion_unica_texto_libre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_pregunta` enum('csat','nps','fcr') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL = pregunta normal, con valor = pregunta genérica',
  `niveles_calificacion_id` bigint unsigned DEFAULT NULL COMMENT 'Solo para CSAT (1-4), NULL para NPS y FCR',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `niveles_calificacion_id` (`niveles_calificacion_id`),
  KEY `idx_tipo_pregunta` (`tipo_pregunta`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.preguntas: ~8 rows (aproximadamente)
INSERT INTO `preguntas` (`id`, `pregunta`, `descripcion`, `tipo`, `tipo_pregunta`, `niveles_calificacion_id`, `is_active`, `created_at`, `updated_at`) VALUES
	(3, 'En una escala del 0 al 10, ¿qué tan probable es que recomiende UNIFRANZ a un amigo o\ncolega?', NULL, 'indicador_0_10', 'nps', NULL, 1, '2025-10-28 06:58:59', '2025-10-29 22:41:16'),
	(4, '¿Qué podríamos haber hecho de manera diferente para que su experiencia con la atención\nfuera satisfactoria?', NULL, 'opcion_unica_texto_libre', 'csat', 1, 1, '2025-10-28 07:34:59', '2025-10-29 21:36:42'),
	(5, '¿Qué podríamos haber hecho de manera diferente para que su experiencia con la atención fuera satisfactoria?', NULL, 'opcion_unica_texto_libre', 'csat', 2, 1, '2025-10-28 07:50:08', '2025-10-28 07:50:08'),
	(6, '¿Qué fue lo que más disfrutó o valoró?', NULL, 'opcion_unica_texto_libre', 'csat', 3, 1, '2025-10-28 07:51:12', '2025-10-28 07:51:12'),
	(8, '¿Se resolvió completamente su consulta o problema durante esta primera\ninteracción?', NULL, 'opcion_unica', 'fcr', NULL, 1, '2025-10-29 08:56:06', '2025-10-30 01:20:42'),
	(9, '¿Qué fue lo que más disfrutó o valoró?', NULL, 'opcion_unica_texto_libre', 'csat', 4, 1, '2025-10-29 21:45:21', '2025-11-04 23:20:25'),
	(10, 'En una escala del 0 al 25, ¿qué tan probable es que recomiende UNIFRANZ a un amigo o\ncolega?', NULL, 'indicador_0_10', 'nps', NULL, 1, '2025-10-28 06:58:59', '2025-11-17 18:14:06'),
	(11, '¿Qué podríamos haber hecho de manera diferente?', NULL, 'opcion_unica_texto_libre', 'csat', 1, 1, '2025-10-28 07:34:59', '2025-11-17 14:01:03');

-- Volcando estructura para tabla calificador_unifranz.respuestas_calificacion
CREATE TABLE IF NOT EXISTS `respuestas_calificacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `calificacion_id` int NOT NULL,
  `pregunta_id` int DEFAULT NULL COMMENT 'ID de la pregunta principal (CSAT nivel, NPS, FCR)',
  `opcion_seleccionada_id` int DEFAULT NULL COMMENT 'ID de la opción seleccionada (para opcion_unica)',
  `opciones_seleccionadas` json DEFAULT NULL COMMENT 'Array de IDs (para opcion_multiple)',
  `respuesta_texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Para texto_libre o texto de opcion_unica_texto_libre',
  `valor_indicador` int DEFAULT NULL COMMENT 'Para indicador_0_10 (score NPS)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calificacion_id` (`calificacion_id`),
  KEY `pregunta_id` (`pregunta_id`),
  KEY `opcion_seleccionada_id` (`opcion_seleccionada_id`),
  CONSTRAINT `respuestas_calificacion_ibfk_1` FOREIGN KEY (`calificacion_id`) REFERENCES `calificaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `respuestas_calificacion_ibfk_2` FOREIGN KEY (`pregunta_id`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `respuestas_calificacion_ibfk_3` FOREIGN KEY (`opcion_seleccionada_id`) REFERENCES `opciones_pregunta` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.respuestas_calificacion: ~2 rows (aproximadamente)
INSERT INTO `respuestas_calificacion` (`id`, `calificacion_id`, `pregunta_id`, `opcion_seleccionada_id`, `opciones_seleccionadas`, `respuesta_texto`, `valor_indicador`, `created_at`, `updated_at`) VALUES
	(1, 1, 11, 113, NULL, NULL, NULL, '2025-11-17 18:02:38', '2025-11-17 18:02:38'),
	(2, 2, 5, 83, NULL, NULL, NULL, '2025-11-17 18:03:26', '2025-11-17 18:03:26'),
	(3, 3, 11, 113, NULL, NULL, NULL, '2025-11-17 18:14:19', '2025-11-17 18:14:19'),
	(4, 4, 10, NULL, NULL, '7', NULL, '2025-11-17 18:14:30', '2025-11-17 18:14:30'),
	(5, 5, 8, 95, NULL, NULL, NULL, '2025-11-17 18:14:33', '2025-11-17 18:14:33');

-- Volcando estructura para tabla calificador_unifranz.respuestas_subpreguntas
CREATE TABLE IF NOT EXISTS `respuestas_subpreguntas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `calificacion_id` int NOT NULL,
  `subpregunta_id` int NOT NULL,
  `opcion_seleccionada` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Texto de la opción seleccionada (para opcion_unica)',
  `opciones_seleccionadas` json DEFAULT NULL COMMENT 'Array de textos (para opcion_multiple)',
  `texto_respuesta` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Para texto_libre',
  `valor_indicador` int DEFAULT NULL COMMENT 'Para indicador_0_10',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calificacion_id` (`calificacion_id`),
  KEY `subpregunta_id` (`subpregunta_id`),
  CONSTRAINT `respuestas_subpreguntas_ibfk_1` FOREIGN KEY (`calificacion_id`) REFERENCES `calificaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `respuestas_subpreguntas_ibfk_2` FOREIGN KEY (`subpregunta_id`) REFERENCES `subpreguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.respuestas_subpreguntas: ~0 rows (aproximadamente)
INSERT INTO `respuestas_subpreguntas` (`id`, `calificacion_id`, `subpregunta_id`, `opcion_seleccionada`, `opciones_seleccionadas`, `texto_respuesta`, `valor_indicador`, `created_at`, `updated_at`) VALUES
	(1, 4, 29, 'Si', NULL, 'Si', NULL, '2025-11-17 18:14:30', '2025-11-17 18:14:30');

-- Volcando estructura para tabla calificador_unifranz.sedes
CREATE TABLE IF NOT EXISTS `sedes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.sedes: ~4 rows (aproximadamente)
INSERT INTO `sedes` (`id`, `nombre`, `lat`, `lng`, `created_at`, `updated_at`) VALUES
	(1, 'La Paz', -16.48970000, -68.11930000, '2025-09-27 03:44:45', '2025-10-30 13:37:33'),
	(2, 'El Alto', -16.50400000, -68.16340000, '2025-09-27 03:44:45', '2025-10-06 16:24:22'),
	(3, 'Santa Cruz', -17.78330000, -63.18210000, '2025-09-27 03:44:45', '2025-10-06 16:24:22'),
	(4, 'Cochabamba', -17.38950000, -66.15680000, '2025-09-27 03:44:45', '2025-10-06 16:24:22');

-- Volcando estructura para tabla calificador_unifranz.subpreguntas
CREATE TABLE IF NOT EXISTS `subpreguntas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `opcion_pregunta_id` int DEFAULT NULL COMMENT 'Subpregunta de una opción específica (ej: FCR "No")',
  `pregunta_id` int DEFAULT NULL COMMENT 'Subpregunta directa de una pregunta (futuro uso)',
  `pregunta_texto` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('opcion_unica','opcion_multiple','texto_libre','indicador_0_10','opcion_unica_texto_libre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opciones` json DEFAULT NULL COMMENT 'Array de opciones para tipos opcion_unica/multiple',
  `es_rango_indicador` tinyint(1) DEFAULT '0' COMMENT 'TRUE = pregunta de rango para NPS',
  `rango_min` int DEFAULT NULL COMMENT 'Valor mínimo del rango (solo para es_rango_indicador)',
  `rango_max` int DEFAULT NULL COMMENT 'Valor máximo del rango (solo para es_rango_indicador)',
  `pregunta_indicador_id` int DEFAULT NULL COMMENT 'Para preguntas de rango NPS (vinculada a pregunta NPS principal)',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opcion_pregunta_id` (`opcion_pregunta_id`),
  KEY `fk_subpregunta_indicador` (`pregunta_indicador_id`),
  KEY `idx_rango` (`es_rango_indicador`,`rango_min`,`rango_max`),
  CONSTRAINT `fk_subpregunta_indicador` FOREIGN KEY (`pregunta_indicador_id`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subpreguntas_ibfk_1` FOREIGN KEY (`opcion_pregunta_id`) REFERENCES `opciones_pregunta` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.subpreguntas: ~4 rows (aproximadamente)
INSERT INTO `subpreguntas` (`id`, `opcion_pregunta_id`, `pregunta_id`, `pregunta_texto`, `tipo`, `opciones`, `es_rango_indicador`, `rango_min`, `rango_max`, `pregunta_indicador_id`, `is_active`, `created_at`, `updated_at`) VALUES
	(17, NULL, NULL, '¿Podría decirnos el motivo principal por el que calificó su experiencia de esta manera?', 'opcion_unica_texto_libre', '"[\\"No me dieron soluci\\\\u00f3n\\",\\"Trato poco amable por parte del personal\\",\\"Demora en la atenci\\\\u00f3n\\",\\"Otro - especifique\\"]"', 1, 0, 6, 3, 1, '2025-10-28 06:58:59', '2025-10-29 22:30:33'),
	(21, NULL, NULL, '¿Qué podríamos haber hecho para que su experiencia fuera mejor?', 'texto_libre', NULL, 1, 7, 8, 3, 1, '2025-10-29 22:30:33', '2025-10-29 22:30:33'),
	(22, NULL, NULL, '¿Qué fue lo que más le gustó de su experiencia con nosotros?', 'opcion_multiple', '"[\\"Respuestas eficientes\\",\\"Trato amable\\",\\"Comunicaci\\\\u00f3n clara y precisa\\"]"', 1, 9, 10, 3, 1, '2025-10-29 22:33:08', '2025-10-29 22:41:16'),
	(28, 96, NULL, '¿Cuál fue el motivo principal por el que su consulta o problema no se resolvió en esta\ninteracción?', 'opcion_unica_texto_libre', '"[\\"Faltaba informaci\\\\u00f3n\\",\\"Me derivaron a otra \\\\u00e1rea\\",\\"Me pidieron volver\\",\\"Otro - especifique\\"]"', 0, NULL, NULL, NULL, 1, '2025-10-30 01:20:42', '2025-10-30 01:20:42'),
	(29, NULL, NULL, 'Hola', 'opcion_unica', '"[\\"Si\\",\\"No\\"]"', 1, 0, 10, 10, 1, '2025-11-17 18:14:06', '2025-11-17 18:14:06');

-- Volcando estructura para tabla calificador_unifranz.tipos_calificacion
CREATE TABLE IF NOT EXISTS `tipos_calificacion` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipos_calificacion_codigo_unique` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.tipos_calificacion: ~3 rows (aproximadamente)
INSERT INTO `tipos_calificacion` (`id`, `nombre`, `codigo`, `descripcion`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'CSAT', 'csat', 'Customer Satisfaction - Satisfacción del cliente con caritas (emojis)', 1, '2025-10-27 21:39:06', '2025-10-27 21:39:06'),
	(2, 'NPS', 'nps', 'Net Promoter Score - Probabilidad de recomendación (escala 0-10)', 1, '2025-10-27 21:39:06', '2025-10-27 21:39:06'),
	(3, 'FCR', 'fcr', 'First Contact Resolution - Resolución en primera interacción (manitas)', 1, '2025-10-27 21:39:06', '2025-10-27 21:39:06');

-- Volcando estructura para tabla calificador_unifranz.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `avatar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `role` enum('admin','gestor','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `sede_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `sede_id` (`sede_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla calificador_unifranz.users: ~3 rows (aproximadamente)
INSERT INTO `users` (`id`, `google_id`, `name`, `email`, `email_verified_at`, `avatar`, `role`, `sede_id`, `created_at`, `updated_at`) VALUES
	(1, '110437457232187697644', 'Orlando Gutierrez', 'orlando.gutierrez@unifranz.edu.bo', '2025-10-30 01:45:27', 'https://lh3.googleusercontent.com/a/ACg8ocJglMFVkrHDZgn2F-ovn7dFsMSDdVHY9qXgfGmbgj8m3iVt5Q=s96-c', 'admin', 1, '2025-09-28 06:32:19', '2025-10-30 01:45:27'),
	(10, '107593533000826940176', 'Soporte-LP', 'soportedocente-lp@unifranz.edu.bo', '2025-10-30 01:45:37', 'https://lh3.googleusercontent.com/a-/ALV-UjUzXCyxnO2AjmTBq89KIuWSVHF5QxqQqZNtHuqdlhmOYjmAcdRJ_iz77IiI0Yib550xPq9rkig8aWaiAVyIf3YYwQR82s8jM8e04_gV4PGn8U8rMAgvnNfT9GbPVAq9ToZaXlN7ZxiOgUPGJMW3GGDLd7iAZmSIzjVH0zrSJ5UrgHz2MfEA7SEsC6XMc6ZjLNFzuhtc2L-6vxy02FGlu7exle2ddwGvvxGTeWwnOx0c2_pZ36u4v-T6IVjqJSqzty8B68lE86lGEdrhoifbaKz_dL625lFGMomdDmIXclRel3e44llMKfreXuHe2yWaL4ts3wkAfApjxCiCenBIUNAfvWoZMtsS_CwXCA8okFjuq7k31owWHhSxBIHr996MQDG8cQIR55UeuaBKqTX2TXsBGWdEIGvdWSlaMAdPd78WeSWZ6k8tyT-XQKzY7sOPZdTcXM_Ce7f5f5BT1T_YxtzdhGdezrzD5bRxHVwYZREC6RBE3KsNwBn98FaRJfoEvYTIh0QUF9Qbidxr_HzNZsrsxIR2Hk6X5pbjaUp6K1ikYU5I-nVZAo1n83i87XJ5B-5vIW9LY_bBGgO4VvS6xlEFnGLUvt_Uyv-ObuoKmX3mMPcCDVJnB6692Ko7-fAQ6VUyNOFNepnjNeTRU90qWdRXo1XuO3jo7V3uqRPqwstEmrEkrIEXnPopOrQQsNxGTxpvLorm6fRsOLePTE9reOM3K5iU3z8iBitg2pWn5sOrCwkGYBKFeo3iqYo9EkcfU0diF9W5pB-5YgbD-unb210y3Ixa3DXGaZMph-fgu6mxS9Xyoy2oAdi9EO8qlu_CwBx-mnZdDAB4m1xJWVBl3cjeYBueVg0r_fS7dpfvDop4W2om5SfC9ci9taJSitn6IK_-JO73VoIBJS71lex7p9CEt8MUKIzqajIF6PHgj5rmFizvZXVcFm1JIOkOqbpA7eLcdP58ac3p-deS8Cf5fFjdKsCKHj1s_mbUFWzAHj6PSbbMrEeGcRhLk5TP87f9d-PfanIb4WjaMi0dJb9wyFWpma2z8JU5Vzhu6WkjASNFrRVWBsrNDw=s96-c', 'admin', 1, '2025-10-15 22:49:48', '2025-10-30 01:45:37'),
	(11, NULL, 'Abraham Simpson Quiroga Manuel', 'abraham.quiroga@unifranz.edu.bo', '2025-10-30 01:45:46', NULL, 'admin', 1, '2025-10-16 23:03:53', '2025-10-30 01:45:46');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
