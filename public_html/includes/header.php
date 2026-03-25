<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoveMatch</title>

    <link rel="stylesheet" href="/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
    
</head>
<body>

<header class="site-header">
    <div class="logo">
        <a href="?page=home">
            <i class="fa-solid fa-heart"></i> LoveMatch
        </a>
    </div>

    <nav class="links">
        <?php foreach ($menu as $p => $label): ?>
            <a href="?page=<?= $p ?>" class="<?= ($page === $p) ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="auth">
        <button type="button">התחברות</button>
        <button type="button">הרשמה</button>
    </div>
</header>