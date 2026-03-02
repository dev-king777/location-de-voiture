<?php
// login.php
session_start();
require 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Location De Vouture Draga4Life</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
</head>
<body class="login-page">
    <div class="ambient-glow glow-1" aria-hidden="true"></div>
    <div class="ambient-glow glow-2" aria-hidden="true"></div>

    <main class="page-shell">
        <section class="brand-panel">
            <p class="eyebrow">Fleet intelligence platform</p>
            <h1>
                <span class="title-line">Location De Vouture</span>
                <span class="title-line brand-animated">Draga4Life</span>
            </h1>
            <p class="brand-copy">
                Manage reservations, monitor returns, and keep every vehicle mission-ready from one professional dashboard.
            </p>
            <div class="feature-list">
                <span>Live fleet status</span>
                <span>Billing automation</span>
                <span>Maintenance alerts</span>
            </div>
            <div class="mini-kpis">
                <article>
                    <strong>24/7</strong>
                    <small>Operation visibility</small>
                </article>
                <article>
                    <strong>Fast</strong>
                    <small>Booking workflow</small>
                </article>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <h2>Sign in</h2>
                <p class="helper">Use your manager credentials to continue.</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="field-wrap">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars($username ?? '') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="field-wrap">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-login">Access Dashboard</button>
                </form>

                <p class="legal-note">Protected access for authorized staff only.</p>
            </div>
        </section>
    </main>
</body>
</html>
