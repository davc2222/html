<!-- register.php-->
<style>
    .password-wrapper {
        position: relative;
    }

    .password-wrapper input {
        width: 100%;
        box-sizing: border-box;
        padding-left: 44px;
    }

    .toggle-pass {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 18px;
        padding: 0;
        line-height: 1;
    }

    .register-terms-wrap {
        margin-top: 12px;
        margin-bottom: 10px;
        padding: 10px 12px;
        background: #fafafa;
        border: 1px solid #eee;
        border-radius: 12px;
    }

    .register-terms-label {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        line-height: 1.6;
        font-size: 14px;
        color: #333;
    }

    .register-terms-label input[type="checkbox"] {
        margin-top: 3px;
        flex: 0 0 auto;
    }

    .inline-terms-link {
        background: none;
        border: none;
        padding: 0;
        margin: 0 2px;
        color: #e11d48;
        font: inherit;
        cursor: pointer;
        text-decoration: underline;
    }

    .inline-terms-link:hover {
        opacity: 0.85;
    }

    .register-terms-note {
        margin-top: 8px;
        font-size: 12px;
        color: #777;
        line-height: 1.5;
    }
</style>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

$genders = [];
$lookGenders = [];
$zones = [];
$places = [];

try {
    $stmt = $pdo->query("SELECT Gender_Id, Gender_Str FROM gender ORDER BY Gender_Id ASC");
    $genders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT Id, Str FROM look_gender ORDER BY Id ASC");
    $lookGenders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT Zone_Id, Zone_Str FROM zone ORDER BY Zone_Id ASC");
    $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT Place_Id, Place_Str FROM place ORDER BY Place_Id ASC");
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                } elseif ($error === 'terms') {
                    echo 'יש לאשר את תנאי השימוש';
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

            <div class="form-group password-group">
                <label for="Pass">סיסמה</label>

                <div class="password-wrapper">
                    <input type="password" name="Pass" id="Pass" required autocomplete="new-password">
                    <button type="button" class="toggle-pass" id="togglePass">👁</button>
                </div>

                <div class="error-text" id="pass-error"></div>
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
                        <option value="<?= htmlspecialchars($gender['Gender_Id']) ?>">
                            <?= htmlspecialchars($gender['Gender_Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Look_Gender">מחפש/ת</label>
                <select name="Look_Gender" id="Look_Gender" required>
                    <option value="">בחר</option>
                    <?php foreach ($lookGenders as $g): ?>
                        <option value="<?= htmlspecialchars($g['Id']) ?>">
                            <?= htmlspecialchars($g['Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Zone_Id">אזור</label>
                <select name="Zone_Id" id="Zone_Id" required>
                    <option value="">בחר אזור</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= htmlspecialchars($zone['Zone_Id']) ?>">
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
                        <option value="<?= htmlspecialchars($place['Place_Id']) ?>">
                            <?= htmlspecialchars($place['Place_Str']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="Open_Date">תאריך פתיחת פרופיל</label>
                <input type="date" name="Open_Date" id="Open_Date" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="register-terms-wrap">
                <label class="register-terms-label">
                    <input type="checkbox" id="termsAgree" name="terms_agree" value="1" required>
                    <span>
                        אני מאשר/ת שקראתי ואני מסכים/ה ל
                        <button type="button" id="registerTermsLink" class="inline-terms-link">
                            תנאי השימוש
                        </button>
                        של האתר.
                    </span>
                </label>

                <div class="register-terms-note">
                    ההרשמה והשימוש באתר כפופים לתנאי השימוש.
                </div>
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
    const passInput = document.getElementById("Pass");
    const nameError = document.getElementById("name-error");
    const emailError = document.getElementById("email-error");
    const passError = document.getElementById("pass-error");
    const termsAgree = document.getElementById("termsAgree");
    const registerTermsLink = document.getElementById("registerTermsLink");

    let nameValid = false;
    let emailValid = false;
    let passValid = false;

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
            if (field.type === "checkbox") {
                if (!field.checked) {
                    allFilled = false;
                }
            } else if (!field.value.trim()) {
                allFilled = false;
            }
        });

        registerBtn.disabled = !(allFilled && nameValid && emailValid && passValid && termsAgree.checked);
    }

    /* בדיקת שם משתמש */
    let nameTimeout;
    nameInput.addEventListener("input", function() {
        clearTimeout(nameTimeout);

        const name = this.value.trim();
        const englishOnly = /^[A-Za-z0-9]+$/;

        nameValid = false;
        checkFormValidity();

        if (name === "") {
            nameError.innerText = "";
            nameError.classList.remove("show");
            this.classList.remove("input-error", "input-ok");
            return;
        }

        if (!englishOnly.test(name)) {
            showError(nameError, this, "רק אנגלית ומספרים");
            return;
        }

        if (name.length > 15) {
            showError(nameError, this, "עד 15 תווים בלבד");
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
                    if (data.valid === false) {
                        showError(nameError, nameInput, "רק אנגלית ומספרים, עד 15 תווים");
                        nameValid = false;
                    } else if (data.exists) {
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
    emailInput.addEventListener("input", function() {
        clearTimeout(emailTimeout);

        const email = this.value.trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        emailValid = false;
        checkFormValidity();

        if (email === "") {
            emailError.innerText = "";
            emailError.classList.remove("show");
            this.classList.remove("input-error", "input-ok");
            return;
        }

        if (!emailPattern.test(email)) {
            showError(emailError, this, "פורמט אימייל לא תקין");
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

    /* בדיקת סיסמה */
    passInput.addEventListener("input", function() {
        const pass = this.value.trim();
        const passPattern = /^(?=.*[A-Za-z])(?=.*[0-9])[A-Za-z0-9]{4,}$/;

        passValid = false;
        checkFormValidity();

        if (pass === "") {
            passError.innerText = "";
            passError.classList.remove("show");
            this.classList.remove("input-error", "input-ok");
            return;
        }

        if (!passPattern.test(pass)) {
            showError(passError, this, "לפחות 4 תווים, אנגלית ומספרים");
            return;
        }

        clearError(passError, this);
        passValid = true;
        checkFormValidity();
    });

    /* בדיקות על שדות נוספים */
    document.querySelectorAll(".register-form input, .register-form select").forEach(el => {
        el.addEventListener("input", checkFormValidity);
        el.addEventListener("change", checkFormValidity);
    });

    if (termsAgree) {
        termsAgree.addEventListener("change", checkFormValidity);
    }

    const togglePass = document.getElementById("togglePass");

    togglePass.addEventListener("click", function() {
        if (passInput.type === "password") {
            passInput.type = "text";
            togglePass.textContent = "🙈";
        } else {
            passInput.type = "password";
            togglePass.textContent = "👁";
        }
    });

    if (registerTermsLink) {
        registerTermsLink.addEventListener("click", function() {
            if (typeof openTermsPopup === "function") {
                openTermsPopup();
            }
        });
    }
</script>