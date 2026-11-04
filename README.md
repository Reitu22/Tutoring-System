# TutorSpace: Tutoring Booking & Student Management System

A PHP 8+ / MySQL tutoring management application for Mathematics and Data Science. The rebuild uses plain CSS and vanilla JavaScript.

## Requirements
- PHP 8.1+ with PDO MySQL enabled
- MySQL 8.0+ or a compatible MariaDB version
- Apache (XAMPP is suitable)

## Setup
1. Copy this folder into your web server document root (for XAMPP, `htdocs`).
2. Import `database/tutoring_system.sql` in phpMyAdmin if you have not already imported the database. The script creates the `tutoring_system` database and its tables. Do not import it again into a database containing existing tables unless you have backed up your data.
3. Open `php/config/database.php` and set the MySQL username and password for your local environment. The default values are `root` with an empty password, which are common XAMPP defaults but may differ on your machine.
4. Start Apache and MySQL, then visit `http://localhost/Tutoring-System/`.
5. Register a student account through the application.

## Create the first administrator
For a fresh installation, generate a password hash using PHP:

```bash
php -r "echo password_hash('ChangeThisPassword!', PASSWORD_DEFAULT), PHP_EOL;"
```

Insert an administrator using the generated hash (replace the hash below):

```sql
INSERT INTO users (full_name, email, password_hash, role)
VALUES ('System Administrator', 'admin@example.com', 'PASTE_GENERATED_HASH_HERE', 'administrator');
```

Sign in with that email and password. Use the administration page to create tutor accounts and assign their programme expertise. Share tutor credentials securely and ask tutors to change their temporary password through an account-management feature before production use; password-change functionality is not included in this demo build.

## Main workflows
- Students register and sign in. Passwords are stored using PHP's `password_hash()`.
- Students submit a simulated payment request for a programme. No card or banking information is collected.
- An administrator approves or rejects pending payment requests. Approval unlocks session booking for that programme.
- Tutors are created by administrators and assigned programme expertise.
- Tutors publish weekday availability slots.
- Students book a weekday time slot. The system finds a tutor with matching programme expertise and availability, then checks for overlapping confirmed sessions before assigning the booking.
- Notifications are created for the student, assigned tutor and administrators when a booking is made.

## Notes and limitations
- Payment is a demonstration workflow, not a real payment gateway or proof-of-payment verification integration.
- The first matching available tutor is assigned automatically.
- The application assumes the SQL schema from `database/tutoring_system.sql`.
- Configure HTTPS, secure database credentials, backups, CSRF protection, rate limiting, audit logging and a proper password reset/change flow before any real deployment. This is an academic/demo application, not a production-ready service.
- The application enforces weekday, tutor expertise, availability and overlap rules in PHP. If multiple users can book concurrently in a real deployment, strengthen concurrency control with database transactions/locking and add appropriate automated tests.
