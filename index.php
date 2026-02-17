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

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SpeedyRental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --accent-color: #ffc107;
        }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding-top: 20px;
            position: fixed;
            width: 250px;
        }
        .sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            padding: 15px 25px;
            transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--accent-color);
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .kpi-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-5px); }
        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
        }
        .status-available { background-color: #28a745; }
        .status-rented { background-color: #dc3545; }
        .status-maintenance { background-color: #ffc107; }
        
        /* Maintenance Alert Dot */
        .maintenance-alert {
            position: absolute;
            top: 10px;
            right: 10px;
            height: 12px;
            width: 12px;
            background-color: red;
            border-radius: 50%;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 82, 82, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 82, 82, 0); }
        }
        .vehicle-card { position: relative; overflow: hidden; }
        .vehicle-img { height: 180px; object-fit: cover; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h3 class="text-center mb-4"><i class="fas fa-car-side"></i> SpeedyRental</h3>
    <a href="index.php" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
    <a href="booking.php"><i class="fas fa-calendar-plus me-2"></i> New Booking</a>
    <a href="invoice_gen.php"><i class="fas fa-file-invoice-dollar me-2"></i> Invoices</a>
    <a href="logout.php" class="mt-5"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard</h2>
        <div class="user-info">
            <span class="me-2">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card kpi-card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Fleet</h5>
                    <h1><?= $totalVehicles ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card bg-success text-white">
                <div class="card-body">
                    <h5>Available Now</h5>
                    <h1><?= $availableVehicles ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card bg-warning text-dark">
                <div class="card-body">
                    <h5>Rented Out</h5>
                    <h1><?= $rentedVehicles ?></h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Returns -->
    <?php if (count($upcomingReturns) > 0): ?>
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="mb-0 text-primary"><i class="fas fa-clock me-2"></i> Upcoming Returns (Next 7 Days)</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Vehicle</th>
                        <th>Return Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingReturns as $return): ?>
                    <tr>
                        <td><?= htmlspecialchars($return['full_name']) ?></td>
                        <td><?= htmlspecialchars($return['brand'] . ' ' . $return['model']) ?></td>
                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($return['end_date']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Fleet Search & List -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fleet Management</h5>
            <form class="d-flex" method="GET">
                <input class="form-control me-2" type="search" name="brand" placeholder="Search Brand" value="<?= htmlspecialchars($searchBrand) ?>">
                <select class="form-select me-2" name="status">
                    <option value="">All Statuses</option>
                    <option value="Available" <?= $searchStatus == 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Rented" <?= $searchStatus == 'Rented' ? 'selected' : '' ?>>Rented</option>
                    <option value="Maintenance" <?= $searchStatus == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
                <button class="btn btn-outline-primary" type="submit">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($vehicles as $v): ?>
                <div class="col-md-4 mb-4">
                    <div class="card vehicle-card h-100 shadow-sm border-0">
                        <?php if ($v['mileage'] > 50000 || $v['status'] == 'Maintenance'): ?>
                            <div class="maintenance-alert" title="Maintenance Required"></div>
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($v['image_url'] ?: 'https://placehold.co/600x400/png?text=Car') ?>" class="card-img-top vehicle-img" alt="Car Image">
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0"><?= htmlspecialchars($v['brand'] . ' ' . $v['model']) ?></h5>
                                <span class="badge bg-secondary"><?= htmlspecialchars($v['matricule'] ?? $v['registration_number']) ?></span>
                            </div>
                            <p class="card-text text-muted small"><?= htmlspecialchars($v['category']) ?> | <?= number_format($v['mileage']) ?> km</p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary">$<?= number_format($v['daily_rate'], 2) ?>/day</span>
                                <span class="badge rounded-pill 
                                    <?= $v['status'] == 'Available' ? 'bg-success' : ($v['status'] == 'Rented' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                    <?= htmlspecialchars($v['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <?php if($v['status'] == 'Available'): ?>
                                <a href="booking.php?vehicle_id=<?= $v['id'] ?>" class="btn btn-sm btn-primary w-100">Book Now</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary w-100" disabled>Unavailable</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
