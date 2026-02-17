<?php
// booking.php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Check Availability Function
function isAvailable($pdo, $vehicle_id, $start_date, $end_date) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations 
                           WHERE vehicle_id = ? 
                           AND status = 'Active'
                           AND (
                               (start_date BETWEEN ? AND ?) OR 
                               (end_date BETWEEN ? AND ?) OR
                               (start_date <= ? AND end_date >= ?)
                           )");
    $stmt->execute([$vehicle_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    return $stmt->fetchColumn() == 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $vehicle_id = $_POST['vehicle_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $damage_log = $_POST['damage_log'];

    // Get Vehicle Rate
    $stmt = $pdo->prepare("SELECT daily_rate FROM vehicles WHERE id = ?");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        $error = "Invalid vehicle selected.";
    } else {
        $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
        if ($days < 1) $days = 1; // Minimum 1 day
        $total_amount = $days * $vehicle['daily_rate'];

        if (isAvailable($pdo, $vehicle_id, $start_date, $end_date)) {
            $stmt = $pdo->prepare("INSERT INTO reservations (client_id, vehicle_id, start_date, end_date, total_amount, damage_log, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
            if ($stmt->execute([$client_id, $vehicle_id, $start_date, $end_date, $total_amount, $damage_log])) {
                // Update vehicle status to Rented if start date is today
                if ($start_date <= date('Y-m-d') && $end_date >= date('Y-m-d')) {
                     $pdo->prepare("UPDATE vehicles SET status = 'Rented' WHERE id = ?")->execute([$vehicle_id]);
                }
                $reservation_id = $pdo->lastInsertId();
                header("Location: invoice_gen.php?id=" . $reservation_id);
                exit;
            } else {
                $error = "Failed to create reservation.";
            }
        } else {
            $error = "Vehicle is not available for the selected dates.";
        }
    }
}

// Fetch Clients and Vehicles for the form
$clients = $pdo->query("SELECT id, full_name, user_id FROM clients ORDER BY full_name")->fetchAll();
$vehicles = $pdo->query("SELECT id, brand, model, daily_rate FROM vehicles WHERE status != 'Maintenance' ORDER BY brand")->fetchAll();

$preselected_vehicle = $_GET['vehicle_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Booking - SpeedyRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="fas fa-calendar-check me-2"></i> New Reservation</h3>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small><a href="#" class="text-decoration-none">+ Add New Client</a></small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= $preselected_vehicle == $v['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['brand'] . ' ' . $v['model'] . ' - $' . $v['daily_rate'] . '/day') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Digital Damage Log (Check-in)</label>
                    <textarea name="damage_log" class="form-control" rows="3" placeholder="Note existing scratches, fuel level, etc..."></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle me-2"></i> Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
