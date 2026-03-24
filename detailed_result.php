<?php
require_once 'config.php';

$attempt_id = $_GET['attempt_id'] ?? 0;

// Debug - remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch attempt details
$stmt = $conn->prepare("SELECT ea.*, e.exam_name, e.exam_code, e.duration_minutes FROM exam_attempts ea JOIN exams e ON ea.exam_id = e.id WHERE ea.id = ?");
$stmt->bind_param("i", $attempt_id);
$stmt->execute();
$result = $stmt->get_result();
$attempt = $result->fetch_assoc();

if (!$attempt) {
    die("Result not found!");
}

if (!$attempt['submitted']) {
    die("Exam not submitted yet!");
}

// Get questions with answers
$detailed_stmt = $conn->prepare("
    SELECT q.*, a.selected_answer,
           CASE 
               WHEN a.selected_answer IS NULL THEN 'skipped'
               WHEN a.selected_answer = q.correct_answer THEN 'correct'
               ELSE 'incorrect'
           END as status
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id AND a.attempt_id = ?
    WHERE q.exam_id = ?
    ORDER BY q.id
");
$detailed_stmt->bind_param("ii", $attempt_id, $attempt['exam_id']);
$detailed_stmt->execute();
$detailed_result = $detailed_stmt->get_result();

// Separate questions
$correct_questions = [];
$incorrect_questions = [];
$skipped_questions = [];

while ($row = $detailed_result->fetch_assoc()) {
    if ($row['status'] == 'correct') {
        $correct_questions[] = $row;
    } elseif ($row['status'] == 'incorrect') {
        $incorrect_questions[] = $row;
    } else {
        $skipped_questions[] = $row;
    }
}

// Calculate
$total_questions = count($correct_questions) + count($incorrect_questions) + count($skipped_questions);
$correct_percentage = $total_questions > 0 ? round((count($correct_questions) / $total_questions) * 100) : 0;
$incorrect_percentage = $total_questions > 0 ? round((count($incorrect_questions) / $total_questions) * 100) : 0;
$skipped_percentage = $total_questions > 0 ? round((count($skipped_questions) / $total_questions) * 100) : 0;

// Time
$start_time = new DateTime($attempt['start_time']);
$end_time = new DateTime($attempt['end_time']);
$time_taken = $start_time->diff($end_time);
$time_taken_str = $time_taken->format('%H:%I:%S');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Result: <?php echo htmlspecialchars($attempt['exam_name']); ?></title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 1000px; margin: auto; }
        .header { background: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .stat { padding: 20px; border-radius: 8px; text-align: center; }
        .correct-stat { background: #d4edda; color: #155724; border: 3px solid #4CAF50; }
        .wrong-stat { background: #f8d7da; color: #721c24; border: 3px solid #f44336; }
        .skip-stat { background: #fff3cd; color: #856404; border: 3px solid #ff9800; }
        .tabs { display: flex; background: white; border-radius: 8px; margin: 20px 0; }
        .tab { flex: 1; padding: 15px; text-align: center; cursor: pointer; }
        .tab.active { background: #4CAF50; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .question { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 5px solid; }
        .correct-q { border-left-color: #4CAF50; }
        .wrong-q { border-left-color: #f44336; }
        .skip-q { border-left-color: #ff9800; }
        .option { padding: 10px; margin: 5px 0; border: 2px solid #ddd; border-radius: 5px; }
        .correct-opt { background: #d4edda; border-color: #4CAF50; }
        .wrong-opt { background: #f8d7da; border-color: #f44336; }
        .explanation { background: #f8f9fa; padding: 15px; margin-top: 15px; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($attempt['exam_code']); ?></h1>
            <h2><?php echo htmlspecialchars($attempt['exam_name']); ?></h2>
            <p>Time: <?php echo $time_taken_str; ?></p>
            <h3>Score: <?php echo $attempt['total_marks']; ?>/<?php echo $total_questions; ?> (<?php echo $correct_percentage; ?>%)</h3>
        </div>
        
        <div class="stats">
            <div class="stat correct-stat">
                <h3><?php echo count($correct_questions); ?></h3>
                <p>Correct (<?php echo $correct_percentage; ?>%)</p>
            </div>
            <div class="stat wrong-stat">
                <h3><?php echo count($incorrect_questions); ?></h3>
                <p>Wrong (<?php echo $incorrect_percentage; ?>%)</p>
            </div>
            <div class="stat skip-stat">
                <h3><?php echo count($skipped_questions); ?></h3>
                <p>Skipped (<?php echo $skipped_percentage; ?>%)</p>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('correct')">Correct (<?php echo count($correct_questions); ?>)</div>
            <div class="tab" onclick="showTab('wrong')">Wrong (<?php echo count($incorrect_questions); ?>)</div>
            <div class="tab" onclick="showTab('skip')">Skipped (<?php echo count($skipped_questions); ?>)</div>
        </div>
        
        <!-- Correct Tab -->
        <div id="correct-tab" class="tab-content active">
            <h2>✅ Correct Answers</h2>
            <?php foreach ($correct_questions as $i => $q): ?>
            <div class="question correct-q">
                <h4>Q<?php echo $i+1; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h4>
                <div class="options">
                    <?php foreach(['A','B','C','D'] as $opt): if($q['option_'.strtolower($opt)]): ?>
                    <div class="option <?php echo ($q['correct_answer']==$opt)?'correct-opt':''; ?>">
                        <?php echo $opt; ?>. <?php echo htmlspecialchars($q['option_'.strtolower($opt)]); ?>
                        <?php if($q['correct_answer']==$opt): ?><span style="float:right;color:green;">✓ Your Answer</span><?php endif; ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php 
if (!empty($q['explanation'])) {
    echo '<div class="explanation">
            <p><strong>Explanation:</strong> ' . htmlspecialchars($q['explanation']) . '</p>
          </div>';
}
?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Wrong Tab -->
        <div id="wrong-tab" class="tab-content">
            <h2>❌ Wrong Answers</h2>
            <?php foreach ($incorrect_questions as $i => $q): ?>
            <div class="question wrong-q">
                <h4>Q<?php echo $i+1; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h4>
                <div class="options">
                    <?php foreach(['A','B','C','D'] as $opt): if($q['option_'.strtolower($opt)]): ?>
                    <div class="option <?php echo ($q['correct_answer']==$opt)?'correct-opt':''; ?> <?php echo ($q['selected_answer']==$opt)?'wrong-opt':''; ?>">
                        <?php echo $opt; ?>. <?php echo htmlspecialchars($q['option_'.strtolower($opt)]); ?>
                        <?php if($q['correct_answer']==$opt): ?><span style="float:right;color:green;">✓ Correct</span><?php endif; ?>
                        <?php if($q['selected_answer']==$opt): ?><span style="float:right;color:red;">✗ Your Answer</span><?php endif; ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <?php 
if (!empty($q['explanation'])) {
    echo '<div class="explanation">
            <p><strong>Explanation:</strong> ' . htmlspecialchars($q['explanation']) . '</p>
          </div>';
}
?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Skip Tab -->
        <div id="skip-tab" class="tab-content">
            <h2>⏭️ Skipped Questions</h2>
            <?php foreach ($skipped_questions as $i => $q): ?>
            <div class="question skip-q">
                <h4>Q<?php echo $i+1; ?>: <?php echo htmlspecialchars($q['question_text']); ?></h4>
                <div class="options">
                    <?php foreach(['A','B','C','D'] as $opt): if($q['option_'.strtolower($opt)]): ?>
                    <div class="option <?php echo ($q['correct_answer']==$opt)?'correct-opt':''; ?>">
                        <?php echo $opt; ?>. <?php echo htmlspecialchars($q['option_'.strtolower($opt)]); ?>
                        <?php if($q['correct_answer']==$opt): ?><span style="float:right;color:green;">✓ Correct Answer</span><?php endif; ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                
				<?php 
					if (!empty($q['explanation'])) {
						echo '<div class="explanation">
								<p><strong>Explanation:</strong> ' . htmlspecialchars($q['explanation']) . '</p>
							</div>';
					}
				?>	
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align:center;margin-top:30px;">
            <a href="index.php"><button class="btn">🏠 Back to Exams</button></a>
            <a href="exam_detail.php?id=<?php echo $attempt['exam_id']; ?>"><button class="btn" style="background:#2196F3;">🔄 Retake</button></a>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            // Hide all
            document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            // Show selected
            document.getElementById(tab+'-tab').style.display = 'block';
            event.target.classList.add('active');
        }
        
        // Show first tab
        document.addEventListener('DOMContentLoaded', function() {
            showTab('correct');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>