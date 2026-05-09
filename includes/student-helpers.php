<?php

if (!function_exists('fetch_registered_student_by_email')) {
    function fetch_registered_student_by_email(mysqli $mysqli, string $email): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id, regNo, firstName, middleName, lastName, gender, contactNo, email
             FROM userregistration
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $student ?: null;
    }
}

if (!function_exists('fetch_registered_student_by_regno')) {
    function fetch_registered_student_by_regno(mysqli $mysqli, string $regNo): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id, regNo, firstName, middleName, lastName, gender, contactNo, email
             FROM userregistration
             WHERE regNo = ?
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $regNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $student ?: null;
    }
}

if (!function_exists('find_existing_booking_for_student')) {
    function find_existing_booking_for_student(mysqli $mysqli, string $email, string $regNo): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id, roomno, emailid, regno
             FROM registration
             WHERE emailid = ? OR regno = ?
             ORDER BY id DESC
             LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ss', $email, $regNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $booking ?: null;
    }
}

if (!function_exists('normalize_contact_value')) {
    function normalize_contact_value(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
