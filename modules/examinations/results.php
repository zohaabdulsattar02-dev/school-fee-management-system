<?php
session_start();
$page_title = "Results";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";

$exam_id  = isset($_REQUEST['exam_id']) ? intval($_REQUEST['exam_id']) : 0;
$class_id = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;

// Helper: get grade from percentage
function get_grade($conn, $percentage) {
    $q = mysqli_query($conn, "SELECT grade_name FROM grade_rules 
        WHERE status=1 AND $percentage >= min_percentage AND $percentage <= max_percentage 
        ORDER BY min_percentage DESC LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q)['grade_name'];
    }
    return 'F';
}

// Generate / Refresh Results
if (isset($_POST['generate_results']) && $exam_id && $class_id) {
    $uid = $_SESSION['user_id'] ?? null;

    // subjects for class
    $subjects = [];
    $sq = mysqli_query($conn, "SELECT * FROM class_subjects WHERE class_id=$class_id AND status=1");
    if ($sq) while ($r = mysqli_fetch_assoc($sq)) $subjects[] = $r;

    $class_total = 0;
    foreach ($subjects as $s) $class_total += floatval($s['total_marks']);

    // students
    $students = [];
    $stq = mysqli_query($conn, "SELECT id FROM students WHERE class_id=$class_id AND status='Active'");
    if ($stq) while ($r = mysqli_fetch_assoc($stq)) $students[] = $r['id'];

    foreach ($students as $student_id) {
        $obtained = 0;
        $failed = false;

        foreach ($subjects as $sub) {
            $sid = $sub['subject_id'];
            $pass = floatval($sub['passing_marks']);
            $tmax = floatval($sub['total_marks']);

            $mq = mysqli_query($conn, "SELECT obtained_marks FROM student_exam_marks 
                WHERE exam_id=$exam_id AND student_id=$student_id AND subject_id=$sid LIMIT 1");
            $om = 0;
            if ($mq && mysqli_num_rows($mq) > 0) {
                $om = floatval(mysqli_fetch_assoc($mq)['obtained_marks']);
            }
            $obtained += $om;
            if ($om < $pass) $failed = true;
        }

        $percentage = ($class_total > 0) ? round(($obtained / $class_total) * 100, 2) : 0;
        $grade = get_grade($conn, $percentage);
        $status = $failed ? 'Fail' : (($percentage >= 40) ? 'Pass' : 'Fail');

        $check = mysqli_query($conn, "SELECT id FROM exam_results WHERE exam_id=$exam_id AND student_id=$student_id LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            $row = mysqli_fetch_assoc($check);
            mysqli_query($conn, "UPDATE exam_results SET 
                class_id=$class_id,
                total_marks=$class_total,
                obtained_marks=$obtained,
                percentage=$percentage,
                grade='$grade',
                result_status='$status',
                generated_by=".($uid ? $uid : 'NULL')."
                WHERE id={$row['id']}");
        } else {
            mysqli_query($conn, "INSERT INTO exam_results 
                (exam_id, student_id, class_id, total_marks, obtained_marks, percentage, grade, result_status, generated_by)
                VALUES ($exam_id, $student_id, $class_id, $class_total, $obtained, $percentage, '$grade', '$status', ".($uid ? $uid : 'NULL').")");
        }
    }

    // Positions (same % = same position)
    $rq = mysqli_query($conn, "SELECT id, percentage, obtained_marks FROM exam_results 
        WHERE exam_id=$exam_id AND class_id=$class_id 
        ORDER BY obtained_marks DESC, percentage DESC");

    $rank = 0;
    $prev_key = null;
    $index = 0;
    if ($rq) {
        while ($r = mysqli_fetch_assoc($rq)) {
            $index++;
            $key = $r['obtained_marks'] . '_' . $r['percentage'];
            if ($prev_key !== $key) {
                $rank = $index;
                $prev_key = $key;
            }
            mysqli_query($conn, "UPDATE exam_results SET position=$rank WHERE id={$r['id']}");
        }
    }

    $success = "Results generated successfully! Totals, %, Grade, Pass/Fail & Positions updated.";
}

// Dropdowns
$exams = [];
$eq = mysqli_query($conn, "SELECT * FROM exams WHERE status=1 ORDER BY id DESC");
if ($eq) while ($r = mysqli_fetch_assoc($eq)) $exams[] = $r;

$classes = [];
$cq = mysqli_query($conn, "SELECT * FROM classes WHERE status=1 ORDER BY id ASC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

// Results list
$results = [];
if ($exam_id && $class_id) {
    $rq = mysqli_query($conn, "SELECT er.*, s.student_name, s.student_code, s.father_name, s.roll_number
        FROM exam_results er
        JOIN students s ON er.student_id = s.id
        WHERE er.exam_id=$exam_id AND er.class_id=$class_id
        ORDER BY er.position ASC, er.obtained_marks DESC");
    if ($rq) while ($r = mysqli_fetch_assoc($rq)) $results[] = $r;
}
?>

<div class="d-flex justify-between align-center mb-4">
    <a href="index.php" class="btn btn-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if ($success): ?>
<div style="background:#E6F7F5;color:#2A9D8F;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#FEF2F2;color:#DC2626;padding:14px;border-radius:10px;margin-bottom:16px;"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="filter-bar" style="margin:0;">
            <select name="exam_id" class="form-control" required style="min-width:200px;">
                <option value="">-- Select Exam --</option>
                <?php foreach ($exams as $e): ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo $exam_id==$e['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($e['exam_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="class_id" class="form-control" required style="min-width:180px;">
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $class_id==$c['id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($c['class_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline">Load</button>
        </form>
    </div>
</div>

<?php if ($exam_id && $class_id): ?>
<form method="POST" style="margin-bottom:16px;">
    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
    <button type="submit" name="generate_results" class="btn btn-primary"
            onclick="return confirm('Generate/Refresh results for this class?')">
        <i class="bi bi-lightning"></i> Generate / Refresh Results
    </button>
    <a href="marks_entry.php?exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-outline">
        Edit Marks
    </a>
    <a href="positions.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-outline">
        Position List
    </a>
</form>

<div class="card">
    <div class="card-header"><strong>Class Results</strong></div>
    <div class="card-body" style="padding:0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th>Roll</th>
                        <th>Student</th>
                        <th>Father</th>
                        <th>Total</th>
                        <th>Obtained</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results)>0): foreach ($results as $r): ?>
                    <tr>
                        <td><strong><?php echo $r['position'] ?? '-'; ?></strong></td>
                        <td><?php echo htmlspecialchars($r['roll_number'] ?? '-'); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r['student_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($r['student_code']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($r['father_name'] ?? '-'); ?></td>
                        <td><?php echo number_format($r['total_marks'], 0); ?></td>
                        <td><strong><?php echo number_format($r['obtained_marks'], 0); ?></strong></td>
                        <td><?php echo number_format($r['percentage'], 2); ?>%</td>
                        <td><span class="badge badge-success"><?php echo htmlspecialchars($r['grade']); ?></span></td>
                        <td>
                            <?php if ($r['result_status'] === 'Pass'): ?>
                                <span class="badge badge-success">Pass</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Fail</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="result_card.php?exam_id=<?php echo $exam_id; ?>&student_id=<?php echo $r['student_id']; ?>"
                               class="btn btn-sm btn-outline" target="_blank">
                                Result Card
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:30px;">
                            No results yet. Pehle marks entry karo, phir <strong>Generate Results</strong> dabao.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>