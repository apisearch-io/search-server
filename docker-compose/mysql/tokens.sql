CREATE TABLE `tokens` (
  `token_uuid` varchar(50) NOT NULL,
  `app_uuid` varchar(50) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`token_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;