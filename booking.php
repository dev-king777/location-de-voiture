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

function isValidDate($date) {
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

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
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;
    $vehicle_id = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $damage_log = trim($_POST['damage_log'] ?? '');

    if ($client_id <= 0 || $vehicle_id <= 0) {
        $error = "Please select a valid client and vehicle.";
    } elseif (!isValidDate($start_date) || !isValidDate($end_date)) {
        $error = "Please provide valid start and end dates.";
    } elseif ($end_date < $start_date) {
        $error = "End date must be the same as or after start date.";
    } else {
        // Validate client
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE id = ?");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch();

        if (!$client) {
            $error = "Invalid client selected.";
        } else {
            // Get Vehicle Rate + Status
            $stmt = $pdo->prepare("SELECT daily_rate, status FROM vehicles WHERE id = ?");
            $stmt->execute([$vehicle_id]);
            $vehicle = $stmt->fetch();

            if (!$vehicle) {
                $error = "Invalid vehicle selected.";
            } elseif ($vehicle['status'] === 'Maintenance') {
                $error = "This vehicle is under maintenance.";
            } else {
                $days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
                if ($days < 1) $days = 1; // Minimum 1 day
                $total_amount = $days * $vehicle['daily_rate'];

                if (isAvailable($pdo, $vehicle_id, $start_date, $end_date)) {
                    try {
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare("
                            INSERT INTO reservations
                                (client_id, vehicle_id, start_date, end_date, total_amount, damage_log, status)
                            VALUES
                                (?, ?, ?, ?, ?, ?, 'Active')
                        ");
                        $stmt->execute([$client_id, $vehicle_id, $start_date, $end_date, $total_amount, $damage_log]);

                        // Update vehicle status to Rented if start date is today
                        if ($start_date <= date('Y-m-d') && $end_date >= date('Y-m-d')) {
                            $pdo->prepare("UPDATE vehicles SET status = 'Rented' WHERE id = ?")->execute([$vehicle_id]);
                        }

                        $reservation_id = $pdo->lastInsertId();
                        $pdo->commit();
                        header("Location: invoice_gen.php?id=" . $reservation_id);
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Failed to create reservation.";
                    }
                } else {
                    $error = "Vehicle is not available for the selected dates.";
                }
            }
        }
    }
}

// Fetch Clients and Vehicles for the form
$clients = $pdo->query("SELECT id, full_name FROM clients ORDER BY full_name")->fetchAll();
$vehicles = $pdo->query("SELECT id, brand, model, daily_rate FROM vehicles WHERE status != 'Maintenance' ORDER BY brand")->fetchAll();

$preselected_vehicle = $_GET['vehicle_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking - SpeedyRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body class="dashboard-body">
<div class="bg-shape shape-a" aria-hidden="true"></div>
<div class="bg-shape shape-b" aria-hidden="true"></div>

<div class="dashboard-shell">
    <aside class="side-panel">
        <div class="brand-wrap">
            <p class="brand-kicker">Control hub</p>
            <h1>Location De Vouture Draga4Life</h1>
            <p class="brand-copy">Command center for bookings, maintenance and fleet performance.</p>
        </div>

        <nav class="main-nav">
            <a href="index.php"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="booking.php" class="active"><i class="fas fa-calendar-plus"></i> New Booking</a>
            <a href="invoice_gen.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a>
            <a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </nav>

        <div class="side-note">
            <p>Today</p>
            <strong><?= date('d M') ?></strong>
            <small>Plan reservations with confidence</small>
        </div>
    </aside>

    <main class="content-panel">
        <header class="glass-card page-head reveal">
            <div>
                <p class="head-kicker">Reservation workspace</p>
                <h2>New Booking</h2>
                <p class="head-meta">
                    Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    <span><?= date('l, d M Y') ?></span>
                </p>
            </div>
            <div class="quick-actions">
                <a href="index.php" class="btn action-btn ghost"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
            </div>
        </header>

        <section class="glass-card section-card reveal" style="--delay: 0.08s;">
            <div class="section-head">
                <h3><i class="fas fa-calendar-check"></i> Reservation Details</h3>
                <p>Fill in the client, vehicle, and dates to confirm the booking.</p>
            </div>

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
                                <option value="<?= $c['id'] ?>" <?= isset($client_id) && (int)$client_id === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small><a href="#" class="text-decoration-none text-primary">+ Add New Client</a></small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">Select Vehicle</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= (($preselected_vehicle == $v['id']) || (isset($vehicle_id) && (int)$vehicle_id === (int)$v['id'])) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['brand'] . ' ' . $v['model'] . ' - $' . $v['daily_rate'] . '/day') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($start_date ?? '') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($end_date ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Digital Damage Log (Check-in)</label>
                    <textarea name="damage_log" class="form-control" rows="3" placeholder="Note existing scratches, fuel level, etc..."><?= htmlspecialchars($damage_log ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn action-btn ghost">Cancel</a>
                    <button type="submit" class="btn action-btn"><i class="fas fa-check-circle me-2"></i> Confirm Booking</button>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
    (function () {
        const revealElements = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            revealElements.forEach((el) => el.classList.add('in-view'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        revealElements.forEach((el) => observer.observe(el));
    })();
</script>
</body>
</html>
