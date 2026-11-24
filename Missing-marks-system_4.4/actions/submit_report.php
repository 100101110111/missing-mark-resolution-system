<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'report_new.php');
}

if (!csrfValid($_POST['csrf_token'] ?? '')) {
    flash('main', 'Your session expired. Please try submitting again.', 'danger');
    redirect(BASE_URL . 'report_new.php');
}

$student_name   = clean($_POST['student_name'] ?? '');
$admission_no   = clean($_POST['admission_no'] ?? '');
$trainer_select = clean($_POST['trainer_select'] ?? '');
$trainer_custom = clean($_POST['trainer_custom'] ?? '');
$trainer_name   = ($trainer_select === '__other__') ? $trainer_custom : $trainer_select;
$unit_name      = clean($_POST['unit_name'] ?? '');
$unit_code      = clean($_POST['unit_code'] ?? '');
$semester       = clean($_POST['semester'] ?? '');
$academic_year  = clean($_POST['academic_year'] ?? '');
$exam_type      = clean($_POST['exam_type'] ?? 'Main Exam');
$description    = clean($_POST['description'] ?? '');

$allowedSemesters = ['Semester 1', 'Semester 2', 'Semester 3'];
$allowedExamTypes = ['CAT 1', 'CAT 2', 'END TERM', 'Both CATS & End Term'];

$errors = [];
if ($student_name === '')                          $errors[] = 'Student name is required.';
if ($admission_no === '')                           $errors[] = 'Admission number is required.';
if ($trainer_name === '')                           $errors[] = "Trainer's name is required.";
if ($unit_name === '')                              $errors[] = 'Unit name is required.';
if (!in_array($semester, $allowedSemesters, true))  $errors[] = 'Please select a valid semester.';
if ($academic_year === '')                          $errors[] = 'Academic year is required.';
if (!in_array($exam_type, $allowedExamTypes, true)) $exam_type = 'Other';

// Marks are awarded by the administrator during report review.
// The student only identifies which assessment result is missing.
$cat1Mark = null;
$cat2Mark = null;

if (!empty($errors)) {
    flash('main', implode(' ', $errors), 'danger');
    redirect(BASE_URL . 'report_new.php');
}

// Save a newly-typed trainer into the trainers list for future dropdowns
if ($trainer_select === '__other__' && $trainer_custom !== '') {
    $stmt = $pdo->prepare("INSERT IGNORE INTO trainers (trainer_name) VALUES (?)");
    $stmt->execute([$trainer_custom]);
}

$reference_no = generateReferenceNo($pdo);

$stmt = $pdo->prepare("INSERT INTO missing_marks_reports
    (reference_no, user_id, student_name, admission_no, trainer_name, unit_name, unit_code, semester, academic_year, exam_type, cat1_mark, cat2_mark, description, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
$stmt->execute([
    $reference_no, $_SESSION['user_id'], $student_name, $admission_no,
    $trainer_name, $unit_name, $unit_code ?: null, $semester, $academic_year, $exam_type,
    $cat1Mark, $cat2Mark, $description ?: null
]);
$reportId = $pdo->lastInsertId();

// Log initial history entry
$stmt = $pdo->prepare("INSERT INTO report_status_history (report_id, status, remarks, changed_by) VALUES (?, 'Pending', 'Report submitted by student.', ?)");
$stmt->execute([$reportId, $_SESSION['user_id']]);

flash('main', "Your Missing Mark report has been submitted successfully!", 'success');
redirect(BASE_URL . 'report_success.php?id=' . $reportId);
