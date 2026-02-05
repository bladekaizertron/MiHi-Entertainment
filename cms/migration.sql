-- Database migration for GrapesJS Website Builder
-- Run this SQL to create the pages table

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `html_content` LONGTEXT,
  `css_content` LONGTEXT,
  `components` LONGTEXT COMMENT 'GrapesJS components JSON',
  `styles` LONGTEXT COMMENT 'GrapesJS styles JSON',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
