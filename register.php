<?php
// This PHP block must be at the very top of the file, before any HTML output.

// Include your database connection class
// Make sure 'Database.php' is in the same directory or adjust the path accordingly.
require_once 'Database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Variable to store success or error messages
$message = '';

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- 1. Input Retrieval and Sanitization ---
    // Retrieve data from the POST request.
    // trim() removes whitespace from the beginning and end of the string.
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password is not trimmed as spaces might be intentional
    $confirm_password = $_POST['confirm_password'];

    // --- 2. Input Validation ---
    // Perform various checks to ensure data quality and security.
    // If any validation fails, an error message is set.

    // Check if any required fields are empty
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = '<div class="alert alert-danger">All fields are required.</div>';
    }
    // Validate email format using PHP's built-in filter
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Invalid email format.</div>';
    }
    // Enforce a minimum password length for security
    elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger">Password must be at least 6 characters long.</div>';
    }
    // Check if the password and confirm password fields match
    elseif ($password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Passwords do not match.</div>';
    }
    else {
        // If initial validation passes, proceed to database checks within a try-catch block
        try {
            // --- 3. Uniqueness Check (Username or Email) ---
            // Before inserting, check if a user with the same username or email already exists.
            // This prevents duplicate accounts and ensures unique identifiers.
            // Use a PDO prepared statement to prevent SQL injection.
            $query = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // If rowCount() is greater than 0, a user with that username or email already exists
            if ($stmt->rowCount() > 0) {
                $message = '<div class="alert alert-warning">Username or Email already exists. Please choose another.</div>';
            } else {
                // --- 4. Password Hashing (CRITICAL SECURITY STEP) ---
                // NEVER store plain-text passwords in the database.
                // password_hash() creates a secure, one-way hash of the password.
                // This hash is what will be stored in the 'password_hash' column.
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // --- 5. Database Insertion ---
                // Insert the new user's data into the 'users' table.
                // Use a PDO prepared statement for secure insertion.
                $query = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password_hash', $password_hash); // Store the hashed password

                // Execute the statement
                if ($stmt->execute()) {
                    // --- 6. Success Redirection ---
                    // If registration is successful, redirect the user to a dedicated success page.
                    // This provides a clean user experience and prevents form re-submission issues.
                    header("Location: registration_successful.php");
                    exit(); // IMPORTANT: Always call exit() after a header redirect
                } else {
                    // If insertion failed for some reason (e.g., database issue)
                    $message = '<div class="alert alert-danger">Something went wrong during registration. Please try again.</div>';
                }
            }
        } catch (PDOException $e) {
            // --- 7. Error Handling ---
            // Catch any PDO (database) exceptions that occur during the process.
            // Log the detailed error for debugging purposes (e.g., to a server log file).
            error_log("Registration error: " . $e->getMessage());
            // Display a generic, user-friendly error message to the user.
            $message = '<div class="alert alert-danger">An unexpected database error occurred. Please try again later.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <!-- Link to Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-container">
            <h1 class="text-center mb-4">Register for an Account</h1>

            <!-- Display success or error messages here -->
            <?php echo $message; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required
                           value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <!-- Link to Bootstrap JS (optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>