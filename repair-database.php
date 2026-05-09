<?php
require_once(__DIR__ . '/includes/dbconn.php');

$report = $schemaBootstrapReport ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Repair | Hostel Management System</title>
    <link rel="stylesheet" href="assets/css/styles.min.css">
    <style>
        body {
            background: #f6f9fc;
            font-family: Arial, sans-serif;
        }
        .repair-shell {
            max-width: 820px;
            margin: 48px auto;
        }
        .repair-card {
            border: 1px solid #e6ecf4;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(17, 28, 45, 0.06);
        }
        .repair-table td,
        .repair-table th {
            padding: 0.8rem 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container repair-shell">
        <div class="repair-card p-4">
            <h2 class="mb-2">Database Repair Complete</h2>
            <p class="text-muted mb-4">The project checked the required tables and recreated any missing or corrupted tables needed by the hostel system.</p>

            <div class="table-responsive">
                <table class="table repair-table">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['table']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($item['action'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="student/register.php">Open Student Registration</a>
                <a class="btn btn-outline-primary" href="admin/register-student.php">Open Admin Register Student</a>
                <a class="btn btn-outline-secondary" href="index.php">Student Login</a>
            </div>
        </div>
    </div>
</body>
</html>
