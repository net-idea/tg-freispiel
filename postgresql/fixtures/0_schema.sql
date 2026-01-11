CREATE TABLE `doctrine_migration_versions` (
    `version` varchar(191) NOT NULL,
    `executed_at` datetime DEFAULT NULL,
    `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

ALTER TABLE `doctrine_migration_versions`
    ADD PRIMARY KEY (`version`);
COMMIT;
