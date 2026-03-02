<?php
// index.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'config/db.php';

// Logic: Fetch KPIs
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$availableVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Available'")->fetchColumn();
$rentedVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Rented'")->fetchColumn();
$maintenanceVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Maintenance'")->fetchColumn();
$activeReservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Active'")->fetchColumn();
$utilizationRate = $totalVehicles > 0 ? (int) round(($rentedVehicles / $totalVehicles) * 100) : 0;
$availabilityRate = $totalVehicles > 0 ? (int) round(($availableVehicles / $totalVehicles) * 100) : 0;

// Logic: Fetch Upcoming Returns (next 7 days)
$upcomingReturns = $pdo->query("
    SELECT r.id, c.full_name, v.brand, v.model, r.end_date 
    FROM reservations r 
    JOIN clients c ON r.client_id = c.id 
    JOIN vehicles v ON r.vehicle_id = v.id 
    WHERE r.status = 'Active' 
    AND r.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY r.end_date ASC
")->fetchAll();

// Logic: Search & Filter
$searchBrand = $_GET['brand'] ?? '';
$searchStatus = $_GET['status'] ?? '';

$query = "SELECT * FROM vehicles WHERE 1=1";
$params = [];

if ($searchBrand) {
    $query .= " AND brand LIKE ?";
    $params[] = "%$searchBrand%";
}
if ($searchStatus) {
    $query .= " AND status = ?";
    $params[] = $searchStatus;
}
$query .= " ORDER BY FIELD(status, 'Available', 'Rented', 'Maintenance'), brand, model";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();
$upcomingCount = count($upcomingReturns);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Location De Vouture Draga4Life</title>
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
            <a href="index.php" class="active"><i class="fas fa-gauge-high"></i> Dashboard</a>
            <a href="booking.php"><i class="fas fa-calendar-plus"></i> New Booking</a>
            <a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a>
            <a href="logout.php"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
        </nav>

        <div class="side-note">
            <p>Utilization</p>
            <strong><?= $utilizationRate ?>%</strong>
            <small><?= (int) $rentedVehicles ?> / <?= (int) $totalVehicles ?> vehicles on rent</small>
        </div>
    </aside>

    <main class="content-panel">
        <header class="glass-card page-head reveal">
            <div>
                <p class="head-kicker">Operations overview</p>
                <h2>Fleet Dashboard</h2>
                <p class="head-meta">
                    Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                    <span><?= date('l, d M Y') ?></span>
                </p>
            </div>
            <div class="quick-actions">
                <a href="booking.php" class="btn action-btn"><i class="fas fa-plus me-2"></i>New Booking</a>
                <a href="invoices.php" class="btn action-btn ghost"><i class="fas fa-receipt me-2"></i>Invoices</a>
            </div>
        </header>

        <section class="kpi-grid">
            <article class="kpi-card reveal kpi-total" style="--delay: 0.05s;">
                <div class="kpi-icon"><i class="fas fa-car-side"></i></div>
                <div>
                    <p>Total Fleet</p>
                    <h3><?= $totalVehicles ?></h3>
                </div>
            </article>
            <article class="kpi-card reveal kpi-available" style="--delay: 0.12s;">
                <div class="kpi-icon"><i class="fas fa-circle-check"></i></div>
                <div>
                    <p>Available Now</p>
                    <h3><?= $availableVehicles ?></h3>
                </div>
            </article>
            <article class="kpi-card reveal kpi-rented" style="--delay: 0.19s;">
                <div class="kpi-icon"><i class="fas fa-key"></i></div>
                <div>
                    <p>Rented Out</p>
                    <h3><?= $rentedVehicles ?></h3>
                </div>
            </article>
            <article class="kpi-card reveal kpi-maintenance" style="--delay: 0.26s;">
                <div class="kpi-icon"><i class="fas fa-screwdriver-wrench"></i></div>
                <div>
                    <p>Maintenance</p>
                    <h3><?= $maintenanceVehicles ?></h3>
                </div>
            </article>
        </section>

        <section class="insight-grid">
            <article class="glass-card insight-card reveal" style="--delay: 0.12s;">
                <h3>Fleet Efficiency</h3>
                <p>Live operating ratios based on the current vehicle status.</p>
                <div class="metric-bar">
                    <span>Availability</span>
                    <strong><?= $availabilityRate ?>%</strong>
                    <div class="bar-track"><div class="bar-fill availability" style="width: <?= $availabilityRate ?>%;"></div></div>
                </div>
                <div class="metric-bar">
                    <span>Utilization</span>
                    <strong><?= $utilizationRate ?>%</strong>
                    <div class="bar-track"><div class="bar-fill utilization" style="width: <?= $utilizationRate ?>%;"></div></div>
                </div>
            </article>
            <article class="glass-card insight-card reveal" style="--delay: 0.18s;">
                <h3>Reservation Pulse</h3>
                <p>Track the most important operational checkpoints at a glance.</p>
                <ul class="pulse-list">
                    <li>
                        <span class="pulse-dot"></span>
                        <div>
                            <strong><?= $activeReservations ?></strong>
                            <small>Active reservations in progress</small>
                        </div>
                    </li>
                    <li>
                        <span class="pulse-dot warning"></span>
                        <div>
                            <strong><?= $upcomingCount ?></strong>
                            <small>Upcoming returns in the next 7 days</small>
                        </div>
                    </li>
                    <li>
                        <span class="pulse-dot danger"></span>
                        <div>
                            <strong><?= $maintenanceVehicles ?></strong>
                            <small>Vehicles that need maintenance priority</small>
                        </div>
                    </li>
                </ul>
            </article>
        </section>

        <section class="glass-card section-card reveal" style="--delay: 0.2s;">
            <div class="section-head">
                <h3><i class="fas fa-clock-rotate-left"></i> Upcoming Returns</h3>
                <p>Expected vehicle returns within 7 days.</p>
            </div>
            <?php if ($upcomingCount > 0): ?>
                <div class="table-wrap">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Vehicle</th>
                                <th>Return Date</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingReturns as $return): ?>
                                <?php $daysLeft = max(0, (int) floor((strtotime($return['end_date']) - strtotime(date('Y-m-d'))) / 86400)); ?>
                                <tr>
                                    <td><?= htmlspecialchars($return['full_name']) ?></td>
                                    <td><?= htmlspecialchars($return['brand'] . ' ' . $return['model']) ?></td>
                                    <td><span class="date-pill"><?= htmlspecialchars($return['end_date']) ?></span></td>
                                    <td><?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-circle-check"></i>
                    <p>No returns scheduled in the next 7 days.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="glass-card section-card reveal" style="--delay: 0.26s;">
            <div class="section-head section-head-split">
                <div>
                    <h3><i class="fas fa-layer-group"></i> Fleet Management</h3>
                    <p>Search and filter your vehicle inventory instantly.</p>
                </div>
                <form class="filter-form" method="GET">
                    <input class="form-control" type="search" name="brand" placeholder="Search brand..." value="<?= htmlspecialchars($searchBrand) ?>">
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="Available" <?= $searchStatus === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Rented" <?= $searchStatus === 'Rented' ? 'selected' : '' ?>>Rented</option>
                        <option value="Maintenance" <?= $searchStatus === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                    <button class="btn filter-btn" type="submit"><i class="fas fa-filter me-1"></i>Apply</button>
                    <?php if ($searchBrand || $searchStatus): ?>
                        <a class="btn reset-btn" href="index.php">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (count($vehicles) > 0): ?>
                <div class="vehicle-grid">
                    <?php foreach ($vehicles as $index => $v): ?>
                        <?php
                            $needsMaintenance = ((int) $v['mileage'] > 50000) || ($v['status'] === 'Maintenance');
                            $statusClass = strtolower($v['status']);
                            $registration = $v['registration_number'] ?? ($v['matricule'] ?? 'N/A');
                            $delay = number_format(0.03 * (($index % 8) + 1), 2, '.', '');
                        ?>
                        <article class="vehicle-card reveal" style="--delay: <?= $delay ?>s;">
                            <div class="vehicle-media">
                                <img src="<?= htmlspecialchars($v['image_url'] ?: 'https://placehold.co/600x400/png?text=Car') ?>" alt="<?= htmlspecialchars($v['brand'] . ' ' . $v['model']) ?>">
                                <?php if ($needsMaintenance): ?>
                                    <span class="maintenance-flag"><i class="fas fa-triangle-exclamation"></i> Service Check</span>
                                <?php endif; ?>
                            </div>
                            <div class="vehicle-body">
                                <div class="vehicle-top">
                                    <h4><?= htmlspecialchars($v['brand'] . ' ' . $v['model']) ?></h4>
                                    <span class="reg-chip"><?= htmlspecialchars($registration) ?></span>
                                </div>
                                <p class="vehicle-meta"><?= htmlspecialchars($v['category']) ?> | <?= number_format($v['mileage']) ?> km</p>
                                <div class="vehicle-bottom">
                                    <div class="rate-block">
                                        <small>Daily rate</small>
                                        <strong>$<?= number_format($v['daily_rate'], 2) ?></strong>
                                    </div>
                                    <span class="status-chip status-<?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($v['status']) ?></span>
                                </div>
                            </div>
                            <div class="vehicle-actions">
                                <?php if ($v['status'] === 'Available'): ?>
                                    <a href="booking.php?vehicle_id=<?= $v['id'] ?>" class="btn book-btn">Book Now</a>
                                <?php else: ?>
                                    <button class="btn book-btn disabled" disabled>Unavailable</button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-car-side"></i>
                    <p>No vehicles found for this filter.</p>
                </div>
            <?php endif; ?>
        </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
