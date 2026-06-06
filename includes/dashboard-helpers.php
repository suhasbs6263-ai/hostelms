<?php

require_once(__DIR__ . '/student-helpers.php');
require_once(__DIR__ . '/room-helpers.php');
require_once(__DIR__ . '/payment-helpers.php');
require_once(__DIR__ . '/complaint-helpers.php');
require_once(__DIR__ . '/notification-helpers.php');
require_once(__DIR__ . '/activity-helpers.php');

if (!function_exists('dashboard_summary')) {
    function dashboard_summary(mysqli $mysqli): array
    {
        $summary = [
            'total_students' => 0,
            'approved_students' => 0,
            'pending_students' => 0,
            'allocated_students' => 0,
            'total_rooms' => 0,
            'occupied_beds' => 0,
            'available_beds' => 0,
            'complaints_pending' => 0,
            'fees_collected' => 0.0,
        ];

        $result = $mysqli->query("SELECT COUNT(*) AS total FROM students");
        if ($result) {
            $summary['total_students'] = (int) ($result->fetch_assoc()['total'] ?? 0);
            $result->close();
        }

        $summary['approved_students'] = count_students_by_status($mysqli, 'approved');
        $summary['pending_students'] = count_students_by_status($mysqli, 'pending');
        $summary['complaints_pending'] = count_complaints_by_status($mysqli, 'pending');
        $summary['fees_collected'] = sum_paid_fees($mysqli);

        $allocationResult = $mysqli->query("SELECT COUNT(*) AS total FROM room_allocations WHERE status = 'allocated'");
        if ($allocationResult) {
            $summary['allocated_students'] = (int) ($allocationResult->fetch_assoc()['total'] ?? 0);
            $allocationResult->close();
        }

        foreach (fetch_rooms($mysqli, true) as $room) {
            $summary['total_rooms']++;
            $summary['occupied_beds'] += (int) $room['occupied_beds'];
            $summary['available_beds'] += (int) $room['available_beds'];
        }

        return $summary;
    }
}

if (!function_exists('dashboard_chart_data')) {
    function dashboard_chart_data(mysqli $mysqli): array
    {
        $chart = [
            'student_status' => [
                'pending' => count_students_by_status($mysqli, 'pending'),
                'approved' => count_students_by_status($mysqli, 'approved'),
                'rejected' => count_students_by_status($mysqli, 'rejected'),
            ],
            'complaints' => [
                'pending' => count_complaints_by_status($mysqli, 'pending'),
                'in_progress' => count_complaints_by_status($mysqli, 'in_progress'),
                'resolved' => count_complaints_by_status($mysqli, 'resolved'),
            ],
        ];

        return $chart;
    }
}
