CREATE DATABASE  IF NOT EXISTS `restaurante` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `restaurante`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: restaurante
-- ------------------------------------------------------
-- Server version	8.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'Buffete'),(2,'Carta'),(3,'Bebida'),(4,'Sopas'),(5,'Postres'),(6,'Entradas'),(7,'Mariscos'),(8,'Ensaladas');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_orden`
--

DROP TABLE IF EXISTS `detalle_orden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_orden` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `orden_id` (`orden_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `detalle_orden_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`),
  CONSTRAINT `detalle_orden_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_orden`
--

LOCK TABLES `detalle_orden` WRITE;
/*!40000 ALTER TABLE `detalle_orden` DISABLE KEYS */;
INSERT INTO `detalle_orden` VALUES (1,1,4,1),(2,1,1,2),(3,2,5,1),(4,2,7,1),(5,2,17,1),(6,3,3,1),(7,3,6,1),(8,3,22,1),(9,4,14,2),(10,4,9,3),(14,6,24,1),(15,6,18,2),(16,7,12,1),(17,7,2,1),(18,8,9,4),(20,8,1,6),(32,8,26,1),(33,8,3,6),(34,5,17,1),(38,9,12,12),(41,8,11,1),(42,8,10,8),(43,8,5,11),(56,5,5,2),(57,10,8,5);
/*!40000 ALTER TABLE `detalle_orden` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `empleados`
--

DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `empleados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol_id` int NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `empleados_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `empleados`
--

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
INSERT INTO `empleados` VALUES (1,'Elmer Laguna','admin','$2y$10$8f4sFyFlvXyAuGZMxXw88.PZoCl54KUAs9p.W7q7IX.XtoH/f6Dj2',1,1),(2,'Elena Guevara','mesero1','$2y$10$Q3mcUOgyboRu/uNqwQ/pneeC7cdKd2UJxXss/2hM2Uvo3sS//.Fvu',2,1),(3,'Erica Galindo','cajero1','$2y$10$7bThsg1tsW9OZl2HyEIniumJim8C4g4uA9s57RdfN.MAjTtUWMzp.',3,1),(4,'Ana Salgado Mayorga','AsalgadoM','$2y$10$SqM404GwhwNiMx3HcAhkbeQsU9QuvePGueV/kbkM9Mj6ux1.Vkpmy',3,0),(5,'David Olivas Hernandez','dolivas','$2y$10$SAi8.FeunJyeO/1vgsGBVOotM6MEurCvQbNnLHsEKNRH.Zpu0SyzK',3,1);
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesas`
--

DROP TABLE IF EXISTS `mesas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mesas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` int NOT NULL,
  `estado` enum('libre','esperando_cuenta','ocupada') DEFAULT 'libre',
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesas`
--

LOCK TABLES `mesas` WRITE;
/*!40000 ALTER TABLE `mesas` DISABLE KEYS */;
INSERT INTO `mesas` VALUES (1,1,'ocupada'),(2,2,'ocupada'),(3,3,'ocupada'),(4,4,'ocupada'),(5,5,'ocupada'),(6,6,'libre'),(7,7,'libre'),(8,8,'libre'),(9,9,'libre'),(10,10,'libre'),(11,11,'ocupada'),(12,12,'libre'),(13,13,'libre'),(14,14,'libre'),(15,15,'libre');
/*!40000 ALTER TABLE `mesas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordenes`
--

DROP TABLE IF EXISTS `ordenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mesa_id` int NOT NULL,
  `estado` enum('abierta','enviada_a_cobro','cerrada') DEFAULT 'abierta',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `metodo_pago` enum('efectivo','tarjeta','cortesia') DEFAULT NULL,
  `total_orden` decimal(10,2) DEFAULT NULL,
  `total_pago` decimal(10,2) DEFAULT NULL,
  `cambio` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mesa_id` (`mesa_id`),
  CONSTRAINT `ordenes_ibfk_1` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordenes`
--

LOCK TABLES `ordenes` WRITE;
/*!40000 ALTER TABLE `ordenes` DISABLE KEYS */;
INSERT INTO `ordenes` VALUES (1,1,'cerrada','2025-07-15 02:00:49',NULL,NULL,NULL,NULL),(2,2,'cerrada','2025-07-19 02:08:20',NULL,NULL,NULL,NULL),(3,3,'cerrada','2025-07-20 02:08:27',NULL,NULL,NULL,NULL),(4,4,'cerrada','2025-07-20 02:08:27',NULL,NULL,NULL,NULL),(5,5,'abierta','2025-07-20 02:08:27',NULL,NULL,NULL,NULL),(6,6,'cerrada','2025-07-19 00:08:28',NULL,NULL,NULL,NULL),(7,7,'cerrada','2025-07-20 02:08:28',NULL,NULL,NULL,NULL),(8,3,'abierta','2025-07-20 02:58:23',NULL,NULL,NULL,NULL),(9,2,'abierta','2025-07-20 20:21:12',NULL,NULL,NULL,NULL),(10,1,'abierta','2025-07-25 00:19:21',NULL,NULL,NULL,NULL),(11,11,'abierta','2025-07-25 00:20:40',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `ordenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,3,'Toña 12oz',45.00),(2,3,'Toña 1L',80.00),(3,4,'Mondongo',120.00),(4,1,'Desayuno Ejecutivo',200.00),(5,2,'Churrascos Deluxe',150.00),(6,3,'Coca-Cola 2L',60.00),(7,3,'Jugo Natural (Naranja)',50.00),(8,3,'Agua Pura',20.00),(9,3,'Cerveza Victoria Clásica',40.00),(10,4,'Sopa de Pollo',100.00),(11,4,'Crema de Tomate',85.00),(12,1,'Buffete Premium',250.00),(13,1,'Buffete Vegetariano',180.00),(14,2,'Filete de Res a la Plancha',220.00),(15,2,'Pechuga a la Naranja',190.00),(16,2,'Lomo de Cerdo Encebollado',185.00),(17,5,'Flan de Caramelo',75.00),(18,5,'Tiramisú',90.00),(19,5,'Brownie con Helado',80.00),(20,6,'Nachos con Queso',95.00),(21,6,'Aros de Cebolla',60.00),(22,6,'Queso Frito',70.00),(23,7,'Camarones al Ajillo',280.00),(24,7,'Pescado Frito Entero',210.00),(25,8,'Ensalada César con Pollo',160.00),(26,8,'Ensalada Mixta',120.00);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrador'),(2,'Cajero'),(3,'Mesero');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `vista_cajero`
--

DROP TABLE IF EXISTS `vista_cajero`;
/*!50001 DROP VIEW IF EXISTS `vista_cajero`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_cajero` AS SELECT 
 1 AS `orden_id`,
 1 AS `mesa`,
 1 AS `fecha`,
 1 AS `total`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'restaurante'
--

--
-- Dumping routines for database 'restaurante'
--
/*!50003 DROP PROCEDURE IF EXISTS `agregar_producto` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`%` PROCEDURE `agregar_producto`(
    IN p_orden_id INT,
    IN p_producto_id INT,
    IN p_cantidad INT
)
BEGIN
    -- Check if the order is open before adding products
    DECLARE orden_estado VARCHAR(20);
    SELECT estado INTO orden_estado FROM ordenes WHERE id = p_orden_id;

    IF orden_estado = 'abierta' THEN
        INSERT INTO detalle_orden (
            orden_id,        -- Corrected column name
            producto_id,     -- Corrected column name
            cantidad
        ) VALUES (
            p_orden_id,
            p_producto_id,
            p_cantidad
        );
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden agregar productos a una orden que no está abierta.';
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `cerrar_orden_y_liberar_mesa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`%` PROCEDURE `cerrar_orden_y_liberar_mesa`(IN p_orden_id INT)
BEGIN
    DECLARE v_mesa_id INT;

    SELECT mesa_id INTO v_mesa_id FROM ordenes WHERE id = p_orden_id;

    IF v_mesa_id IS NOT NULL THEN
        UPDATE ordenes SET estado = 'cerrada' WHERE id = p_orden_id;
        UPDATE mesas SET estado = 'libre' WHERE id = v_mesa_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Orden no encontrada.';
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `crear_orden` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`%` PROCEDURE `crear_orden`(IN p_mesa_id INT)
BEGIN
    DECLARE mesa_estado VARCHAR(20);

    SELECT estado INTO mesa_estado FROM mesas WHERE id = p_mesa_id;

    IF mesa_estado = 'libre' THEN
        INSERT INTO ordenes (mesa_id) VALUES (p_mesa_id);
        UPDATE mesas SET estado = 'ocupada' WHERE id = p_mesa_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La mesa ya está ocupada o esperando cuenta.';
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `pedir_cuenta` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`%` PROCEDURE `pedir_cuenta`(IN p_mesa_id INT)
BEGIN
    DECLARE orden_abierta_id INT;

    SELECT id INTO orden_abierta_id
    FROM ordenes
    WHERE mesa_id = p_mesa_id AND estado = 'abierta'
    ORDER BY fecha DESC
    LIMIT 1;

    IF orden_abierta_id IS NOT NULL THEN
        UPDATE ordenes SET estado = 'enviada_a_cobro' WHERE id = orden_abierta_id;
        UPDATE mesas SET estado = 'esperando_cuenta' WHERE id = p_mesa_id;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No hay orden abierta para esta mesa.';
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `reporte_ventas_del_dia` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`%` PROCEDURE `reporte_ventas_del_dia`()
BEGIN
    SELECT
        o.id AS orden_id,
        m.numero AS numero_mesa,
        o.fecha AS fecha_orden,
        SUM(do.cantidad * p.precio) AS total_orden
    FROM
        ordenes o
    JOIN
        mesas m ON o.mesa_id = m.id
    JOIN
        detalle_orden do ON o.id = do.orden_id
    JOIN
        productos p ON do.producto_id = p.id
    WHERE
        o.estado = 'cerrada' AND DATE(o.fecha) = CURDATE()
    GROUP BY
        o.id, m.numero, o.fecha
    ORDER BY
        o.fecha DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `reporte_ventas_entre_fechas` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`%` PROCEDURE `reporte_ventas_entre_fechas`(
    IN p_fecha_inicio DATETIME,
    IN p_fecha_fin DATETIME
)
BEGIN
    SELECT
        o.id AS orden_id,
        m.numero AS numero_mesa,
        o.fecha AS fecha_orden,
        SUM(do.cantidad * p.precio) AS total_orden
    FROM
        ordenes o
    JOIN
        mesas m ON o.mesa_id = m.id
    JOIN
        detalle_orden do ON o.id = do.orden_id
    JOIN
        productos p ON do.producto_id = p.id
    WHERE
        o.estado = 'cerrada' AND o.fecha BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY
        o.id, m.numero, o.fecha
    ORDER BY
        o.fecha DESC;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `vista_cajero`
--

/*!50001 DROP VIEW IF EXISTS `vista_cajero`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `vista_cajero` AS select `o`.`id` AS `orden_id`,`m`.`numero` AS `mesa`,`o`.`fecha` AS `fecha`,sum((`p`.`precio` * `do`.`cantidad`)) AS `total` from (((`ordenes` `o` join `mesas` `m` on((`o`.`mesa_id` = `m`.`id`))) join `detalle_orden` `do` on((`do`.`orden_id` = `o`.`id`))) join `productos` `p` on((`do`.`producto_id` = `p`.`id`))) where (`o`.`estado` = 'enviada_a_cobro') group by `o`.`id`,`m`.`numero`,`o`.`fecha` order by `o`.`fecha` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-25  0:31:08
