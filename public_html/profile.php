<?php include __DIR__."/../includes/db.php"; 

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users_profile WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if(!$user) die("משתמש לא נמצא");

$img = $user['image'] ?? 'default.jpg';
?>

<div class="profile-page">
    <img src="images/<?php echo $img; ?>" class="profile-big">
    <h2><?php echo $user['username']; ?></h2>
    <p>גיל: <?php echo $user['age']; ?></p>
    <p>אזור: <?php echo $user['zone']; ?></p>
    <div class="bio"><?php echo $user['bio'] ?? ''; ?></div>
</div>