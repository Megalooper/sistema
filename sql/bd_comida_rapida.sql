-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-07-2025 a las 04:25:48
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
-- Base de datos: `bd_comida_rapida`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id_area` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Barra, Cocina, etc.',
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `areas`
--

INSERT INTO `areas` (`id_area`, `nombre`, `fecha_registro`) VALUES
(1, 'Barra', '2025-07-18 14:52:44'),
(2, 'Cocina', '2025-07-18 14:52:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `id_area` int(11) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nombre`, `id_area`, `fecha_registro`) VALUES
(1, 'Taqueria', 2, '2025-07-18 14:29:47'),
(2, 'Entrada', 2, '2025-07-18 14:30:02'),
(3, 'Batidos', 1, '2025-07-18 14:30:11'),
(4, 'Merengadas', 1, '2025-07-18 14:30:21'),
(5, 'Pizzeria', 2, '2025-07-18 14:30:36'),
(6, 'Burguer', 2, '2025-07-18 14:30:46'),
(7, 'Postres', 1, '2025-07-18 14:30:57'),
(8, 'Bebidas', 1, '2025-07-18 14:31:07'),
(9, 'Aguas Frescas', 1, '2025-07-18 14:31:33'),
(10, 'Cafe', 1, '2025-07-18 14:31:42'),
(11, 'Mockteles', 1, '2025-07-18 14:36:37'),
(12, 'Desayunos', 2, '2025-07-18 14:37:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deliverys`
--

CREATE TABLE `deliverys` (
  `id_delivery` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `telefono_cliente` varchar(20) DEFAULT NULL,
  `costo_usd` decimal(10,2) NOT NULL,
  `costo_bs` decimal(10,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

CREATE TABLE `detalles_pedido` (
  `id_detalle` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL,
  `id_preparacion` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal_usd` decimal(10,2) NOT NULL,
  `subtotal_bs` decimal(10,2) NOT NULL,
  `tipo` enum('producto','preparacion') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_pedido`
--

INSERT INTO `detalles_pedido` (`id_detalle`, `id_pedido`, `id_producto`, `id_preparacion`, `cantidad`, `precio_unitario`, `subtotal_usd`, `subtotal_bs`, `tipo`) VALUES
(2, 1, 15, NULL, 1, 1.00, 1.00, 118.28, 'producto'),
(3, 1, 3, NULL, 1, 1.00, 1.00, 118.28, 'preparacion'),
(4, 1, 11, NULL, 1, 1.00, 1.00, 118.28, 'preparacion'),
(6, 2, 11, NULL, 2, 1.00, 2.00, 236.56, 'producto'),
(7, 2, 3, NULL, 1, 1.00, 1.00, 118.28, 'preparacion'),
(8, 1, NULL, 14, 3, 10.00, 10.00, 120.00, 'preparacion'),
(12, 3, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(13, 3, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(14, 3, 15, NULL, 1, 1.00, 1.00, 118.28, 'producto'),
(17, 4, NULL, 11, 2, 2.50, 5.00, 591.40, 'preparacion'),
(18, 4, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(19, 5, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(20, 5, NULL, 2, 1, 4.00, 4.00, 473.12, 'preparacion'),
(21, 6, NULL, 3, 2, 5.00, 10.00, 1182.80, 'preparacion'),
(22, 7, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(23, 7, NULL, 3, 2, 5.00, 10.00, 1182.80, 'preparacion'),
(24, 7, NULL, 2, 1, 4.00, 4.00, 473.12, 'preparacion'),
(25, 8, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(26, 8, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(31, 9, 15, NULL, 1, 1.00, 1.00, 118.28, 'producto'),
(32, 9, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(33, 10, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(38, 11, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(39, 11, NULL, 2, 1, 4.00, 4.00, 473.12, 'preparacion'),
(40, 12, 15, NULL, 1, 1.00, 1.00, 118.28, 'producto'),
(41, 12, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(43, 13, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(44, 14, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(45, 14, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(55, 15, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(57, 16, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(58, 16, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(59, 16, NULL, 11, 2, 2.50, 5.00, 591.40, 'preparacion'),
(60, 17, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(61, 17, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(62, 18, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(63, 18, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(64, 18, NULL, 2, 1, 4.00, 4.00, 473.12, 'preparacion'),
(65, 19, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(66, 20, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(67, 21, NULL, 3, 1, 5.00, 5.00, 591.40, 'preparacion'),
(68, 21, NULL, 2, 1, 4.00, 4.00, 473.12, 'preparacion'),
(69, 21, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(70, 22, NULL, 11, 1, 2.50, 2.50, 295.70, 'preparacion'),
(71, 23, NULL, 11, 1, 2.50, 2.50, 307.10, 'preparacion'),
(72, 24, NULL, 11, 1, 2.50, 2.50, 307.10, 'preparacion');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_venta`
--

CREATE TABLE `detalles_venta` (
  `id_detalle` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) DEFAULT NULL COMMENT 'Producto simple',
  `id_preparacion` int(11) DEFAULT NULL COMMENT 'Preparación/plato',
  `cantidad` int(11) NOT NULL,
  `precio_unitario_usd` decimal(10,2) NOT NULL,
  `precio_unitario_bs` decimal(10,2) NOT NULL,
  `subtotal_usd` decimal(10,2) NOT NULL,
  `subtotal_bs` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalles_venta`
--

INSERT INTO `detalles_venta` (`id_detalle`, `id_venta`, `id_producto`, `id_preparacion`, `cantidad`, `precio_unitario_usd`, `precio_unitario_bs`, `subtotal_usd`, `subtotal_bs`) VALUES
(23, 6, 16, NULL, 1, 1.00, 118.28, 1.00, 118.28);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `deudas_delivery`
--

CREATE TABLE `deudas_delivery` (
  `id_deuda` int(11) NOT NULL,
  `nombre_repartidor` varchar(100) DEFAULT 'Un Delivery Mas',
  `mes` int(11) NOT NULL CHECK (`mes` >= 1 and `mes` <= 12),
  `anio` int(11) NOT NULL,
  `monto_bs` decimal(10,2) NOT NULL,
  `monto_usd` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagado') DEFAULT 'pendiente',
  `fecha_pago` datetime DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `deudas_delivery`
--

INSERT INTO `deudas_delivery` (`id_deuda`, `nombre_repartidor`, `mes`, `anio`, `monto_bs`, `monto_usd`, `estado`, `fecha_pago`, `notas`, `fecha_registro`) VALUES
(1, 'Repartidores Delivery', 7, 2025, 196.54, 1.60, 'pagado', '2025-07-29 22:00:50', 'Acumulado de deliverys del mes', '2025-07-29 21:52:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `impresoras`
--

CREATE TABLE `impresoras` (
  `id_impresora` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `direccion_ip` varchar(15) NOT NULL,
  `puerto` int(11) NOT NULL DEFAULT 9100
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_movimientos`
--

CREATE TABLE `inventario_movimientos` (
  `id_movimiento` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `tipo` enum('compra','produccion','merma') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fecha_movimiento` datetime DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `inventario_movimientos`
--

INSERT INTO `inventario_movimientos` (`id_movimiento`, `id_producto`, `tipo`, `cantidad`, `fecha_movimiento`, `id_usuario`, `descripcion`) VALUES
(7, 16, 'compra', 24, '2025-07-19 17:04:25', 0, ''),
(8, 1, 'compra', 5, '2025-07-19 18:42:23', 0, ''),
(9, 2, 'produccion', 5, '2025-07-19 18:42:31', 0, ''),
(10, 3, 'compra', 5, '2025-07-19 18:42:36', 0, ''),
(11, 10, 'compra', 3, '2025-07-29 21:31:02', 1, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `id_nota` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `tipo` enum('texto','tabla') NOT NULL DEFAULT 'texto',
  `contenido` text DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `notas`
--

INSERT INTO `notas` (`id_nota`, `titulo`, `tipo`, `contenido`, `id_usuario`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Pagos a proveedores', 'tabla', '[]', 1, '2025-07-29 22:08:30', '2025-07-29 22:09:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id_pago` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `metodo_pago` enum('efectivo','pago_movil','zelle','tarjeta_debito','transferencia','otro') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL COMMENT 'Ej: número de Pago Móvil, correo de Zelle, etc',
  `monto_bs` decimal(10,2) NOT NULL,
  `fecha_pago` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id_pago`, `id_pedido`, `metodo_pago`, `referencia`, `monto_bs`, `fecha_pago`) VALUES
(1, 6, 'pago_movil', '1234', 1182.80, '2025-07-26 16:06:31'),
(2, 10, 'pago_movil', '1234', 591.40, '2025-07-26 19:19:21'),
(3, 11, '', NULL, 768.82, '2025-07-27 14:32:53'),
(4, 12, 'efectivo', NULL, 1064.52, '2025-07-27 14:42:54'),
(5, 13, 'efectivo', NULL, 1064.52, '2025-07-27 15:01:36'),
(6, 18, 'efectivo', NULL, 1537.64, '2025-07-27 18:30:50'),
(7, 19, 'efectivo', NULL, 1005.38, '2025-07-27 18:40:05'),
(8, 20, 'efectivo', NULL, 295.70, '2025-07-29 16:10:37'),
(9, 21, 'pago_movil', NULL, 1596.78, '2025-07-29 16:12:25'),
(10, 24, 'efectivo', NULL, 552.78, '2025-07-29 21:52:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `numero_mesa` varchar(20) DEFAULT NULL COMMENT 'Ej: Mesa 5, Delivery #123',
  `tipo_pedido` enum('mesa','delivery') NOT NULL DEFAULT 'mesa',
  `direccion_delivery` text DEFAULT NULL,
  `telefono_cliente` varchar(20) DEFAULT NULL,
  `estado` enum('abierto','en espera','cerrado','cancelado') DEFAULT 'abierto',
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `propina_bs` decimal(10,2) DEFAULT NULL COMMENT 'Propina opcional en Bs',
  `total_usd` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_bs` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_turno`, `id_usuario`, `numero_mesa`, `tipo_pedido`, `direccion_delivery`, `telefono_cliente`, `estado`, `fecha_apertura`, `fecha_cierre`, `propina_bs`, `total_usd`, `total_bs`) VALUES
(1, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-25 20:10:45', NULL, NULL, 3.00, 354.84),
(2, 10, 1, '', '', '', '', 'cancelado', '2025-07-26 14:13:12', NULL, NULL, 3.00, 354.84),
(3, 10, 1, 'mesa 3', 'mesa', '', '', 'cancelado', '2025-07-26 14:27:51', NULL, NULL, 8.50, 1005.38),
(4, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-26 14:55:45', NULL, NULL, 10.00, 1182.80),
(5, 10, 1, 'M-01', 'mesa', '', '', 'cancelado', '2025-07-26 15:05:40', NULL, NULL, 6.50, 768.82),
(6, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-26 15:39:04', '2025-07-26 16:06:31', NULL, 10.00, 1182.80),
(7, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-26 18:02:49', NULL, NULL, 16.50, 1951.62),
(8, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-26 18:24:03', NULL, NULL, 7.50, 887.10),
(9, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-26 18:44:13', NULL, NULL, 6.00, 709.68),
(10, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-26 19:18:53', '2025-07-26 19:19:21', NULL, 5.00, 591.40),
(11, 9, 1, '', '', '', '', 'cerrado', '2025-07-27 13:24:07', '2025-07-27 14:32:53', NULL, 6.50, 768.82),
(12, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-27 14:39:45', '2025-07-27 14:42:54', NULL, 6.00, 709.68),
(13, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-27 15:01:00', '2025-07-27 15:01:36', NULL, 5.00, 591.40),
(14, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-27 15:43:49', NULL, NULL, 7.50, 887.10),
(15, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-27 16:31:19', NULL, NULL, 5.00, 591.40),
(16, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-27 18:16:06', NULL, NULL, 10.00, 1182.80),
(17, 10, 1, 'Mesa 1', 'mesa', '', '', 'cancelado', '2025-07-27 18:21:27', NULL, NULL, 7.50, 887.10),
(18, 10, 1, 'mesa 3', 'mesa', '', '', 'cerrado', '2025-07-27 18:29:41', '2025-07-27 18:30:50', NULL, 9.00, 1064.52),
(19, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-27 18:39:32', '2025-07-27 18:40:05', NULL, 2.50, 295.70),
(20, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-27 18:45:12', '2025-07-29 16:10:37', NULL, 2.50, 295.70),
(21, 10, 1, 'Mesa 1', 'mesa', '', '', 'cerrado', '2025-07-29 16:11:34', '2025-07-29 16:12:25', NULL, 13.50, 1596.78),
(22, 10, 1, '', 'delivery', 'asd', '0412-4943302', 'en espera', '2025-07-29 16:13:07', NULL, NULL, 2.50, 295.70),
(23, 10, 1, 'Mesa 1', 'mesa', '', '', 'en espera', '2025-07-29 16:36:33', NULL, NULL, 2.50, 307.10),
(24, 10, 1, '', 'delivery', 'ASD', '0412-4943302', 'cerrado', '2025-07-29 21:52:15', '2025-07-29 21:52:38', NULL, 4.50, 552.78);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preparaciones`
--

CREATE TABLE `preparaciones` (
  `id_preparacion` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_usd` decimal(10,2) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preparaciones`
--

INSERT INTO `preparaciones` (`id_preparacion`, `nombre`, `descripcion`, `precio_usd`, `id_categoria`, `imagen`, `fecha_registro`) VALUES
(2, 'Hamburguesa Clasica', 'Pan de autor - Carne - Queso Americano - Lechuga - Tomate - Salsa S', 4.00, 6, NULL, '2025-07-18 16:16:04'),
(3, 'Cheese Burguer', 'Pan de autor - Carne - Queso Americano - Tocineta - Salsa Burguer', 5.00, 6, NULL, '2025-07-18 16:18:23'),
(9, 'Melon', 'Batido de melon', 2.00, 3, NULL, '2025-07-19 15:47:20'),
(10, 'Piña', 'Batido de piña', 2.00, 3, NULL, '2025-07-19 15:47:33'),
(11, 'Fresa', 'Batido de fresa', 2.50, 3, NULL, '2025-07-19 15:48:25'),
(12, 'Fresa Mora', 'Batido de fresa mora', 2.50, 3, NULL, '2025-07-19 15:48:44'),
(13, 'Limon', 'Batido de limon con hierbabuena', 2.50, 3, NULL, '2025-07-19 15:49:08'),
(14, 'Limon Hierbabuena', 'Batido de limon con hierbabuena', 2.50, 3, NULL, '2025-07-19 15:49:32'),
(15, 'Fresa', 'Merengada de fresa', 3.00, 4, NULL, '2025-07-19 15:51:38'),
(16, 'Fresa Mora', 'Merengada de fresa mora', 3.00, 4, NULL, '2025-07-19 15:51:59'),
(17, 'Melon', 'Merengada de melon', 3.00, 4, NULL, '2025-07-19 15:52:14'),
(18, 'Piña', 'Merengada de piña', 3.00, 4, NULL, '2025-07-19 15:52:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_usd` decimal(10,2) NOT NULL,
  `es_ingrediente` tinyint(1) DEFAULT 0 COMMENT '1=Es materia prima',
  `visible_venta` tinyint(1) DEFAULT 1 COMMENT '1=Visible en ventas',
  `stock` int(11) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `codigo`, `id_categoria`, `nombre`, `descripcion`, `precio_usd`, `es_ingrediente`, `visible_venta`, `stock`, `imagen`, `fecha_registro`) VALUES
(1, 'CH-100', 6, 'Carne Hamburguesa 100gr', 'Carne de hamburguesas de 100 gramos', 1.50, 1, 0, -1, NULL, '2025-07-18 14:51:28'),
(2, 'PAN-BG', 6, 'Pan Burguer', 'Pan de hamburguesa', 1.50, 1, 0, -1, NULL, '2025-07-18 14:52:56'),
(3, 'FCL', 6, 'Facilistas', 'Facilistas de queso para hamburguesas', 1.00, 1, 0, -1, NULL, '2025-07-18 14:55:16'),
(5, 'FRU-FR', 3, 'Fresa', 'Fresa para batidos', 1.00, 1, 0, 23, NULL, '2025-07-19 15:29:12'),
(6, 'FRU-ME', 3, 'Melon', 'Melon para batidos', 1.00, 1, 0, 74, NULL, '2025-07-19 15:30:59'),
(7, 'FRU-LH', 3, 'Limon Hierbabuena', 'Limon con hierbabuena para batidos', 1.00, 1, 0, 117, NULL, '2025-07-19 15:32:24'),
(8, 'FRU-PICOL', 3, 'Piña Colada', 'Porcion de piña colada', 1.00, 1, 0, 24, NULL, '2025-07-19 15:33:20'),
(9, 'FRU-LM', 3, 'Limon', 'Limon para batidos', 1.00, 1, 0, 13, NULL, '2025-07-19 15:34:00'),
(10, 'FRU-FM', 9, 'Fresa Mora', 'Fresa Mora para batidos', 1.00, 1, 0, 4, NULL, '2025-07-19 15:34:49'),
(11, 'FRU-PI', 3, 'Piña', 'Piña para batidos', 1.00, 1, 0, 8, NULL, '2025-07-19 15:35:22'),
(12, 'FRU-MNG', 3, 'Mango', 'Mango para mangonada', 1.00, 1, 0, 132, NULL, '2025-07-19 15:35:55'),
(13, 'GOL-LT', 8, 'Golden Lata', 'refresco golden de lata', 1.50, 0, 1, 10, NULL, '2025-07-19 16:12:00'),
(14, 'GOL-BOT', 8, 'Golden 350ml', 'refresco golden de botella', 1.00, 0, 1, 23, NULL, '2025-07-19 16:12:44'),
(15, '7UP-BOT', 8, '7UP 350ml', 'refresco 7up de botella', 1.00, 0, 1, 4, NULL, '2025-07-19 16:13:41'),
(16, 'PEP-BOT', 8, 'Pepsi 350ml', 'refresco pepsi de botella', 1.00, 0, 1, 34, NULL, '2025-07-19 16:14:35'),
(17, '7UP-LT', 8, '7UP Litro', 'refresco 7up de litro', 2.00, 0, 1, 10, NULL, '2025-07-19 16:16:13'),
(18, 'PEP-LT', 8, 'Pepsi Litro', 'refresco pepsi de litro', 2.00, 0, 1, 10, NULL, '2025-07-19 16:18:19'),
(19, 'PEP-LM', 8, 'Pepsi Litro y medio', 'refresco pepsi de litro y medio', 2.50, 0, 1, 4, NULL, '2025-07-19 16:19:18'),
(20, 'PEP-2LT', 8, 'Pepsi 2 Litros', 'refresco pepsi de dos litros', 3.00, 0, 1, 6, NULL, '2025-07-19 16:20:29'),
(21, '7UP-LM', 8, '7UP Litro y medio', 'refresco 7up de litro y medio', 2.50, 0, 1, 4, NULL, '2025-07-19 16:21:27'),
(22, 'MAL-BOT', 8, 'Malta 350ml', 'malta de botella', 1.30, 0, 1, 1, NULL, '2025-07-19 16:22:38'),
(23, 'YUK-BOT', 8, 'Yukery 250ml', 'jugo yukery de botella', 1.50, 0, 1, 10, NULL, '2025-07-19 16:23:57'),
(24, 'AG-600', 8, 'Agua 600ml', 'Agua mineral minalba mediana', 1.50, 0, 1, 13, NULL, '2025-07-19 16:25:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propinas`
--

CREATE TABLE `propinas` (
  `id_propina` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `monto_bs` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','pago_movil','zelle','tarjeta_debito','transferencia','otro') NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;

--
-- Volcado de datos para la tabla `propinas`
--

INSERT INTO `propinas` (`id_propina`, `id_pedido`, `id_turno`, `monto_bs`, `metodo_pago`, `referencia`, `fecha`) VALUES
(1, 6, 10, 100.00, 'pago_movil', '2345', '2025-07-26'),
(2, 21, 10, 236.56, 'efectivo', 'Propina del pedido', '2025-07-29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id_receta` int(11) NOT NULL,
  `id_preparacion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `recetas`
--

INSERT INTO `recetas` (`id_receta`, `id_preparacion`, `id_producto`, `cantidad`, `fecha_registro`) VALUES
(4, 3, 1, 1.00, '2025-07-18 16:21:29'),
(5, 3, 3, 1.00, '2025-07-18 16:21:36'),
(6, 3, 2, 1.00, '2025-07-18 16:21:40'),
(7, 2, 1, 1.00, '2025-07-19 02:38:28'),
(8, 2, 3, 1.00, '2025-07-19 02:38:33'),
(9, 2, 2, 1.00, '2025-07-19 02:38:37'),
(10, 11, 5, 1.00, '2025-07-19 15:49:45'),
(11, 12, 10, 1.00, '2025-07-19 15:49:58'),
(12, 13, 9, 1.00, '2025-07-19 15:50:15'),
(13, 14, 7, 1.00, '2025-07-19 15:50:24'),
(14, 9, 6, 1.00, '2025-07-19 15:50:34'),
(15, 10, 11, 1.00, '2025-07-19 15:50:52'),
(16, 15, 5, 1.00, '2025-07-19 15:52:44'),
(17, 16, 10, 1.00, '2025-07-19 15:52:53'),
(18, 17, 6, 1.00, '2025-07-19 15:53:12'),
(19, 18, 11, 1.00, '2025-07-19 15:53:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasas_cambio`
--

CREATE TABLE `tasas_cambio` (
  `id_tasa` int(11) NOT NULL,
  `valor_dolar` decimal(10,2) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tasas_cambio`
--

INSERT INTO `tasas_cambio` (`id_tasa`, `valor_dolar`, `fecha_registro`) VALUES
(0, 122.84, '2025-07-29 16:14:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id_turno` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Mañana, Tarde',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id_turno`, `nombre`, `hora_inicio`, `hora_fin`, `activo`, `fecha_registro`) VALUES
(9, 'Mañana', '06:00:00', '14:00:00', 1, '2025-07-18 17:51:14'),
(10, 'Tarde', '14:01:00', '22:00:00', 1, '2025-07-18 17:51:14'),
(11, 'Noche', '22:01:00', '05:59:00', 1, '2025-07-18 17:51:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','cocina','barra') DEFAULT 'admin',
  `nombre_empresa` varchar(255) DEFAULT NULL,
  `rif` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL COMMENT 'Ruta del logo',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `impresora_cocina` varchar(255) DEFAULT NULL,
  `impresora_barra` varchar(255) DEFAULT NULL,
  `ip_cocina` varchar(100) DEFAULT NULL,
  `ip_barra` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nombre`, `correo`, `contrasena`, `rol`, `nombre_empresa`, `rif`, `direccion`, `telefono`, `logo`, `fecha_registro`, `impresora_cocina`, `impresora_barra`, `ip_cocina`, `ip_barra`) VALUES
(1, 'Erwin Mujica', 'erwin.ricardo08@gmail.com', '$2y$10$zo0VdEtLoykHHlDkJF8rl.oaQ3z8w7JHckXSxVPKR77Mb435iHfwe', 'admin', 'MAMACHULA Taqueria & Cantina', 'J-50447657-9', 'Calle 12, Entre Avenida 8 Y 9, Edificio Don Jorge, Planta Baja, Local 3, Sector Caja De Agua, San Felipe, Estado Yaracuy.', '04125682001', 'logo_empresa.png', '2025-07-18 12:21:50', '', 'XP-58', '192.168.0.140', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendedores`
--

CREATE TABLE `vendedores` (
  `id_vendedor` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_area` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_vendedor` int(11) NOT NULL,
  `total_usd` decimal(10,2) NOT NULL,
  `total_bs` decimal(10,2) NOT NULL,
  `fecha_venta` datetime NOT NULL,
  `id_turno` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_vendedor`, `total_usd`, `total_bs`, `fecha_venta`, `id_turno`) VALUES
(6, 0, 1.00, 118.28, '2025-07-19 17:23:04', 10);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id_area`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `id_area` (`id_area`);

--
-- Indices de la tabla `deliverys`
--
ALTER TABLE `deliverys`
  ADD PRIMARY KEY (`id_delivery`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_preparacion` (`id_preparacion`);

--
-- Indices de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_preparacion` (`id_preparacion`);

--
-- Indices de la tabla `deudas_delivery`
--
ALTER TABLE `deudas_delivery`
  ADD PRIMARY KEY (`id_deuda`),
  ADD UNIQUE KEY `unique_repartidor_mes` (`nombre_repartidor`,`mes`,`anio`);

--
-- Indices de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  ADD PRIMARY KEY (`id_impresora`);

--
-- Indices de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_turno` (`id_turno`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `preparaciones`
--
ALTER TABLE `preparaciones`
  ADD PRIMARY KEY (`id_preparacion`),
  ADD KEY `id_area` (`id_categoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `propinas`
--
ALTER TABLE `propinas`
  ADD PRIMARY KEY (`id_propina`),
  ADD KEY `id_pedido` (`id_pedido`),
  ADD KEY `id_turno` (`id_turno`),
  ADD KEY `idx_fecha_turno` (`fecha`,`id_turno`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id_receta`),
  ADD KEY `id_preparacion` (`id_preparacion`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id_turno`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_vendedor` (`id_vendedor`),
  ADD KEY `id_turno` (`id_turno`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `deliverys`
--
ALTER TABLE `deliverys`
  MODIFY `id_delivery` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `deudas_delivery`
--
ALTER TABLE `deudas_delivery`
  MODIFY `id_deuda` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `impresoras`
--
ALTER TABLE `impresoras`
  MODIFY `id_impresora` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `notas`
--
ALTER TABLE `notas`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `preparaciones`
--
ALTER TABLE `preparaciones`
  MODIFY `id_preparacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `propinas`
--
ALTER TABLE `propinas`
  MODIFY `id_propina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id_receta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`id_area`) REFERENCES `areas` (`id_area`);

--
-- Filtros para la tabla `deliverys`
--
ALTER TABLE `deliverys`
  ADD CONSTRAINT `deliverys_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE;

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  ADD CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `detalles_pedido_ibfk_3` FOREIGN KEY (`id_preparacion`) REFERENCES `preparaciones` (`id_preparacion`);

--
-- Filtros para la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD CONSTRAINT `detalles_venta_ibfk_1` FOREIGN KEY (`id_preparacion`) REFERENCES `preparaciones` (`id_preparacion`),
  ADD CONSTRAINT `detalles_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `inventario_movimientos`
--
ALTER TABLE `inventario_movimientos`
  ADD CONSTRAINT `inventario_movimientos_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`),
  ADD CONSTRAINT `inventario_movimientos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`),
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Filtros para la tabla `preparaciones`
--
ALTER TABLE `preparaciones`
  ADD CONSTRAINT `preparaciones_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);

--
-- Filtros para la tabla `propinas`
--
ALTER TABLE `propinas`
  ADD CONSTRAINT `propinas_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`),
  ADD CONSTRAINT `propinas_ibfk_2` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`);

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`id_preparacion`) REFERENCES `preparaciones` (`id_preparacion`),
  ADD CONSTRAINT `recetas_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_vendedor`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_turno`) REFERENCES `turnos` (`id_turno`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
