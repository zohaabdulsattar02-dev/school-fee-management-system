<?php
session_start();
$page_title = "Position List";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

$exams = [];
$eq = mysqli_query($conn, "SELECT * FROM exams WHERE status=1 ORDER BY id DESC");
if ($eq) while ($r = mysqli_fetch_assoc($eq)) $exams[] = $r;

// classes that have results for this exam
$classes_data = [];
if ($exam_id > 0) {
    $cq = mysqli_query($conn, "SELECT DISTINCT er.class_id, c.class_name 
        FROM exam_results er 
        JOIN classes c ON er.class_id = c.id
        WHERE er.exam_id=$exam_id
        ORDER BY c.id ASC");
    if ($cq) {
        while ($c = mysqli_fetch_assoc($cq)) {
            $cid = $c['class_id'];
            $top = [];
            $tq = mysqli_query($conn, "SELECT er.*, s.student_name, s.roll_number
                FROM exam_results er
                JOIN students s ON er.student_id = s.id
                WHERE er.exam_id=$exam_id AND er.class_id=$cid
                ORDER BY er.position ASC, er.obtained_marks DESC
                LIMIT 5");
            if ($tq) while ($r = mysqli_fetch_assoc($tq)) $top[] = $r;
            $classes_data[] = [
                'class_name' => $c['class_name'],
                'top' => $top
            ];
        }
    }
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <?php if ($exam_id): ?>
    <button onclick="window.print()" class="btn btn-primary btn-sm">🖨 Print</button>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="filter-bar" style="margin:0;">
            <select name="exam_id" class="form-control" required style="min-width:240px;" onchange="this.form.submit()">
                <option value="">-- Select Exam --</option>
                <?php foreach ($exams as $e): ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo $exam_id==$e['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($e['exam_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($exam_id > 0): ?>
    <?php if (count($classes_data) == 0): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:40px;">
            Is exam ke liye results generate nahi hue. Pehle Results screen se Generate karo.
        </div></div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <?php foreach ($classes_data as $cd): ?>
            <div class="card">
                <div class="card-header">
                    <strong><?php echo htmlspecialchars($cd['class_name']); ?></strong>
                </div>
                <div class="card-body">
                    <?php if (count($cd['top'])==0): ?>
                        <p class="text-muted">No positions</p>
                    <?php else: foreach ($cd['top'] as $t): 
                        $medal = '';
                        if ($t['position'] == 1) $medal = '🥇';
                        elseif ($t['position'] == 2) $medal = '🥈';
                        elseif ($t['position'] == 3) $medal = '🥉';
                        else $medal = '#'.$t['position'];
                    ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border);">
                            <div>
                                <strong><?php echo $medal; ?> <?php echo htmlspecialchars($t['student_name']); ?></strong>
                                <div class="text-muted" style="font-size:12px;">
                                    Roll: <?php echo htmlspecialchars($t['roll_number'] ?? '-'); ?>
                                    · <?php echo number_format($t['percentage'], 2); ?>%
                                    · Grade <?php echo htmlspecialchars($t['grade']); ?>
                                </div>
                            </div>
                            <div style="font-weight:700; color:var(--primary);">
                                <?php echo number_format($t['obtained_marks'], 0); ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
@media print {
    .filter-bar, .btn, a.btn { display: none !important; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>