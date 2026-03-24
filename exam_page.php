<?php
require_once 'config.php';

$attempt_id = $_GET['attempt_id'] ?? 0;
if (!$attempt_id) die("No attempt ID");

// Get attempt
$stmt = $conn->prepare("SELECT ea.*, e.exam_name, e.exam_code, e.duration_minutes 
                        FROM exam_attempts ea 
                        JOIN exams e ON ea.exam_id = e.id 
                        WHERE ea.id = ?");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$attempt = $stmt->get_result()->fetch_assoc();
if (!$attempt) die("Invalid attempt");

// Get questions
$stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY id");
$stmt->bind_param("i", $attempt['exam_id']);
$stmt->execute();
$questions = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exam: <?php echo $attempt['exam_name']; ?></title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .question { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .options label { display: block; margin: 5px 0; }
        .timer { position: fixed; top: 10px; right: 10px; background: red; color: white; padding: 10px; }
    </style>
</head>
<body>
    <div class="timer" id="timer">60:00</div>
    <h1><?php echo $attempt['exam_code']; ?></h1>
    
    <form id="examForm" action="submit_exam.php" method="POST">
        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
        
        <?php $q_num = 1; while($q = $questions->fetch_assoc()): ?>
        <div class="question">
            <p><strong>Q<?php echo $q_num; ?>:</strong> <?php echo $q['question_text']; ?></p>
            <div class="options">
                <?php foreach(['A','B','C','D'] as $opt): if($q['option_'.strtolower($opt)]): ?>
                <label>
                    <input type="radio" name="question_<?php echo $q['id']; ?>" value="<?php echo $opt; ?>">
                    <?php echo $opt; ?>. <?php echo $q['option_'.strtolower($opt)]; ?>
                </label>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php $q_num++; endwhile; ?>
        
        <button type="submit">Submit Exam</button>
    </form>
    
    <script>
        // Simple timer
        let timeLeft = 60 * 60; // 60 minutes in seconds
        function updateTimer() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            document.getElementById('timer').textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            timeLeft--;
            if (timeLeft < 0) document.getElementById('examForm').submit();
        }
        setInterval(updateTimer, 1000);
		
		//from submit checking
		document.getElementById('examForm').addEventListener('submit', function() {
    alert('Form is submitting!');
    return true;
});
		
    </script>
</body>
</html>
<?php $conn->close(); ?>