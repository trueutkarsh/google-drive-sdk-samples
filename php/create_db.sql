--
-- Database: `dredit`
--
CREATE DATABASE `dredit`;
USE `dredit`;

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `refresh_token` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
);
