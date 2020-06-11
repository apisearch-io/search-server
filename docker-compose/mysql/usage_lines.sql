CREATE TABLE `usage_lines` (
  `event` varchar(15) NOT NULL,
  `app_uuid` varchar(50) NOT NULL,
  `index_uuid` varchar(50) NOT NULL,
  `time` int(11) NOT NULL,
  `n` int(7) NOT NULL,
  KEY `time_event` (`time`, `event`),
  KEY `time_event_app` (`time`, `event`, `app_uuid`),
  KEY `time_event_app_index` (`time`, `event`, `app_uuid`, `index_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;