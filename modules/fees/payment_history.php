<?php
session_start();
$page_title = "Payment History";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$payments = [];
$query = "SELECT fp.*, f.family_code, f.father_name, f.contact, u.name as received_by_name
          FROM fee_payments fp
          LEFT JOIN families f ON fp.family_id = f.id
          LEFT JOIN users u ON fp.received_by = u.id";

if (!empty($search)) {
    $query .= " WHERE f.family_code LIKE '%$search%' 
                OR f.father_name LIKE '%$search%' 
                OR fp.receipt_no LIKE '%$search%'";
}

$query .= " ORDER BY fp.payment_date DESC, fp.id DESC";

$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
    }
}
?>

<div class="filter-bar">
    <form method="GET" style="display:flex; gap:12px; width:100%;">
        <input type="text" name="search" class="form-control" 
               placeholder="Search Family ID / Father Name / Receipt No" 
               style="min-width:300px;"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-outline">
            <i class="bi bi-search"></i> Search
        </button>
        <a href="collection.php" class="btn btn-primary" style="margin-left:auto;">
            <i class="bi bi-cash-coin"></i> Collect Fee
        </a>
    </form>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Date</th>
                        <th>Family</th>
                        <th>Father Name</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                        <th>Method</th>
                        <th>Received By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p['receipt_no']); ?></strong></td>
                            <td><?php echo date('d-M-Y', strtotime($p['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($p['family_code']); ?></td>
                            <td><?php echo htmlspecialchars($p['father_name']); ?></td>
                            <td>Rs. <?php echo number_format($p['total_amount'], 0); ?></td>
                            <td><strong class="text-success">Rs. <?php echo number_format($p['paid_amount'], 0); ?></strong></td>
                            <td>
                                <?php if ($p['remaining_amount'] > 0): ?>
                                    <span class="text-danger">Rs. <?php echo number_format($p['remaining_amount'], 0); ?></span>
                                <?php else: ?>
                                    <span class="text-success">Rs. 0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($p['received_by_name'] ?? '-'); ?></td>
                            <td>
                                <a href="receipt.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline">
                                    <i class="bi bi-receipt"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px; color:var(--text-secondary);">
                                No payment records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>