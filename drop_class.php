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
$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $registration_id = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);

    if (!$registration_id) {
        $_SESSION['message'] = '<div class="alert alert-danger">Invalid registration ID.</div>';
        header("Location: my_classes.php");
        exit();
    }

    try {
        // Ensure the user is authorized to drop this specific registration
        // This prevents a user from dropping another user's class by manipulating the ID
        $query_check_ownership = "SELECT user_id FROM registrations WHERE registration_id = :registration_id LIMIT 1";
        $stmt_check_ownership = $db->prepare($query_check_ownership);
        $stmt_check_ownership->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);
        $stmt_check_ownership->execute();
        $reg_owner = $stmt_check_ownership->fetch(PDO::FETCH_ASSOC);

        if (!$reg_owner || $reg_owner['user_id'] != $user_id) {
            $_SESSION['message'] = '<div class="alert alert-danger">You are not authorized to drop this class.</div>';
            header("Location: my_classes.php");
            exit();
        }

        // Perform the deletion
        $query_delete = "DELETE FROM registrations WHERE registration_id = :registration_id";
        $stmt_delete = $db->prepare($query_delete);
        $stmt_delete->bindParam(':registration_id', $registration_id, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            $_SESSION['message'] = '<div class="alert alert-success">Class successfully dropped.</div>';
        } else {
            $_SESSION['message'] = '<div class="alert alert-danger">Failed to drop class. Please try again.</div>';
        }

    } catch (PDOException $e) {
        error_log("Error dropping class: " . $e->getMessage());
        $_SESSION['message'] = '<div class="alert alert-danger">An unexpected error occurred.</div>';
    }
} else {
    $_SESSION['message'] = '<div class="alert alert-danger">Invalid request method.</div>';
}

header("Location: my_classes.php");
exit();
?>