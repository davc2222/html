 <!-- index.php-->

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$page = $_GET['page'] ?? 'home';

$menu = [
    'home' => 'בית',
    'search' => 'חיפוש',
    'messages' => 'הודעות',
    'views' => 'צפיות',
  
];

//$allowed = array_keys($menu);

$allowed = ['home', 'search', 'messages', 'views', 'contact', 'login', 'register','verify_notice'];
if (!in_array($page, $allowed)) {
    $page = 'home';
}

include __DIR__ . "/includes/header.php";

$page_file = __DIR__ . "/$page.php";
if (file_exists($page_file)) {
    include $page_file;
} else {
    echo "<main class='page-shell'><p>דף לא נמצא</p></main>";
}

include __DIR__ . "/includes/footer.php";