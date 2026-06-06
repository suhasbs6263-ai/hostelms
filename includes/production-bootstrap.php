<?php

if (!function_exists('production_table_exists')) {
    function production_table_exists(mysqli $mysqli, string $table): bool
    {
        $safeTable = $mysqli->real_escape_string($table);
        $result = $mysqli->query("SHOW TABLES LIKE '{$safeTable}'");
        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('production_column_exists')) {
    function production_column_exists(mysqli $mysqli, string $table, string $column): bool
    {
        if (!production_table_exists($mysqli, $table)) {
            return false;
        }

        $safeColumn = $mysqli->real_escape_string($column);
        $result = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '{$safeColumn}'");
        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('production_add_column_if_missing')) {
    function production_add_column_if_missing(mysqli $mysqli, string $table, string $column, string $definition): void
    {
        if (!production_column_exists($mysqli, $table, $column)) {
            $mysqli->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}

if (!function_exists('production_add_index_if_missing')) {
    function production_add_index_if_missing(mysqli $mysqli, string $table, string $indexName, string $sql): void
    {
        if (!production_table_exists($mysqli, $table)) {
            return;
        }

        $safeIndex = $mysqli->real_escape_string($indexName);
        $result = $mysqli->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$safeIndex}'");

        if (!$result || $result->num_rows === 0) {
            $mysqli->query($sql);
        }
    }
}

if (!function_exists('ensure_production_core_tables')) {
    function ensure_production_core_tables(mysqli $mysqli): void
    {
        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS admins (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL DEFAULT 'Administrator',
                username VARCHAR(100) NOT NULL,
                email VARCHAR(150) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY admins_username_unique (username),
                UNIQUE KEY admins_email_unique (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS wardens (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL,
                username VARCHAR(100) DEFAULT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(20) DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY wardens_email_unique (email),
                UNIQUE KEY wardens_username_unique (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS students (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                registration_number VARCHAR(80) NOT NULL,
                first_name VARCHAR(120) NOT NULL,
                middle_name VARCHAR(120) DEFAULT NULL,
                last_name VARCHAR(120) NOT NULL,
                gender VARCHAR(20) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                email VARCHAR(150) NOT NULL,
                password VARCHAR(255) NOT NULL,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                course_id INT DEFAULT NULL,
                profile_photo VARCHAR(255) DEFAULT NULL,
                id_document_path VARCHAR(255) DEFAULT NULL,
                emergency_contact VARCHAR(20) DEFAULT NULL,
                guardian_name VARCHAR(150) DEFAULT NULL,
                guardian_relation VARCHAR(80) DEFAULT NULL,
                guardian_phone VARCHAR(20) DEFAULT NULL,
                address_line TEXT DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                pincode VARCHAR(20) DEFAULT NULL,
                approved_by_admin_id INT DEFAULT NULL,
                approval_remarks TEXT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY students_registration_number_unique (registration_number),
                UNIQUE KEY students_email_unique (email),
                CONSTRAINT fk_students_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
                CONSTRAINT fk_students_approved_by FOREIGN KEY (approved_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        if (production_table_exists($mysqli, 'rooms')) {
            production_add_column_if_missing($mysqli, 'rooms', 'room_type', "VARCHAR(50) NOT NULL DEFAULT 'Standard' AFTER `room_no`");
            production_add_column_if_missing($mysqli, 'rooms', 'capacity', "INT NOT NULL DEFAULT 0 AFTER `room_type`");
            production_add_column_if_missing($mysqli, 'rooms', 'occupied_beds', "INT NOT NULL DEFAULT 0 AFTER `capacity`");
            production_add_column_if_missing($mysqli, 'rooms', 'status', "ENUM('available','full','maintenance','inactive') NOT NULL DEFAULT 'available' AFTER `fees`");
            production_add_column_if_missing($mysqli, 'rooms', 'created_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
            production_add_column_if_missing($mysqli, 'rooms', 'updated_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            $mysqli->query("UPDATE rooms SET capacity = CASE WHEN capacity <= 0 THEN seater ELSE capacity END");
            $mysqli->query("UPDATE rooms SET seater = CASE WHEN seater <= 0 THEN capacity ELSE seater END");
        }

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS room_allocations (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                preferred_room_id INT DEFAULT NULL,
                assigned_room_id INT DEFAULT NULL,
                stay_from DATE NOT NULL,
                duration_months INT NOT NULL DEFAULT 1,
                food_status TINYINT(1) NOT NULL DEFAULT 0,
                monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status ENUM('pending','allocated','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
                requested_notes TEXT DEFAULT NULL,
                admin_remarks TEXT DEFAULT NULL,
                allocated_by_admin_id INT DEFAULT NULL,
                allocated_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_allocations_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                CONSTRAINT fk_allocations_preferred_room FOREIGN KEY (preferred_room_id) REFERENCES rooms(id) ON DELETE SET NULL,
                CONSTRAINT fk_allocations_assigned_room FOREIGN KEY (assigned_room_id) REFERENCES rooms(id) ON DELETE SET NULL,
                CONSTRAINT fk_allocations_admin FOREIGN KEY (allocated_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS payments (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                allocation_id INT DEFAULT NULL,
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_month VARCHAR(20) DEFAULT NULL,
                payment_method ENUM('cash','upi','bank_transfer','card','other') NOT NULL DEFAULT 'upi',
                transaction_reference VARCHAR(120) DEFAULT NULL,
                proof_path VARCHAR(255) DEFAULT NULL,
                receipt_number VARCHAR(60) DEFAULT NULL,
                status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
                paid_at DATETIME DEFAULT NULL,
                remarks TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_payments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                CONSTRAINT fk_payments_allocation FOREIGN KEY (allocation_id) REFERENCES room_allocations(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS complaints (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                assigned_warden_id INT DEFAULT NULL,
                subject VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
                status ENUM('pending','in_progress','resolved') NOT NULL DEFAULT 'pending',
                remarks TEXT DEFAULT NULL,
                resolved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_complaints_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                CONSTRAINT fk_complaints_warden FOREIGN KEY (assigned_warden_id) REFERENCES wardens(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_type ENUM('student','admin','warden') NOT NULL,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                actor_type ENUM('student','admin','warden','system') NOT NULL DEFAULT 'system',
                actor_id INT DEFAULT NULL,
                action VARCHAR(120) NOT NULL,
                description TEXT DEFAULT NULL,
                ip_address VARCHAR(50) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}

if (!function_exists('seed_default_admin_and_warden')) {
    function seed_default_admin_and_warden(mysqli $mysqli): void
    {
        $adminCount = 0;
        $result = $mysqli->query("SELECT COUNT(*) AS total FROM admins");
        if ($result) {
            $adminCount = (int) ($result->fetch_assoc()['total'] ?? 0);
            $result->close();
        }

        if ($adminCount === 0) {
            $stmt = $mysqli->prepare("INSERT INTO admins (full_name, username, email, password, status) VALUES (?, ?, ?, ?, 'active')");
            if ($stmt) {
                $fullName = 'System Administrator';
                $username = 'admin';
                $email = 'admin@hostel.local';
                $password = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt->bind_param('ssss', $fullName, $username, $email, $password);
                $stmt->execute();
                $stmt->close();
            }
        }

        $wardenCount = 0;
        $result = $mysqli->query("SELECT COUNT(*) AS total FROM wardens");
        if ($result) {
            $wardenCount = (int) ($result->fetch_assoc()['total'] ?? 0);
            $result->close();
        }

        if ($wardenCount === 0) {
            $stmt = $mysqli->prepare("INSERT INTO wardens (full_name, username, email, phone, password, status) VALUES (?, ?, ?, ?, ?, 'active')");
            if ($stmt) {
                $fullName = 'Hostel Warden';
                $username = 'warden';
                $email = 'warden@hostel.local';
                $phone = '9999999999';
                $password = password_hash('warden123', PASSWORD_DEFAULT);
                $stmt->bind_param('sssss', $fullName, $username, $email, $phone, $password);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

if (!function_exists('migrate_legacy_students')) {
    function migrate_legacy_students(mysqli $mysqli): void
    {
        if (!production_table_exists($mysqli, 'userregistration')) {
            return;
        }

        $result = $mysqli->query("SELECT id, regNo, firstName, middleName, lastName, gender, contactNo, email, password, postingDate FROM userregistration ORDER BY id ASC");
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $stmt = $mysqli->prepare("SELECT id FROM students WHERE email = ? OR registration_number = ? LIMIT 1");
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('ss', $row['email'], $row['regNo']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                continue;
            }

            $insert = $mysqli->prepare(
                "INSERT INTO students (
                    registration_number, first_name, middle_name, last_name, gender, phone, email, password,
                    status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?)"
            );

            if (!$insert) {
                continue;
            }

            $createdAt = $row['postingDate'] ?: date('Y-m-d H:i:s');
            $updatedAt = $createdAt;
            $insert->bind_param(
                'ssssssssss',
                $row['regNo'],
                $row['firstName'],
                $row['middleName'],
                $row['lastName'],
                $row['gender'],
                $row['contactNo'],
                $row['email'],
                $row['password'],
                $createdAt,
                $updatedAt
            );
            $insert->execute();
            $insert->close();
        }

        $result->close();
    }
}

if (!function_exists('migrate_legacy_admins')) {
    function migrate_legacy_admins(mysqli $mysqli): void
    {
        if (!production_table_exists($mysqli, 'admin')) {
            return;
        }

        $result = $mysqli->query("SELECT username, password, created_at FROM admin ORDER BY id ASC");
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $stmt = $mysqli->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('s', $row['username']);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($exists) {
                continue;
            }

            $insert = $mysqli->prepare("INSERT INTO admins (full_name, username, email, password, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', ?, ?)");
            if (!$insert) {
                continue;
            }

            $fullName = ucfirst($row['username']);
            $email = $row['username'] . '@hostel.local';
            $createdAt = $row['created_at'] ?: date('Y-m-d H:i:s');
            $updatedAt = $createdAt;
            $insert->bind_param('ssssss', $fullName, $row['username'], $email, $row['password'], $createdAt, $updatedAt);
            $insert->execute();
            $insert->close();
        }

        $result->close();
    }
}

if (!function_exists('migrate_legacy_allocations')) {
    function migrate_legacy_allocations(mysqli $mysqli): void
    {
        if (!production_table_exists($mysqli, 'registration')) {
            return;
        }

        $result = $mysqli->query("SELECT * FROM registration ORDER BY id ASC");
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $studentStmt = $mysqli->prepare("SELECT id FROM students WHERE email = ? OR registration_number = ? LIMIT 1");
            if (!$studentStmt) {
                continue;
            }

            $studentStmt->bind_param('ss', $row['emailid'], $row['regno']);
            $studentStmt->execute();
            $student = $studentStmt->get_result()->fetch_assoc();
            $studentStmt->close();

            if (!$student) {
                continue;
            }

            $roomStmt = $mysqli->prepare("SELECT id, fees FROM rooms WHERE room_no = ? LIMIT 1");
            if (!$roomStmt) {
                continue;
            }

            $roomStmt->bind_param('s', $row['roomno']);
            $roomStmt->execute();
            $room = $roomStmt->get_result()->fetch_assoc();
            $roomStmt->close();

            if (!$room) {
                continue;
            }

            $existingStmt = $mysqli->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND assigned_room_id = ? AND stay_from = ? LIMIT 1");
            if (!$existingStmt) {
                continue;
            }

            $stayFrom = $row['stayfrom'];
            $existingStmt->bind_param('iis', $student['id'], $room['id'], $stayFrom);
            $existingStmt->execute();
            $existing = $existingStmt->get_result()->fetch_assoc();
            $existingStmt->close();

            if ($existing) {
                continue;
            }

            $insert = $mysqli->prepare(
                "INSERT INTO room_allocations (
                    student_id, preferred_room_id, assigned_room_id, stay_from, duration_months,
                    food_status, monthly_fee, status, requested_notes, admin_remarks, allocated_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'allocated', '', '', ?, ?, ?)"
            );

            if (!$insert) {
                continue;
            }

            $duration = (int) ($row['duration'] ?: 1);
            $foodStatus = (int) ($row['foodstatus'] ?: 0);
            $monthlyFee = (float) ($row['feespm'] ?: $room['fees']);
            $createdAt = $row['postingDate'] ?: date('Y-m-d H:i:s');
            $updatedAt = $createdAt;
            $insert->bind_param(
                'iiisiidsss',
                $student['id'],
                $room['id'],
                $room['id'],
                $stayFrom,
                $duration,
                $foodStatus,
                $monthlyFee,
                $createdAt,
                $createdAt,
                $updatedAt
            );
            $insert->execute();
            $insert->close();
        }

        $result->close();
    }
}

if (!function_exists('migrate_legacy_activity_logs')) {
    function migrate_legacy_activity_logs(mysqli $mysqli): void
    {
        if (!production_table_exists($mysqli, 'userLog')) {
            return;
        }

        $count = 0;
        $result = $mysqli->query("SELECT COUNT(*) AS total FROM activity_logs");
        if ($result) {
            $count = (int) ($result->fetch_assoc()['total'] ?? 0);
            $result->close();
        }

        if ($count > 0) {
            return;
        }

        $result = $mysqli->query("SELECT userId, userEmail, userIp, loginTime FROM userLog ORDER BY id ASC");
        if (!$result) {
            return;
        }

        while ($row = $result->fetch_assoc()) {
            $actorId = (int) ($row['userId'] ?? 0);
            $description = 'Legacy login activity for ' . ($row['userEmail'] ?? 'student');
            $stmt = $mysqli->prepare(
                "INSERT INTO activity_logs (actor_type, actor_id, action, description, ip_address, created_at)
                 VALUES ('student', ?, 'login', ?, ?, ?)"
            );

            if (!$stmt) {
                continue;
            }

            $createdAt = $row['loginTime'] ?: date('Y-m-d H:i:s');
            $stmt->bind_param('isss', $actorId, $description, $row['userIp'], $createdAt);
            $stmt->execute();
            $stmt->close();
        }

        $result->close();
    }
}

if (!function_exists('recalculate_room_occupancy')) {
    function recalculate_room_occupancy(mysqli $mysqli): void
    {
        if (!production_table_exists($mysqli, 'rooms') || !production_table_exists($mysqli, 'room_allocations')) {
            return;
        }

        $mysqli->query("UPDATE rooms SET occupied_beds = 0");
        $mysqli->query(
            "UPDATE rooms r
             LEFT JOIN (
                SELECT assigned_room_id, COUNT(*) AS total
                FROM room_allocations
                WHERE status = 'allocated' AND assigned_room_id IS NOT NULL
                GROUP BY assigned_room_id
             ) a ON a.assigned_room_id = r.id
             SET r.occupied_beds = COALESCE(a.total, 0)"
        );

        $mysqli->query(
            "UPDATE rooms
             SET status = CASE
                WHEN status = 'maintenance' THEN 'maintenance'
                WHEN capacity <= 0 THEN 'inactive'
                WHEN occupied_beds >= capacity THEN 'full'
                ELSE 'available'
             END"
        );
    }
}

if (!function_exists('ensure_production_schema')) {
    function ensure_production_schema(mysqli $mysqli): void
    {
        ensure_production_core_tables($mysqli);
        seed_default_admin_and_warden($mysqli);
        migrate_legacy_admins($mysqli);
        migrate_legacy_students($mysqli);
        migrate_legacy_allocations($mysqli);
        migrate_legacy_activity_logs($mysqli);
        recalculate_room_occupancy($mysqli);
    }
}
