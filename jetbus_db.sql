-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-02-2026 a las 01:42:47
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
-- Base de datos: `jetbus_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `numero_maquina` varchar(10) NOT NULL,
  `patente` varchar(15) NOT NULL,
  `capacidad` int(11) NOT NULL DEFAULT 40,
  `estado` enum('ACTIVO','INACTIVO','TALLER') DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `buses`
--

INSERT INTO `buses` (`id`, `numero_maquina`, `patente`, `capacidad`, `estado`) VALUES
(1, '101', 'AB-CD-12', 40, 'ACTIVO'),
(2, '102', 'EF-GH-34', 44, 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `rut` varchar(15) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`rut`, `nombre`, `telefono`, `fecha_registro`) VALUES
('215307700', 'ivan alejandro bustos osses', '991598006', '2026-02-27 00:10:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matriz_precios`
--

CREATE TABLE `matriz_precios` (
  `id` int(11) NOT NULL,
  `origen_tramo` varchar(50) NOT NULL,
  `destino_tramo` varchar(50) NOT NULL,
  `precio_adulto` decimal(10,2) NOT NULL,
  `precio_estudiante` decimal(10,2) NOT NULL,
  `precio_mayor` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `matriz_precios`
--

INSERT INTO `matriz_precios` (`id`, `origen_tramo`, `destino_tramo`, `precio_adulto`, `precio_estudiante`, `precio_mayor`) VALUES
(1, 'Concepción', 'Laraquete', 3000.00, 2000.00, 1500.00),
(2, 'Concepción', 'Curanilahue', 4000.00, 3000.00, 2000.00),
(3, 'Concepción', 'Cañete', 6000.00, 4000.00, 3000.00),
(4, 'Laraquete', 'Curanilahue', 1500.00, 1000.00, 800.00),
(6, 'cañete', 'concepcion', 6000.00, 4000.00, 3000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `codigo_ticket` varchar(20) NOT NULL,
  `id_viaje` int(11) DEFAULT NULL,
  `fecha_viaje_historico` datetime DEFAULT NULL,
  `bus_historico` varchar(50) DEFAULT NULL,
  `nro_asiento` int(11) NOT NULL,
  `rut_pasajero` varchar(15) DEFAULT NULL,
  `nombre_pasajero` varchar(100) NOT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `tipo_pasajero` enum('ADULTO','ESTUDIANTE','MAYOR') DEFAULT 'ADULTO',
  `origen_boleto` varchar(50) NOT NULL,
  `destino_boleto` varchar(50) NOT NULL,
  `total_pagado` decimal(10,2) NOT NULL,
  `canal` enum('CAJA','WEB') DEFAULT 'CAJA',
  `oficina_venta` varchar(50) DEFAULT 'WEB',
  `estado` enum('CONFIRMADO','ANULADO','PENDIENTE') DEFAULT 'CONFIRMADO',
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `codigo_ticket`, `id_viaje`, `fecha_viaje_historico`, `bus_historico`, `nro_asiento`, `rut_pasajero`, `nombre_pasajero`, `telefono_contacto`, `tipo_pasajero`, `origen_boleto`, `destino_boleto`, `total_pagado`, `canal`, `oficina_venta`, `estado`, `fecha_venta`) VALUES
(1, 'CAJA-2F492', 3, '2026-02-27 20:50:00', '101 (AB-CD-12)', 32, '215307700', 'ivan alejandro bustos osses', '991598006', 'ADULTO', 'Concepción', 'cañete', 6000.00, 'CAJA', 'Oficina Concepcion', 'CONFIRMADO', '2026-02-27 00:10:54'),
(2, 'WEB-51162', 3, '2026-02-27 20:50:00', '101', 20, '215307700', 'ivan bustos osses', NULL, 'ADULTO', 'Concepción', 'cañete', 6000.00, 'WEB', 'Venta Online', 'PENDIENTE', '2026-02-27 00:18:17'),
(3, 'WEB-84654', 3, '2026-02-27 20:50:00', '101', 12, '215307700', 'ivan bustos', NULL, 'ADULTO', 'Concepción', 'cañete', 6000.00, 'WEB', 'Venta Online', 'CONFIRMADO', '2026-02-27 00:23:38'),
(4, 'WEB-50255', 3, '2026-02-27 20:50:00', '101', 8, '215307700', 'ivan bustos', NULL, 'ADULTO', 'Concepción', 'cañete', 6000.00, 'WEB', 'Venta Online', 'CONFIRMADO', '2026-02-27 00:29:08'),
(5, 'WEB-D8185', 3, '2026-02-27 20:50:00', '101', 16, '213597744', 'constanza duran fernadez', NULL, 'ADULTO', 'Concepción', 'cañete', 6000.00, 'WEB', 'Venta Online', 'CONFIRMADO', '2026-02-27 00:34:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `viajes`
--

CREATE TABLE `viajes` (
  `id` int(11) NOT NULL,
  `id_bus` int(11) NOT NULL,
  `origen` varchar(50) NOT NULL DEFAULT 'Concepción',
  `destino` varchar(50) NOT NULL DEFAULT 'Cañete',
  `fecha_hora` datetime NOT NULL,
  `estado` enum('PROGRAMADO','EN_RUTA','FINALIZADO','CANCELADO') DEFAULT 'PROGRAMADO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `viajes`
--

INSERT INTO `viajes` (`id`, `id_bus`, `origen`, `destino`, `fecha_hora`, `estado`) VALUES
(3, 1, 'Concepción', 'Cañete', '2026-02-27 20:50:00', 'PROGRAMADO');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`rut`);

--
-- Indices de la tabla `matriz_precios`
--
ALTER TABLE `matriz_precios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tramo_unico` (`origen_tramo`,`destino_tramo`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_ticket` (`codigo_ticket`),
  ADD KEY `id_viaje` (`id_viaje`);

--
-- Indices de la tabla `viajes`
--
ALTER TABLE `viajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_bus` (`id_bus`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `matriz_precios`
--
ALTER TABLE `matriz_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `viajes`
--
ALTER TABLE `viajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_viaje`) REFERENCES `viajes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `viajes`
--
ALTER TABLE `viajes`
  ADD CONSTRAINT `viajes_ibfk_1` FOREIGN KEY (`id_bus`) REFERENCES `buses` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
