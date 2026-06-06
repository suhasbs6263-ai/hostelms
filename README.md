# Hostel Management System

Production-style plain PHP + MySQL hostel management system for XAMPP, upgraded from a college CRUD project into a role-based workflow application.

## Core workflow

1. Student creates an account.
2. Student account stays `pending`.
3. Admin reviews and approves or rejects the account.
4. Approved students can log in.
5. Student submits a room request.
6. Admin allocates a room.
7. Student submits fee payments.
8. Student raises complaints.
9. Admin or Warden updates complaint progress and resolution.

## Roles

- `Admin`
  - review student approvals
  - manage rooms
  - manage warden accounts
  - allocate rooms
  - verify payments
  - manage complaints
  - monitor analytics
- `Student`
  - register
  - log in after approval
  - update profile
  - apply for room
  - view allocation
  - submit payments
  - raise complaints
- `Warden`
  - log in
  - monitor and resolve complaints
  - receive operational notifications

## Security improvements

- `password_hash()` and `password_verify()`
- prepared statements for business-critical queries
- CSRF protection on major forms
- session fingerprinting and timeout handling
- role-based access checks
- duplicate email and registration number prevention

## Major modules

- student approval system
- room capacity and allocation system
- payments with receipt numbers and status tracking
- complaints with priority, remarks, and status flow
- warden account management and complaint assignment
- notifications for workflow events
- audit log / activity tracking

## Local setup

1. Start Apache and MySQL from XAMPP.
2. Open phpMyAdmin and create a database named `hostelms`.
3. Import `hostelms_full_setup.sql`.
4. If upgrading an old project database, also import `hostelms_production_migration.sql`.
5. Open [http://localhost/hostel-management](http://localhost/hostel-management).

## Default accounts

- Admin:
  - username: `admin`
  - password: `admin123`
- Warden:
  - username: `warden`
  - password: `warden123`

## Key folders

- `config/`
  - app configuration
- `includes/`
  - auth, security, helpers, notifications, dashboard logic
- `admin/`
  - admin dashboard and workflow pages
- `student/`
  - student dashboard and self-service pages
- `warden/`
  - warden workflow pages
- `assets/`
  - Bootstrap, UI theme, icons, JS
- `uploads/`
  - profile photos and document uploads (auto-created by config)

## Database files

- `hostelms_full_setup.sql`
  - base schema and compatibility seed
- `hostelms_production_migration.sql`
  - production-style upgrade tables for roles, allocations, payments, complaints, notifications, and logs
  - migrates legacy admins, students, bookings, and login logs into the new workflow tables

## Notes

- The project keeps legacy compatibility where practical, but the main workflow now uses:
  - `students`
  - `admins`
  - `wardens`
  - `rooms`
  - `room_allocations`
  - `payments`
  - `complaints`
  - `notifications`
  - `activity_logs`
