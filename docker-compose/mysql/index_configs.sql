CREATE TABLE `index_configs` (
  `repository_reference_uuid` varchar(50) NOT NULL,
  `content` text,
  PRIMARY KEY (`repository_reference_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;