<?php
// ============================================================
// admin.php — Admin Dashboard
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

$msg = '';
$msg_type = '';

// ── Handle Actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Student or Faculty
    if ($action === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? '';

        if (empty($name) || empty($email) || empty($password) || !in_array($role, ['student', 'faculty'])) {
            $msg = 'All fields are required and role must be student or faculty.';
            $msg_type = 'danger';
        } else {
            // Check email uniqueness
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $msg = 'A user with this email already exists.';
                $msg_type = 'danger';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")
                    ->execute([$name, $email, $hashed, $role]);
                $msg = ucfirst($role) . " '{$name}' added successfully.";
                $msg_type = 'success';
            }
        }
    }

    // Assign Student to Faculty
    elseif ($action === 'assign') {
        $faculty_id = (int) ($_POST['faculty_id'] ?? 0);
        $student_id = (int) ($_POST['student_id'] ?? 0);

        if (!$faculty_id || !$student_id) {
            $msg = 'Please select both a faculty and a student.';
            $msg_type = 'danger';
        } else {
            try {
                $pdo->prepare("INSERT INTO faculty_assignments (faculty_id, student_id) VALUES (?, ?)")
                    ->execute([$faculty_id, $student_id]);
                $msg = 'Assignment created successfully.';
                $msg_type = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $msg = 'This student is already assigned to the selected faculty.';
                    $msg_type = 'danger';
                } else {
                    $msg = 'Assignment failed: ' . $e->getMessage();
                    $msg_type = 'danger';
                }
            }
        }
    }

    // Remove Assignment
    elseif ($action === 'remove_assignment') {
        $assign_id = (int) ($_POST['assign_id'] ?? 0);
        if ($assign_id) {
            $pdo->prepare("DELETE FROM faculty_assignments WHERE id = ?")->execute([$assign_id]);
            $msg = 'Assignment removed.';
            $msg_type = 'success';
        }
    }
}

// ── Fetch Data ──
$students = $pdo->query("SELECT id, name, email FROM users WHERE role='student' ORDER BY name")->fetchAll();
$faculties = $pdo->query("SELECT id, name, email FROM users WHERE role='faculty' ORDER BY name")->fetchAll();
$all_projects = $pdo->query(
    "SELECT p.*, u.name AS student_name, u.email AS student_email,
            r.comments, r.reviewed_at,
            fu.name AS faculty_name
     FROM projects p
     JOIN users u ON u.id = p.student_id
     LEFT JOIN reviews r ON r.project_id = p.id
     LEFT JOIN users fu ON fu.id = r.faculty_id
     ORDER BY p.submitted_at DESC"
)->fetchAll();

$assignments = $pdo->query(
    "SELECT fa.id, fu.name AS faculty_name, s.name AS student_name
     FROM faculty_assignments fa
     JOIN users fu ON fu.id = fa.faculty_id
     JOIN users s  ON s.id  = fa.student_id
     ORDER BY fu.name, s.name"
)->fetchAll();

// Stats
$total_students = count($students);
$total_faculties = count($faculties);
$total_projects = count($all_projects);
$total_pending = count(array_filter($all_projects, fn($p) => $p['status'] === 'pending'));
$total_approved = count(array_filter($all_projects, fn($p) => $p['status'] === 'approved'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Project Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tab-bar {
            display: flex;
            gap: .5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: .55rem 1.25rem;
            border: 2px solid var(--primary);
            border-radius: 7px;
            background: #fff;
            color: var(--primary);
            font-weight: 600;
            font-size: .875rem;
            cursor: pointer;
            transition: .2s;
        }

        .tab-btn.active,
        .tab-btn:hover {
            background: var(--primary);
            color: #fff;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        @media(max-width:640px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="admin.php" class="navbar-brand">🎓 <span>Project Portal</span></a>
        <ul class="navbar-nav">
            <li><a href="admin.php" class="active">Dashboard</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>
        </ul>
    </nav>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>Admin Dashboard</h2>
                <p>Manage users, assignments, and view all project submissions</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Students</span>
                <span class="stat-value">
                    <?= $total_students ?>
                </span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Faculty</span>
                <span class="stat-value">
                    <?= $total_faculties ?>
                </span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Total Projects</span>
                <span class="stat-value">
                    <?= $total_projects ?>
                </span>
            </div>
            <div class="stat-card warning">
                <span class="stat-label">Pending Review</span>
                <span class="stat-value">
                    <?= $total_pending ?>
                </span>
            </div>
            <div class="stat-card success">
                <span class="stat-label">Approved</span>
                <span class="stat-value">
                    <?= $total_approved ?>
                </span>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg_type === 'success' ? '✅' : '⚠️' ?>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('tab-add')">➕ Add User</button>
            <button class="tab-btn" onclick="switchTab('tab-assign')">🔗 Assign Students</button>
            <button class="tab-btn" onclick="switchTab('tab-users')">👥 User List</button>
            <button class="tab-btn" onclick="switchTab('tab-projects')">📁 All Projects</button>
        </div>

        <!-- ── TAB: Add User ── -->
        <div class="tab-panel active" id="tab-add">
            <div class="card">
                <div class="card-header">➕ Add Student / Faculty</div>
                <div class="card-body">
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="add_user">
                        <div class="two-col">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Priya Sharma"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="" disabled selected>— Select Role —</option>
                                    <option value="student">Student</option>
                                    <option value="faculty">Faculty</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="user@college.edu"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Set initial password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── TAB: Assign Students ── -->
        <div class="tab-panel" id="tab-assign">
            <div class="card">
                <div class="card-header">🔗 Assign Student to Faculty</div>
                <div class="card-body">
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action" value="assign">
                        <div class="two-col">
                            <div class="form-group">
                                <label>Select Faculty</label>
                                <select name="faculty_id" class="form-control" required>
                                    <option value="" disabled selected>— Choose Faculty —</option>
                                    <?php foreach ($faculties as $f): ?>
                                        <option value="<?= $f['id'] ?>">
                                            <?= htmlspecialchars($f['name']) ?> (
                                            <?= htmlspecialchars($f['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Student</label>
                                <select name="student_id" class="form-control" required>
                                    <option value="" disabled selected>— Choose Student —</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= htmlspecialchars($s['name']) ?> (
                                            <?= htmlspecialchars($s['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Assign</button>
                    </form>
                </div>
            </div>

            <!-- Current Assignments -->
            <div class="card">
                <div class="card-header">📋 Current Assignments</div>
                <div class="card-body" style="padding:0">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Faculty</th>
                                    <th>Student</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignments)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center;padding:1.5rem;color:var(--gray-600);">No
                                            assignments yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($assignments as $i => $a): ?>
                                        <tr>
                                            <td>
                                                <?= $i + 1 ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($a['faculty_name']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($a['student_name']) ?>
                                            </td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('Remove this assignment?')">
                                                    <input type="hidden" name="action" value="remove_assignment">
                                                    <input type="hidden" name="assign_id" value="<?= $a['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">✕ Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB: User List ── -->
        <div class="tab-panel" id="tab-users">
            <div class="two-col">
                <div class="card">
                    <div class="card-header">🎓 Students (
                        <?= $total_students ?>)
                    </div>
                    <div class="card-body" style="padding:0">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;padding:1rem;color:var(--gray-600);">No
                                                students yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $i => $s): ?>
                                            <tr>
                                                <td>
                                                    <?= $i + 1 ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($s['name']) ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($s['email']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">👨‍🏫 Faculty (
                        <?= $total_faculties ?>)
                    </div>
                    <div class="card-body" style="padding:0">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($faculties)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center;padding:1rem;color:var(--gray-600);">No
                                                faculty yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($faculties as $i => $f): ?>
                                            <tr>
                                                <td>
                                                    <?= $i + 1 ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($f['name']) ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($f['email']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TAB: All Projects ── -->
        <div class="tab-panel" id="tab-projects">
            <div class="card">
                <div class="card-header">📁 All Submitted Projects (
                    <?= $total_projects ?>)
                </div>
                <div class="card-body" style="padding:0">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Project Title</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Reviewer</th>
                                    <th>File</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_projects)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-600);">No
                                            projects submitted yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_projects as $i => $p): ?>
                                        <tr>
                                            <td>
                                                <?= $i + 1 ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:500;">
                                                    <?= htmlspecialchars($p['student_name']) ?>
                                                </div>
                                                <div style="font-size:.78rem;color:var(--gray-600);">
                                                    <?= htmlspecialchars($p['student_email']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($p['title']) ?>
                                            </td>
                                            <td>
                                                <?= date('d M Y', strtotime($p['submitted_at'])) ?>
                                            </td>
                                            <td><span class="badge badge-<?= $p['status'] ?>">
                                                    <?= ucfirst($p['status']) ?>
                                                </span></td>
                                            <td>
                                                <?= htmlspecialchars($p['faculty_name'] ?? '—') ?>
                                            </td>
                                            <td>
                                                <a href="uploads/<?= urlencode($p['document_path']) ?>"
                                                    class="btn btn-secondary btn-sm" download>⬇ Download</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(id) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>

</html>