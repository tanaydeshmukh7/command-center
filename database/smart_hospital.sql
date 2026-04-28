-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: smart_hospital
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_sessions`
--

DROP TABLE IF EXISTS `admin_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `admin_user_id` int NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_admin_sessions_user` (`admin_user_id`),
  KEY `idx_admin_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_admin_sessions_user` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_sessions`
--

LOCK TABLES `admin_sessions` WRITE;
/*!40000 ALTER TABLE `admin_sessions` DISABLE KEYS */;
INSERT INTO `admin_sessions` VALUES (1,1,'46f681f71c42f406b9ddd98516e1c7f0c76dae0e75726d59b67250cdc1f86474','2026-04-27 23:17:24','2026-04-27 11:17:24','2026-04-27 11:17:24'),(2,1,'80d9fc6a1dd7ce0b516e9f290d90147cf2a3d395dc829b3495e718ccd95c0a11','2026-04-27 23:18:18','2026-04-27 11:18:18','2026-04-27 11:18:18'),(3,1,'5d44a69933f4cad119110d63abf09441b551a20a9da6afabea8951eb8077ff04','2026-04-27 23:18:19','2026-04-27 11:18:19','2026-04-27 11:18:19');
/*!40000 ALTER TABLE `admin_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(120) DEFAULT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin@test','$2y$12$.hjrbdCZJFc3bkZwDVFE2eMrwbfzaCJ3dV81okwMXi1WWC4LRXA.K','Command Center Admin','active','2026-04-27 11:15:29','2026-04-27 11:15:29');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_analysis`
--

DROP TABLE IF EXISTS `ai_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_analysis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `severity_score` decimal(4,2) NOT NULL,
  `recommended_resource` varchar(100) DEFAULT NULL,
  `confidence` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rationale` json DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `ai_analysis_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_analysis`
--

LOCK TABLES `ai_analysis` WRITE;
/*!40000 ALTER TABLE `ai_analysis` DISABLE KEYS */;
INSERT INTO `ai_analysis` VALUES (1,6,4.50,'General Ward Bed',95.00,'{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"cyan\", \"title\": \"Symptom Analysis: Elevated Indicator\", \"detail\": \"Detected symptom pattern: \\\"fever\\\" — classified as Elevated priority with weight factor of 1.5. 1 symptom indicator(s) matched against the medical knowledge base.\"}, {\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Critical Oxygen Desaturation\", \"detail\": \"SpO2 at 50% — critically below safe threshold of 90%. Immediate supplemental oxygen and possible intubation required. This level indicates severe hypoxemia with risk of organ damage.\"}, {\"icon\": \"account_tree\", \"color\": \"cyan\", \"title\": \"Resource Allocation Decision\", \"detail\": \"Based on composite severity score of 4.5/10: Condition is manageable with standard nursing care and periodic monitoring. Recommended: General Ward Bed.\"}]}','completed','2026-04-22 12:12:53'),(2,7,5.50,'Telemetry Monitor Unit',95.00,'{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"cyan\", \"title\": \"Symptom Analysis: Elevated Indicator\", \"detail\": \"Detected symptom pattern: \\\"chest pain\\\" — classified as Elevated priority with weight factor of 2.5. 1 symptom indicator(s) matched against the medical knowledge base.\"}, {\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Critical Oxygen Desaturation\", \"detail\": \"SpO2 at 80% — critically below safe threshold of 90%. Immediate supplemental oxygen and possible intubation required. This level indicates severe hypoxemia with risk of organ damage.\"}, {\"icon\": \"account_tree\", \"color\": \"cyan\", \"title\": \"Resource Allocation Decision\", \"detail\": \"Based on composite severity score of 5.5/10: Moderate condition requires continuous cardiac and vital sign monitoring. Recommended: Telemetry Monitor Unit.\"}]}','completed','2026-04-22 12:30:02'),(6,11,9.00,'ICU',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','completed','2026-04-22 13:02:28'),(7,12,9.00,'ICU',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','completed','2026-04-22 14:34:59'),(8,13,9.00,'ICU',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','completed','2026-04-22 14:35:53'),(13,18,3.00,'General Ward Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','completed','2026-04-22 15:05:39'),(15,20,3.00,'General Ward Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','completed','2026-04-25 14:51:42'),(16,21,3.00,'General Ward Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','completed','2026-04-25 15:02:36'),(17,22,3.00,'General Ward Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','completed','2026-04-25 15:11:09'),(18,23,3.00,'General Ward Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','completed','2026-04-25 15:18:24'),(19,24,9.00,'ICU',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','completed','2026-04-25 15:23:13'),(20,25,9.00,'ICU Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','completed','2026-04-25 16:03:12'),(21,26,3.00,'General Bed',99.00,'{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','completed','2026-04-25 17:01:10'),(22,27,9.20,'ICU Bed',98.00,'{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (89%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"air\", \"color\": \"pink\", \"title\": \"Ventilator active\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 72% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','completed','2026-04-26 15:53:46'),(23,28,8.00,'ICU Bed',98.00,'{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 95% signals respiratory compromise. Age 19 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 95% signals respiratory compromise. Age 19 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 60% (MODERATE)\", \"detail\": \"Moderate risk of worsening condition. Close monitoring and potential escalation advised.\"}]}','completed','2026-04-27 19:05:14'),(25,30,8.00,'ICU Bed',98.00,'{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"thermostat\", \"color\": \"cyan\", \"title\": \"Fever present\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 60% (MODERATE)\", \"detail\": \"Moderate risk of worsening condition. Close monitoring and potential escalation advised.\"}]}','completed','2026-04-27 19:15:56'),(26,31,1.50,'General Bed',98.00,'{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (73 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 98% with normal trends. Age 22 with symptoms: cold. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','completed','2026-04-27 19:30:45');
/*!40000 ALTER TABLE `ai_analysis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `allocations`
--

DROP TABLE IF EXISTS `allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `resource_id` int NOT NULL,
  `ai_confidence` decimal(5,2) NOT NULL DEFAULT '0.00',
  `ai_explanation` text,
  `ai_rationale` json DEFAULT NULL,
  `status` enum('active','released','pending') NOT NULL DEFAULT 'pending',
  `allocated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `released_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `resource_id` (`resource_id`),
  CONSTRAINT `allocations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `allocations_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `allocations`
--

LOCK TABLES `allocations` WRITE;
/*!40000 ALTER TABLE `allocations` DISABLE KEYS */;
INSERT INTO `allocations` VALUES (1,1,1,94.20,'Patient Elias Vance requires ICU-level care due to acute respiratory distress with critically low SpO2 levels.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Respiratory Distress Marker\", \"detail\": \"SpO2 levels dropped below 88% sustained for >15 minutes.\"}, {\"icon\": \"history_edu\", \"color\": \"cyan\", \"title\": \"Historical Correlation\", \"detail\": \"Pattern matches 89% of previous admissions requiring intubation within 4 hours.\"}, {\"icon\": \"vaccines\", \"color\": \"purple\", \"title\": \"Medication Conflict Risk\", \"detail\": \"Flagged interaction between current IV drip and proposed immediate-response protocols.\"}]}','released','2026-04-22 12:12:49','2026-04-22 15:40:35'),(2,2,9,87.50,'Patient Sarah Connor has stable vitals suitable for general ward care. No ICU resources needed.','{\"markers\": [{\"icon\": \"vital_signs\", \"color\": \"cyan\", \"title\": \"Stable Vital Signs\", \"detail\": \"SpO2 at 97%, heart rate normal, blood pressure within range.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"Low Severity Assessment\", \"detail\": \"AI severity score of 2.1 indicates minimal risk. General bed sufficient.\"}]}','released','2026-04-22 12:12:49','2026-04-26 11:04:38'),(3,3,21,91.30,'Patient Marcus Wright shows cardiac instability requiring continuous telemetry monitoring.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"Cardiac Instability\", \"detail\": \"Elevated heart rate of 110 BPM with intermittent arrhythmia detected.\"}, {\"icon\": \"trending_down\", \"color\": \"cyan\", \"title\": \"Oxygen Trend Analysis\", \"detail\": \"SpO2 declining trend from 95% to 91% over 2 hours suggests worsening condition.\"}, {\"icon\": \"elderly\", \"color\": \"purple\", \"title\": \"Age-Risk Factor\", \"detail\": \"Age 45 with cardiovascular symptoms increases ICU transfer probability by 34%.\"}]}','released','2026-04-22 12:12:49','2026-04-27 11:41:20'),(4,4,23,95.80,'Patient Anya Corazon is post-surgical with excellent recovery metrics. Discharge lounge appropriate.','{\"markers\": [{\"icon\": \"healing\", \"color\": \"cyan\", \"title\": \"Post-Surgery Recovery\", \"detail\": \"All post-operative vitals within normal range for 24+ hours.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"Discharge Readiness\", \"detail\": \"Meets all discharge criteria. Resource can be freed for incoming patients.\"}]}','released','2026-04-22 12:12:49','2026-04-27 19:12:53'),(5,5,2,96.70,'Patient James Okoro is in critical condition post cardiac arrest with multi-organ risk requiring immediate ICU resources.','{\"markers\": [{\"icon\": \"emergency\", \"color\": \"pink\", \"title\": \"Post Cardiac Arrest\", \"detail\": \"Patient experienced cardiac arrest 3 hours ago. Requires continuous cardiac monitoring and ventilator standby.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"Multi-Organ Risk\", \"detail\": \"Kidney function declining, liver enzymes elevated. Multi-organ failure risk at 45%.\"}, {\"icon\": \"pulmonology\", \"color\": \"purple\", \"title\": \"Critical SpO2 Level\", \"detail\": \"SpO2 at 82% even with supplemental oxygen. May require intubation within 1 hour.\"}]}','active','2026-04-22 12:12:49',NULL),(6,1,4,99.00,'Patient Elias Vance allocated to ICU Bay 04: Low oxygen indicates respiratory distress','{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','released','2026-04-22 15:40:35','2026-04-22 16:19:08'),(7,1,1,0.00,'Manual resource assignment','{\"markers\": [{\"icon\": \"touch_app\", \"color\": \"cyan\", \"title\": \"Manual Assignment\", \"detail\": \"Assigned manually to ICU-01.\"}]}','released','2026-04-22 16:19:08','2026-04-26 13:57:35'),(8,20,12,0.00,'Manual resource assignment','{\"markers\": [{\"icon\": \"touch_app\", \"color\": \"cyan\", \"title\": \"Manual Assignment\", \"detail\": \"Assigned manually to GEN-04.\"}]}','released','2026-04-25 14:52:19','2026-04-26 11:05:12'),(9,21,14,0.00,'Manual resource assignment','{\"markers\": [{\"icon\": \"touch_app\", \"color\": \"cyan\", \"title\": \"Manual Assignment\", \"detail\": \"Assigned manually to GEN-06.\"}]}','active','2026-04-25 15:03:00',NULL),(10,24,4,0.00,'Manual resource assignment','{\"markers\": [{\"icon\": \"touch_app\", \"color\": \"cyan\", \"title\": \"Manual Assignment\", \"detail\": \"Assigned manually to ICU-04.\"}]}','active','2026-04-25 15:23:23',NULL),(11,25,5,99.00,'Low oxygen indicates respiratory distress','{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Low oxygen indicates respiratory distress\"}]}','released','2026-04-25 16:03:12','2026-04-26 11:55:44'),(12,26,9,99.00,'Patient vitals are stable','{\"markers\": [{\"icon\": \"psychology\", \"color\": \"cyan\", \"title\": \"AI Allocation Rationale\", \"detail\": \"Patient vitals are stable\"}]}','released','2026-04-25 17:01:10','2026-04-26 11:04:51'),(13,1,5,98.00,'Patient Elias Vance allocated to ICU Bay 05: Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.50% signals respiratory compromise. Age 62 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (86.5%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.50% signals respiratory compromise. Age 62 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (121 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.50% signals respiratory compromise. Age 62 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"air\", \"color\": \"pink\", \"title\": \"Ventilator active\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.50% signals respiratory compromise. Age 62 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 78% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','active','2026-04-26 13:57:35',NULL),(14,18,9,98.00,'Patient tanmay allocated to General Bed 01: Patient vitals are within stable parameters. SpO₂ at 98.00% with normal trends. Age 20 with symptoms: cold. Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (72 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 98.00% with normal trends. Age 20 with symptoms: cold. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','active','2026-04-26 14:27:50',NULL),(15,25,6,98.00,'Patient ved allocated to ICU Bay 06: Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89.00% signals respiratory compromise. Age 22 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (89%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89.00% signals respiratory compromise. Age 22 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89.00% signals respiratory compromise. Age 22 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89.00% signals respiratory compromise. Age 22 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 72% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','released','2026-04-26 14:27:50','2026-04-26 15:49:45'),(16,13,8,98.00,'Patient ram allocated to ICU Bay 08: Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 50.00% signals respiratory compromise. Age 20 with symptoms: cold. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (50%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 50.00% signals respiratory compromise. Age 20 with symptoms: cold. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 50.00% signals respiratory compromise. Age 20 with symptoms: cold. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"air\", \"color\": \"pink\", \"title\": \"Ventilator active\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 50.00% signals respiratory compromise. Age 20 with symptoms: cold. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 85% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','released','2026-04-26 14:27:50','2026-04-26 15:49:43'),(17,12,15,98.00,'Patient sita allocated to OR Trauma 1: Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 88.00% signals respiratory compromise. Age 55 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (88%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 88.00% signals respiratory compromise. Age 55 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 88.00% signals respiratory compromise. Age 55 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 88.00% signals respiratory compromise. Age 55 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 72% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','released','2026-04-26 14:27:50','2026-04-27 11:41:26'),(18,11,17,98.00,'Patient varun allocated to OR Surgery 1: Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.00% signals respiratory compromise. Age 60 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (86%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.00% signals respiratory compromise. Age 60 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.00% signals respiratory compromise. Age 60 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"air\", \"color\": \"pink\", \"title\": \"Ventilator active\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 86.00% signals respiratory compromise. Age 60 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 78% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','released','2026-04-26 14:27:50','2026-04-27 19:13:19'),(19,7,21,98.00,'Patient Tanay Deshmukh allocated to Cardiac Monitor 01: Patient shows moderate-severity indicators requiring enhanced monitoring. SpO₂ at 80.00% with concerning vital trends. Age 19 with symptoms: Crushing chest pain. Cardiac telemetry recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (80%)\", \"detail\": \"Patient shows moderate-severity indicators requiring enhanced monitoring. SpO₂ at 80.00% with concerning vital trends. Age 19 with symptoms: Crushing chest pain. Cardiac telemetry recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"cyan\", \"title\": \"Elevated HR (114 bpm)\", \"detail\": \"Patient shows moderate-severity indicators requiring enhanced monitoring. SpO₂ at 80.00% with concerning vital trends. Age 19 with symptoms: Crushing chest pain. Cardiac telemetry recommended.\"}, {\"icon\": \"air\", \"color\": \"pink\", \"title\": \"Ventilator active\", \"detail\": \"Patient shows moderate-severity indicators requiring enhanced monitoring. SpO₂ at 80.00% with concerning vital trends. Age 19 with symptoms: Crushing chest pain. Cardiac telemetry recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 42% (MODERATE)\", \"detail\": \"Moderate risk of worsening condition. Close monitoring and potential escalation advised.\"}]}','active','2026-04-26 14:27:50',NULL),(21,26,11,98.00,'Patient abhi allocated to General Bed 03: Patient vitals are within stable parameters. SpO₂ at 96.00% with normal trends. Age 30 with symptoms: fever. Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (75 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 96.00% with normal trends. Age 30 with symptoms: fever. Standard observation protocol sufficient.\"}, {\"icon\": \"thermostat\", \"color\": \"cyan\", \"title\": \"Fever present\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 96.00% with normal trends. Age 30 with symptoms: fever. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','active','2026-04-26 14:27:50',NULL),(22,23,12,98.00,'Patient om allocated to General Bed 04: Patient vitals are within stable parameters. SpO₂ at 95.00% with normal trends. Age 25 with symptoms: mild chest pain and headchace. Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (76 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 95.00% with normal trends. Age 25 with symptoms: mild chest pain and headchace. Standard observation protocol sufficient.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 95.00% with normal trends. Age 25 with symptoms: mild chest pain and headchace. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','released','2026-04-26 14:27:50','2026-04-27 19:12:20'),(23,22,20,98.00,'Patient om allocated to Ventilator Unit 03: Patient vitals are within stable parameters. SpO₂ at 92.00% with normal trends. Age 36 with symptoms: mild chest pain . Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"cyan\", \"title\": \"Reduced SpO₂ (92%)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 92.00% with normal trends. Age 36 with symptoms: mild chest pain . Standard observation protocol sufficient.\"}, {\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (76 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 92.00% with normal trends. Age 36 with symptoms: mild chest pain . Standard observation protocol sufficient.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 92.00% with normal trends. Age 36 with symptoms: mild chest pain . Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','released','2026-04-26 14:27:50','2026-04-27 19:12:15'),(24,20,23,98.00,'Patient Vikrant Dixith allocated to Discharge Lounge 01: Patient vitals are within stable parameters. SpO₂ at 93.00% with normal trends. Age 20 with symptoms: Rapid weight loss, Extreme fatigue and weakness. Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"cyan\", \"title\": \"Reduced SpO₂ (93%)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 93.00% with normal trends. Age 20 with symptoms: Rapid weight loss, Extreme fatigue and weakness. Standard observation protocol sufficient.\"}, {\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (72 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 93.00% with normal trends. Age 20 with symptoms: Rapid weight loss, Extreme fatigue and weakness. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','released','2026-04-26 14:27:50','2026-04-27 19:12:56'),(25,2,24,98.00,'Patient Sarah Connor allocated to Discharge Lounge 02: Patient vitals are within stable parameters. SpO₂ at 97.00% with normal trends. Age 34 with symptoms: Mild headache, nausea. Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (81 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 97.00% with normal trends. Age 34 with symptoms: Mild headache, nausea. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','active','2026-04-26 14:27:50',NULL),(26,27,6,98.00,'Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"pulmonology\", \"color\": \"pink\", \"title\": \"Low SpO₂ (89%)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"air\", \"color\": \"pink\", \"title\": \"Ventilator active\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 89% signals respiratory compromise. Age 56 with symptoms: Acute respiratory distress, chest pain, fever. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 72% (HIGH)\", \"detail\": \"High probability of clinical deterioration within the next hour. Immediate intervention recommended.\"}]}','active','2026-04-26 15:53:46',NULL),(27,28,8,98.00,'Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 95% signals respiratory compromise. Age 19 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 95% signals respiratory compromise. Age 19 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 95% signals respiratory compromise. Age 19 with symptoms: Chest pain, breathlessness. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 60% (MODERATE)\", \"detail\": \"Moderate risk of worsening condition. Close monitoring and potential escalation advised.\"}]}','released','2026-04-27 19:05:14','2026-04-27 19:06:50'),(29,30,8,98.00,'Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"pink\", \"title\": \"High HR (130 bpm)\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"cardiology\", \"color\": \"pink\", \"title\": \"Cardiac risk\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"thermostat\", \"color\": \"cyan\", \"title\": \"Fever present\", \"detail\": \"Patient presents with critical indicators requiring immediate ICU-level care. SpO₂ at 98% signals respiratory compromise. Age 20 with symptoms: fever, chest pain. Continuous monitoring and ventilator standby recommended.\"}, {\"icon\": \"warning\", \"color\": \"pink\", \"title\": \"1-Hour Risk: 60% (MODERATE)\", \"detail\": \"Moderate risk of worsening condition. Close monitoring and potential escalation advised.\"}]}','active','2026-04-27 19:15:56',NULL),(30,31,12,98.00,'Patient vitals are within stable parameters. SpO₂ at 98% with normal trends. Age 22 with symptoms: cold. Standard observation protocol sufficient.','{\"markers\": [{\"icon\": \"monitor_heart\", \"color\": \"purple\", \"title\": \"Normal HR (73 bpm)\", \"detail\": \"Patient vitals are within stable parameters. SpO₂ at 98% with normal trends. Age 22 with symptoms: cold. Standard observation protocol sufficient.\"}, {\"icon\": \"check_circle\", \"color\": \"cyan\", \"title\": \"1-Hour Risk: 5% (MINIMAL)\", \"detail\": \"Minimal risk detected. Patient vitals stable with no concerning trends.\"}]}','released','2026-04-27 19:30:45','2026-04-27 19:32:20');
/*!40000 ALTER TABLE `allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bias_logs`
--

DROP TABLE IF EXISTS `bias_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bias_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trigger_type` varchar(100) NOT NULL,
  `adjustment` varchar(255) DEFAULT NULL,
  `authority` varchar(100) NOT NULL DEFAULT 'System Auto',
  `status` enum('applied','logged','pending') NOT NULL DEFAULT 'logged',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bias_logs`
--

LOCK TABLES `bias_logs` WRITE;
/*!40000 ALTER TABLE `bias_logs` DISABLE KEYS */;
INSERT INTO `bias_logs` VALUES (1,'Age Skew > 15%','Weight +0.5 to >65 cohort','System Auto','applied','2023-10-27 09:02:01'),(2,'Manual Review','Reset baseline parameters','Admin_Chen','logged','2023-10-26 03:45:44'),(3,'Gender Variance > 18%','Equalize resource pool allocation','System Auto','applied','2023-10-25 12:18:12');
/*!40000 ALTER TABLE `bias_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `age` int NOT NULL,
  `gender` enum('M','F','O') NOT NULL DEFAULT 'O',
  `symptoms` text,
  `oxygen_level` decimal(5,2) DEFAULT NULL,
  `severity` varchar(20) DEFAULT NULL,
  `severity_score` decimal(4,2) DEFAULT '0.00',
  `ward` varchar(50) DEFAULT NULL,
  `status` enum('admitted','discharged','waiting') NOT NULL DEFAULT 'waiting',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `allocation` varchar(50) DEFAULT NULL,
  `assigned_resource` varchar(50) DEFAULT NULL,
  `allocation_time` datetime DEFAULT NULL,
  `icu_required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_code` (`patient_code`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'993-A2X','Elias Vance',62,'M','Acute respiratory distress, chest pain, fever',86.50,'critical',9.20,'ICU-Ward B','admitted','2026-04-22 12:12:49','2026-04-26 13:57:35',NULL,'ICU-05',NULL,0),(2,'402-B1Y','Sarah Connor',34,'F','Mild headache, nausea',97.00,'stable',1.50,'Discharge Area','admitted','2026-04-22 12:12:49','2026-04-26 14:27:50',NULL,'DL-02',NULL,0),(3,'771-C9Z','Marcus Wright',45,'M','Elevated heart rate, dizziness, shortness of breath',91.00,'moderate',5.60,'Tele-Unit 08','waiting','2026-04-22 12:12:49','2026-04-27 11:41:20',NULL,NULL,NULL,0),(4,'105-D4W','Anya Corazon',28,'F','Post-surgery recovery, stable vitals',98.50,'stable',1.20,'Discharge Area','waiting','2026-04-22 12:12:49','2026-04-27 19:12:53',NULL,NULL,NULL,0),(5,'559-E7K','James Okoro',71,'M','Cardiac arrest recovery, low oxygen, multi-organ risk',82.00,'critical',9.10,'ICU-Ward A','admitted','2026-04-22 12:12:49','2026-04-22 16:18:32',NULL,'ICU-02',NULL,0),(6,'7C0-124','ini',20,'M','fever',50.00,'moderate',6.50,'ICU-Ward A','waiting','2026-04-22 12:12:53','2026-04-27 11:41:25',NULL,NULL,NULL,0),(7,'879-929','Tanay Deshmukh',19,'M','Crushing chest pain',80.00,'moderate',6.50,'Tele-Unit 08','admitted','2026-04-22 12:30:02','2026-04-26 14:27:50',NULL,'MON-01',NULL,0),(11,'153-06E','varun',60,'M','Acute respiratory distress, chest pain, fever',86.00,'critical',9.20,'Surgical Wing','waiting','2026-04-22 13:02:28','2026-04-27 19:13:19','ICU',NULL,NULL,0),(12,'76D-89B','sita',55,'F','fever, chest pain',88.00,'critical',9.20,'Trauma Center','waiting','2026-04-22 14:34:59','2026-04-27 11:41:26','ICU',NULL,NULL,0),(13,'2F7-862','ram',20,'M','cold',50.00,'critical',9.50,'ICU-Ward B','waiting','2026-04-22 14:35:53','2026-04-26 15:49:43','ICU',NULL,NULL,0),(18,'F8F-348','tanmay',20,'M','cold',98.00,'stable',1.50,'GenMed-12','admitted','2026-04-22 15:05:39','2026-04-26 14:27:50','General Ward Bed','GEN-01',NULL,0),(20,'2C8-099','Vikrant Dixith',20,'M','Rapid weight loss, Extreme fatigue and weakness',93.00,'stable',2.00,'Discharge Area','waiting','2026-04-25 14:51:42','2026-04-27 19:12:56','General Ward Bed',NULL,NULL,0),(21,'BD9-FA4','shaurya nandan',20,'M','Occasional chest pain',98.00,'stable',3.00,'GenMed-16','admitted','2026-04-25 15:02:36','2026-04-25 15:03:00','General Ward Bed','GEN-06',NULL,0),(22,'BFA-826','om',36,'M','mild chest pain ',92.00,'stable',2.00,'ICU-Ward B','waiting','2026-04-25 15:11:09','2026-04-27 19:12:15','General Ward Bed',NULL,NULL,0),(23,'F68-587','om',25,'M','mild chest pain and headchace',95.00,'stable',2.00,'GenMed-14','waiting','2026-04-25 15:18:24','2026-04-27 19:12:20','General Ward Bed',NULL,NULL,0),(24,'020-B8C','maruti',35,'M','Crushing chest pain',89.00,'critical',9.00,'ICU-Ward A','admitted','2026-04-25 15:23:13','2026-04-25 15:23:23','ICU','ICU-04',NULL,0),(25,'FF8-AF3','ved',22,'M','Chest pain, breathlessness',89.00,'critical',9.00,'ICU-Ward B','waiting','2026-04-25 16:03:12','2026-04-26 15:49:45','ICU Bed',NULL,NULL,0),(26,'9EC-96B','abhi',30,'M','fever',96.00,'stable',2.00,'GenMed-14','admitted','2026-04-25 17:01:10','2026-04-26 14:27:50','GENERAL BED','GEN-03','2026-04-25 17:01:10',0),(27,'FDD-E70','shanta bai',56,'F','Acute respiratory distress, chest pain, fever',89.00,'critical',9.20,'ICU-Ward B','admitted','2026-04-26 15:53:46','2026-04-26 15:53:46','ICU BED','ICU-06','2026-04-26 15:53:46',0),(28,'F44-4F2','VEDIKA',19,'F','Chest pain, breathlessness',95.00,'critical',8.00,'ICU-Ward B','waiting','2026-04-27 19:05:14','2026-04-27 19:11:52','ICU BED',NULL,'2026-04-27 19:05:14',0),(30,'D7D-297','swarali',20,'F','fever, chest pain',98.00,'critical',8.00,'ICU-Ward B','admitted','2026-04-27 19:15:56','2026-04-27 19:15:56','ICU BED','ICU-08','2026-04-27 19:15:56',0),(31,'BE1-9DA','depika',22,'F','cold',98.00,'stable',1.50,'GenMed-14','waiting','2026-04-27 19:30:45','2026-04-27 19:32:20','GENERAL BED',NULL,'2026-04-27 19:30:45',0);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `resource_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('icu_bed','general_bed','or_room','ventilator','monitor','wheelchair','discharge_lounge') NOT NULL,
  `ward` varchar(50) NOT NULL,
  `status` enum('available','occupied','maintenance') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource_code` (`resource_code`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resources`
--

LOCK TABLES `resources` WRITE;
/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
INSERT INTO `resources` VALUES (1,'ICU-01','ICU Bay 01','icu_bed','ICU-Ward A','occupied','2026-04-22 12:12:49'),(2,'ICU-02','ICU Bay 02','icu_bed','ICU-Ward A','occupied','2026-04-22 12:12:49'),(3,'ICU-03','ICU Bay 03','icu_bed','ICU-Ward A','occupied','2026-04-22 12:12:49'),(4,'ICU-04','ICU Bay 04','icu_bed','ICU-Ward A','occupied','2026-04-22 12:12:49'),(5,'ICU-05','ICU Bay 05','icu_bed','ICU-Ward B','occupied','2026-04-22 12:12:49'),(6,'ICU-06','ICU Bay 06','icu_bed','ICU-Ward B','occupied','2026-04-22 12:12:49'),(7,'ICU-07','ICU Bay 07','icu_bed','ICU-Ward B','occupied','2026-04-22 12:12:49'),(8,'ICU-08','ICU Bay 08','icu_bed','ICU-Ward B','occupied','2026-04-22 12:12:49'),(9,'GEN-01','General Bed 01','general_bed','GenMed-12','occupied','2026-04-22 12:12:49'),(10,'GEN-02','General Bed 02','general_bed','GenMed-12','occupied','2026-04-22 12:12:49'),(11,'GEN-03','General Bed 03','general_bed','GenMed-14','occupied','2026-04-22 12:12:49'),(12,'GEN-04','General Bed 04','general_bed','GenMed-14','available','2026-04-22 12:12:49'),(13,'GEN-05','General Bed 05','general_bed','GenMed-16','occupied','2026-04-22 12:12:49'),(14,'GEN-06','General Bed 06','general_bed','GenMed-16','occupied','2026-04-22 12:12:49'),(15,'OR-T1','OR Trauma 1','or_room','Trauma Center','available','2026-04-22 12:12:49'),(16,'OR-T2','OR Trauma 2','or_room','Trauma Center','occupied','2026-04-22 12:12:49'),(17,'OR-S1','OR Surgery 1','or_room','Surgical Wing','available','2026-04-22 12:12:49'),(19,'VENT-02','Ventilator Unit 02','ventilator','ICU-Ward A','occupied','2026-04-22 12:12:49'),(20,'VENT-03','Ventilator Unit 03','ventilator','ICU-Ward B','available','2026-04-22 12:12:49'),(21,'MON-01','Cardiac Monitor 01','monitor','Tele-Unit 08','available','2026-04-22 12:12:49'),(22,'MON-02','Cardiac Monitor 02','monitor','Tele-Unit 08','occupied','2026-04-22 12:12:49'),(23,'DL-01','Discharge Lounge 01','discharge_lounge','Discharge Area','available','2026-04-22 12:12:49'),(24,'DL-02','Discharge Lounge 02','discharge_lounge','Discharge Area','occupied','2026-04-22 12:12:49');
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-28  1:50:01
