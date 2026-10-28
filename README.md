# Hope Academy — Student Academic Records & Attendance Management System

A web-based School Management System built using PHP, MySQL, and Bootstrap 5. Designed to centralize academic operations, class management, student enrollment, attendance tracking, and grade evaluation with role-based access control.

---

## Table of Contents
- [Project Overview](#project-overview)
- [Problem Statement](#problem-statement)
- [System Architecture](#system-architecture)
- [Technology Stack](#technology-stack)
- [Functional Requirements](#functional-requirements)
- [Database Schema & Models](#database-schema--models)
- [User Roles & Access Control](#user-roles--access-control)
- [Installation & Setup](#installation--setup)
- [Security & Validation](#security--validation)
- [Default Testing Accounts](#default-testing-accounts)
- [Future Enhancements](#future-enhancements)

---

## Project Overview

**Hope Academy School Management System** simplifies institutional operations by replacing paper registers and fragmented spreadsheets with a centralized, web-based database system. The system focuses on authorized workflows for lecturers, administrators, and students.

### Scope of the System
- **User Authentication**: Secure registration and login with encrypted passwords and role-based redirects.
- **Class Management**: Create and organize classes linked to authorized lecturers.
- **Student Enrollment**: Register students with unique registration numbers and link them to classes (`class_students`).
- **Attendance Management**: Record daily attendance (`Present`, `Absent`, `Late`) and review historical attendance grouped by date.
- **Academic Records & Grading**: Input, update, and auto-evaluate student marks and letter grades.
- **Student Search**: Search student profiles by name, registration number, or email address.

---

## Problem Statement

Educational institutions often manage student information, daily attendance, and academic performance through paper registers, separate spreadsheets, or disjointed files. These approaches:
- Make real-time record retrieval slow and cumbersome.
- Increase the risk of duplicate, conflicting, or lost records.
- Make historical attendance tracking and academic performance trend analysis difficult to audit.

This system provides a single, structured MySQL relational database with a responsive web interface to ensure consistent and secure record-keeping.

---

## System Architecture

```
+-------------------------------------------------------------+
|                     Client Web Browser                      |
|                (HTML5, CSS3, JavaScript, Bootstrap 5)        |
+-------------------------------------------------------------+
                              |
                        HTTP Requests
                              v
+-------------------------------------------------------------+
|                    Apache Web Server / PHP                  |
|  - Authentication & Session Guard (lecturer_auth.php)       |
|  - CSRF Protection & Input Validation                       |
|  - Controller Logic (Modules: Auth, Lecturer, Admin)        |
+-------------------------------------------------------------+
                              |
                    PDO Prepared Statements
                              v
+-------------------------------------------------------------+
|                  MySQL / MariaDB Database                   |
|  Tables: users, classes, students, class_students,          |
|          attendance, academic_records, fees                 |
+-------------------------------------------------------------+
```

---

## Technology Stack

| Layer | Technology | Details |
| :--- | :--- | :--- |
| **Front End** | HTML5, CSS3, JavaScript, Bootstrap 5.3.3 | Responsive UI with Bootstrap Icons |
| **Back End** | PHP (8.x) | Native server-side business logic and session management |
| **Database** | MySQL / MariaDB | Relational database with foreign key constraints |
| **Database Driver** | PDO (PHP Data Objects) | Prepared statements preventing SQL injection |
| **Server** | Apache (XAMPP) | Local/Production web server environment |

---

## Functional Requirements

| ID | Requirement | Implemented Feature | Module Path |
| :--- | :--- | :--- | :--- |
| **FR1** | **User Registration** | Register new users with full name, email, username, password, and role. | `modules/auth/register.php` |
| **FR2** | **Authentication** | Secure credential authentication using `password_verify` and session creation. | `modules/auth/login.php` |
| **FR3** | **Lecturer Session Guard** | Restrict lecturer pages to verified lecturer sessions. | `includes/lecturer_auth.php` |
| **FR4** | **Class Management** | Lecturers can create classes and view their assigned classes. | `modules/lecturer/classes.php` |
| **FR5** | **Student Enrollment** | Associate students with classes via many-to-many relationship. | `modules/lecturer/classes.php` |
| **FR6** | **Student Search** | Search student profiles by name, email, or registration number. | `modules/lecturer/students.php` |
| **FR7** | **Attendance Marking** | Mark daily attendance (`Present`, `Absent`, `Late`) for a selected class. | `modules/lecturer/attendance.php` |
| **FR8** | **Attendance History** | View historical attendance grouped by date with lecturer ownership check. | `modules/lecturer/view_attendance_history.php` |
| **FR9** | **Academic Records** | Input, update, and auto-evaluate student marks and letter grades. | `modules/lecturer/grades.php` |
| **FR10** | **Access Control** | Prevent unauthorized access to protected routes with 302 redirects. | `includes/lecturer_auth.php` |

---

## Database Schema & Models

The database is named **`naini`** and consists of the following key relational tables:

1. **`users`**
   - `id` (INT, Primary Key, Auto Increment)
   - `fullname` (VARCHAR(100))
   - `email` (VARCHAR(100), UNIQUE)
   - `username` (VARCHAR(50), UNIQUE)
   - `password` (VARCHAR(255), Hashed with BCRYPT)
   - `role` (ENUM: `'admin'`, `'lecturer'`, `'student'`)
   - `created_at` (TIMESTAMP)

2. **`classes`**
   - `id` (INT, Primary Key, Auto Increment)
   - `class_name` (VARCHAR(100))
   - `course_code` (VARCHAR(50))
   - `lecturer_id` (INT, Foreign Key &rarr; `users.id`)
   - `created_at` (TIMESTAMP)

3. **`students`**
   - `id` (INT, Primary Key, Auto Increment)
   - `fullname` (VARCHAR(100))
   - `email` (VARCHAR(100), UNIQUE)
   - `registration_number` (VARCHAR(50), UNIQUE)
   - `program`, `year_of_study`, `current_semester`, `admission_date`
   - `created_at` (TIMESTAMP)

4. **`class_students`** (Enrollment Junction)
   - `draw_id` (INT, Primary Key, Auto Increment)
   - `class_id` (INT, Foreign Key &rarr; `classes.id`)
   - `student_id` (INT, Foreign Key &rarr; `students.id`)
   - `assigned_at` (TIMESTAMP)
   - `UNIQUE (class_id, student_id)`

5. **`attendance`**
   - `id` (INT, Primary Key, Auto Increment)
   - `class_id` (INT, Foreign Key &rarr; `classes.id`)
   - `student_id` (INT, Foreign Key &rarr; `students.id`)
   - `attendance_date` (DATE)
   - `status` (ENUM: `'present'`, `'absent'`, `'late'`)
   - `recorded_at` (TIMESTAMP)

6. **`academic_records`**
   - `id` (INT, Primary Key, Auto Increment)
   - `class_id` (INT, Foreign Key &rarr; `classes.id`)
   - `student_id` (INT, Foreign Key &rarr; `students.id`)
   - `marks` (DECIMAL(5,2))
   - `grade` (VARCHAR(5))
   - `created_at`, `updated_at` (TIMESTAMP)

---

## User Roles & Access Control

- **Lecturer**:
  - Manages assigned classes.
  - Registers/enrolls students into classes.
  - Marks daily attendance and reviews historical records.
  - Enters and updates grades/academic records.
- **Admin**:
  - System oversight, lecturer accounts, overall stats, revenue/fee overview.
- **Student**:
  - View individual academic records, class enrollments, and status.

---

## Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (Apache 2.4+, PHP 8.x, MySQL / MariaDB 10.4+)
- Web browser (Chrome, Edge, Firefox)

### Step-by-Step Setup

1. **Clone the repository into your web root (`htdocs`)**:
   ```bash
   cd c:\xampp\htdocs
   git clone https://github.com/Jackson3673/The-School-management-System.git
   ```

2. **Start Apache & MySQL**:
   - Open the **XAMPP Control Panel**.
   - Click **Start** for both **Apache** and **MySQL**.

3. **Set Up the Database**:
   - Open [phpMyAdmin](http://localhost/phpmyadmin) or MySQL CLI.
   - Run the initial setup script via browser:
     ```
     http://localhost/The-School-management-System/setup_database.php
     ```
     *(Or import `setup_db.sql` into a database named `naini`)*.

4. **Verify Database Configuration**:
   - Check `config/db.php`:
     ```php
     $host = 'localhost';
     $db   = 'naini';
     $user = 'root';
     $pass = '';
     ```

5. **Access the Application**:
   - Open your browser to:
     ```
     http://localhost/The-School-management-System/
     ```

---

## Security & Validation

- **Password Encryption**: All user passwords are encrypted using `password_hash()` with `PASSWORD_BCRYPT` / `PASSWORD_DEFAULT`. Plaintext passwords are never saved.
- **SQL Injection Prevention**: All queries utilize PDO prepared statements with parameter binding.
- **CSRF Defense**: Sensitive forms (attendance, grade entry, class creation) generate and validate CSRF tokens via `includes/csrf.php`.
- **Strict Role Verification**: Lecturers can only access, view, and modify records for classes that belong to their account ID.
- **Duplicate Prevention**: Registration verifies username and email uniqueness individually before database insertion.

---

## Default Testing Accounts

| Role | Username | Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin@hopeacademy.com` | `admin123` |
| **Lecturer** | `Test` | `test@gmail.com` | `test123` |
| **Student** | `BIT/2026/0001` | `metrotechenterprise@gmail.com` | *Registered* |

---

## Project Structure

```
The-School-management-System/
├── assets/                  # CSS stylesheets and UI assets
├── config/
│   └── db.php               # Central PDO database connection
├── includes/
│   ├── csrf.php             # CSRF token generation & validation
│   ├── footer.php           # Common footer
│   ├── header.php           # Bootstrap 5 & HTML head components
│   ├── lecturer_auth.php    # Session security guard for lecturers
│   └── navbar.php           # Dynamic navigation bar with role detection
├── modules/
│   ├── auth/
│   │   ├── login.php        # User authentication
│   │   ├── logout.php       # Session termination
│   │   └── register.php     # Account creation with validation
│   ├── dashboard/
│   │   ├── admin.php        # Administrative overview
│   │   └── student.php      # Student portal
│   └── lecturer/
│       ├── attendance.php   # Daily attendance marking
│       ├── classes.php      # Class creation & student enrollment
│       ├── dashboard.php    # Lecturer hub
│       ├── grades.php       # Grade and marks entry
│       ├── students.php     # Student search and directory
│       └── view_attendance_history.php # Historical attendance
├── index.php                # Landing page
├── setup_database.php       # Automated schema generator
└── README.md                # Project documentation
```

---

## License & Credits
- **Project**: Hope Academy Student Information & Academic Records System
- **Documentation**: Adapted from `PRISCILLA DOCUMENTATION.docx`
- **Developed for**: Educational Institution Management
