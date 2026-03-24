<?php
require_once 'config.php';

$exam_id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->bind_param("i", $exam_id);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
if (!$exam) die("Exam not found");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $exam['exam_code']; ?></title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: auto; }
        .info { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
        input { padding: 10px; width: 300px; margin: 10px 0; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1><?php echo $exam['exam_code']; ?></h1>
    <div class="info">
        <p><strong>Duration:</strong> <?php echo $exam['duration_minutes']; ?> minutes</p>
        <p><strong>Questions:</strong> <?php echo $exam['total_questions']; ?></p>
        <p><?php echo $exam['description']; ?></p>
    </div>
    
    <form action="start_exam.php" method="POST">
        <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
        <input type="text" name="student_name" placeholder="Your Name" required>
        <br>
        <button type="submit">Start Exam</button>
    </form>
</body>
</html>
<?php $conn->close(); ?>