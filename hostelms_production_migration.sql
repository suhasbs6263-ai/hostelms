-- Production-style upgrade for the Hostel Management System
-- Run this after importing hostelms_full_setup.sql if you are upgrading an existing database.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS admins (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wardens (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE rooms
    ADD COLUMN IF NOT EXISTS room_type VARCHAR(50) NOT NULL DEFAULT 'Standard' AFTER room_no,
    ADD COLUMN IF NOT EXISTS capacity INT NOT NULL DEFAULT 0 AFTER room_type,
    ADD COLUMN IF NOT EXISTS occupied_beds INT NOT NULL DEFAULT 0 AFTER capacity,
    ADD COLUMN IF NOT EXISTS status ENUM('available','full','maintenance','inactive') NOT NULL DEFAULT 'available' AFTER fees,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE rooms SET capacity = CASE WHEN capacity <= 0 THEN seater ELSE capacity END;
UPDATE rooms SET seater = CASE WHEN seater <= 0 THEN capacity ELSE seater END;

CREATE TABLE IF NOT EXISTS students (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_allocations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS complaints (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('student','admin','warden') NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('student','admin','warden','system') NOT NULL DEFAULT 'system',
    actor_id INT DEFAULT NULL,
    action VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admins (full_name, username, email, password, status)
SELECT 'System Administrator', 'admin', 'admin@hostel.local', '$2y$10$6HvLo7mFwfXW5nGd1gDijO3U.yltfUeEGB4HOxoWay20FrwVYoVRO', 'active'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');

INSERT INTO wardens (full_name, username, email, phone, password, status)
SELECT 'Hostel Warden', 'warden', 'warden@hostel.local', '9999999999', '$2y$10$OES5PzozkJB0mV7xKgurSuP1kQ0igcmJuYiIgbjBHxb4cS9yxwQwS', 'active'
WHERE NOT EXISTS (SELECT 1 FROM wardens WHERE username = 'warden');

INSERT INTO admins (full_name, username, email, password, status, created_at, updated_at)
SELECT
    CONCAT(UCASE(LEFT(a.username, 1)), SUBSTRING(a.username, 2)),
    a.username,
    CONCAT(a.username, '@hostel.local'),
    a.password,
    'active',
    COALESCE(a.created_at, NOW()),
    COALESCE(a.created_at, NOW())
FROM admin a
LEFT JOIN admins pa ON pa.username = a.username OR pa.email = CONCAT(a.username, '@hostel.local')
WHERE pa.id IS NULL;

INSERT INTO students (
    registration_number,
    first_name,
    middle_name,
    last_name,
    gender,
    phone,
    email,
    password,
    status,
    created_at,
    updated_at
)
SELECT
    ur.regNo,
    ur.firstName,
    ur.middleName,
    ur.lastName,
    ur.gender,
    ur.contactNo,
    ur.email,
    ur.password,
    'approved',
    COALESCE(ur.postingDate, NOW()),
    COALESCE(ur.postingDate, NOW())
FROM userregistration ur
LEFT JOIN students s ON s.email = ur.email OR s.registration_number = ur.regNo
WHERE s.id IS NULL;

INSERT INTO room_allocations (
    student_id,
    preferred_room_id,
    assigned_room_id,
    stay_from,
    duration_months,
    food_status,
    monthly_fee,
    status,
    requested_notes,
    admin_remarks,
    allocated_at,
    created_at,
    updated_at
)
SELECT
    s.id,
    r.id,
    r.id,
    reg.stayfrom,
    CAST(COALESCE(NULLIF(reg.duration, ''), '1') AS UNSIGNED),
    CAST(COALESCE(NULLIF(reg.foodstatus, ''), '0') AS UNSIGNED),
    CAST(COALESCE(NULLIF(reg.feespm, ''), r.fees, '0.00') AS DECIMAL(10,2)),
    'allocated',
    '',
    '',
    COALESCE(reg.postingDate, NOW()),
    COALESCE(reg.postingDate, NOW()),
    COALESCE(reg.postingDate, NOW())
FROM registration reg
INNER JOIN students s ON s.email = reg.emailid OR s.registration_number = reg.regno
INNER JOIN rooms r ON r.room_no = reg.roomno
LEFT JOIN room_allocations ra
    ON ra.student_id = s.id
    AND ra.assigned_room_id = r.id
    AND ra.stay_from = reg.stayfrom
WHERE ra.id IS NULL;

INSERT INTO activity_logs (actor_type, actor_id, action, description, ip_address, created_at)
SELECT
    'student',
    COALESCE(s.id, ul.userId),
    'login',
    CONCAT('Legacy login activity for ', COALESCE(ul.userEmail, 'student')),
    ul.userIp,
    COALESCE(ul.loginTime, NOW())
FROM userLog ul
LEFT JOIN students s ON s.email = ul.userEmail
LEFT JOIN activity_logs al
    ON al.actor_type = 'student'
    AND al.action = 'login'
    AND al.ip_address = ul.userIp
    AND al.created_at = ul.loginTime
WHERE al.id IS NULL;

UPDATE rooms SET occupied_beds = 0;

UPDATE rooms r
LEFT JOIN (
    SELECT assigned_room_id, COUNT(*) AS total
    FROM room_allocations
    WHERE status = 'allocated' AND assigned_room_id IS NOT NULL
    GROUP BY assigned_room_id
) a ON a.assigned_room_id = r.id
SET r.occupied_beds = COALESCE(a.total, 0);

UPDATE rooms
SET status = CASE
    WHEN status = 'maintenance' THEN 'maintenance'
    WHEN capacity <= 0 THEN 'inactive'
    WHEN occupied_beds >= capacity THEN 'full'
    ELSE 'available'
END;

SET FOREIGN_KEY_CHECKS = 1;
