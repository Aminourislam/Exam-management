<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_id = $_POST['exam_id'];
    $student_name = $_POST['student_name'];
    
    $stmt = $conn->prepare("INSERT INTO exam_attempts (exam_id, student_name, start_time) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $exam_id, $student_name);
    $stmt->execute();
    $attempt_id = $stmt->insert_id;
    
    $_SESSION['attempt_id'] = $attempt_id;
    
    header("Location: exam_page.php?attempt_id=" . $attempt_id);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>