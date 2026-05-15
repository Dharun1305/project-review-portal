<?php
// ============================================================
// student.php — Student Dashboard
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

$student_id = $_SESSION['user_id'];
$msg = '';
$msg_type = '';

// ── Fetch student's projects ──
$projects = $pdo->prepare(
    "SELECT p.*, r.comments, r.reviewed_at, u.name AS faculty_name
     FROM projects p
     LEFT JOIN reviews r ON r.project_id = p.id
     LEFT JOIN users   u ON u.id = r.faculty_id
     WHERE p.student_id = ?
     ORDER BY p.submitted_at DESC"
);
$projects->execute([$student_id]);
$projects = $projects->fetchAll();

// ── Counts ──
$total = count($projects);
$pending = count(array_filter($projects, fn($p) => $p['status'] === 'pending'));
$approved = count(array_filter($projects, fn($p) => $p['status'] === 'approved'));
$revision = count(array_filter($projects, fn($p) => $p['status'] === 'revision'));

// ── Handle Upload ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    $title = trim($_POST['title'] ?? '');
    $file = $_FILES['document'] ?? null;

    if (empty($title)) {
        $msg = 'Project title is required.';
        $msg_type = 'danger';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Please select a valid file to upload.';
        $msg_type = 'danger';
    } else {
        $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $max_size = 10 * 1024 * 1024; // 10 MB

        if (!in_array($ext, $allowed_ext)) {
            $msg = 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, ZIP.';
            $msg_type = 'danger';
        } elseif ($file['size'] > $max_size) {
            $msg = 'File size must be under 10 MB.';
            $msg_type = 'danger';
        } else {
            $filename = 'proj_' . $student_id . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("INSERT INTO projects (student_id, title, document_path, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$student_id, $title, $filename]);
                $msg = 'Project submitted successfully! Faculty will review it soon.';
                $msg_type = 'success';
                header('Location: student.php?msg=uploaded');
                exit;
            } else {
                $msg = 'Failed to save file. Check server permissions on uploads/ folder.';
                $msg_type = 'danger';
            }
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'uploaded') {
    $msg = 'Project submitted successfully! Faculty will review it soon.';
    $msg_type = 'success';
    // Re-fetch after redirect
    $projects_stmt = $pdo->prepare(
        "SELECT p.*, r.comments, r.reviewed_at, u.name AS faculty_name
         FROM projects p
         LEFT JOIN reviews r ON r.project_id = p.id
         LEFT JOIN users   u ON u.id = r.faculty_id
         WHERE p.student_id = ?
         ORDER BY p.submitted_at DESC"
    );
    $projects_stmt->execute([$student_id]);
    $projects = $projects_stmt->fetchAll();
    $total = count($projects);
    $pending = count(array_filter($projects, fn($p) => $p['status'] === 'pending'));
    $approved = count(array_filter($projects, fn($p) => $p['status'] === 'approved'));
    $revision = count(array_filter($projects, fn($p) => $p['status'] === 'revision'));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — Project Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="student.php" class="navbar-brand">🎓 <span>Project Portal</span></a>
        <ul class="navbar-nav">
            <li><a href="student.php" class="active">Dashboard</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>
        </ul>
    </nav>

    <div class="page-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2>Welcome,
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </h2>
                <p>Student Dashboard — Submit and track your project submissions</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Total Submissions</span>
                <span class="stat-value">
                    <?= $total ?>
                </span>
            </div>
            <div class="stat-card warning">
                <span class="stat-label">Pending Review</span>
                <span class="stat-value">
                    <?= $pending ?>
                </span>
            </div>
            <div class="stat-card success">
                <span class="stat-label">Approved</span>
                <span class="stat-value">
                    <?= $approved ?>
                </span>
            </div>
            <div class="stat-card danger">
                <span class="stat-label">Needs Revision</span>
                <span class="stat-value">
                    <?= $revision ?>
                </span>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg_type === 'success' ? '✅' : '⚠️' ?>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="card">
            <div class="card-header">📤 Submit New Project</div>
            <div class="card-body">
                <form method="POST" action="student.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label>Project Title</label>
                        <input type="text" name="title" class="form-control"
                            placeholder="e.g. Library Management System" required>
                    </div>
                    <div class="form-group">
                        <label>Project File</label>
                        <label class="upload-zone" id="uploadZone">
                            <input type="file" name="document" id="fileInput" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip"
                                required>
                            <div id="uploadIcon" style="font-size:2rem;">📄</div>
                            <div id="uploadText" style="font-weight:600;margin-top:.5rem;">Click to browse or drag &
                                drop</div>
                            <div class="upload-hint">Supported: PDF, DOC, DOCX, PPT, PPTX, ZIP · Max 10 MB</div>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Project</button>
                </form>
            </div>
        </div>

        <!-- Submissions Table -->
        <div class="card">
            <div class="card-header">📋 My Submissions</div>
            <div class="card-body" style="padding:0">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Title</th>
                                <th>Submitted At</th>
                                <th>Status</th>
                                <th>Faculty Remarks</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:var(--gray-600);padding:2rem;">
                                        No projects submitted yet. Use the form above to get started!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $i => $p): ?>
                                    <tr>
                                        <td>
                                            <?= $i + 1 ?>
                                        </td>
                                        <td><strong>
                                                <?= htmlspecialchars($p['title']) ?>
                                            </strong></td>
                                        <td>
                                            <?= date('d M Y, h:i A', strtotime($p['submitted_at'])) ?>
                                        </td>
                                        <td><span class="badge badge-<?= $p['status'] ?>">
                                                <?= ucfirst($p['status']) ?>
                                            </span></td>
                                        <td>
                                            <?php if ($p['comments']): ?>
                                                <div class="remarks-box">
                                                    <?= nl2br(htmlspecialchars($p['comments'])) ?>
                                                    <div class="faculty-name">
                                                        —
                                                        <?= htmlspecialchars($p['faculty_name'] ?? 'Faculty') ?>
                                                        ·
                                                        <?= date('d M Y', strtotime($p['reviewed_at'])) ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:var(--gray-600);font-size:.85rem;">Awaiting review…</span>
                                            <?php endif; ?>
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

    <script>
        // File upload preview
        document.getElementById('fileInput').addEventListener('change', function () {
            const name = this.files[0] ? this.files[0].name : 'Click to browse or drag & drop';
            document.getElementById('uploadText').textContent = name;
            document.getElementById('uploadIcon').textContent = this.files[0] ? '✅' : '📄';
        });
        // Drag and drop
        const zone = document.getElementById('uploadZone');
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = 'var(--accent)'; });
        zone.addEventListener('dragleave', () => { zone.style.borderColor = 'var(--gray-200)'; });
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.style.borderColor = 'var(--gray-200)';
            const files = e.dataTransfer.files;
            if (files.length) {
                document.getElementById('fileInput').files = files;
                document.getElementById('uploadText').textContent = files[0].name;
                document.getElementById('uploadIcon').textContent = '✅';
            }
        });
    </script>
</body>

</html>