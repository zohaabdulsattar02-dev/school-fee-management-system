<?php
session_start();
$page_title = "Examination & Results";

require_once '../../includes/db.php';
require_once '../../includes/header.php';

$total_exams = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM exams");
if ($r) $total_exams = mysqli_fetch_assoc($r)['t'];

$total_subjects = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM exam_subjects WHERE status=1");
if ($r) $total_subjects = mysqli_fetch_assoc($r)['t'];

$total_results = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as t FROM exam_results");
if ($r) $total_results = mysqli_fetch_assoc($r)['t'];
?>

<div class="kpi-grid" style="margin-bottom:24px;">
    <div class="kpi-card">
        <div class="label">Total Exams</div>
        <div class="value"><?php echo $total_exams; ?></div>
    </div>
    <div class="kpi-card">
        <div class="label">Subjects</div>
        <div class="value"><?php echo $total_subjects; ?></div>
    </div>
    <div class="kpi-card green">
        <div class="label">Results Generated</div>
        <div class="value"><?php echo $total_results; ?></div>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:18px;">
    <a href="exams.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Exam Management</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">Add / Edit / Delete exams & terms</p>
        </div>
    </a>
    <a href="subjects.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Subjects</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">Manage all subjects</p>
        </div>
    </a>
    <a href="class_subjects.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Class Subjects</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">Assign subjects + total marks per class</p>
        </div>
    </a>
    <a href="grades.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Grade Rules</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">Percentage to grade mapping</p>
        </div>
    </a>
    <a href="marks_entry.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Marks Entry</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">Enter / edit student marks</p>
        </div>
    </a>
    <a href="results.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Results & Cards</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">Generate results, cards, positions</p>
        </div>
    </a>
    <a href="positions.php" class="card" style="text-decoration:none; color:inherit;">
        <div class="card-body">
            <strong>Position List</strong>
            <p class="text-muted" style="margin-top:8px; font-size:13px;">1st, 2nd, 3rd class-wise</p>
        </div>
    </a>
</div>

<?php require_once '../../includes/footer.php'; ?>