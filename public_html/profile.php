// profile.php
<head>
<meta charset="UTF-8">
<title>LoveMatch</title>

<link rel="stylesheet" href="/css/style.css">
<link rel="stylesheet" href="/css/profile.css">

</head>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['user_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: /?page=login');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        Id,
        Name,
        DOB,
        Gender_Str,
        Place_Str,
        Zone_Str,
        Family_Status_Str,
        Childs_Num_Str,
        Childs_Pos_Str,
        Religion_Str,
        Religion_Ref_Str,
        Height_Str,
        Hair_Color_Str,
        Body_Type_Str,
        Who_Am_I,
        I_Looking_For,
        Ideal_Relation_Is
    FROM users_profile
    WHERE Id = :id
    LIMIT 1
");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<main class='page-shell'><p>המשתמש לא נמצא</p></main>";
    exit;
}

$age = '';
if (!empty($user['DOB'])) {
    try {
        $birthDate = new DateTime($user['DOB']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
    } catch (Exception $e) {
        $age = '';
    }
}
?>

<main class="page-shell">
    <section class="profile-layout">

        <!-- צד ימין -->
        <aside class="profile-sidebar">

            <div class="profile-top-card">
                <img src="/images/no_photo.jpg" alt="<?= htmlspecialchars($user['Name'] ?? '') ?>" class="profile-main-image">

                <h1 class="profile-name"><?= htmlspecialchars($user['Name'] ?? '') ?></h1>

                <?php if ($age !== ''): ?>
                    <div class="profile-age">גיל <?= htmlspecialchars((string)$age) ?></div>
                <?php endif; ?>

                <button type="button" class="profile-save-btn" onclick="saveProfile()">שמירה</button>
                <div id="saveMessage" class="save-message"></div>
            </div>

            <div class="profile-side-section">
                <button type="button" class="profile-side-toggle" onclick="toggleSection('basicSection')">
                    <span>פרטים בסיסיים</span>
                    <span id="basicSection_icon">▾</span>
                </button>

                <div class="profile-side-panel" id="basicSection">
                    <div class="profile-side-field">
                        <label>שם</label>
                        <input type="text" id="Name" value="<?= htmlspecialchars($user['Name'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>תאריך לידה</label>
                        <input type="date" id="DOB" value="<?= htmlspecialchars($user['DOB'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>מגדר</label>
                        <input type="text" id="Gender_Str" value="<?= htmlspecialchars($user['Gender_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>עיר</label>
                        <input type="text" id="Place_Str" value="<?= htmlspecialchars($user['Place_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>אזור</label>
                        <input type="text" id="Zone_Str" value="<?= htmlspecialchars($user['Zone_Str'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="profile-side-section">
                <button type="button" class="profile-side-toggle" onclick="toggleSection('familySection')">
                    <span>משפחה</span>
                    <span id="familySection_icon">▾</span>
                </button>

                <div class="profile-side-panel" id="familySection">
                    <div class="profile-side-field">
                        <label>מצב משפחתי</label>
                        <input type="text" id="Family_Status_Str" value="<?= htmlspecialchars($user['Family_Status_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>מספר ילדים</label>
                        <input type="text" id="Childs_Num_Str" value="<?= htmlspecialchars($user['Childs_Num_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>מיקום ילדים</label>
                        <input type="text" id="Childs_Pos_Str" value="<?= htmlspecialchars($user['Childs_Pos_Str'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="profile-side-section">
                <button type="button" class="profile-side-toggle" onclick="toggleSection('religionSection')">
                    <span>דת</span>
                    <span id="religionSection_icon">▾</span>
                </button>

                <div class="profile-side-panel" id="religionSection">
                    <div class="profile-side-field">
                        <label>דת</label>
                        <input type="text" id="Religion_Str" value="<?= htmlspecialchars($user['Religion_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>זיקה דתית</label>
                        <input type="text" id="Religion_Ref_Str" value="<?= htmlspecialchars($user['Religion_Ref_Str'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="profile-side-section">
                <button type="button" class="profile-side-toggle" onclick="toggleSection('lookSection')">
                    <span>מראה</span>
                    <span id="lookSection_icon">▾</span>
                </button>

                <div class="profile-side-panel" id="lookSection">
                    <div class="profile-side-field">
                        <label>גובה</label>
                        <input type="text" id="Height_Str" value="<?= htmlspecialchars($user['Height_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>צבע שיער</label>
                        <input type="text" id="Hair_Color_Str" value="<?= htmlspecialchars($user['Hair_Color_Str'] ?? '') ?>">
                    </div>

                    <div class="profile-side-field">
                        <label>מבנה גוף</label>
                        <input type="text" id="Body_Type_Str" value="<?= htmlspecialchars($user['Body_Type_Str'] ?? '') ?>">
                    </div>
                </div>
            </div>

        </aside>

        <!-- מרכז -->
        <section class="profile-main-content">

            <article class="profile-text-card">
                <div class="profile-text-header">
                    <h2>קצת על עצמי</h2>
                    <button type="button" class="edit-text-btn" onclick="toggleTextEdit('Who_Am_I')">✏️</button>
                </div>

                <div id="Who_Am_I_display" class="profile-text-display">
                    <?= nl2br(htmlspecialchars($user['Who_Am_I'] ?? '')) ?>
                </div>

                <textarea id="Who_Am_I_input" class="profile-text-input" style="display:none;"><?= htmlspecialchars($user['Who_Am_I'] ?? '') ?></textarea>
            </article>

            <article class="profile-text-card">
                <div class="profile-text-header">
                    <h2>מה אני מחפש</h2>
                    <button type="button" class="edit-text-btn" onclick="toggleTextEdit('I_Looking_For')">✏️</button>
                </div>

                <div id="I_Looking_For_display" class="profile-text-display">
                    <?= nl2br(htmlspecialchars($user['I_Looking_For'] ?? '')) ?>
                </div>

                <textarea id="I_Looking_For_input" class="profile-text-input" style="display:none;"><?= htmlspecialchars($user['I_Looking_For'] ?? '') ?></textarea>
            </article>

            <article class="profile-text-card">
                <div class="profile-text-header">
                    <h2>הקשר האידיאלי מבחינתי</h2>
                    <button type="button" class="edit-text-btn" onclick="toggleTextEdit('Ideal_Relation_Is')">✏️</button>
                </div>

                <div id="Ideal_Relation_Is_display" class="profile-text-display">
                    <?= nl2br(htmlspecialchars($user['Ideal_Relation_Is'] ?? '')) ?>
                </div>

                <textarea id="Ideal_Relation_Is_input" class="profile-text-input" style="display:none;"><?= htmlspecialchars($user['Ideal_Relation_Is'] ?? '') ?></textarea>
            </article>

        </section>

    </section>
</main>

<script>
function toggleSection(id) {
    const panel = document.getElementById(id);
    const icon = document.getElementById(id + '_icon');

    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        icon.textContent = '▴';
    } else {
        panel.style.display = 'none';
        icon.textContent = '▾';
    }
}

function toggleTextEdit(field) {
    const display = document.getElementById(field + '_display');
    const input = document.getElementById(field + '_input');

    if (input.style.display === 'none' || input.style.display === '') {
        display.style.display = 'none';
        input.style.display = 'block';
        input.focus();
    } else {
        display.style.display = 'block';
        input.style.display = 'none';
    }
}

async function saveProfile() {
    const payload = {
        Name: document.getElementById('Name').value,
        DOB: document.getElementById('DOB').value,
        Gender_Str: document.getElementById('Gender_Str').value,
        Place_Str: document.getElementById('Place_Str').value,
        Zone_Str: document.getElementById('Zone_Str').value,
        Family_Status_Str: document.getElementById('Family_Status_Str').value,
        Childs_Num_Str: document.getElementById('Childs_Num_Str').value,
        Childs_Pos_Str: document.getElementById('Childs_Pos_Str').value,
        Religion_Str: document.getElementById('Religion_Str').value,
        Religion_Ref_Str: document.getElementById('Religion_Ref_Str').value,
        Height_Str: document.getElementById('Height_Str').value,
        Hair_Color_Str: document.getElementById('Hair_Color_Str').value,
        Body_Type_Str: document.getElementById('Body_Type_Str').value,
        Who_Am_I: document.getElementById('Who_Am_I_input').value,
        I_Looking_For: document.getElementById('I_Looking_For_input').value,
        Ideal_Relation_Is: document.getElementById('Ideal_Relation_Is_input').value
    };

    try {
        const res = await fetch('/profile_update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        const msg = document.getElementById('saveMessage');

        if (data.success) {
            msg.textContent = 'נשמר בהצלחה';
            msg.className = 'save-message success';

            document.getElementById('Who_Am_I_display').innerHTML =
                payload.Who_Am_I.replace(/\n/g, '<br>');
            document.getElementById('I_Looking_For_display').innerHTML =
                payload.I_Looking_For.replace(/\n/g, '<br>');
            document.getElementById('Ideal_Relation_Is_display').innerHTML =
                payload.Ideal_Relation_Is.replace(/\n/g, '<br>');
        } else {
            msg.textContent = data.error || 'שגיאה בשמירה';
            msg.className = 'save-message error';
        }
    } catch (e) {
        const msg = document.getElementById('saveMessage');
        msg.textContent = 'שגיאת תקשורת';
        msg.className = 'save-message error';
    }
}
</script>