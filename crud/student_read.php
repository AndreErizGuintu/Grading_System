<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$studentId = (int)($studentId ?? 0);
$syId = $syId ? (int)$syId : null;
$semId = $semId ? (int)$semId : null;

$sql = "
    SELECT
        c.course_code,
        c.course_name,
        t.full_name AS teacher_name,
        g.prelim,
        g.midterm,
        g.finals,
        g.semestral,
        g.status
    FROM tb_enrollments e
    INNER JOIN tb_course_offerings o ON o.offering_id = e.offering_id
    INNER JOIN tb_courses c ON c.course_id = o.course_id
    INNER JOIN tb_teachers t ON t.teacher_id = o.teacher_id
    LEFT JOIN tb_grades g ON g.enrollment_id = e.enrollment_id
    WHERE e.student_id = ?
";

$params = [$studentId];
$types = 'i';

if ($syId) {
    $sql .= " AND o.sy_id = ?";
    $params[] = $syId;
    $types .= 'i';
}
if ($semId) {
    $sql .= " AND o.sem_id = ?";
    $params[] = $semId;
    $types .= 'i';
}

$sql .= " ORDER BY c.course_code";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    error_log('Prepare error in student_read.php: ' . $mysqli->error);
    $rows = [];
} else {
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        error_log('Execute error in student_read.php: ' . $stmt->error);
        $rows = [];
    } else {
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}
