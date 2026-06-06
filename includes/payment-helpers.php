<?php

require_once(__DIR__ . '/activity-helpers.php');
require_once(__DIR__ . '/notification-helpers.php');
require_once(__DIR__ . '/security-helpers.php');
require_once(__DIR__ . '/room-helpers.php');

if (!function_exists('fetch_student_payments')) {
    function fetch_student_payments(mysqli $mysqli, int $studentId): array
    {
        $payments = [];
        $stmt = $mysqli->prepare(
            "SELECT p.*, r.room_no
             FROM payments p
             LEFT JOIN room_allocations ra ON ra.id = p.allocation_id
             LEFT JOIN rooms r ON r.id = ra.assigned_room_id
             WHERE p.student_id = ?
             ORDER BY p.created_at DESC, p.id DESC"
        );

        if (!$stmt) {
            return $payments;
        }

        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
        }

        $stmt->close();
        return $payments;
    }
}

if (!function_exists('fetch_all_payments')) {
    function fetch_all_payments(mysqli $mysqli): array
    {
        $payments = [];
        $result = $mysqli->query(
            "SELECT p.*, s.registration_number, s.first_name, s.middle_name, s.last_name, r.room_no
             FROM payments p
             INNER JOIN students s ON s.id = p.student_id
             LEFT JOIN room_allocations ra ON ra.id = p.allocation_id
             LEFT JOIN rooms r ON r.id = ra.assigned_room_id
             ORDER BY p.created_at DESC, p.id DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
            $result->close();
        }

        return $payments;
    }
}

if (!function_exists('normalize_payment_month')) {
    function normalize_payment_month(string $paymentMonth): string
    {
        $paymentMonth = normalize_text($paymentMonth);

        if (preg_match('/^\d{4}-\d{2}$/', $paymentMonth) !== 1) {
            return '';
        }

        $date = DateTime::createFromFormat('!Y-m', $paymentMonth);
        if (!$date || $date->format('Y-m') !== $paymentMonth) {
            return '';
        }

        return $paymentMonth;
    }
}

if (!function_exists('payment_exists_for_allocation_month')) {
    function payment_exists_for_allocation_month(mysqli $mysqli, int $allocationId, string $paymentMonth, int $excludePaymentId = 0): bool
    {
        $stmt = $mysqli->prepare(
            "SELECT id
             FROM payments
             WHERE allocation_id = ?
             AND payment_month = ?
             AND status IN ('pending', 'paid')
             AND id <> ?
             LIMIT 1"
        );

        if (!$stmt) {
            return true;
        }

        $stmt->bind_param('isi', $allocationId, $paymentMonth, $excludePaymentId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }
}

if (!function_exists('create_payment_record')) {
    function create_payment_record(mysqli $mysqli, int $studentId, array $data): array
    {
        $allocation = fetch_student_allocation($mysqli, $studentId);
        if (!$allocation) {
            return ['ok' => false, 'errors' => ['general' => 'Payments are enabled only after your room is allocated.']];
        }

        $allocationId = (int) $allocation['id'];
        $amount = (float) ($allocation['monthly_fee'] ?? $allocation['fees'] ?? 0);
        $paymentMonth = normalize_payment_month($data['payment_month'] ?? '');
        $paymentMethod = normalize_text($data['payment_method'] ?? 'upi');
        $transactionReference = normalize_text($data['transaction_reference'] ?? '');
        $remarks = normalize_text($data['remarks'] ?? '');
        $proofPath = normalize_text($data['proof_path'] ?? '');

        $errors = [];
        if ($amount <= 0) {
            $errors['amount'] = 'The allocated monthly fee is not configured correctly. Please contact the admin.';
        }
        if ($paymentMonth === '') {
            $errors['payment_month'] = 'Choose a valid payment month.';
        }
        if (!in_array($paymentMethod, ['cash', 'upi', 'bank_transfer', 'card', 'other'], true)) {
            $errors['payment_method'] = 'Choose a valid payment method.';
        }
        if ($paymentMethod !== 'cash' && $transactionReference === '') {
            $errors['transaction_reference'] = 'Transaction reference is required.';
        }
        if ($paymentMonth !== '' && payment_exists_for_allocation_month($mysqli, $allocationId, $paymentMonth)) {
            $errors['payment_month'] = 'A payment for this allocation and month is already pending or paid.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $receiptNumber = 'RCT-' . date('Ymd') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);

        $stmt = $mysqli->prepare(
            "INSERT INTO payments (
                student_id, allocation_id, amount, payment_month, payment_method, transaction_reference,
                proof_path, receipt_number, status, remarks, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())"
        );

        if (!$stmt) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to record payment.']];
        }

        $stmt->bind_param(
            'iidssssss',
            $studentId,
            $allocationId,
            $amount,
            $paymentMonth,
            $paymentMethod,
            $transactionReference,
            $proofPath,
            $receiptNumber,
            $remarks
        );

        $ok = $stmt->execute();
        $paymentId = (int) $stmt->insert_id;
        $stmt->close();

        if (!$ok) {
            return ['ok' => false, 'errors' => ['general' => 'Unable to record payment.']];
        }

        log_activity($mysqli, 'student', $studentId, 'payment_submitted', 'Student submitted payment reference ' . $transactionReference . '.');
        create_notification($mysqli, 'student', $studentId, 'Payment submitted', 'Your payment record has been submitted for verification.', '../student/payments.php');
        create_notifications_for_role($mysqli, 'admin', 'New payment submitted', 'A student payment needs verification.', '../admin/payments.php');

        return ['ok' => true, 'errors' => [], 'payment_id' => $paymentId];
    }
}

if (!function_exists('update_payment_status')) {
    function update_payment_status(mysqli $mysqli, int $paymentId, string $status, string $remarks = ''): bool
    {
        if (!in_array($status, ['pending', 'paid', 'failed'], true)) {
            return false;
        }

        $lookup = $mysqli->prepare("SELECT student_id, allocation_id, payment_month FROM payments WHERE id = ? LIMIT 1");
        if (!$lookup) {
            return false;
        }

        $lookup->bind_param('i', $paymentId);
        $lookup->execute();
        $result = $lookup->get_result();
        $payment = $result ? $result->fetch_assoc() : null;
        $lookup->close();

        if (!$payment) {
            return false;
        }

        if (in_array($status, ['pending', 'paid'], true) && !empty($payment['allocation_id']) && !empty($payment['payment_month'])) {
            if (payment_exists_for_allocation_month($mysqli, (int) $payment['allocation_id'], (string) $payment['payment_month'], $paymentId)) {
                return false;
            }
        }

        $stmt = $mysqli->prepare(
            "UPDATE payments
             SET status = ?, remarks = ?, paid_at = CASE WHEN ? = 'paid' THEN NOW() ELSE paid_at END, updated_at = NOW()
             WHERE id = ?"
        );

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sssi', $status, $remarks, $status, $paymentId);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            return false;
        }

        create_notification(
            $mysqli,
            'student',
            (int) $payment['student_id'],
            'Payment ' . ucfirst($status),
            'Your payment status has been updated to ' . ucfirst($status) . '.',
            '../student/payments.php'
        );

        return true;
    }
}

if (!function_exists('sum_paid_fees')) {
    function sum_paid_fees(mysqli $mysqli): float
    {
        $result = $mysqli->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'paid'");
        if ($result) {
            $row = $result->fetch_assoc();
            $result->close();
            return (float) ($row['total'] ?? 0);
        }

        return 0.0;
    }
}
