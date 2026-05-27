<?php
session_start(); // Start the session to access user data
require_once 'Database.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = '';
$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// --- Fetch available courses for the dropdown ---
$available_courses = [];
try {
    $query_courses = "SELECT course_id, course_code, course_title, semester FROM courses ORDER BY semester DESC, course_code ASC";
    $stmt_courses = $db->prepare($query_courses);
    $stmt_courses->execute();
    $available_courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $message = '<div class="alert alert-danger">Could not load courses. Please try again later.</div>';
}

// --- Handle form submission for enrollment ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    // 1. Input Validation
    if (!$course_id) {
        $message = '<div class="alert alert-danger">Please select a valid course.</div>';
    } else {
        try {
            // 2. Check if user is already registered for this course
            $query_check = "SELECT registration_id FROM registrations WHERE user_id = :user_id AND course_id = :course_id LIMIT 1";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':course_id', $course_id, PDO::PARAM_INT);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                $message = '<div class="alert alert-warning">You are already registered for this course.</div>';
            } else {
                // 3. Check course capacity (optional but good practice)
                $query_capacity = "SELECT max_capacity FROM courses WHERE course_id = :course_id";
                $stmt_capacity = $db->prepare($query_capacity);
                $stmt_capacity->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                $stmt_capacity->execute();
                $course_info = $stmt_capacity->fetch(PDO::FETCH_ASSOC);

                if ($course_info) {
                    $query_current_enrollment = "SELECT COUNT(*) AS enrolled_count FROM registrations WHERE course_id = :course_id AND status = 'enrolled'";
                    $stmt_current_enrollment = $db->prepare($query_current_enrollment);
                    $stmt_current_enrollment->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                    $stmt_current_enrollment->execute();
                    $enrollment_count = $stmt_current_enrollment->fetch(PDO::FETCH_ASSOC)['enrolled_count'];

                    if ($enrollment_count >= $course_info['max_capacity']) {
                        $message = '<div class="alert alert-danger">This course is full.</div>';
                    } else {
                        // 4. Insert new registration into the database
                        $query_insert = "INSERT INTO registrations (user_id, course_id, status) VALUES (:user_id, :course_id, 'enrolled')";
                        $stmt_insert = $db->prepare($query_insert);
                        $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt_insert->bindParam(':course_id', $course_id, PDO::PARAM_INT);

                        if ($stmt_insert->execute()) {
                            $message = '<div class="alert alert-success">Successfully enrolled in the course!</div>';
                        } else {
                            $message = '<div class="alert alert-danger">Failed to enroll in the course. Please try again.</div>';
                        }
                    }
                } else {
                    $message = '<div class="alert alert-danger">Selected course not found.</div>';
                }
            }
        } catch (PDOException $e) {
            error_log("Enrollment error: " . $e->getMessage());
            $message = '<div class="alert alert-danger">An unexpected error occurred during enrollment.</div>';
        }
    }
}
?>
<!-- HTML part follows, with the PHP message and dynamic options -->
<!-- ... (HTML from above) ... -->
<div class="container mt-5">
    <h1 class="text-center">Enroll in a Class</h1>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php echo $message; // Display success/error messages here ?>

            <form action="enroll.php" method="POST">
                <div class="mb-3">
                    <label for="course_id" class="form-label">Select Course</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">-- Select a Course --</option>
                        <?php foreach ($available_courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_title'] . ' (' . $course['semester'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Enroll</button>
            </form>
            <p class="mt-3 text-center"><a href="dashboard.php">Back to Dashboard</a></p>
        </div>
    </div>
</div>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in a Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Enroll in a Class</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php // echo $message; // Display success/error messages here ?>

                <form action="enroll.php" method="POST">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Select Course</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">-- Select a Course --</option>
                            <?php
                            // PHP will dynamically populate options here
                            // Example: <option value="1">CS101 - Intro to CS (Fall 2026)</option>
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Enroll</button>
                </form>
                <p class="mt-3 text-center"><a href="dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>