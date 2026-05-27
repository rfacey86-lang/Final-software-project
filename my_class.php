<?php
session_start(); // Start the session
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
$registered_courses = [];

// Handle messages from other pages (e.g., after dropping a class)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

try {
    // Query to get all courses the current user is registered for
    // We use JOINs to combine data from users, registrations, and courses tables
    $query = "
        SELECT
            r.registration_id,
            c.course_code,
            c.course_title,
            c.semester,
            c.credits,
            r.status
        FROM
            registrations r
        JOIN
            courses c ON r.course_id = c.course_id
        WHERE
            r.user_id = :user_id
        ORDER BY
            c.semester DESC, c.course_code ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $registered_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching registered courses: " . $e->getMessage());
    $message = '<div class="alert alert-danger">Could not load your registered classes. Please try again later.</div>';
}
?>
<!-- HTML part follows, with the PHP message and dynamic table rows -->
<!-- ... (HTML from above) ... -->
<div class="container mt-5">
    <h1 class="text-center mb-4">My Registered Classes</h1>
    <?php echo $message; // Display success/error messages here ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Semester</th>
                    <th>Credits</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registered_courses)): ?>
                    <?php foreach ($registered_courses as $reg_course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg_course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($reg_course['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($reg_course['semester']); ?></td>
                            <td><?php echo htmlspecialchars($reg_course['credits']); ?></td>
                            <td><?php echo htmlspecialchars($reg_course['status']); ?></td>
                            <td>
                                <?php if ($reg_course['status'] == 'enrolled'): ?>
                                    <form action="drop_class.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo htmlspecialchars($reg_course['registration_id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to drop this class?');">Drop</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">You are not registered for any classes yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-center">
        <a href="enroll.php" class="btn btn-success">Enroll in New Class</a>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </p>
</div>

YPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registered Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">My Registered Classes</h1>
        <?php // echo $message; // Display success/error messages here ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Semester</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // PHP will dynamically populate table rows here
                    // Example:
                    // <tr>
                    //     <td>CS101</td>
                    //     <td>Intro to CS</td>
                    //     <td>Fall 2026</td>
                    //     <td>3.0</td>
                    //     <td>enrolled</td>
                    //     <td><a href="drop_class.php?registration_id=1" class="btn btn-sm btn-danger">Drop</a></td>
                    // </tr>
                    ?>
                    <?php if (empty($registered_courses)): ?>
                        <tr>
                            <td colspan="6" class="text-center">You are not registered for any classes yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-center">
            <a href="enroll.php" class="btn btn-success">Enroll in New Class</a>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>