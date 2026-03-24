<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Exam Portal</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .exam { background: #f5f5f5; padding: 15px; margin: 10px; border-radius: 5px; }
        .exam:hover { background: #e0e0e0; }
        a { text-decoration: none; color: #333; display: block; }
    </style>
</head>
<body>
    <h1>Available Exams</h1>
    <?php
    $sql = "SELECT * FROM exams ORDER BY academic_year DESC";
    $result = $conn->query($sql);
    while ($exam = $result->fetch_assoc()): ?>
        <div class="exam">
            <a href="exam_detail.php?id=<?php echo $exam['id']; ?>">
                <h3><?php echo $exam['exam_code']; ?></h3>
                <p><?php echo $exam['description']; ?></p>
                <small><?php echo $exam['academic_year']; ?></small>
            </a>
        </div>
    <?php endwhile; ?>
</body>
</html>
<?php $conn->close(); ?>