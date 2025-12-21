<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$page_title = 'Contact Us';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';

$name = '';
$email = '';
$subject = $_GET['subject'] ?? '';
$message = '';
$success = '';
$error = '';

// Pre-fill user data if logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $name = $user['name'];
        $email = $user['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // Create table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $user_id = $_SESSION['user_id'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO contact_inquiries (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $email, $subject, $message]);

            // --- Send Email Notification to Admin ---
            // Note: This requires a configured mail server (like sendmail) on your web server to work.
            // The '@' symbol suppresses errors if mail sending fails.
            if (defined('ADMIN_EMAIL') && ADMIN_EMAIL !== 'admin@example.com') {
                $to = ADMIN_EMAIL;
                $email_subject = "New Contact Inquiry: " . $subject;
                
                $email_body = "You have received a new inquiry from your website contact form.\n\n";
                $email_body .= "--------------------------------------------------\n";
                $email_body .= "From: " . $name . " (" . $email . ")\n";
                $email_body .= "Subject: " . $subject . "\n\n";
                $email_body .= "Message:\n" . $message . "\n";
                $email_body .= "--------------------------------------------------\n\n";
                $email_body .= "You can view and reply to this inquiry in the admin panel.";

                $headers = "From: no-reply@mtpflex.com\r\n" . "Reply-To: " . $email . "\r\n" . "X-Mailer: PHP/" . phpversion();

                @mail($to, $email_subject, $email_body, $headers);
            }

            $success = "Thank you for contacting us! We have received your message and will respond shortly.";
            $message = ''; // Clear message
        } catch (PDOException $e) {
            $error = "An error occurred while sending your message. Please try again.";
        }
    }
}
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
    <div class="form-container">
        <h2>Contact Us</h2>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem;">
            Have a question or need assistance? Fill out the form below.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form action="contact_us.php" method="POST">
            <div class="form-group">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" class="form-control" value="<?= htmlspecialchars($subject) ?>" required>
            </div>

            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" class="form-control" rows="5" required><?= htmlspecialchars($message) ?></textarea>
            </div>

            <button type="submit" class="btn-primary">Send Message</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="index.php" style="color: var(--text-secondary); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php'; ?>
