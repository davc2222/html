 <!-- register.php-->

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

$genders = [];
$zones = [];
$places = [];

try {
    $stmt = $pdo->query("SELECT Gender_id, Gender_Str FROM gender ORDER BY Gender_id ASC");
    $genders = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT Zone_id, Zone_Str FROM zone ORDER BY Zone_id ASC");
    $zones = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT Place_id, Place_Str FROM place ORDER BY Place_id ASC");
    $places = $stmt->fetchAll();
} catch (PDOException $e) {
    die("שגיאה בטעינת נתוני הרשמה: " . htmlspecialchars($e->getMessage()));
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>

<section class="register-page">
    <div class="register-box">
        <h1>הרשמה ל־LoveMatch</h1>
        <p class="subtitle">צור פרופיל חדש בכמה צעדים פשוטים</p>

        <?php if ($error !== ''): ?>
            <div class="message-box error">
                <?php
                if ($error === 'doubleEmail') {
                    echo 'האימייל כבר קיים במערכת';
                } elseif ($error === 'doubleName') {
                    echo 'שם המשתמש כבר קיים במערכת';
                } elseif ($error === 'missing') {
                    echo 'יש למלא את כל השדות';
                } else {
                    echo 'אירעה שגיאה בהרשמה';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($success === '1'): ?>
            <div class="message-box success">ההרשמה בוצעה בהצלחה</div>
        <?php endif; ?>

        <form class="register-form" method="POST" action="/register_action.php">
            <div class="form-group">
                <label for="Name">שם משתמש</label>
                <input type="text" name="Name" id="Name" required>
                <div class="error-text" id="name-error"></div>
            </div>

            <div class="form-group">
                <label for="Email">אימייל</label>
                <input type="email" name="Email" id="Email" required>
                <div class="error-text" id="email-error"></div>
            </div>

            <div class="form-group">
                <label for="Pass">סיסמה</label>
                <input type="password" name="Pass" id="Pass" required>
            </div>

            <div class="form-group">
                <label for="DOB">תאריך לידה</label>
                <input type="date" name="DOB" id="DOB" required>
            </div>

            <div class="form-group">
                <label for="Gender_Id">אני</label>
                <select name="Gender_Id" id="Gender_Id" required>
                    <option value="">בחר</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?= htmlspecialchars($gender['Gender_id']) ?>">
                            <?= htmlspecialchars($gender['Gender_Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Look_Gender">מחפש/ת</label>
                <select name="Look_Gender" id="Look_Gender" required>
                    <option value="">בחר</option>
                    <?php foreach ($genders as $gender): ?>
                        <option value="<?= htmlspecialchars($gender['Gender_id']) ?>">
                            <?= htmlspecialchars($gender['Gender_Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Zone_Id">אזור</label>
                <select name="Zone_Id" id="Zone_Id" required>
                    <option value="">בחר אזור</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= htmlspecialchars($zone['Zone_id']) ?>">
                            <?= htmlspecialchars($zone['Zone_Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Place_Id">מקום</label>
                <select name="Place_Id" id="Place_Id" required>
                    <option value="">בחר מקום</option>
                    <?php foreach ($places as $place): ?>
                        <option value="<?= htmlspecialchars($place['Place_id']) ?>">
                            <?= htmlspecialchars($place['Place_Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="Open_Date">תאריך פתיחת פרופיל</label>
                <input type="date" name="Open_Date" id="Open_Date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="submit-wrap">
                <button type="submit" id="register-btn" disabled>צור חשבון</button>
            </div>
        </form>

        <div class="back-home">
            <a href="?page=home">חזרה לדף הבית</a>
        </div>
    </div>
</section>

<script>
const nameInput = document.getElementById("Name");
const emailInput = document.getElementById("Email");
const registerBtn = document.getElementById("register-btn");

const nameError = document.getElementById("name-error");
const emailError = document.getElementById("email-error");

let nameValid = false;
let emailValid = false;

function showError(element, input, message) {
    element.innerText = message;
    element.classList.add("show");
    input.classList.add("input-error");
    input.classList.remove("input-ok");
}

function clearError(element, input) {
    element.innerText = "";
    element.classList.remove("show");
    input.classList.remove("input-error");
    input.classList.add("input-ok");
}

function checkFormValidity() {
    const requiredFields = document.querySelectorAll(".register-form input[required], .register-form select[required]");
    let allFilled = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            allFilled = false;
        }
    });

    registerBtn.disabled = !(allFilled && nameValid && emailValid);
}

/* בדיקת שם משתמש */
let nameTimeout;
nameInput.addEventListener("input", function () {
    clearTimeout(nameTimeout);

    const name = this.value.trim();
    const englishOnly = /^[A-Za-z]+$/;

    nameValid = false;
    checkFormValidity();

    if (name === "") {
        nameError.innerText = "";
        nameError.classList.remove("show");
        this.classList.remove("input-error", "input-ok");
        return;
    }

    if (!englishOnly.test(name)) {
        showError(nameError, this, "רק אותיות באנגלית");
        return;
    }

    if (name.length > 10) {
        showError(nameError, this, "עד 10 תווים בלבד");
        return;
    }

    nameTimeout = setTimeout(() => {
        fetch("/check_name.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "name=" + encodeURIComponent(name)
        })
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                showError(nameError, nameInput, "שם המשתמש תפוס");
                nameValid = false;
            } else {
                clearError(nameError, nameInput);
                nameValid = true;
            }
            checkFormValidity();
        })
        .catch(() => {
            showError(nameError, nameInput, "שגיאה בבדיקת שם משתמש");
            nameValid = false;
            checkFormValidity();
        });
    }, 400);
});

/* בדיקת אימייל */
let emailTimeout;
emailInput.addEventListener("input", function () {
    clearTimeout(emailTimeout);

    const email = this.value.trim();
    emailValid = false;
    checkFormValidity();

    if (email === "") {
        emailError.innerText = "";
        emailError.classList.remove("show");
        this.classList.remove("input-error", "input-ok");
        return;
    }

    emailTimeout = setTimeout(() => {
        fetch("/check_email.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "email=" + encodeURIComponent(email)
        })
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                showError(emailError, emailInput, "האימייל כבר קיים");
                emailValid = false;
            } else {
                clearError(emailError, emailInput);
                emailValid = true;
            }
            checkFormValidity();
        })
        .catch(() => {
            showError(emailError, emailInput, "שגיאה בבדיקת אימייל");
            emailValid = false;
            checkFormValidity();
        });
    }, 400);
});

/* בדיקות על שדות נוספים */
document.querySelectorAll(".register-form input, .register-form select").forEach(el => {
    el.addEventListener("input", checkFormValidity);
    el.addEventListener("change", checkFormValidity);
});
</script>