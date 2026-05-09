<?php

if (!function_exists('get_table_columns_meta')) {
    function get_table_columns_meta(mysqli $mysqli, string $table): array
    {
        $columns = [];
        $result = $mysqli->query("SHOW COLUMNS FROM `{$table}`");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[$row['Field']] = $row;
            }
        }

        return $columns;
    }
}

if (!function_exists('find_matching_column')) {
    function find_matching_column(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (isset($columns[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('ensure_courses_schema')) {
    function ensure_courses_schema(mysqli $mysqli): array
    {
        $mysqli->query(
            "CREATE TABLE IF NOT EXISTS courses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_fn VARCHAR(255) DEFAULT NULL,
                course_sn VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $columnsMeta = get_table_columns_meta($mysqli, 'courses');
        $columns = array_keys($columnsMeta);

        if (!in_array('course_fn', $columns, true)) {
            $mysqli->query("ALTER TABLE courses ADD COLUMN course_fn VARCHAR(255) DEFAULT NULL");
            $columns[] = 'course_fn';
        }

        if (!in_array('course_sn', $columns, true)) {
            $mysqli->query("ALTER TABLE courses ADD COLUMN course_sn VARCHAR(100) DEFAULT NULL");
            $columns[] = 'course_sn';
        }

        return $columns;
    }
}

if (!function_exists('resolve_course_schema')) {
    function resolve_course_schema(mysqli $mysqli): array
    {
        ensure_courses_schema($mysqli);
        $columnsMeta = get_table_columns_meta($mysqli, 'courses');

        $fullColumn = find_matching_column($columnsMeta, [
            'course_fn',
            'course_full_name',
            'coursefullname',
            'courseFullName',
            'course_name',
            'courseName',
            'full_name',
            'fullname',
            'name',
            'title',
            'course',
        ]);

        $shortColumn = find_matching_column($columnsMeta, [
            'course_sn',
            'course_short_name',
            'courseShortName',
            'course_code',
            'courseCode',
            'short_name',
            'shortname',
            'short',
            'code',
        ]);

        return [
            'columns' => $columnsMeta,
            'full' => $fullColumn ?? 'course_fn',
            'short' => $shortColumn ?? 'course_sn',
        ];
    }
}

if (!function_exists('build_temporal_default_value')) {
    function build_temporal_default_value(string $type): string
    {
        if (str_contains($type, 'datetime') || str_contains($type, 'timestamp')) {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/\bdate\b/', $type)) {
            return date('Y-m-d');
        }

        if (preg_match('/\btime\b/', $type)) {
            return date('H:i:s');
        }

        if (str_contains($type, 'year')) {
            return date('Y');
        }

        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('build_enum_default_value')) {
    function build_enum_default_value(string $type): string
    {
        if (preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches) && !empty($matches[1][0])) {
            return stripcslashes($matches[1][0]);
        }

        return '';
    }
}

if (!function_exists('build_default_value_for_column')) {
    function build_default_value_for_column(array $meta, string $courseFull, string $courseShort)
    {
        $field = strtolower($meta['Field']);
        $type = strtolower($meta['Type']);

        if (str_contains($field, 'short') || str_contains($field, 'code') || str_contains($field, '_sn')) {
            return $courseShort;
        }

        if (str_contains($field, 'name') || str_contains($field, 'title') || str_contains($field, 'course')) {
            return $courseFull;
        }

        if (str_contains($field, 'status')) {
            return '1';
        }

        if (str_contains($field, 'date') || str_contains($field, 'time')) {
            return build_temporal_default_value($type);
        }

        if (str_contains($type, 'enum(') || str_contains($type, 'set(')) {
            return build_enum_default_value($type);
        }

        if (str_contains($type, 'int') || str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
            return 0;
        }

        return '';
    }
}

if (!function_exists('course_exists')) {
    function course_exists(mysqli $mysqli, string $courseFull, string $courseShort): bool
    {
        $schema = resolve_course_schema($mysqli);
        $fullColumn = $schema['full'];
        $shortColumn = $schema['short'];

        $sql = "SELECT 1 FROM `courses` WHERE `{$fullColumn}` = ? OR `{$shortColumn}` = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ss', $courseFull, $courseShort);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result && $result->fetch_row();
        $stmt->close();

        return (bool) $exists;
    }
}

if (!function_exists('insert_course_record')) {
    function insert_course_record(mysqli $mysqli, string $courseFull, string $courseShort): array
    {
        $schema = resolve_course_schema($mysqli);
        $columnsMeta = $schema['columns'];
        $fullColumn = $schema['full'];
        $shortColumn = $schema['short'];

        if (course_exists($mysqli, $courseFull, $courseShort)) {
            return ['ok' => true, 'error' => '', 'duplicate' => true];
        }

        $insertValues = [];

        foreach ($columnsMeta as $name => $meta) {
            $extra = strtolower($meta['Extra']);
            $defaultValue = $meta['Default'];
            $nullable = strtoupper($meta['Null']) === 'YES';

            if (str_contains($extra, 'auto_increment')) {
                continue;
            }

            if ($name === $fullColumn) {
                $insertValues[$name] = $courseFull;
                continue;
            }

            if ($name === $shortColumn) {
                $insertValues[$name] = $courseShort;
                continue;
            }

            if ($defaultValue !== null || $nullable) {
                continue;
            }

            $insertValues[$name] = build_default_value_for_column($meta, $courseFull, $courseShort);
        }

        if (!isset($insertValues[$fullColumn])) {
            $insertValues[$fullColumn] = $courseFull;
        }

        if (!isset($insertValues[$shortColumn])) {
            $insertValues[$shortColumn] = $courseShort;
        }

        $columnNames = array_keys($insertValues);
        $placeholders = implode(', ', array_fill(0, count($columnNames), '?'));
        $columnsSql = implode(', ', array_map(fn ($name) => "`{$name}`", $columnNames));
        $sql = "INSERT INTO `courses` ({$columnsSql}) VALUES ({$placeholders})";

        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            return ['ok' => false, 'error' => $mysqli->error];
        }

        $types = str_repeat('s', count($columnNames));
        $values = array_map(static fn ($value) => (string) $value, array_values($insertValues));
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return ['ok' => $success, 'error' => $error, 'duplicate' => false];
    }
}

if (!function_exists('fetch_courses')) {
    function fetch_courses(mysqli $mysqli): array
    {
        $schema = resolve_course_schema($mysqli);
        $fullColumn = $schema['full'];
        $shortColumn = $schema['short'];

        $courses = [];
        $result = $mysqli->query("SELECT `{$fullColumn}` AS course_fn, `{$shortColumn}` AS course_sn FROM `courses` ORDER BY `{$fullColumn}` ASC");

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }

        return $courses;
    }
}
