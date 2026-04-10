<?php
require_once __DIR__ . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = 'נא למלא את כל השדות';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'אימייל לא תקין';
    } else {

        $to = 'davc22@gmail.com';
        $subject = 'פנייה חדשה מהאתר';

        $body = "שם: $name\n";
        $body .= "אימייל: $email\n\n";
        $body .= "הודעה:\n$message\n";

        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";

        if (mail($to, $subject, $body, $headers)) {
            $success = true;
        } else {
            $error = 'שגיאה בשליחה';
        }
    }
}
?>

<main class="page-shell">
    <section class="search-container">

        <h2 style="text-align:center;">צור קשר</h2>

        <?php if ($success): ?>
            <div style="text-align:center; color:green; margin-bottom:15px;">
                ההודעה נשלחה בהצלחה ✔
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="text-align:center; color:red; margin-bottom:15px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="max-width:400px; margin:0 auto; display:flex; flex-direction:column; gap:10px;">

            <input type="text" name="name" placeholder="שם" required>

            <input type="email" name="email" placeholder="אימייל" required>

            <textarea name="message" placeholder="הודעה" rows="5" required></textarea>

            <button type="submit" class="lm-btn lm-btn-primary">שליחה</button>

        </form>

    </section>
</main>