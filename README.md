# Hostel Management System

PHP and MySQL based hostel management project built for XAMPP.

## Features

- Student registration and login
- Admin login and dashboard
- Course management
- Room management with seat-based occupancy
- Hostel booking for students and admins
- Student room details and login activity view

## Local setup

1. Start Apache and MySQL from XAMPP.
2. Open phpMyAdmin and create a database named `hostelms`.
3. Import `hostelms_full_setup.sql`.
4. Open `http://localhost/hostel-management`.

## Default login

- Admin username: `admin`
- Admin password: `admin123`

## Project structure

- `index.php`: student login
- `admin/`: admin pages
- `student/`: student pages
- `includes/`: shared helpers and layout files
- `assets/`: CSS, JS, and images
- `hostelms_full_setup.sql`: database schema and seed data
