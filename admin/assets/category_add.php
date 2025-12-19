<?php
// Centralized initialization and security check
require_once __DIR__ . '/../init.php';

$name = $description = '';
$message = $message_type = '';

// --- PHP LOGIC: Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $message = "Category Name is required.";
        $message_type = 'danger';
    } else {
        try {
            // Insert category into the database using the admin PDO connection
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, description)
                VALUES (:name, :description)
            ");
            
            $stmt->execute([
                'name' => $name,
                'description' => $description
            ]);

            $message = "Category '{$name}' added successfully!";
            $message_type = 'success';
            
            // Clear form fields on success
            $name = $description = '';

        } catch (PDOException $e) {
            // Check for unique constraint violation (Category name must be unique)
            if ($e->getCode() == '23000') {
                 $message = "Database Error: A category with the name '{$name}' already exists.";
            } else {
                 $message = "Database Error: Failed to add category. " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    }
}

$page_title = 'Add New Category';
require_once __DIR__ . '/../assets/header.php';
?>