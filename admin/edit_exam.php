<?php
require_once 'config.php';
checkAdminLogin();
require_once '../config.php';

$question_id = $_GET['id'] ?? 0;

// Get question details
$question = $conn->query("SELECT * FROM questions WHERE id = $question_id")->fetch_assoc();
if (!$question) {
    die("Question not found!");
}

// Get exams for dropdown
$exams = $conn->query("SELECT * FROM exams ORDER BY exam_code");

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_question'])) {
    $exam_id = $_POST['exam_id'];
    $question_text = $_POST['question_text'];
    $option_a = $_POST['option_a'];
    $option_b = $_POST['option_b'];
    $option_c = $_POST['option_c'];
    $option_d = $_POST['option_d'];
    $correct_answer = $_POST['correct_answer'];
    $explanation = $_POST['explanation'];
    
    $stmt = $conn->prepare("UPDATE questions SET exam_id=?, question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, explanation=? WHERE id=?");
    $stmt->bind_param("isssssssi", $exam_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $question_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Question updated successfully!";
        header("Location: questions.php?exam_id=" . $exam_id);
        exit();
    } else {
        $error = "Error updating question: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> <span>Exam Portal</span></h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="exams.php"><i class="fas fa-file-alt"></i> <span>Manage Exams</span></a></li>
                    <li><a href="questions.php"><i class="fas fa-question-circle"></i> <span>Manage Questions</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-bar"></i> <span>View Results</span></a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <span>View Site</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-edit"></i> Edit Question</h1>
                <a href="questions.php?exam_id=<?php echo $question['exam_id']; ?>" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Questions
                </a>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Question #<?php echo $question['id']; ?></h3>
                </div>
                <form method="POST">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Select Exam *</label>
                            <select name="exam_id" class="form-control" required>
                                <option value="">-- Select Exam --</option>
                                <?php while($exam = $exams->fetch_assoc()): ?>
                                <option value="<?php echo $exam['id']; ?>" <?php echo $question['exam_id'] == $exam['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_code']); ?> - <?php echo htmlspecialchars($exam['exam_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Question Text *</label>
                            <textarea name="question_text" class="form-control" rows="4" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label class="form-label">Option A *</label>
                                <input type="text" name="option_a" class="form-control" required 
                                       value="<?php echo htmlspecialchars($question['option_a']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Option B *</label>
                                <input type="text" name="option_b" class="form-control" required 
                                       value="<?php echo htmlspecialchars($question['option_b']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Option C</label>
                                <input type="text" name="option_c" class="form-control" 
                                       value="<?php echo htmlspecialchars($question['option_c']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Option D</label>
                                <input type="text" name="option_d" class="form-control" 
                                       value="<?php echo htmlspecialchars($question['option_d']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Correct Answer *</label>
                            <select name="correct_answer" class="form-control" required>
                                <option value="A" <?php echo $question['correct_answer'] == 'A' ? 'selected' : ''; ?>>Option A</option>
                                <option value="B" <?php echo $question['correct_answer'] == 'B' ? 'selected' : ''; ?>>Option B</option>
                                <option value="C" <?php echo $question['correct_answer'] == 'C' ? 'selected' : ''; ?>>Option C</option>
                                <option value="D" <?php echo $question['correct_answer'] == 'D' ? 'selected' : ''; ?>>Option D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Explanation (Optional)</label>
                            <textarea name="explanation" class="form-control" rows="3"><?php echo htmlspecialchars($question['explanation']); ?></textarea>
                        </div>
                    </div>
                    <div class="card-footer" style="padding: 20px; border-top: 1px solid var(--border-color); text-align: right;">
                        <button type="button" class="btn" onclick="window.history.back()">Cancel</button>
                        <button type="submit" name="update_question" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Question
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>