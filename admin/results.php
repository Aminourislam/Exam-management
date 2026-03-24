<?php
require_once 'config.php';
checkAdminLogin();
require_once '../config.php';

// Handle delete attempt
if (isset($_POST['delete_attempt'])) {
    $attempt_id = $_POST['attempt_id'];
    $conn->query("DELETE FROM exam_attempts WHERE id = $attempt_id");
    $conn->query("DELETE FROM answers WHERE attempt_id = $attempt_id");
    $_SESSION['success'] = "Attempt deleted successfully!";
    header("Location: results.php");
    exit();
}

// Get all attempts with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Count total attempts
$total_attempts = $conn->query("SELECT COUNT(*) as total FROM exam_attempts WHERE submitted=1")->fetch_assoc()['total'];
$total_pages = ceil($total_attempts / $limit);

// Get attempts
$attempts = $conn->query("
    SELECT ea.*, e.exam_code, e.exam_name 
    FROM exam_attempts ea 
    JOIN exams e ON ea.exam_id = e.id 
    WHERE ea.submitted=1 
    ORDER BY ea.end_time DESC 
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - Admin Panel</title>
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
                    <li><a href="exams.php"><i class="fas fa-file-alt"></i> <span>Manage Exams</span></a></li>
                    <li><a href="questions.php"><i class="fas fa-question-circle"></i> <span>Manage Questions</span></a></li>
                    <li><a href="results.php" class="active"><i class="fas fa-chart-bar"></i> <span>View Results</span></a></li>
                    <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> <span>View Site</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-chart-bar"></i> Exam Results</h1>
                <div class="user-menu">
                    <span>Total Attempts: <?php echo $total_attempts; ?></span>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $unique_students = $conn->query("SELECT COUNT(DISTINCT student_name) as total FROM exam_attempts")->fetch_assoc()['total'];
                        ?>
                        <h3><?php echo $unique_students; ?></h3>
                        <p>Unique Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $avg_score = $conn->query("SELECT AVG(total_marks) as avg FROM exam_attempts WHERE submitted=1")->fetch_assoc()['avg'];
                        ?>
                        <h3><?php echo number_format($avg_score, 1); ?></h3>
                        <p>Average Score</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $avg_time = $conn->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg FROM exam_attempts WHERE submitted=1")->fetch_assoc()['avg'];
                        ?>
                        <h3><?php echo number_format($avg_time, 0); ?>m</h3>
                        <p>Avg. Time Taken</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="stat-info">
                        <?php
                        $pass_rate = $conn->query("
                            SELECT COUNT(*) as passed 
                            FROM exam_attempts ea 
                            JOIN exams e ON ea.exam_id = e.id 
                            WHERE ea.submitted=1 AND (ea.total_marks * 100.0 / e.total_questions) >= 40
                        ")->fetch_assoc()['passed'];
                        $pass_percentage = $total_attempts > 0 ? round(($pass_rate / $total_attempts) * 100) : 0;
                        ?>
                        <h3><?php echo $pass_percentage; ?>%</h3>
                        <p>Pass Rate (40%+)</p>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Exam Attempts</h3>
                    <div class="search-box">
                        <input type="text" placeholder="Search students..." class="form-control" style="width: 200px;">
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Exam</th>
                                <th>Score</th>
                                <th>Correct/Wrong/Skip</th>
                                <th>Time Taken</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($attempt = $attempts->fetch_assoc()): 
                                $start = new DateTime($attempt['start_time']);
                                $end = new DateTime($attempt['end_time']);
                                $diff = $start->diff($end);
                                $time_taken = $diff->format('%H:%I:%S');
                                
                                // Get exam total questions
                                $exam_info = $conn->query("SELECT total_questions FROM exams WHERE id = " . $attempt['exam_id'])->fetch_assoc();
                                $percentage = $exam_info['total_questions'] > 0 ? round(($attempt['total_marks'] / $exam_info['total_questions']) * 100) : 0;
                            ?>
                            <tr>
                                <td><?php echo $attempt['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($attempt['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($attempt['exam_code']); ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo $attempt['total_marks']; ?>/<?php echo $exam_info['total_questions']; ?></strong>
                                        <span class="badge <?php echo $percentage >= 40 ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $percentage; ?>%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        <span style="color: #10b981;">✓ <?php echo $attempt['correct_count']; ?></span> |
                                        <span style="color: #ef4444;">✗ <?php echo $attempt['incorrect_count']; ?></span> |
                                        <span style="color: #f59e0b;">⏭️ <?php echo $attempt['skipped_count']; ?></span>
                                    </div>
                                </td>
                                <td><?php echo $time_taken; ?></td>
                                <td><?php echo date('M d, Y', strtotime($attempt['end_time'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="../detailed_result.php?attempt_id=<?php echo $attempt['id']; ?>" target="_blank" 
                                           class="btn btn-success btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this attempt?')">
                                            <input type="hidden" name="attempt_id" value="<?php echo $attempt['id']; ?>">
                                            <button type="submit" name="delete_attempt" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination" style="padding: 20px; text-align: center;">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : ''; ?>"
                       style="margin: 0 2px;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>