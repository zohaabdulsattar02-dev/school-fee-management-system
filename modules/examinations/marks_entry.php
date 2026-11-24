<?php
session_start();
$page_title = "Marks Entry";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$success = "";
$error = "";

$exam_id  = isset($_REQUEST['exam_id']) ? intval($_REQUEST['exam_id']) : 0;
$class_id = isset($_REQUEST['class_id']) ? intval($_REQUEST['class_id']) : 0;

// Save marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $exam_id  = intval($_POST['exam_id']);
    $class_id = intval($_POST['class_id']);
    $uid = $_SESSION['user_id'] ?? null;

    if ($exam_id && $class_id && isset($_POST['marks']) && is_array($_POST['marks'])) {
        foreach ($_POST['marks'] as $student_id => $subjects) {
            $student_id = intval($student_id);
            foreach ($subjects as $subject_id => $val) {
                $subject_id = intval($subject_id);
                $obtained = ($val === '' || $val === null) ? 0 : floatval($val);

                // get total marks for validation
                $tq = mysqli_query($conn, "SELECT total_marks FROM class_subjects WHERE class_id=$class_id AND subject_id=$subject_id LIMIT 1");
                $total = 100;
                if ($tq && mysqli_num_rows($tq) > 0) {
                    $total = floatval(mysqli_fetch_assoc($tq)['total_marks']);
                }
                if ($obtained > $total) $obtained = $total;
                if ($obtained < 0) $obtained = 0;

                $check = mysqli_query($conn, "SELECT id FROM student_exam_marks 
                    WHERE exam_id=$exam_id AND student_id=$student_id AND subject_id=$subject_id LIMIT 1");

                if ($check && mysqli_num_rows($check) > 0) {
                    $row = mysqli_fetch_assoc($check);
                    mysqli_query($conn, "UPDATE student_exam_marks SET 
                        obtained_marks=$obtained, total_marks=$total, updated_by=".($uid ? $uid : 'NULL')."
                        WHERE id={$row['id']}");
                } else {
                    mysqli_query($conn, "INSERT INTO student_exam_marks 
                        (exam_id, student_id, class_id, subject_id, total_marks, obtained_marks, entered_by)
                        VALUES ($exam_id, $student_id, $class_id, $subject_id, $total, $obtained, ".($uid ? $uid : 'NULL').")");
                }
            }
        }
        $success = "Marks saved successfully!";
    } else {
        $error = "Please select exam, class and enter marks.";
    }
}

// Dropdown data
$exams = [];
$eq = mysqli_query($conn, "SELECT * FROM exams WHERE status=1 ORDER BY id DESC");
if ($eq) while ($r = mysqli_fetch_assoc($eq)) $exams[] = $r;

$classes = [];
$cq = mysqli_query($conn, "SELECT * FROM classes WHERE status=1 ORDER BY id ASC");
if ($cq) while ($r = mysqli_fetch_assoc($cq)) $classes[] = $r;

// Subjects for class
$subjects = [];
if ($class_id > 0) {
    $sq = mysqli_query($conn, "SELECT cs.*, s.subject_name 
        FROM class_subjects cs 
        JOIN exam_subjects s ON cs.subject_id = s.id
        WHERE cs.class_id=$class_id AND cs.status=1
        ORDER BY cs.display_order ASC, s.subject_name ASC");
    if ($sq) while ($r = mysqli_fetch_assoc($sq)) $subjects[] = $r;
}

// Students of class
$students = [];
if ($class_id > 0) {
    $stq = mysqli_query($conn, "SELECT id, student_code, student_name, father_name, roll_number 
        FROM students 
        WHERE class_id=$class_id AND status='Active' 
        ORDER BY roll_number ASC, student_name ASC");
    if ($stq) while ($r = mysqli_fetch_assoc($stq)) $students[] = $r;
}

// Existing marks map: [student_id][subject_id] = obtained
$marks_map = [];
if ($exam_id > 0 && $class_id > 0) {
    $mq = mysqli_query($conn, "SELECT student_id, subject_id, obtained_marks 
        FROM student_exam_marks 
        WHERE exam_id=$exam_id AND class_id=$class_id");
    if ($mq) {
        while ($r = mysqli_fetch_assoc($mq)) {
            $marks_map[$r['student_id']][$r['subject_id']] = $r['obtained_marks'];
        }
    }
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

<!-- Filters -->
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

            <button type="submit" class="btn btn-primary">Load Students</button>
        </form>
    </div>
</div>

<?php if ($exam_id > 0 && $class_id > 0): ?>

    <?php if (count($subjects) == 0): ?>
        <div class="card">
            <div class="card-body" style="text-align:center; padding:40px;">
                Is class ke liye subjects assign nahi hain.
                <br><br>
                <a href="class_subjects.php?class_id=<?php echo $class_id; ?>" class="btn btn-primary">Assign Subjects</a>
            </div>
        </div>
    <?php elseif (count($students) == 0): ?>
        <div class="card">
            <div class="card-body" style="text-align:center; padding:40px;">
                Is class mein koi active student nahi mila.
            </div>
        </div>
    <?php else: ?>

    <form method="POST">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">

        <div class="card">
            <div class="card-header">
                <strong>Enter Marks</strong>
                <button type="submit" name="save_marks" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-lg"></i> Save Marks
                </button>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Roll</th>
                                <th>Student</th>
                                <th>Father</th>
                                <?php foreach ($subjects as $sub): ?>
                                    <th style="min-width:90px; text-align:center;">
                                        <?php echo htmlspecialchars($sub['subject_name']); ?>
                                        <div style="font-size:11px; color:#64748B; font-weight:400;">
                                            / <?php echo number_format($sub['total_marks'], 0); ?>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $st): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['roll_number'] ?? '-'); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($st['student_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($st['student_code']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($st['father_name'] ?? '-'); ?></td>
                                <?php foreach ($subjects as $sub): 
                                    $sid = $sub['subject_id'];
                                    $val = $marks_map[$st['id']][$sid] ?? '';
                                    $max = $sub['total_marks'];
                                ?>
                                <td>
                                    <input type="number" 
                                           name="marks[<?php echo $st['id']; ?>][<?php echo $sid; ?>]"
                                           class="form-control mark-input"
                                           value="<?php echo $val !== '' ? $val : ''; ?>"
                                           min="0" max="<?php echo $max; ?>" step="0.01"
                                           style="width:80px; height:38px; padding:4px 8px; text-align:center;"
                                           data-max="<?php echo $max; ?>"
                                           oninput="validateMark(this)">
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px;">
                <a href="results.php?exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $class_id; ?>" class="btn btn-outline">
                    Go to Results
                </a>
                <button type="submit" name="save_marks" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Marks
                </button>
            </div>
        </div>
    </form>

    <script>
    function validateMark(el) {
        const max = parseFloat(el.getAttribute('data-max')) || 100;
        let v = parseFloat(el.value);
        if (isNaN(v)) return;
        if (v > max) {
            el.value = max;
            alert('Maximum marks for this subject: ' + max);
        }
        if (v < 0) el.value = 0;
    }
    </script>

    <?php endif; ?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>