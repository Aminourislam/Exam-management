<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auto_save'])) {
    $attempt_id = $_POST['attempt_id'];
    $answers = json_decode($_POST['answers'], true);
    
    // Validate attempt is still active
    $check_stmt = $conn->prepare("SELECT submitted FROM exam_attempts WHERE id = ?");
    $check_stmt->bind_param("i", $attempt_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $attempt = $check_result->fetch_assoc();
        
        // If exam is already submitted, don't save
        if ($attempt['submitted']) {
            echo "Exam already submitted";
            exit();
        }
        
        // Save each answer
        foreach ($answers as $question_name => $answer) {
            $question_id = str_replace('question_', '', $question_name);
            
            // Validate question exists
            $validate_stmt = $conn->prepare("SELECT id FROM questions WHERE id = ?");
            $validate_stmt->bind_param("i", $question_id);
            $validate_stmt->execute();
            $validate_result = $validate_stmt->get_result();
            
            if ($validate_result->num_rows > 0) {
                // Check if answer already exists
                $check_answer_stmt = $conn->prepare("SELECT id FROM answers WHERE attempt_id = ? AND question_id = ?");
                $check_answer_stmt->bind_param("ii", $attempt_id, $question_id);
                $check_answer_stmt->execute();
                $check_answer_result = $check_answer_stmt->get_result();
                
                if ($check_answer_result->num_rows > 0) {
                    // Update existing answer
                    $update_stmt = $conn->prepare("UPDATE answers SET selected_answer = ? WHERE attempt_id = ? AND question_id = ?");
                    $update_stmt->bind_param("sii", $answer, $attempt_id, $question_id);
                    $update_stmt->execute();
                } else {
                    // Insert new answer
                    $insert_stmt = $conn->prepare("INSERT INTO answers (attempt_id, question_id, selected_answer) VALUES (?, ?, ?)");
                    $insert_stmt->bind_param("iis", $attempt_id, $question_id, $answer);
                    $insert_stmt->execute();
                }
            }
        }
        
        echo "Saved successfully at " . date('H:i:s');
        
        // Update last activity time
        $update_activity = $conn->prepare("UPDATE exam_attempts SET end_time = NOW() WHERE id = ?");
        $update_activity->bind_param("i", $attempt_id);
        $update_activity->execute();
        
    } else {
        echo "Invalid attempt";
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['single_save'])) {
    // Save single answer (for immediate save)
    $attempt_id = $_POST['attempt_id'];
    $question_id = $_POST['question_id'];
    $answer = $_POST['answer'];
    
    // Validate attempt is still active
    $check_stmt = $conn->prepare("SELECT submitted FROM exam_attempts WHERE id = ?");
    $check_stmt->bind_param("i", $attempt_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $attempt = $check_result->fetch_assoc();
        
        if ($attempt['submitted']) {
            echo "Exam already submitted";
            exit();
        }
        
        // Check if answer already exists
        $check_answer_stmt = $conn->prepare("SELECT id FROM answers WHERE attempt_id = ? AND question_id = ?");
        $check_answer_stmt->bind_param("ii", $attempt_id, $question_id);
        $check_answer_stmt->execute();
        $check_answer_result = $check_answer_stmt->get_result();
        
        if ($check_answer_result->num_rows > 0) {
            // Update existing answer
            $update_stmt = $conn->prepare("UPDATE answers SET selected_answer = ? WHERE attempt_id = ? AND question_id = ?");
            $update_stmt->bind_param("sii", $answer, $attempt_id, $question_id);
            $update_stmt->execute();
        } else {
            // Insert new answer
            $insert_stmt = $conn->prepare("INSERT INTO answers (attempt_id, question_id, selected_answer) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iis", $attempt_id, $question_id, $answer);
            $insert_stmt->execute();
        }
        
        echo "Answer saved";
        
    } else {
        echo "Invalid attempt";
    }
    
} else {
    // Invalid request
    header("HTTP/1.0 403 Forbidden");
    echo "Access denied";
}
?>