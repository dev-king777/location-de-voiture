<?php
// invoice_gen.php
session_start();
require 'config/db.php';

// 1. Vérification de la sécurité et de l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Accès refusé : Il manque l'ID de la facture dans l'URL.");
}

if (!isset($_SESSION['user_id'])) {
    die("Accès refusé : Veuillez vous connecter.");
}

$reservation_id = $_GET['id'];

// 2. Récupérer les détails de la réservation
$stmt = $pdo->prepare("
    SELECT r.*, 
           c.full_name, c.license_number, c.phone, c.address,
           v.brand, v.model, v.registration_number, v.daily_rate
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.id = ?
");
$stmt->execute([$reservation_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die("Facture introuvable dans la base de données.");
}

// 3. Calculs sécurisés
$start = new DateTime($invoice['start_date']);
$end = new DateTime($invoice['end_date']);
$days = $start->diff($end)->days;

if ($days < 1) {
    $days = 1;
}

$subtotal = $days * $invoice['daily_rate'];
$vat_rate = 0.20; // TVA 20%
$vat_amount = $subtotal * $vat_rate;
$total = $subtotal + $vat_amount;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Facture INV-<?= str_pad($invoice['id'], 6, '0', STR_PAD_LEFT) ?> - Draga4Life</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .invoice-container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 40px auto;
            padding: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
        }

        .brand-color {
            color: #0f3b73;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .invoice-header {
            border-bottom: 3px solid #0f3b73;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }

        .table-invoice th {
            background-color: #0f3b73 !important;
            color: white !important;
        }

        .signature-box {
            height: 120px;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            margin-top: 15px;
            background-color: #fafbfa;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                padding: 20px;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="text-center mt-4 mb-2 no-print">
        <a href="invoices.php" class="btn btn-secondary btn-lg me-2"><i class="fas fa-arrow-left"></i> Retour</a>
        <button onclick="window.print()" class="btn btn-primary btn-lg"
            style="background-color: #0f3b73; border-color: #0f3b73;"><i class="fas fa-print"></i> Imprimer</button>
    </div>

    <div class="invoice-container">
        <div class="invoice-header d-flex justify-content-between align-items-center">
            <div class="brand-color logo-text">Draga4Life <i class="fas fa-car-side"></i></div>
            <div class="text-end">
                <h2 class="fw-bold brand-color mb-1">FACTURE</h2>
                <p class="mb-0 text-muted">Date : <?= date('d/m/Y') ?></p>
                <p class="fw-bold">Réf : INV-<?= str_pad($invoice['id'], 6, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h6 class="text-muted text-uppercase mb-2">Émetteur :</h6>
                <h5 class="fw-bold brand-color">Location De Voiture Draga4Life</h5>
                <p class="mb-0">Avenue Hassan II<br>Marrakech, Maroc<br>Tél : +212 600 00 00 00</p>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted text-uppercase mb-2">Facturé à :</h6>
                <h5 class="fw-bold"><?= htmlspecialchars($invoice['full_name']) ?></h5>
                <p class="mb-0"><?= htmlspecialchars($invoice['address']) ?><br>
                    <strong>Permis N° :</strong> <?= htmlspecialchars($invoice['license_number']) ?><br>
                    <strong>Tél :</strong> <?= htmlspecialchars($invoice['phone']) ?>
                </p>
            </div>
        </div>

        <table class="table table-bordered table-invoice mt-4">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Tarif/Jour</th>
                    <th class="text-center">Jours</th>
                    <th class="text-end">Total HT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="py-3">
                        <strong><?= htmlspecialchars($invoice['brand'] . ' ' . $invoice['model']) ?></strong><br>
                        <span class="text-muted small">Immatriculation :
                            <?= htmlspecialchars($invoice['registration_number']) ?></span><br>
                        <span class="text-muted small">Du <?= date('d/m/Y', strtotime($invoice['start_date'])) ?> au
                            <?= date('d/m/Y', strtotime($invoice['end_date'])) ?></span>
                    </td>
                    <td class="text-center align-middle"><?= number_format($invoice['daily_rate'], 2) ?> MAD</td>
                    <td class="text-center align-middle"><?= $days ?></td>
                    <td class="text-end align-middle"><?= number_format($subtotal, 2) ?> MAD</td>
                </tr>
            </tbody>
        </table>

        <div class="row justify-content-end mt-4">
            <div class="col-md-5">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-end"><strong>Sous-total :</strong></td>
                        <td class="text-end"><?= number_format($subtotal, 2) ?> MAD</td>
                    </tr>
                    <tr>
                        <td class="text-end border-bottom"><strong>TVA (20%) :</strong></td>
                        <td class="text-end border-bottom"><?= number_format($vat_amount, 2) ?> MAD</td>
                    </tr>
                    <tr>
                        <td class="text-end fs-5 brand-color pt-2"><strong>Total TTC :</strong></td>
                        <td class="text-end fs-5 fw-bold brand-color pt-2"><?= number_format($total, 2) ?> MAD</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="row mt-5 pt-3">
            <div class="col-6">
                <p class="fw-bold mb-1">Signature Agence :</p>
                <div class="signature-box"></div>
            </div>
            <div class="col-6">
                <p class="fw-bold mb-1">Signature Client :</p>
                <div class="signature-box"></div>
            </div>
        </div>
    </div>
</body>

</html>