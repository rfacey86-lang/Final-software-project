<?php
class Database {
    private $host = "localhost";
    private $db_name = "user_management_db"; // Your database name
    private $username = "root"; // Your MySQL username (default for XAMPP)
    private $password = ""; // Your MySQL password (default for XAMPP is empty)
    public $conn;

    /**
     * Get the database connection
     * @return PDO connection object
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                                  $this->username,
                                  $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable error reporting
            $this->conn->exec("set names utf8"); // Set character set
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            // In a production environment, you would log this error, not display it.
        }

        return $this->conn;
    }
}
?>