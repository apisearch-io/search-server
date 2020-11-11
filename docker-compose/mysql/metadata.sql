CREATE TABLE `metadata` (
  `repository_reference_uuid` varchar(255) NOT NULL,
  `key` varchar(15) NOT NULL,
  `val` longtext NOT NULL,
  `factory` varchar(128) NULL default NULL,
  PRIMARY KEY (`repository_reference_uuid`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;