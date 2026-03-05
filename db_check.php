<?php
/**
 * Database Connection Diagnostic Script
 * Save this file as 'db_check.php' on your server and access it in your browser.
 * Remember to delete this file after troubleshooting for security reasons.
 */

require_once 'config.php';

echo "<h2>Database Connection Diagnostic</h2>";

try {
    echo "Attempting to connect to <strong>" . DB_NAME . "</strong> as user <strong>" . DB_USER . "</strong> on <strong>" . DB_HOST . "</strong>...<br>";
    
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<span style='color: green;'>✓ Successfully connected to the database!</span><br><br>";
    
    // Check if the submissions table exists
    echo "Checking if the 'submissions' table exists...<br>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'submissions'");
    if ($stmt->rowCount() > 0) {
        echo "<span style='color: green;'>✓ Table 'submissions' found.</span><br><br>";
        
        // Describe table structure
        echo "Table Structure:<br><pre>";
        $columns = $pdo->query("DESCRIBE submissions")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ") - Null: " . $col['Null'] . " - Key: " . $col['Key'] . "\n";
        }
        echo "</pre>";
        
        // Check for specific columns
        $columnNames = array_column($columns, 'Field');
        if (in_array('wedding_colors', $columnNames)) {
             echo "<span style='color: green;'>✓ Column 'wedding_colors' is present.</span><br>";
        } else {
             echo "<span style='color: red;'>✗ Column 'wedding_colors' is MISSING. Please run the SQL update from our previous talk.</span><br>";
        }
        
    } else {
        echo "<span style='color: red;'>✗ Table 'submissions' does NOT exist. Please import 'database.sql' in phpMyAdmin.</span><br>";
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>✗ Connection Failed:</span><br>";
    echo "Error Message: <strong>" . $e->getMessage() . "</strong><br><br>";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<em>Recommendation: Check your DB_USER and DB_PASS in config.php. Also ensure the user has permissions for the u764136565_wedding_db database.</em>";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<em>Recommendation: Check your DB_NAME in config.php. Ensure it matches exactly what you created in Hostinger.</em>";
    }
}
?>
