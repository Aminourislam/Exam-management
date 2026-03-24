<?php
require_once 'config.php';
checkAdminLogin();
require_once '../config.php';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_exam'])) {
        $exam_code = $_POST['exam_code'];
        $exam_name = $_POST['exam_name'];
        $academic_year = $_POST['academic_year'];
        $description = $_POST['description'];
        $duration = $_POST['duration_minutes'];
        $total_questions = $_POST['total_questions'];
        
        $stmt = $conn->prepare("INSERT INTO exams (exam_code, exam_name, academic_year, description, duration_minutes, total_questions) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $exam_code, $exam_name, $academic_year, $description, $duration, $total_questions);
        $stmt->execute();
        
        $_SESSION['success'] = "Exam added successfully!";
        header("Location: exams.php");
        exit();
    }
    
    if (isset($_POST['delete_exam'])) {
        $exam_id = $_POST['exam_id'];
        $conn->query("DELETE FROM exams WHERE id = $exam_id");
        $_SESSION['success'] = "Exam deleted successfully!";
        header("Location: exams.php");
        exit();
    }
}

// Get all exams
$exams = $conn->query("SELECT * FROM exams ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar (same as index.php) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> <span>Exam Portal</span></h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="exams.php" class="active"><i class="fas fa-file-alt"></i> <span>Manage Exams</span></a></li>
                    <li><a href="questions.php"><i class="fas fa-question-circle"></i> <span>Manage Questions</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-bar"></i> <span>View Results</span></a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <span>View Site</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-file-alt"></i> Manage Exams</h1>
                <button class="btn btn-primary" onclick="openModal('addExamModal')">
                    <i class="fas fa-plus"></i> Add New Exam
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Exams</h3>
                    <div class="search-box">
                        <input type="text" placeholder="Search exams..." class="form-control" style="width: 200px;">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Exam Code</th>
                                <th>Exam Name</th>
                                <th>Academic Year</th>
                                <th>Duration</th>
                                <th>Questions</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($exam = $exams->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $exam['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($exam['exam_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['academic_year']); ?></td>
                                <td><?php echo $exam['duration_minutes']; ?> min</td>
                                <td><?php echo $exam['total_questions']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-question-circle"></i> Questions
                                        </a>
                                        <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this exam?')">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                            <button type="submit" name="delete_exam" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Exam Modal -->
    <div class="modal" id="addExamModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Exam</h3>
                <button class="close-modal" onclick="closeModal('addExamModal')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Exam Code *</label>
                        <input type="text" name="exam_code" class="form-control" required 
                               placeholder="e.g., CU A 24-25">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exam Name *</label>
                        <input type="text" name="exam_name" class="form-control" required 
                               placeholder="e.g., CU A 24-25 Exam">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Academic Year *</label>
                        <input type="text" name="academic_year" class="form-control" required 
                               placeholder="e.g., 2024-2025">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" 
                                  placeholder="Exam description..."></textarea>
                    </div>
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Duration (minutes) *</label>
                            <input type="number" name="duration_minutes" class="form-control" required 
                                   min="1" value="60">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Questions *</label>
                            <input type="number" name="total_questions" class="form-control" required 
                                   min="1" value="20">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('addExamModal')">Cancel</button>
                    <button type="submit" name="add_exam" class="btn btn-primary">Add Exam</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>