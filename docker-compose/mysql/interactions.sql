CREATE TABLE `interactions` (
    `user_uuid` varchar(50) NOT NULL,
    `app_uuid` varchar(50) NOT NULL,
    `index_uuid` varchar(50) NOT NULL,
    `item_uuid` varchar(50) NOT NULL,
    `ip` varchar(16) NOT NULL,
    `host` varchar(50) NOT NULL,
    `platform` varchar(10) NOT NULL,
    `type` varchar(10) NOT NULL,
    `time` int(8) NOT NULL,
    KEY `time_app` (`time`, `app_uuid`),
    KEY `time_app_index` (`time`, `app_uuid`, `index_uuid`),
    KEY `time_app_index_platform` (`time`, `app_uuid`, `index_uuid`, `platform`),
    KEY `time_app_index_user` (`time`, `app_uuid`, `index_uuid`, `user_uuid`),
    KEY `time_app_index_type` (`time`, `app_uuid`, `index_uuid`, `type`),
    KEY `time_app_index_item` (`time`, `app_uuid`, `index_uuid`, `item_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
