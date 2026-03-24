<?php
require_once 'config.php';
checkAdminLogin();
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Exam Portal</title>
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
                    <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                    <li><a href="exams.php"><i class="fas fa-file-alt"></i> <span>Manage Exams</span></a></li>
                    <li><a href="questions.php"><i class="fas fa-question-circle"></i> <span>Manage Questions</span></a></li>
                    <li><a href="results.php"><i class="fas fa-chart-bar"></i> <span>View Results</span></a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <span>View Site</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-menu">
                    <span>Welcome, Admin</span>
                    <div class="avatar">A</div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_exams = $conn->query("SELECT COUNT(*) as total FROM exams")->fetch_assoc()['total'];
                        ?>
                        <h3><?php echo $total_exams; ?></h3>
                        <p>Total Exams</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_questions = $conn->query("SELECT COUNT(*) as total FROM questions")->fetch_assoc()['total'];
                        ?>
                        <h3><?php echo $total_questions; ?></h3>
                        <p>Total Questions</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $total_attempts = $conn->query("SELECT COUNT(*) as total FROM exam_attempts")->fetch_assoc()['total'];
                        ?>
                        <h3><?php echo $total_attempts; ?></h3>
                        <p>Exam Attempts</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $avg_score = $conn->query("SELECT AVG(total_marks) as avg FROM exam_attempts WHERE submitted=1")->fetch_assoc()['avg'];
                        ?>
                        <h3><?php echo number_format($avg_score, 1); ?></h3>
                        <p>Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Recent Exams -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Recent Exams</h3>
                    <a href="exams.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Exam Code</th>
                                <th>Exam Name</th>
                                <th>Year</th>
                                <th>Duration</th>
                                <th>Questions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $exams = $conn->query("SELECT * FROM exams ORDER BY created_at DESC LIMIT 5");
                            while($exam = $exams->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exam['exam_code']); ?></td>
                                <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                <td><?php echo htmlspecialchars($exam['academic_year']); ?></td>
                                <td><?php echo $exam['duration_minutes']; ?> min</td>
                                <td><?php echo $exam['total_questions']; ?></td>
                                <td><span class="badge badge-success">Active</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Attempts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Recent Exam Attempts</h3>
                    <a href="results.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Exam</th>
                                <th>Score</th>
                                <th>Time Taken</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $attempts = $conn->query("
                                SELECT ea.*, e.exam_code 
                                FROM exam_attempts ea 
                                JOIN exams e ON ea.exam_id = e.id 
                                WHERE ea.submitted=1 
                                ORDER BY ea.end_time DESC 
                                LIMIT 5
                            ");
                            while($attempt = $attempts->fetch_assoc()):
                                $start = new DateTime($attempt['start_time']);
                                $end = new DateTime($attempt['end_time']);
                                $diff = $start->diff($end);
                                $time_taken = $diff->format('%H:%I:%S');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['exam_code']); ?></td>
                                <td><strong><?php echo $attempt['total_marks']; ?></strong></td>
                                <td><?php echo $time_taken; ?></td>
                                <td><?php echo date('M d, Y', strtotime($attempt['end_time'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Modal functionality
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside
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