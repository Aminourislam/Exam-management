<?php
require_once 'config.php';
checkAdminLogin();
require_once '../config.php';

$exam_id = $_GET['exam_id'] ?? 0;

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_question'])) {
        $exam_id = $_POST['exam_id'];
        $question_text = $_POST['question_text'];
        $option_a = $_POST['option_a'];
        $option_b = $_POST['option_b'];
        $option_c = $_POST['option_c'];
        $option_d = $_POST['option_d'];
        $correct_answer = $_POST['correct_answer'];
        $explanation = $_POST['explanation'];
        
        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $exam_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Question added successfully!";
            header("Location: questions.php?exam_id=$exam_id&added=" . $stmt->insert_id);
            exit();
        } else {
            $_SESSION['error'] = "❌ Error: " . $stmt->error;
        }
    }
    
    if (isset($_POST['update_question'])) {
        $question_id = $_POST['question_id'];
        $question_text = $_POST['question_text'];
        $option_a = $_POST['option_a'];
        $option_b = $_POST['option_b'];
        $option_c = $_POST['option_c'];
        $option_d = $_POST['option_d'];
        $correct_answer = $_POST['correct_answer'];
        $explanation = $_POST['explanation'];
        
        $stmt = $conn->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, explanation=? WHERE id=?");
        $stmt->bind_param("sssssssi", $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $explanation, $question_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "✅ Question updated successfully!";
        } else {
            $_SESSION['error'] = "❌ Error: " . $conn->error;
        }
        header("Location: questions.php?exam_id=$exam_id");
        exit();
    }
    
    if (isset($_POST['delete_question'])) {
        $question_id = $_POST['question_id'];
        $conn->query("DELETE FROM questions WHERE id = $question_id");
        $_SESSION['success'] = "🗑️ Question deleted successfully!";
        header("Location: questions.php?exam_id=$exam_id");
        exit();
    }
}

// Get exams for dropdown
$exams = $conn->query("SELECT * FROM exams ORDER BY exam_code");

// Get questions for selected exam (newest first)
$questions_result = null;
$current_exam = null;
if ($exam_id > 0) {
    $questions_result = $conn->query("SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id DESC");
    $current_exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
}

// Scroll to newly added question
$added_id = $_GET['added'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Additional mobile styles */
        @media (max-width: 768px) {
            .question-form-grid {
                grid-template-columns: 1fr !important;
            }
            .question-card {
                padding: 8px !important;
            }
            .question-actions {
                flex-direction: column;
                gap: 8px;
            }
            .question-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .option-row {
                grid-template-columns: 1fr !important;
            }
            .table-container {
                overflow-x: auto;
                font-size: 14px;
            }
            .data-table th, .data-table td {
                padding: 10px 8px !important;
                font-size: 13px;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            .top-bar h1 {
                font-size: 1.5rem;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
        }
        
        /* Question form styling */
        .question-form-container {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }
        
        .question-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-full-width {
            grid-column: 1 / -1;
        }
        
        .question-card {
            background: var(--bg-card);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s;
        }
        
        .question-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent-primary);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .question-number {
            background: var(--accent-primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .question-text {
            flex: 1;
            margin-left: 15px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .options-container {
            margin: 15px 0;
        }
        
        .option-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .option-item {
            background: var(--bg-dark);
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .option-letter {
            width: 24px;
            height: 24px;
            background: var(--bg-hover);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        
        .option-correct {
            background: rgba(16, 185, 129, 0.2);
            border-color: var(--accent-success);
        }
        
        .option-correct .option-letter {
            background: var(--accent-success);
            color: white;
        }
        
        .question-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        /* Highlight newly added question */
        .question-highlight {
            animation: highlight 2s ease;
            border: 2px solid var(--accent-success) !important;
        }
        
        @keyframes highlight {
            0% { background: rgba(16, 185, 129, 0.3); }
            100% { background: var(--bg-card); }
        }
        
        /* Mobile specific */
        .mobile-only {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-only {
                display: block;
            }
            .desktop-only {
                display: none;
            }
            .question-header {
                flex-direction: column;
                gap: 10px;
            }
            .question-text {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> <span class="desktop-only">Exam Portal</span></h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="exams.php"><i class="fas fa-file-alt"></i> <span>Manage Exams</span></a></li>
                    <li><a href="questions.php" class="active"><i class="fas fa-question-circle"></i> <span>Questions</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-bar"></i> <span>Results</span></a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <span>View Site</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-question-circle"></i> Manage Questions</h1>
                <div class="mobile-only">
                    <button class="btn btn-primary" onclick="scrollToForm()">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <!-- Exam Selector -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt"></i> Select Exam</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-row" style="display: flex; gap: 15px; align-items: center;">
                        <div class="form-group" style="flex: 1;">
                            <select name="exam_id" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Select Exam --</option>
                                <?php 
                                $exams_list = $conn->query("SELECT * FROM exams ORDER BY exam_code");
                                while($exam = $exams_list->fetch_assoc()):
                                ?>
                                <option value="<?php echo $exam['id']; ?>" <?php echo $exam_id == $exam['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_code']); ?> - <?php echo htmlspecialchars($exam['exam_name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php if ($exam_id > 0): ?>
                        <div>
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> <?php echo htmlspecialchars($current_exam['exam_code']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($exam_id > 0): ?>
            <!-- Add Question Form (BELOW exam selector) -->
            <div class="question-form-container" id="questionForm">
                <h3 style="margin-bottom: 20px; color: var(--accent-primary);">
                    <i class="fas fa-plus-circle"></i> Add New Question
                </h3>
                <form method="POST">
                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                    
                    <div class="form-group form-full-width">
                        <label class="form-label">Question Text *</label>
                        <textarea name="question_text" class="form-control" rows="3" required 
                                  placeholder="Enter the question..." style="resize: vertical; min-height: 100px;"></textarea>
                    </div>
                    
                    <div class="question-form-grid">
                        <div class="form-group">
                            <label class="form-label">Option A *</label>
                            <input type="text" name="option_a" class="form-control" required 
                                   placeholder="Enter option A">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option B *</label>
                            <input type="text" name="option_b" class="form-control" required 
                                   placeholder="Enter option B">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" class="form-control" 
                                   placeholder="Enter option C (optional)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" class="form-control" 
                                   placeholder="Enter option D (optional)">
                        </div>
                    </div>
                    
                    <div class="question-form-grid">
                        <div class="form-group">
                            <label class="form-label">Correct Answer *</label>
                            <select name="correct_answer" class="form-control" required>
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Explanation (Optional)</label>
                            <input type="text" name="explanation" class="form-control" 
                                   placeholder="Brief explanation for answer">
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <button type="reset" class="btn">
                            <i class="fas fa-redo"></i> Clear
                        </button>
                        <button type="submit" name="add_question" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Question
                        </button>
                    </div>
                </form>
            </div>

            <!-- Questions List (NEWEST FIRST) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> Questions 
                        <span class="badge badge-primary"><?php echo $questions_result->num_rows; ?> total</span>
                    </h3>
                    <div class="desktop-only">
                        <button class="btn btn-primary" onclick="scrollToForm()">
                            <i class="fas fa-plus"></i> Add New Question
                        </button>
                    </div>
                </div>
                
                <?php if ($questions_result->num_rows > 0): ?>
                <div class="questions-list" style="padding: 20px;">
                    <?php 
                    $counter = 1;
                    while($q = $questions_result->fetch_assoc()): 
                        $is_new = ($added_id == $q['id']);
                    ?>
                    <div class="question-card <?php echo $is_new ? 'question-highlight' : ''; ?>" id="question-<?php echo $q['id']; ?>">
                        <div class="question-header">
                            <div class="question-number"><?php echo $counter; ?></div>
                            <div class="question-text"><?php echo htmlspecialchars($q['question_text']); ?></div>
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> <?php echo $q['correct_answer']; ?>
                            </span>
                        </div>
                        
                        <div class="options-container">
                            <div class="option-row">
                                <div class="option-item <?php echo $q['correct_answer'] == 'A' ? 'option-correct' : ''; ?>">
                                    <div class="option-letter">A</div>
                                    <div><?php echo htmlspecialchars($q['option_a']); ?></div>
                                </div>
                                <div class="option-item <?php echo $q['correct_answer'] == 'B' ? 'option-correct' : ''; ?>">
                                    <div class="option-letter">B</div>
                                    <div><?php echo htmlspecialchars($q['option_b']); ?></div>
                                </div>
                            </div>
                            <?php if (!empty($q['option_c']) || !empty($q['option_d'])): ?>
                            <div class="option-row">
                                <?php if (!empty($q['option_c'])): ?>
                                <div class="option-item <?php echo $q['correct_answer'] == 'C' ? 'option-correct' : ''; ?>">
                                    <div class="option-letter">C</div>
                                    <div><?php echo htmlspecialchars($q['option_c']); ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($q['option_d'])): ?>
                                <div class="option-item <?php echo $q['correct_answer'] == 'D' ? 'option-correct' : ''; ?>">
                                    <div class="option-letter">D</div>
                                    <div><?php echo htmlspecialchars($q['option_d']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($q['explanation'])): ?>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px 15px; border-radius: 6px; margin-top: 10px; font-size: 14px;">
                            <strong><i class="fas fa-lightbulb"></i> Explanation:</strong> 
                            <?php echo htmlspecialchars($q['explanation']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="question-actions">
                            <button class="btn btn-warning btn-sm" onclick="editQuestion(
                                <?php echo $q['id']; ?>,
                                '<?php echo addslashes($q['question_text']); ?>',
                                '<?php echo addslashes($q['option_a']); ?>',
                                '<?php echo addslashes($q['option_b']); ?>',
                                '<?php echo addslashes($q['option_c']); ?>',
                                '<?php echo addslashes($q['option_d']); ?>',
                                '<?php echo $q['correct_answer']; ?>',
                                '<?php echo addslashes($q['explanation']); ?>'
                            )">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this question?');">
                                <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                <button type="submit" name="delete_question" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php 
                    $counter++;
                    endwhile; 
                    ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    <i class="fas fa-question-circle" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No Questions Yet</h3>
                    <p>Start by adding questions using the form above.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <i class="fas fa-file-alt" style="font-size: 64px; color: var(--text-secondary); margin-bottom: 20px;"></i>
                <h3>Select an Exam</h3>
                <p>Choose an exam from the dropdown above to manage its questions.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Edit Question Modal (for mobile/desktop) -->
    <div class="modal" id="editQuestionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Question</h3>
                <button class="close-modal" onclick="closeModal('editQuestionModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="question_id" id="edit_question_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Question Text *</label>
                        <textarea name="question_text" id="edit_question_text" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Option A *</label>
                            <input type="text" name="option_a" id="edit_option_a" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option B *</label>
                            <input type="text" name="option_b" id="edit_option_b" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option C</label>
                            <input type="text" name="option_c" id="edit_option_c" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Option D</label>
                            <input type="text" name="option_d" id="edit_option_d" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correct Answer *</label>
                        <select name="correct_answer" id="edit_correct_answer" class="form-control" required>
                            <option value="A">Option A</option>
                            <option value="B">Option B</option>
                            <option value="C">Option C</option>
                            <option value="D">Option D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Explanation (Optional)</label>
                        <input type="text" name="explanation" id="edit_explanation" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('editQuestionModal')">Cancel</button>
                    <button type="submit" name="update_question" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Question
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Scroll to newly added question
        <?php if ($added_id > 0): ?>
        setTimeout(() => {
            const element = document.getElementById('question-<?php echo $added_id; ?>');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 500);
        <?php endif; ?>

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Edit question function
        function editQuestion(id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation) {
            document.getElementById('edit_question_id').value = id;
            document.getElementById('edit_question_text').value = question_text;
            document.getElementById('edit_option_a').value = option_a;
            document.getElementById('edit_option_b').value = option_b;
            document.getElementById('edit_option_c').value = option_c;
            document.getElementById('edit_option_d').value = option_d;
            document.getElementById('edit_correct_answer').value = correct_answer;
            document.getElementById('edit_explanation').value = explanation;
            
            openModal('editQuestionModal');
        }

        // Scroll to form
        function scrollToForm() {
            const form = document.getElementById('questionForm');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                // Focus on first input
                form.querySelector('textarea').focus();
            }
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Auto-resize textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });

        // Mobile menu toggle (for very small screens)
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('mobile-show');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>