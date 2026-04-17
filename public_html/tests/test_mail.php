<?php
// ===== FILE: tests/test_mail.php =====

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo 'START<br>';

// חשוב — זה הנתיב הנכון!
require __DIR__ . '/../mail.php';

echo 'MAIL LOADED<br>';

$result = sendMail(
    'davc22@gmail.com',
    'Test from LoveMatch',
    '<h2>זה עובד 🎉</h2><p>בדיקה מגו-דאדי</p>'
);

echo '<br>RESULT:<br>';

if ($result === true) {
    echo "✅ Mail sent!";
} else {
    echo "❌ Error: " . htmlspecialchars($result);
}
