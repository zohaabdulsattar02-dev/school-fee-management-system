<?php
session_start();
require_once '../../includes/db.php';

$exam_id    = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (!$exam_id || !$student_id) {
    die("Invalid request");
}

// Student
$stq = mysqli_query($conn, "SELECT s.*, c.class_name, f.father_name as fam_father
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN families f ON s.family_id = f.id
    WHERE s.id=$student_id LIMIT 1");
$student = ($stq && mysqli_num_rows($stq) > 0) ? mysqli_fetch_assoc($stq) : null;
if (!$student) die("Student not found");

// Exam
$eq = mysqli_query($conn, "SELECT e.*, a.year_name FROM exams e 
    LEFT JOIN academic_years a ON e.academic_year_id=a.id WHERE e.id=$exam_id LIMIT 1");
$exam = ($eq && mysqli_num_rows($eq) > 0) ? mysqli_fetch_assoc($eq) : null;
if (!$exam) die("Exam not found");

// Result summary
$rq = mysqli_query($conn, "SELECT * FROM exam_results WHERE exam_id=$exam_id AND student_id=$student_id LIMIT 1");
$result = ($rq && mysqli_num_rows($rq) > 0) ? mysqli_fetch_assoc($rq) : null;

// Subject marks
$marks = [];
$mq = mysqli_query($conn, "SELECT sem.*, es.subject_name, cs.total_marks as max_marks, cs.passing_marks
    FROM student_exam_marks sem
    JOIN exam_subjects es ON sem.subject_id = es.id
    LEFT JOIN class_subjects cs ON cs.class_id = sem.class_id AND cs.subject_id = sem.subject_id
    WHERE sem.exam_id=$exam_id AND sem.student_id=$student_id
    ORDER BY cs.display_order ASC, es.subject_name ASC");
if ($mq) while ($r = mysqli_fetch_assoc($mq)) $marks[] = $r;

// School settings (optional)
function get_set($conn, $key, $default='') {
    $k = mysqli_real_escape_string($conn, $key);
    $q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key='$k' LIMIT 1");
    if ($q && mysqli_num_rows($q)>0) return mysqli_fetch_assoc($q)['setting_value'];
    return $default;
}
$school_name = get_set($conn, 'school_name', 'THE GREAT CHANCELLORS HIGH SCHOOL RAIWIND');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Card - <?php echo htmlspecialchars($student['student_name']); ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background:#f0f2f5; padding:30px; color:#1E293B; }
        .card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
        }
        .header {
            background: #0F1C3F;
            color: #fff;
            padding: 24px 30px;
            text-align: center;
        }
        .header img { width: 70px; height: 70px; object-fit: contain; margin-bottom: 10px; }
        .header h1 { font-size: 20px; margin-bottom: 4px; }
        .header p { font-size: 13px; opacity: 0.75; }
        .title {
            text-align: center;
            padding: 18px;
            background: #FFF7ED;
            border-bottom: 1px solid #FED7AA;
        }
        .title h2 { color: #E85D04; font-size: 22px; letter-spacing: 1px; }
        .title span { font-size: 14px; color: #64748B; }
        .info {
            padding: 20px 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
            font-size: 14px;
        }
        .info .label { color: #64748B; font-size: 12px; }
        .info strong { display: block; margin-top: 2px; }
        table {
            width: calc(100% - 60px);
            margin: 10px 30px 20px;
            border-collapse: collapse;
            font-size: 14px;
        }
        th {
            background: #F8FAFC;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            color: #64748B;
            text-transform: uppercase;
            border-bottom: 1px solid #E2E8F0;
        }
        td { padding: 10px 12px; border-bottom: 1px solid #F1F5F9; }
        .summary {
            margin: 0 30px 20px;
            padding: 16px;
            background: #F8FAFC;
            border-radius: 10px;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            text-align: center;
        }
        .summary .label { font-size: 11px; color: #64748B; }
        .summary .val { font-size: 16px; font-weight: 700; margin-top: 4px; color: #E85D04; }
        .footer {
            padding: 25px 30px 30px;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #E2E8F0;
        }
        .sign { text-align: center; width: 180px; }
        .sign .line { border-top: 1px solid #94A3B8; margin-bottom: 6px; margin-top: 40px; }
        .sign p { font-size: 12px; color: #64748B; }
        .actions {
            text-align: center;
            padding: 20px;
            background: #F8FAFC;
            border-top: 1px solid #E2E8F0;
        }
        .actions button, .actions a {
            display: inline-block;
            padding: 10px 18px;
            margin: 0 6px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-print { background: #E85D04; color: #fff; }
        .btn-back { background: #fff; border: 1px solid #E2E8F0; color: #1E293B; }
        .watermark {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.04; pointer-events: none;
        }
        .watermark img { width: 320px; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .card { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="watermark">
        <img src="../../assets/images/logo.png" alt="">
    </div>

    <div class="header">
        <img src="../../assets/images/logo.png" alt="Logo">
        <h1><?php echo htmlspecialchars($school_name); ?></h1>
        <p>School Management Portal</p>
    </div>

    <div class="title">
        <h2>RESULT CARD</h2>
        <span><?php echo htmlspecialchars($exam['exam_name']); ?>
            <?php if (!empty($exam['year_name'])) echo ' · ' . htmlspecialchars($exam['year_name']); ?>
        </span>
    </div>

    <div class="info">
        <div>
            <div class="label">Student Name</div>
            <strong><?php echo htmlspecialchars($student['student_name']); ?></strong>
        </div>
        <div>
            <div class="label">Father Name</div>
            <strong><?php echo htmlspecialchars($student['father_name'] ?? $student['fam_father'] ?? '-'); ?></strong>
        </div>
        <div>
            <div class="label">Roll No</div>
            <strong><?php echo htmlspecialchars($student['roll_number'] ?? '-'); ?></strong>
        </div>
        <div>
            <div class="label">Class</div>
            <strong><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></strong>
        </div>
        <div>
            <div class="label">Student ID</div>
            <strong><?php echo htmlspecialchars($student['student_code']); ?></strong>
        </div>
        <div>
            <div class="label">Exam</div>
            <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Total</th>
                <th>Obtained</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($marks)>0): foreach ($marks as $m): 
                $max = $m['max_marks'] ?? $m['total_marks'];
                $pass = $m['passing_marks'] ?? 40;
                $ok = floatval($m['obtained_marks']) >= floatval($pass);
            ?>
            <tr>
                <td><?php echo htmlspecialchars($m['subject_name']); ?></td>
                <td><?php echo number_format($max, 0); ?></td>
                <td><strong><?php echo number_format($m['obtained_marks'], 0); ?></strong></td>
                <td><?php echo $ok ? 'Pass' : 'Fail'; ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" style="text-align:center;">No marks found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($result): ?>
    <div class="summary">
        <div>
            <div class="label">Total Marks</div>
            <div class="val"><?php echo number_format($result['total_marks'], 0); ?></div>
        </div>
        <div>
            <div class="label">Obtained</div>
            <div class="val"><?php echo number_format($result['obtained_marks'], 0); ?></div>
        </div>
        <div>
            <div class="label">Percentage</div>
            <div class="val"><?php echo number_format($result['percentage'], 2); ?>%</div>
        </div>
        <div>
            <div class="label">Grade</div>
            <div class="val"><?php echo htmlspecialchars($result['grade']); ?></div>
        </div>
        <div>
            <div class="label">Position</div>
            <div class="val"><?php echo $result['position'] ?? '-'; ?></div>
        </div>
    </div>
    <div style="text-align:center; margin-bottom:15px;">
        <strong>Result:</strong>
        <?php echo $result['result_status'] === 'Pass' ? '<span style="color:#2A9D8F;">PASS</span>' : '<span style="color:#DC2626;">FAIL</span>'; ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        <div class="sign">
            <div class="line"></div>
            <p>Teacher Signature</p>
        </div>
        <div class="sign">
            <div class="line"></div>
            <p>Principal Signature</p>
        </div>
    </div>

    <div class="actions">
        <button class="btn-print" onclick="window.print()">🖨 Print</button>
        <a href="results.php?exam_id=<?php echo $exam_id; ?>&class_id=<?php echo $student['class_id']; ?>" class="btn-back">← Back</a>
    </div>
</div>
</body>
</html>