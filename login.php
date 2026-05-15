<?php
// ============================================================
// login.php — Unified Login Page
// ============================================================
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $success = 'You have been logged out successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            $redirectMap = [
                'student' => 'student.php',
                'faculty' => 'faculty.php',
                'admin' => 'admin.php',
            ];
            header('Location: ' . $redirectMap[$user['role']]);
            exit;
        } else {
            $error = 'Invalid email, password, or role. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Project Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-outer">
        <div class="login-card">
            <div class="login-header">
                <span class="icon">🎓</span>
                <h1>Project Submission Portal</h1>
                <p>College Project Review System</p>
            </div>
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">⚠️
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">✅
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="role">Login As</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="" disabled selected>— Select Role —</option>
                            <option value="student" <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>
                                >Student</option>
                            <option value="faculty" <?= (($_POST['role'] ?? '') === 'faculty') ? 'selected' : '' ?>
                                >Faculty</option>
                            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="you@college.edu"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                            required>
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="width:100%;justify-content:center;padding:.7rem;">
                        Sign In →
                    </button>
                </form>
                <p style="text-align:center;margin-top:1rem;font-size:.8rem;color:var(--gray-600);">
                    Default Admin: <strong>admin@portal.com</strong> / <strong>password</strong>
                </p>
            </div>
        </div>
    </div>
</body>

</html>