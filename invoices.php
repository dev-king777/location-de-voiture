<?php
// invoices.php
session_start();
require 'config/db.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Accès refusé : Veuillez vous connecter.");
}

// Récupérer toutes les réservations
$stmt = $pdo->query("
    SELECT r.id, r.start_date, r.end_date, 
           c.full_name, 
           v.brand, v.model 
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    ORDER BY r.id DESC
");
$reservations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Draga4Life</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles inspirés de ton Dashboard */
        body {
            background-color: #f0f4f8; /* Le fond clair de ton dashboard */
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #333;
        }
        .main-container {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .text-theme-dark {
            color: #0f3b73; /* Le bleu foncé de ta sidebar */
        }
        .text-theme-muted {
            color: #6c757d;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        /* Style de la carte blanche */
        .dashboard-card {
            background: #ffffff;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            padding: 20px;
        }
        /* Bouton de retour */
        .btn-back {
            background-color: white;
            color: #0f3b73;
            border: 2px solid #0f3b73;
            border-radius: 8px;
            font-weight: 600;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background-color: #0f3b73;
            color: white;
        }
        /* Style du tableau */
        .table-custom-header {
            background-color: #0f3b73 !important;
            color: white !important;
        }
        .table th {
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="main-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="text-theme-muted text-uppercase fw-bold mb-1">Operations Overview</h6>
            <h2 class="text-theme-dark fw-bold mb-0">Invoices & Bookings</h2>
        </div>
        <a href="index.php" class="btn btn-back text-decoration-none">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['message']); 
        unset($_SESSION['msg_type']);
        ?>
    <?php endif; ?>

    <div class="dashboard-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="table-custom-header rounded-start">Inv #</th>
                        <th class="table-custom-header">Client Name</th>
                        <th class="table-custom-header">Vehicle</th>
                        <th class="table-custom-header">Start Date</th>
                        <th class="table-custom-header">End Date</th>
                        <th class="table-custom-header rounded-end text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $row): ?>
                    <tr>
                        <td class="fw-bold text-theme-dark">INV-<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['brand'] . ' ' . $row['model']) ?></td>
                        <td><?= htmlspecialchars($row['start_date']) ?></td>
                        <td><?= htmlspecialchars($row['end_date']) ?></td>
                        <td class="text-center">
                            <a href="invoice_gen.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="View / Print">
                                <i class="fas fa-print"></i>
                            </a>
                            
                            <a href="delete_booking.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger ms-1" 
                               onclick="return confirm('Es-tu sûr de vouloir supprimer cette facture et la réservation associée ?');" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($reservations)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3 opacity-50"></i>
                    <h5>No invoices found</h5>
                    <p>Create a new booking to generate an invoice.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>