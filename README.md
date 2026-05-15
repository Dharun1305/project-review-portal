# project-review-portal

# 🎓 College Project Submission & Review Portal

A web-based system for managing college project submissions, faculty reviews, and admin oversight — built with **PHP**, **MySQL**, and **HTML/CSS/JS**.

## ✨ Features

### 🔐 Authentication
- Unified login page with role selection (Student / Faculty / Admin)
- Secure bcrypt password hashing
- Session-based access control & session fixation protection

### 🎓 Student
- Upload project files (PDF, DOC, DOCX, PPT, PPTX, ZIP — max 10 MB)
- Drag-and-drop file upload interface
- Track submission status: **Pending** / **Needs Revision** / **Approved**
- Read faculty remarks/comments

### 👨‍🏫 Faculty
- View list of assigned students
- Download submitted project files
- Open modal to **update project status** and **submit feedback**
- Review history preserved per project

### ⚙️ Admin
- **Add Students / Faculty** with hashed passwords
- **Assign** students to faculty via dropdown; remove assignments
- View all registered users (students & faculty lists)
- **Global view** of all submitted projects with download links

---

## 📁 Project Structure

```
project_portal/
├── index.php               ← Redirects to login
├── login.php               ← Unified role-based login
├── logout.php              ← Session destroy
├── student.php             ← Student dashboard
├── faculty.php             ← Faculty dashboard
├── admin.php               ← Admin dashboard
├── db_connect.php          ← PDO database connection
├── schema.sql              ← Database schema + seed data
├── setup.bat               ← One-time database setup script
├── run.bat                 ← Start XAMPP services + open browser
├── requirements.txt        ← System requirements
├── assets/
│   └── css/
│       └── style.css       ← Custom blue & white theme
└── uploads/
    └── .htaccess           ← Security: blocks PHP execution
```

---

## ⚙️ Requirements

See [`requirements.txt`](requirements.txt) for the full list.

**Quick summary:**
- XAMPP (Apache 2.4+, MySQL 5.7+ / MariaDB 10.4+)
- PHP 7.4 or higher
- Modern browser (Chrome, Firefox, Edge)
- Windows OS (for `.bat` scripts)

---

## 🚀 Installation

### Option A — Automatic (Recommended)

1. **Copy** the `project_portal/` folder to `C:\xampp\htdocs\`
2. **Double-click `setup.bat`** — this will:
   - Connect to MySQL
   - Create the `project_portal` database
   - Import all tables and seed the admin user
3. **Double-click `run.bat`** to start services and open the app

### Option B — Manual

1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
3. Click **Import** → choose `schema.sql` → click **Go**
4. Visit `http://localhost/project_portal/`

---

## 🖥️ Usage

| Role | URL | Action |
|------|-----|--------|
| Admin | `/admin.php` | Add users, assign faculty-student pairs, view all projects |
| Faculty | `/faculty.php` | Review submissions, download files, submit feedback |
| Student | `/student.php` | Upload projects, track status, read remarks |

**Workflow:**
1. Admin adds a Student and a Faculty member
2. Admin assigns the Student to the Faculty
3. Student logs in and uploads their project
4. Faculty logs in, downloads the file, reviews it, submits remarks + status
5. Student sees updated status and remarks on their dashboard

---

## 🔑 Default Credentials

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@portal.com` | `password` |

> ⚠️ Change the admin password immediately after first login via phpMyAdmin.

---

## 🔒 Security Notes

- Passwords stored as **bcrypt** hashes (via PHP `password_hash`)
- All database queries use **PDO prepared statements** (SQL injection safe)
- `uploads/.htaccess` blocks PHP execution inside the uploads directory
- Every page validates session role — wrong role → redirected to login
- `session_regenerate_id(true)` used on login to prevent session fixation

---

## 🛠️ Customization

**Change DB credentials** → edit `db_connect.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       // ← add your password here
define('DB_NAME', 'project_portal');
```

**Change upload size limit** → edit `student.php`:
```php
$max_size = 10 * 1024 * 1024;   // ← change 10 to your MB limit
```

---

## 📄 License

This project is developed for educational purposes as a college submission.
