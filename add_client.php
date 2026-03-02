<?php
// add_client.php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $license_number = trim($_POST['license_number']);
    $address = trim($_POST['address']);

    if (empty($full_name) || empty($phone) || empty($license_number)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if license number already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE license_number = ?");
        $stmt->execute([$license_number]);
        if ($stmt->fetchColumn() > 0) {
            $error = "A client with this license number already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clients (full_name, phone, license_number, address) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $phone, $license_number, $address])) {
                $success = "Client added successfully!";
                header("Refresh: 2; url=booking.php");
            } else {
                $error = "Failed to add client.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Client - SpeedyRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg mx-auto" style="max-width: 600px;">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="fas fa-user-plus me-2"></i> Add New Client</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <p>Redirecting to booking page...</p>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">License Number *</label>
                    <input type="text" name="license_number" class="form-control" required value="<?= htmlspecialchars($_POST['license_number'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="booking.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i> Save Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
