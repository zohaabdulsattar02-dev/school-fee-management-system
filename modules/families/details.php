<?php
session_start();
$page_title = "Family Details";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$family_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($family_id === 0) {
    echo "<div class='card'><div class='card-body'>Invalid Family ID</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch family
$family = null;
$fam_q = mysqli_query($conn, "SELECT * FROM families WHERE id = $family_id LIMIT 1");
if ($fam_q && mysqli_num_rows($fam_q) > 0) {
    $family = mysqli_fetch_assoc($fam_q);
} else {
    echo "<div class='card'><div class='card-body'>Family not found</div></div>";
    require_once '../../includes/footer.php';
    exit;
}

// Fetch children
$children = [];
$child_q = mysqli_query($conn, "SELECT s.*, c.class_name 
                                FROM students s 
                                LEFT JOIN classes c ON s.class_id = c.id
                                WHERE s.family_id = $family_id 
                                ORDER BY s.id ASC");
if ($child_q) {
    while ($row = mysqli_fetch_assoc($child_q)) {
        $children[] = $row;
    }
}

$total_fee = 0;
foreach ($children as $c) {
    $total_fee += ($c['monthly_fee'] - $c['discount']);
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
    <a href="../fees/collection.php?search=<?php echo urlencode($family['family_code']); ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-cash-coin"></i> Collect Fee
    </a>
</div>

<!-- Family Info Card -->
<div class="card mb-4">
    <div class="card-header">
        <div>
            <strong style="font-size:18px;">
                <?php echo htmlspecialchars($family['family_code']); ?> — 
                <?php echo htmlspecialchars($family['father_name']); ?> Family
            </strong>
        </div>
        <?php if ($family['status'] === 'Active'): ?>
            <span class="badge badge-success">Active</span>
        <?php else: ?>
            <span class="badge badge-secondary">Inactive</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:20px;">
            <div>
                <div class="text-muted" style="font-size:12px;">Father / Guardian</div>
                <strong><?php echo htmlspecialchars($family['father_name']); ?></strong>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px;">Mother / Guardian</div>
                <strong><?php echo htmlspecialchars($family['mother_name'] ?? '-'); ?></strong>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px;">Contact</div>
                <strong><?php echo htmlspecialchars($family['contact'] ?? '-'); ?></strong>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px;">WhatsApp</div>
                <strong><?php echo htmlspecialchars($family['whatsapp'] ?? '-'); ?></strong>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px;">Total Children</div>
                <strong><?php echo count($children); ?></strong>
            </div>
            <div>
                <div class="text-muted" style="font-size:12px;">Registration Date</div>
                <strong><?php echo date('d-M-Y', strtotime($family['created_at'])); ?></strong>
            </div>
            <div style="grid-column: span 2;">
                <div class="text-muted" style="font-size:12px;">Address</div>
                <strong><?php echo htmlspecialchars($family['address'] ?? '-'); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Children Table -->
<div class="card">
    <div class="card-header">
        <strong>Children</strong>
        <a href="../students/add.php" class="btn btn-sm btn-primary">+ Add Child</a>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Monthly Fee</th>
                        <th>Discount</th>
                        <th>Payable</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($children) > 0): ?>
                        <?php foreach ($children as $child): 
                            $payable = $child['monthly_fee'] - $child['discount'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($child['student_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($child['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($child['class_name'] ?? '-'); ?></td>
                            <td>Rs. <?php echo number_format($child['monthly_fee'], 0); ?></td>
                            <td>Rs. <?php echo number_format($child['discount'], 0); ?></td>
                            <td><strong>Rs. <?php echo number_format($payable, 0); ?></strong></td>
                            <td>
                                <?php if ($child['status'] === 'Active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?php echo $child['status']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:var(--text-secondary);">
                                No children found in this family.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
            <div>
                <span class="text-muted">Total Monthly Fee:</span>
                <strong style="font-size:18px; color:var(--primary); margin-left:8px;">
                    Rs. <?php echo number_format($total_fee, 0); ?>
                </strong>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>