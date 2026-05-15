<?php
// ============================================================
// faculty.php — Faculty Dashboard
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'faculty') {
    header('Location: login.php');
    exit;
}
require_once 'db_connect.php';

$faculty_id = $_SESSION['user_id'];
$msg = '';
$msg_type = '';

// ── Handle review submission (status + comments) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    $project_id = (int) ($_POST['project_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $comments = trim($_POST['comments'] ?? '');

    $allowed_statuses = ['pending', 'revision', 'approved'];
    if ($project_id && in_array($status, $allowed_statuses)) {
        // Update project status
        $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?")->execute([$status, $project_id]);

        // Upsert review
        $existing = $pdo->prepare("SELECT id FROM reviews WHERE project_id = ? AND faculty_id = ?")->execute([$project_id, $faculty_id]);
        $rev = $pdo->prepare("SELECT id FROM reviews WHERE project_id = ? AND faculty_id = ?")->execute([$project_id, $faculty_id]);
        $rev_row = $pdo->query("SELECT id FROM reviews WHERE project_id = {$project_id} AND faculty_id = {$faculty_id} LIMIT 1")->fetch();

        if ($rev_row) {
            $pdo->prepare("UPDATE reviews SET comments = ?, reviewed_at = NOW() WHERE id = ?")->execute([$comments, $rev_row['id']]);
        } else {
            $pdo->prepare("INSERT INTO reviews (project_id, faculty_id, comments) VALUES (?, ?, ?)")->execute([$project_id, $faculty_id, $comments]);
        }

        $msg = 'Review submitted successfully.';
        $msg_type = 'success';
    } else {
        $msg = 'Invalid review data.';
        $msg_type = 'danger';
    }
}

// ── Fetch assigned students + their projects ──
$assigned = $pdo->prepare(
    "SELECT u.id AS student_id, u.name AS student_name, u.email AS student_email
     FROM faculty_assignments fa
     JOIN users u ON u.id = fa.student_id
     WHERE fa.faculty_id = ?
     ORDER BY u.name"
);
$assigned->execute([$faculty_id]);
$assigned_students = $assigned->fetchAll();

$student_ids = array_column($assigned_students, 'student_id');

$projects = [];
if (!empty($student_ids)) {
    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, u.name AS student_name,
                r.comments, r.reviewed_at
         FROM projects p
         JOIN users u ON u.id = p.student_id
         LEFT JOIN reviews r ON r.project_id = p.id AND r.faculty_id = {$faculty_id}
         WHERE p.student_id IN ($placeholders)
         ORDER BY p.submitted_at DESC"
    );
    $stmt->execute($student_ids);
    $projects = $stmt->fetchAll();
}

$total_projects = count($projects);
$pending_count = count(array_filter($projects, fn($p) => $p['status'] === 'pending'));
$approved_count = count(array_filter($projects, fn($p) => $p['status'] === 'approved'));
$revision_count = count(array_filter($projects, fn($p) => $p['status'] === 'revision'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard — Project Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        }

        .modal-box h3 {
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .modal-close {
            float: right;
            cursor: pointer;
            font-size: 1.3rem;
            color: var(--gray-600);
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="faculty.php" class="navbar-brand">🎓 <span>Project Portal</span></a>
        <ul class="navbar-nav">
            <li><a href="faculty.php" class="active">Dashboard</a></li>
            <li><a href="logout.php" class="logout">Logout</a></li>
        </ul>
    </nav>

    <div class="page-wrapper">
        <div class="page-header">
            <div>
                <h2>Welcome,
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </h2>
                <p>Faculty Dashboard — Review student project submissions</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Assigned Students</span>
                <span class="stat-value">
                    <?= count($assigned_students) ?>
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
                    <?= $pending_count ?>
                </span>
            </div>
            <div class="stat-card success">
                <span class="stat-label">Approved</span>
                <span class="stat-value">
                    <?= $approved_count ?>
                </span>
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= $msg_type === 'success' ? '✅' : '⚠️' ?>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <!-- Assigned Students -->
        <div class="card">
            <div class="card-header">👥 Assigned Students (
                <?= count($assigned_students) ?>)
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
                            <?php if (empty($assigned_students)): ?>
                                <tr>
                                    <td colspan="3" style="text-align:center;padding:1.5rem;color:var(--gray-600);">No
                                        students assigned yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assigned_students as $i => $s): ?>
                                    <tr>
                                        <td>
                                            <?= $i + 1 ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($s['student_name']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($s['student_email']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Projects to Review -->
        <div class="card">
            <div class="card-header">📋 Project Submissions</div>
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
                                <th>File</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--gray-600);">No
                                        projects submitted by your students yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $i => $p): ?>
                                    <tr>
                                        <td>
                                            <?= $i + 1 ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($p['student_name']) ?>
                                        </td>
                                        <td><strong>
                                                <?= htmlspecialchars($p['title']) ?>
                                            </strong></td>
                                        <td>
                                            <?= date('d M Y', strtotime($p['submitted_at'])) ?>
                                        </td>
                                        <td><span class="badge badge-<?= $p['status'] ?>">
                                                <?= ucfirst($p['status']) ?>
                                            </span></td>
                                        <td>
                                            <a href="uploads/<?= urlencode($p['document_path']) ?>"
                                                class="btn btn-secondary btn-sm" download>⬇ Download</a>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm"
                                                onclick="openReview(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= $p['status'] ?>', `<?= addslashes(htmlspecialchars($p['comments'] ?? '')) ?>`)">
                                                ✏️ Review
                                            </button>
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

    <!-- Review Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-box">
            <span class="modal-close" onclick="closeReview()">✕</span>
            <h3>📝 Review Project</h3>
            <p id="modalProjectTitle" style="color:var(--gray-600);font-size:.9rem;margin-bottom:1.25rem;"></p>
            <form method="POST" action="faculty.php">
                <input type="hidden" name="action" value="review">
                <input type="hidden" name="project_id" id="modalProjectId">
                <div class="form-group">
                    <label>Update Status</label>
                    <select name="status" id="modalStatus" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="revision">Needs Revision</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Comments / Remarks</label>
                    <textarea name="comments" id="modalComments" class="form-control"
                        placeholder="Provide detailed feedback for the student…"></textarea>
                </div>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeReview()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReview(id, title, status, comments) {
            document.getElementById('modalProjectId').value = id;
            document.getElementById('modalProjectTitle').textContent = '📄 ' + title;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalComments').value = comments;
            document.getElementById('reviewModal').classList.add('open');
        }
        function closeReview() {
            document.getElementById('reviewModal').classList.remove('open');
        }
        document.getElementById('reviewModal').addEventListener('click', function (e) {
            if (e.target === this) closeReview();
        });
    </script>
</body>

</html>