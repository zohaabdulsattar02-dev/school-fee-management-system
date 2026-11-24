<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../includes/db.php';

// Get payment ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($payment_id === 0) {
    die("Invalid Receipt");
}

// Standard: Prepared Statement for payment + family
$stmt = mysqli_prepare($conn, "SELECT fp.*, f.family_code, f.father_name, f.contact, f.whatsapp, u.name as received_by_name FROM fee_payments fp LEFT JOIN families f ON fp.family_id = f.id LEFT JOIN users u ON fp.received_by = u.id WHERE fp.id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $payment_id);
mysqli_stmt_execute($stmt);
$payment_result = mysqli_stmt_get_result($stmt);

if (!$payment_result || mysqli_num_rows($payment_result) === 0) {
    die("Receipt not found");
}

$payment = mysqli_fetch_assoc($payment_result);

// Fetch payment details (students)
$details = [];
$stmt2 = mysqli_prepare($conn, "SELECT pd.*, s.student_name, s.student_code, s.monthly_fee, s.discount, c.class_name FROM payment_details pd LEFT JOIN students s ON pd.student_id = s.id LEFT JOIN classes c ON s.class_id = c.id WHERE pd.payment_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $payment_id);
mysqli_stmt_execute($stmt2);
$details_result = mysqli_stmt_get_result($stmt2);
if ($details_result) {
    while ($row = mysqli_fetch_assoc($details_result)) {
        $details[] = $row;
    }
}

// WhatsApp message prepare
$phone = !empty($payment['whatsapp']) ? $payment['whatsapp'] : ($payment['contact'] ?? '');
$phone = preg_replace('/[^0-9]/', '', $phone);

// Pakistan number format (03xxxxxxxxx -> 923xxxxxxxxx)
if (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
    $phone = '92' . substr($phone, 1);
}

$message  = "Assalam o Alaikum,\n\n";
$message .= "Great Chancellors School\n";
$message .= "Fee Receipt Confirmation\n\n";
$message .= "Receipt No: " . $payment['receipt_no'] . "\n";
$message .= "Date: " . date('d-M-Y', strtotime($payment['payment_date'])) . "\n";
$message .= "Family: " . $payment['father_name'] . " (" . $payment['family_code'] . ")\n";
$message .= "Amount Paid: Rs. " . number_format($payment['paid_amount'], 0) . "\n";
$message .= "Payment Method: " . $payment['payment_method'] . "\n\n";
$message .= "Thank you!";

$whatsapp_link = !empty($phone) ? "https://wa.me/" . $phone . "?text=" . urlencode($message) : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Fee Receipt - <?php echo htmlspecialchars($payment['receipt_no']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f2f5;
            padding: 40px 20px;
            color: #1E293B;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
        }
        .receipt-header {
            background: #0F1C3F;
            color: #fff;
            padding: 30px 40px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .receipt-header img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        .receipt-header h1 { font-size: 22px; margin-bottom: 4px; }
        .receipt-header p { font-size: 13px; opacity: 0.7; }
        .receipt-title {
            text-align: center;
            padding: 25px 40px 10px;
        }
        .receipt-title h2 {
            font-size: 26px;
            color: #E85D04;
            letter-spacing: 1px;
        }
        .receipt-info {
            padding: 20px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 40px;
            font-size: 14px;
        }
        .receipt-info .label {
            color: #64748B;
            font-size: 12px;
        }
        .receipt-info strong {
            display: block;
            margin-top: 2px;
        }
        .received-from {
            margin: 10px 40px;
            padding: 14px 18px;
            background: #FFF7ED;
            border-radius: 8px;
            border-left: 4px solid #E85D04;
            font-size: 14px;
        }
        table {
            width: calc(100% - 80px);
            margin: 20px 40px;
            border-collapse: collapse;
            font-size: 14px;
        }
        table th {
            background: #F8FAFC;
            padding: 12px 14px;
            text-align: left;
            font-size: 12px;
            color: #64748B;
            text-transform: uppercase;
            border-bottom: 1px solid #E2E8F0;
        }
        table td {
            padding: 12px 14px;
            border-bottom: 1px solid #F1F5F9;
        }
        .totals {
            padding: 10px 40px 25px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-box { width: 280px; }
        .totals-box div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .totals-box .grand {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #E85D04;
            font-size: 18px;
            font-weight: 700;
            color: #E85D04;
        }
        .footer-section {
            padding: 25px 40px 35px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid #E2E8F0;
        }
        .signature { text-align: center; }
        .signature .line {
            width: 160px;
            border-top: 1px solid #94A3B8;
            margin-bottom: 6px;
        }
        .signature p { font-size: 12px; color: #64748B; }
        .actions {
            text-align: center;
            padding: 25px;
            background: #F8FAFC;
            border-top: 1px solid #E2E8F0;
        }
        .actions button, .actions a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            margin: 0 6px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #E85D04; color: #fff; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-back { background: #fff; border: 1px solid #E2E8F0; color: #1E293B; }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.04;
            pointer-events: none;
        }
        .watermark img { width: 350px; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .receipt-container { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Watermark -->
        <div class="watermark">
            <img src="../../assets/images/logo.png" alt="">
        </div>

        <!-- Header -->
        <div class="receipt-header">
            <img src="../../assets/images/logo.png" alt="Logo">
            <div>
                <h1>Great Chancellors School</h1>
                <p>Staff Only - Internal Fee Management</p>
            </div>
        </div>

        <!-- Title -->
        <div class="receipt-title">
            <h2>FEE RECEIPT</h2>
        </div>

        <!-- Info -->
        <div class="receipt-info">
            <div>
                <div class="label">Receipt No</div>
                <strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong>
            </div>
            <div>
                <div class="label">Date</div>
                <strong><?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?></strong>
            </div>
            <div>
                <div class="label">Payment Method</div>
                <strong><?php echo htmlspecialchars($payment['payment_method']); ?></strong>
            </div>
            <div>
                <div class="label">Family ID</div>
                <strong><?php echo htmlspecialchars($payment['family_code']); ?></strong>
            </div>
        </div>

        <!-- Received From -->
        <div class="received-from">
            <strong>Received From:</strong> 
            <?php echo htmlspecialchars($payment['father_name']); ?> 
            (<?php echo htmlspecialchars($payment['family_code']); ?>)
        </div>

        <!-- Students Table -->
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Fee</th>
                    <th>Discount</th>
                    <th>Paid</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($details) > 0): ?>
                    <?php foreach ($details as $item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['student_name']); ?><br>
                            <small style="color:#64748B;"><?php echo htmlspecialchars($item['student_code']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($item['class_name'] ?? '-'); ?></td>
                        <td>Rs. <?php echo number_format($item['monthly_fee'], 0); ?></td>
                        <td>Rs. <?php echo number_format($item['discount'], 0); ?></td>
                        <td><strong>Rs. <?php echo number_format($item['amount_paid'], 0); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No student details found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-box">
                <div>
                    <span>Total Amount</span>
                    <span>Rs. <?php echo number_format($payment['total_amount'], 0); ?></span>
                </div>
                <div>
                    <span>Amount Paid</span>
                    <span>Rs. <?php echo number_format($payment['paid_amount'], 0); ?></span>
                </div>
                <div>
                    <span>Remaining</span>
                    <span>Rs. <?php echo number_format($payment['remaining_amount'], 0); ?></span>
                </div>
                <div class="grand">
                    <span>Total Paid</span>
                    <span>Rs. <?php echo number_format($payment['paid_amount'], 0); ?></span>
                </div>
            </div>
        </div>

        <!-- Signature -->
        <div class="footer-section">
            <div>
                <div class="label" style="font-size:12px; color:#64748B;">Received By</div>
                <strong><?php echo htmlspecialchars($payment['received_by_name'] ?? 'Staff'); ?></strong>
            </div>
            <div class="signature">
                <div class="line"></div>
                <p>Authorized Signature</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions">
            <button class="btn-print" onclick="window.print()">🖨 Print</button>

            <?php if (!empty($whatsapp_link)): ?>
                <a href="<?php echo htmlspecialchars($whatsapp_link); ?>" target="_blank" rel="noopener" class="btn-whatsapp">
                    📱 Send WhatsApp
                </a>
            <?php endif; ?>

            <a href="collection.php" class="btn-back">← Back to Collection</a>
        </div>
    </div>
</body>
</html>