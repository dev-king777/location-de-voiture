<?php
// invoice_gen.php
session_start();
require 'config/db.php';

// Temporary Debugging Checks
if (!isset($_GET['id'])) {
    die("Access Denied: The URL is missing the '?id=' parameter.");
}

if (!isset($_SESSION['user_id'])) {
    die("Access Denied: You are not logged in, or the session is broken.");
}

$reservation_id = $_GET['id'];

// Fetch Reservation Details
// ... the rest of your original code continues here ...

// Fetch Reservation Details
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
    die("Invoice not found.");
}

// Calculations
$days = (strtotime($invoice['end_date']) - strtotime($invoice['start_date'])) / (60 * 60 * 24);
if ($days < 1)
    $days = 1;
$subtotal = $days * $invoice['daily_rate'];
$vat_rate = 0.20; // 20% VAT
$vat_amount = $subtotal * $vat_rate;
$total = $subtotal + $vat_amount;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $invoice['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #eee;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .invoice-container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #1e3c72;
        }

        .invoice-header {
            border-bottom: 2px solid #1e3c72;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .table-invoice th {
            background-color: #f8f9fa;
        }

        .signature-box {
            height: 100px;
            border: 1px dashed #ccc;
            margin-top: 10px;
        }

        @media print {
            body {
                background: white;
                margin: 0;
            }

            .invoice-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="text-center mt-3 mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg">Print Invoice / Download PDF</button>
        <a href="index.php" class="btn btn-secondary btn-lg">Back to Dashboard</a>
    </div>

    <div class="invoice-container">
        <div class="invoice-header d-flex justify-content-between align-items-center">
            <div class="logo">SpeedyRental <i class="fas fa-car-side"></i></div>
            <div class="text-end">
                <h4>INVOICE</h4>
                <p class="mb-0">Date: <?= date('Y-m-d') ?></p>
                <p>Ref: INV-<?= str_pad($invoice['id'], 6, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h5>From:</h5>
                <strong>SpeedyRental Agency</strong><br>
                123 Boulevard Mohamed V<br>
                Casablanca, Morocco<br>
                Phone: +212 522 00 00 00
            </div>
            <div class="col-6 text-end">
                <h5>Bill To:</h5>
                <strong><?= htmlspecialchars($invoice['full_name']) ?></strong><br>
                <?= htmlspecialchars($invoice['address']) ?><br>
                License: <?= htmlspecialchars($invoice['license_number']) ?><br>
                Phone: <?= htmlspecialchars($invoice['phone']) ?>
            </div>
        </div>

        <table class="table table-bordered table-invoice">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Rate/Day</th>
                    <th class="text-center">Days</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Vehicle Rental:
                        <strong><?= htmlspecialchars($invoice['brand'] . ' ' . $invoice['model']) ?></strong><br>
                        <small>Registration: <?= htmlspecialchars($invoice['registration_number']) ?></small><br>
                        <small>Dates: <?= $invoice['start_date'] ?> to <?= $invoice['end_date'] ?></small>
                    </td>
                    <td class="text-center">$<?= number_format($invoice['daily_rate'], 2) ?></td>
                    <td class="text-center"><?= $days ?></td>
                    <td class="text-end">$<?= number_format($subtotal, 2) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">Subtotal</td>
                    <td class="text-end">$<?= number_format($subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">VAT (20%)</td>
                    <td class="text-end">$<?= number_format($vat_amount, 2) ?></td>
                </tr>
                <tr class="table-dark">
                    <td colspan="3" class="text-end"><strong>Total Amount</strong></td>
                    <td class="text-end"><strong>$<?= number_format($total, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-5">
            <div class="col-6">
                <p><strong>Manager Signature:</strong></p>
                <div class="signature-box"></div>
            </div>
            <div class="col-6">
                <p><strong>Client Signature:</strong></p>
                <div class="signature-box"></div>
                <p class="small text-muted mt-1">By signing, I accept the terms and conditions.</p>
            </div>
        </div>

        <div class="text-center mt-5 text-muted small">
            <p>Thank you for choosing SpeedyRental!</p>
        </div>
    </div>

</body>

</html>