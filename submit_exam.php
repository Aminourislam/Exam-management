<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $attempt_id = $_POST['attempt_id'];
    
    // Get exam_id for this attempt
    $exam_query = $conn->query("SELECT exam_id FROM exam_attempts WHERE id = $attempt_id");
    $exam_data = $exam_query->fetch_assoc();
    $exam_id = $exam_data['exam_id'];
    
    // Initialize counters
    $score = 0;
    $correct = 0;
    $wrong = 0;
    
    // Process each answer
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question_') === 0) {
            $question_id = str_replace('question_', '', $key);
            
            // Get correct answer
            $correct_query = $conn->query("SELECT correct_answer FROM questions WHERE id = $question_id");
            if ($correct_query->num_rows > 0) {
                $correct_data = $correct_query->fetch_assoc();
                $correct_answer = $correct_data['correct_answer'];
                
                // Save answer to database
                $conn->query("INSERT INTO answers (attempt_id, question_id, selected_answer) 
                             VALUES ($attempt_id, $question_id, '$value')");
                
                // Check if correct
                if ($value == $correct_answer) {
                    $score++;
                    $correct++;
                } else {
                    $wrong++;
                }
            }
        }
    }
    
    // Get total questions
    $total_query = $conn->query("SELECT COUNT(*) as total FROM questions WHERE exam_id = $exam_id");
    $total_data = $total_query->fetch_assoc();
    $total_questions = $total_data['total'];
    $skipped = $total_questions - ($correct + $wrong);
    
    // Update attempt
    $update_sql = "UPDATE exam_attempts 
                   SET submitted = 1, 
                       total_marks = $score,
                       correct_count = $correct,
                       incorrect_count = $wrong,
                       skipped_count = $skipped,
                       end_time = NOW()
                   WHERE id = $attempt_id";
    
    $conn->query($update_sql);
    
    // Redirect to results
    header("Location: detailed_result.php?attempt_id=" . $attempt_id);
    exit();
    
} else {
    header("Location: index.php");
    exit();
}
?>