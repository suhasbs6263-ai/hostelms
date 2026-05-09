<?php

if (!function_exists('schema_table_health')) {
    function schema_table_health(mysqli $mysqli, string $table): array
    {
        $safeTable = $mysqli->real_escape_string($table);
        $existsResult = $mysqli->query("SHOW TABLES LIKE '{$safeTable}'");
        $exists = $existsResult && $existsResult->num_rows > 0;

        if (!$exists) {
            return [
                'exists' => false,
                'healthy' => false,
                'errno' => 0,
                'error' => '',
            ];
        }

        $result = $mysqli->query("SELECT 1 FROM `{$table}` LIMIT 1");

        if ($result === false) {
            return [
                'exists' => true,
                'healthy' => false,
                'errno' => (int) $mysqli->errno,
                'error' => (string) $mysqli->error,
            ];
        }

        return [
            'exists' => true,
            'healthy' => true,
            'errno' => 0,
            'error' => '',
        ];
    }
}

if (!function_exists('schema_sync_columns')) {
    function schema_sync_columns(mysqli $mysqli, string $table, array $columns): void
    {
        $existing = [];
        $result = $mysqli->query("SHOW COLUMNS FROM `{$table}`");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $existing[] = $row['Field'];
            }
        }

        foreach ($columns as $name => $definition) {
            if (!in_array($name, $existing, true)) {
                $mysqli->query("ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$definition}");
            }
        }
    }
}

if (!function_exists('schema_sync_indexes')) {
    function schema_sync_indexes(mysqli $mysqli, string $table, array $indexes): void
    {
        $existing = [];
        $result = $mysqli->query("SHOW INDEX FROM `{$table}`");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $existing[] = $row['Key_name'];
            }
        }

        foreach ($indexes as $indexName => $sql) {
            if (!in_array($indexName, $existing, true)) {
                $mysqli->query($sql);
            }
        }
    }
}

if (!function_exists('schema_recreate_table')) {
    function schema_recreate_table(mysqli $mysqli, string $table, array $definition): void
    {
        $mysqli->query('SET FOREIGN_KEY_CHECKS = 0');
        $mysqli->query("DROP TABLE IF EXISTS `{$table}`");
        $mysqli->query($definition['create']);
        $mysqli->query('SET FOREIGN_KEY_CHECKS = 1');

        schema_sync_columns($mysqli, $table, $definition['columns'] ?? []);
        schema_sync_indexes($mysqli, $table, $definition['indexes'] ?? []);

        foreach ($definition['seed'] ?? [] as $seedSql) {
            $mysqli->query($seedSql);
        }
    }
}

if (!function_exists('schema_bootstrap')) {
    function schema_bootstrap(mysqli $mysqli): array
    {
        $definitions = [
            'admin' => [
                'create' => "CREATE TABLE `admin` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `username` VARCHAR(100) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `admin_username_unique` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'columns' => [
                    'username' => 'VARCHAR(100) NOT NULL',
                    'password' => 'VARCHAR(255) NOT NULL',
                    'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                ],
                'indexes' => [
                    'admin_username_unique' => "ALTER TABLE `admin` ADD UNIQUE KEY `admin_username_unique` (`username`)",
                ],
                'seed' => [
                    "INSERT INTO `admin` (`username`, `password`)
                     SELECT 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "'
                     WHERE NOT EXISTS (SELECT 1 FROM `admin` WHERE `username` = 'admin')",
                ],
            ],
            'userregistration' => [
                'create' => "CREATE TABLE `userregistration` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `regNo` VARCHAR(80) NOT NULL,
                    `firstName` VARCHAR(120) NOT NULL,
                    `middleName` VARCHAR(120) DEFAULT NULL,
                    `lastName` VARCHAR(120) NOT NULL,
                    `gender` VARCHAR(20) NOT NULL,
                    `contactNo` VARCHAR(20) NOT NULL,
                    `email` VARCHAR(150) NOT NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `postingDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `userregistration_email_unique` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'columns' => [
                    'regNo' => 'VARCHAR(80) NOT NULL',
                    'firstName' => 'VARCHAR(120) NOT NULL',
                    'middleName' => 'VARCHAR(120) DEFAULT NULL',
                    'lastName' => 'VARCHAR(120) NOT NULL',
                    'gender' => 'VARCHAR(20) NOT NULL',
                    'contactNo' => 'VARCHAR(20) NOT NULL',
                    'email' => 'VARCHAR(150) NOT NULL',
                    'password' => 'VARCHAR(255) NOT NULL',
                    'postingDate' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                ],
                'indexes' => [
                    'userregistration_email_unique' => "ALTER TABLE `userregistration` ADD UNIQUE KEY `userregistration_email_unique` (`email`)",
                ],
            ],
            'rooms' => [
                'create' => "CREATE TABLE `rooms` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `room_no` VARCHAR(20) NOT NULL,
                    `seater` INT NOT NULL DEFAULT 0,
                    `fees` DECIMAL(10,2) NOT NULL DEFAULT 0,
                    `posting_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `rooms_room_no_unique` (`room_no`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'columns' => [
                    'room_no' => 'VARCHAR(20) NOT NULL',
                    'seater' => 'INT NOT NULL DEFAULT 0',
                    'fees' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
                    'posting_date' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                ],
                'indexes' => [
                    'rooms_room_no_unique' => "ALTER TABLE `rooms` ADD UNIQUE KEY `rooms_room_no_unique` (`room_no`)",
                ],
            ],
            'courses' => [
                'create' => "CREATE TABLE `courses` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `course_fn` VARCHAR(255) DEFAULT NULL,
                    `course_sn` VARCHAR(100) DEFAULT NULL,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'columns' => [
                    'course_fn' => 'VARCHAR(255) DEFAULT NULL',
                    'course_sn' => 'VARCHAR(100) DEFAULT NULL',
                    'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'registration' => [
                'create' => "CREATE TABLE `registration` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `roomno` VARCHAR(20) NOT NULL,
                    `seater` VARCHAR(20) NOT NULL,
                    `feespm` VARCHAR(20) NOT NULL,
                    `foodstatus` VARCHAR(20) NOT NULL,
                    `stayfrom` DATE NOT NULL,
                    `duration` VARCHAR(20) NOT NULL,
                    `course` VARCHAR(255) NOT NULL,
                    `regno` VARCHAR(80) NOT NULL,
                    `firstName` VARCHAR(120) NOT NULL,
                    `middleName` VARCHAR(120) DEFAULT NULL,
                    `lastName` VARCHAR(120) NOT NULL,
                    `gender` VARCHAR(20) NOT NULL,
                    `contactno` VARCHAR(20) NOT NULL,
                    `emailid` VARCHAR(150) NOT NULL,
                    `egycontactno` VARCHAR(20) NOT NULL,
                    `guardianName` VARCHAR(150) NOT NULL,
                    `guardianRelation` VARCHAR(80) NOT NULL,
                    `guardianContactno` VARCHAR(20) NOT NULL,
                    `corresAddress` TEXT NOT NULL,
                    `corresCity` VARCHAR(100) NOT NULL,
                    `corresPincode` VARCHAR(20) NOT NULL,
                    `pmntAddress` TEXT NOT NULL,
                    `pmntCity` VARCHAR(100) NOT NULL,
                    `pmntPincode` VARCHAR(20) NOT NULL,
                    `postingDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'columns' => [
                    'roomno' => 'VARCHAR(20) NOT NULL',
                    'seater' => 'VARCHAR(20) NOT NULL',
                    'feespm' => 'VARCHAR(20) NOT NULL',
                    'foodstatus' => 'VARCHAR(20) NOT NULL',
                    'stayfrom' => 'DATE NOT NULL',
                    'duration' => 'VARCHAR(20) NOT NULL',
                    'course' => 'VARCHAR(255) NOT NULL',
                    'regno' => 'VARCHAR(80) NOT NULL',
                    'firstName' => 'VARCHAR(120) NOT NULL',
                    'middleName' => 'VARCHAR(120) DEFAULT NULL',
                    'lastName' => 'VARCHAR(120) NOT NULL',
                    'gender' => 'VARCHAR(20) NOT NULL',
                    'contactno' => 'VARCHAR(20) NOT NULL',
                    'emailid' => 'VARCHAR(150) NOT NULL',
                    'egycontactno' => 'VARCHAR(20) NOT NULL',
                    'guardianName' => 'VARCHAR(150) NOT NULL',
                    'guardianRelation' => 'VARCHAR(80) NOT NULL',
                    'guardianContactno' => 'VARCHAR(20) NOT NULL',
                    'corresAddress' => 'TEXT NOT NULL',
                    'corresCity' => 'VARCHAR(100) NOT NULL',
                    'corresPincode' => 'VARCHAR(20) NOT NULL',
                    'pmntAddress' => 'TEXT NOT NULL',
                    'pmntCity' => 'VARCHAR(100) NOT NULL',
                    'pmntPincode' => 'VARCHAR(20) NOT NULL',
                    'postingDate' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
            'userLog' => [
                'create' => "CREATE TABLE `userLog` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `userId` INT DEFAULT NULL,
                    `userEmail` VARCHAR(150) DEFAULT NULL,
                    `userIp` VARCHAR(50) DEFAULT NULL,
                    `city` VARCHAR(100) DEFAULT NULL,
                    `country` VARCHAR(100) DEFAULT NULL,
                    `loginTime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                'columns' => [
                    'userId' => 'INT DEFAULT NULL',
                    'userEmail' => 'VARCHAR(150) DEFAULT NULL',
                    'userIp' => 'VARCHAR(50) DEFAULT NULL',
                    'city' => 'VARCHAR(100) DEFAULT NULL',
                    'country' => 'VARCHAR(100) DEFAULT NULL',
                    'loginTime' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
                ],
            ],
        ];

        $report = [];

        foreach ($definitions as $table => $definition) {
            $status = schema_table_health($mysqli, $table);
            $action = 'verified';

            if (!$status['exists']) {
                schema_recreate_table($mysqli, $table, $definition);
                $action = 'created';
            } elseif (!$status['healthy'] && ($status['errno'] === 1932 || stripos($status['error'], "doesn't exist in engine") !== false)) {
                schema_recreate_table($mysqli, $table, $definition);
                $action = 'recreated';
            } else {
                schema_sync_columns($mysqli, $table, $definition['columns'] ?? []);
                schema_sync_indexes($mysqli, $table, $definition['indexes'] ?? []);

                foreach ($definition['seed'] ?? [] as $seedSql) {
                    $mysqli->query($seedSql);
                }
            }

            $report[] = [
                'table' => $table,
                'action' => $action,
            ];
        }

        return $report;
    }
}
