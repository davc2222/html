<?php
// ===== FILE: register.php =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    die("שגיאה בטעינת נתוני הרשמה: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_old']);

function oldValue(array $old, string $key, string $default = ''): string {
    return htmlspecialchars($old[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function oldSelected(array $old, string $key, $value): string {
    return isset($old[$key]) && (string)$old[$key] === (string)$value ? 'selected' : '';
}

function oldChecked(array $old, string $key, string $value = '1'): string {
    return isset($old[$key]) && (string)$old[$key] === $value ? 'checked' : '';
}

$errorMessage = '';
if ($error !== '') {
    if ($error === 'doubleEmail') {
        $errorMessage = 'האימייל כבר קיים במערכת';
    } elseif ($error === 'doubleName') {
        $errorMessage = 'שם המשתמש כבר קיים במערכת';
    } elseif ($error === 'missing') {
        $errorMessage = 'יש למלא את כל שדות החובה';
    } elseif ($error === 'terms') {
        $errorMessage = 'יש לאשר את תנאי השימוש';
    } elseif ($error === 'invalidEmail') {
        $errorMessage = 'כתובת האימייל אינה תקינה';
    } elseif ($error === 'weakPass') {
        $errorMessage = 'הסיסמה חייבת להכיל לפחות 6 תווים';
    } elseif ($error === 'invalidName') {
        $errorMessage = 'שם המשתמש חייב להכיל רק אותיות באנגלית ומספרים, עד 15 תווים';
    } elseif ($error === 'invalidDob') {
        $errorMessage = 'תאריך הלידה אינו תקין';
    } elseif ($error === 'underAge') {
        $errorMessage = 'ההרשמה מגיל 18 ומעלה בלבד';
    } elseif ($error === 'db') {
        $errorMessage = 'אירעה שגיאה בשמירת ההרשמה. נסה שוב.';
    } else {
        $errorMessage = 'אירעה שגיאה בהרשמה';
    }
}

$dobMax = date('Y-m-d', strtotime('-18 years'));
$dobMin = date('Y-m-d', strtotime('-100 years'));
?>

<style>
    .password-wrapper input {
        padding-left: 44px;
        padding-right: 60px;
        /* יותר מקום ל✔ */
    }


    .password-group .password-wrapper .field-icon {
        position: absolute;
        right: 12px;
        /* צמוד לימין */
        top: 50%;
        transform: translateY(-50%);
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
        z-index: 3;
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

    .form-group {
        position: relative;
    }

    .form-group input:not([type="checkbox"]),
    .form-group select {
        padding-right: 34px;
    }

    .field-icon {
        position: absolute;
        right: 12px;
        top: 41px;
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 2;
    }

    .field-icon.ok {
        color: #16a34a;
    }

    .field-icon.error {
        color: #dc2626;
    }



    /* תאריך */
    input[type="date"] {
        direction: ltr;
        text-align: right;
    }

    input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 1;
    }

    #dob-age {
        margin-top: -11px;
        /* היה גדול מדי */
        margin-bottom: 0;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.1;
        text-align: right;
        min-height: 14px;
    }

    .error-text {
        margin-top: 6px;
        font-size: 13px;
        color: #dc2626;
        line-height: 1.4;
        min-height: 18px;
        text-align: right;
    }

    input.valid,
    input.invalid,
    select.valid,
    select.invalid,
    input:valid,
    input:invalid,
    select:valid,
    select:invalid {
        background: #fff !important;
        box-shadow: none !important;
    }

    submit-wrap {
        margin-top: -40px;
    }
</style>

<section class="register-page">
    <div class="register-box">
        <h1>הרשמה ל־LoveMatch</h1>
        <p class="subtitle">צור פרופיל חדש בכמה צעדים פשוטים</p>

        <?php if ($errorMessage !== ''): ?>
            <div class="message-box error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success === '1'): ?>
            <div class="message-box success">ההרשמה בוצעה בהצלחה</div>
        <?php endif; ?>

        <form class="register-form" method="POST" action="/register_action.php" novalidate>
            <div class="form-group">
                <label for="Name">שם משתמש</label>
                <input
                    type="text"
                    name="Name"
                    id="Name"
                    maxlength="15"
                    autocomplete="off"
                    value="<?= oldValue($old, 'Name') ?>"
                    required>
                <span class="field-icon" id="name-icon"></span>
                <div class="error-text" id="name-error"></div>
            </div>

            <div class="form-group">
                <label for="Email">אימייל</label>
                <input
                    type="email"
                    id="Email"
                    name="Email"
                    autocomplete="email"
                    value="<?= oldValue($old, 'Email') ?>"
                    required>
                <span class="field-icon" id="email-icon"></span>
                <div class="error-text" id="email-error"></div>
            </div>

            <div class="form-group password-group">
                <label for="Pass">סיסמה</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        id="Pass"
                        name="Pass"
                        autocomplete="new-password"
                        required>
                    <button type="button" class="toggle-pass" id="togglePass" aria-label="הצג או הסתר סיסמה">👁</button>
                    <span class="field-icon" id="pass-icon"></span>
                </div>

                <div class="error-text" id="pass-error"></div>
            </div>

            <div class="form-group">
                <label for="DOB">תאריך לידה</label>
                <input
                    type="date"
                    name="DOB"
                    id="DOB"
                    min="<?= htmlspecialchars($dobMin, ENT_QUOTES, 'UTF-8') ?>"
                    max="<?= htmlspecialchars($dobMax, ENT_QUOTES, 'UTF-8') ?>"
                    value="<?= oldValue($old, 'DOB') ?>"
                    required>
                <span class="field-icon" id="dob-icon"></span>
                <div class="error-text" id="dob-error"></div>
                <div id="dob-age"></div>
            </div>

            <div class="form-group">
                <label for="Gender_Id">אני</label>
                <select name="Gender_Id" id="Gender_Id" required>
                    <option value="">בחר</option>
                    <?php foreach ($genders as $gender): ?>
                        <option
                            value="<?= htmlspecialchars($gender['Gender_Id'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= oldSelected($old, 'Gender_Id', $gender['Gender_Id']) ?>>
                            <?= htmlspecialchars($gender['Gender_Str'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Look_Gender">מחפש/ת</label>
                <select name="Look_Gender" id="Look_Gender" required>
                    <option value="">בחר</option>
                    <?php foreach ($lookGenders as $g): ?>
                        <option
                            value="<?= htmlspecialchars($g['Id'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= oldSelected($old, 'Look_Gender', $g['Id']) ?>>
                            <?= htmlspecialchars($g['Str'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Zone_Id">אזור</label>
                <select name="Zone_Id" id="Zone_Id" required>
                    <option value="">בחר אזור</option>
                    <?php foreach ($zones as $zone): ?>
                        <option
                            value="<?= htmlspecialchars($zone['Zone_Id'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= oldSelected($old, 'Zone_Id', $zone['Zone_Id']) ?>>
                            <?= htmlspecialchars($zone['Zone_Str'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="Place_Id">מקום</label>
                <select name="Place_Id" id="Place_Id" required>
                    <option value="">בחר מקום</option>
                    <?php foreach ($places as $place): ?>
                        <option
                            value="<?= htmlspecialchars($place['Place_Id'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= oldSelected($old, 'Place_Id', $place['Place_Id']) ?>>
                            <?= htmlspecialchars($place['Place_Str'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full">
                <label for="Open_Date">תאריך פתיחת פרופיל</label>
                <input
                    type="date"
                    name="Open_Date"
                    id="Open_Date"
                    value="<?= oldValue($old, 'Open_Date', date('Y-m-d')) ?>"
                    readonly
                    required>
            </div>

            <div class="register-terms-wrap">
                <label class="register-terms-label">
                    <input
                        type="checkbox"
                        id="termsAgree"
                        name="terms_agree"
                        value="1"
                        <?= oldChecked($old, 'terms_agree', '1') ?>
                        required>
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


    </div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector(".register-form");

        const nameInput = document.getElementById("Name");
        const emailInput = document.getElementById("Email");
        const passInput = document.getElementById("Pass");
        const dobInput = document.getElementById("DOB");

        const genderInput = document.getElementById("Gender_Id");
        const lookGenderInput = document.getElementById("Look_Gender");
        const zoneInput = document.getElementById("Zone_Id");
        const placeInput = document.getElementById("Place_Id");


        const nameError = document.getElementById("name-error");
        const emailError = document.getElementById("email-error");
        const passError = document.getElementById("pass-error");
        const dobError = document.getElementById("dob-error");

        const nameIcon = document.getElementById("name-icon");
        const emailIcon = document.getElementById("email-icon");
        const passIcon = document.getElementById("pass-icon");
        const dobIcon = document.getElementById("dob-icon");

        const dobAgeBox = document.getElementById("dob-age");
        const registerBtn = document.getElementById("register-btn");
        const termsAgree = document.getElementById("termsAgree");
        const registerTermsLink = document.getElementById("registerTermsLink");
        const togglePass = document.getElementById("togglePass");

        let submitting = false;

        const state = {
            nameValid: false,
            emailValid: false,
            passValid: false,
            dobValid: false,
            nameTouched: false,
            emailTouched: false,
            passTouched: false,
            dobTouched: false,
            nameRequestId: 0,
            emailRequestId: 0
        };

        function setIcon(icon, stateValue) {
            if (!icon) return;

            icon.textContent = "";
            icon.classList.remove("ok", "error");

            if (stateValue === "ok") {
                icon.textContent = "✔";
                icon.classList.add("ok");
            } else if (stateValue === "error") {
                icon.textContent = "✖";
                icon.classList.add("error");
            }
        }

        function showError(element, message) {
            if (!element) return;
            element.innerText = message;
            element.classList.add("show");
        }

        function clearError(element) {
            if (!element) return;
            element.innerText = "";
            element.classList.remove("show");
        }

        function calculateAge(dateStr) {
            const birthDate = new Date(dateStr);
            const today = new Date();

            if (isNaN(birthDate.getTime())) {
                return null;
            }

            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            return age;
        }

        function validateNameLocal(showMessage = false) {
            const name = nameInput.value.trim();
            const englishOnly = /^[A-Za-z0-9]+$/;

            state.nameValid = false;

            if (name === "") {
                if (showMessage) {
                    showError(nameError, "יש למלא שם משתמש");
                    setIcon(nameIcon, "error");
                } else {
                    clearError(nameError);
                    setIcon(nameIcon, null);
                }
                return false;
            }

            if (!englishOnly.test(name)) {
                if (showMessage) {
                    showError(nameError, "רק אנגלית ומספרים");
                    setIcon(nameIcon, "error");
                } else {
                    clearError(nameError);
                    setIcon(nameIcon, null);
                }
                return false;
            }

            if (name.length > 15) {
                if (showMessage) {
                    showError(nameError, "עד 15 תווים בלבד");
                    setIcon(nameIcon, "error");
                } else {
                    clearError(nameError);
                    setIcon(nameIcon, null);
                }
                return false;
            }

            clearError(nameError);
            if (!showMessage) {
                setIcon(nameIcon, null);
            }
            return true;
        }

        function validateEmailLocal(showMessage = false) {
            const email = emailInput.value.trim();
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            state.emailValid = false;

            if (email === "") {
                if (showMessage) {
                    showError(emailError, "יש למלא אימייל");
                    setIcon(emailIcon, "error");
                } else {
                    clearError(emailError);
                    setIcon(emailIcon, null);
                }
                return false;
            }

            if (!emailPattern.test(email)) {
                if (showMessage) {
                    showError(emailError, "פורמט אימייל לא תקין");
                    setIcon(emailIcon, "error");
                } else {
                    clearError(emailError);
                    setIcon(emailIcon, null);
                }
                return false;
            }

            clearError(emailError);
            if (!showMessage) {
                setIcon(emailIcon, null);
            }
            return true;
        }

        function validatePassLocal(showMessage = false) {
            const pass = passInput.value.trim();

            state.passValid = false;

            if (pass === "") {
                if (showMessage) {
                    showError(passError, "יש למלא סיסמה");
                    setIcon(passIcon, "error");
                } else {
                    clearError(passError);
                    setIcon(passIcon, null);
                }
                return false;
            }

            if (pass.length < 6) {
                if (showMessage) {
                    showError(passError, "לפחות 6 תווים");
                    setIcon(passIcon, "error");
                } else {
                    clearError(passError);
                    setIcon(passIcon, null);
                }
                return false;
            }

            clearError(passError);
            state.passValid = true;

            if (showMessage) {
                setIcon(passIcon, "ok");
            }

            return true;
        }

        function validateDOB(showMessage = false) {
            const value = dobInput.value;
            state.dobValid = false;

            if (!value) {
                dobAgeBox.innerText = "";
                dobAgeBox.style.color = "#555";

                if (showMessage) {
                    showError(dobError, "יש למלא תאריך לידה");
                    setIcon(dobIcon, "error");
                } else {
                    clearError(dobError);
                    setIcon(dobIcon, null);
                }
                return false;
            }

            const birthDate = new Date(value);
            const today = new Date();

            if (isNaN(birthDate.getTime())) {
                dobAgeBox.innerText = "";

                if (showMessage) {
                    showError(dobError, "תאריך לא תקין");
                    setIcon(dobIcon, "error");
                } else {
                    clearError(dobError);
                    setIcon(dobIcon, null);
                }
                return false;
            }

            if (birthDate > today) {
                dobAgeBox.innerText = "";

                if (showMessage) {
                    showError(dobError, "תאריך לא יכול להיות בעתיד");
                    setIcon(dobIcon, "error");
                } else {
                    clearError(dobError);
                    setIcon(dobIcon, null);
                }
                return false;
            }

            const age = calculateAge(value);

            if (age === null) {
                dobAgeBox.innerText = "";

                if (showMessage) {
                    showError(dobError, "תאריך לא תקין");
                    setIcon(dobIcon, "error");
                } else {
                    clearError(dobError);
                    setIcon(dobIcon, null);
                }
                return false;
            }

            if (age < 18) {
                dobAgeBox.innerText = `גיל: ${age} (נדרש 18+)`;
                dobAgeBox.style.color = "#dc2626";

                if (showMessage) {
                    showError(dobError, "ההרשמה מגיל 18 ומעלה");
                    setIcon(dobIcon, "error");
                } else {
                    clearError(dobError);
                    setIcon(dobIcon, null);
                }
                return false;
            }

            if (age > 100) {
                dobAgeBox.innerText = `גיל: ${age}`;
                dobAgeBox.style.color = "#dc2626";

                if (showMessage) {
                    showError(dobError, "תאריך לידה לא הגיוני");
                    setIcon(dobIcon, "error");
                } else {
                    clearError(dobError);
                    setIcon(dobIcon, null);
                }
                return false;
            }

            dobAgeBox.innerText = `גיל: ${age}`;
            dobAgeBox.style.color = "#16a34a";
            clearError(dobError);
            state.dobValid = true;

            if (showMessage) {
                setIcon(dobIcon, "ok");
            }

            return true;
        }

        function areSelectsValid() {
            return (
                genderInput.value.trim() !== "" &&
                lookGenderInput.value.trim() !== "" &&
                zoneInput.value.trim() !== "" &&
                placeInput.value.trim() !== "" 

            );
        }

        function checkFormValidity() {
            const allFilled =
                nameInput.value.trim() !== "" &&
                emailInput.value.trim() !== "" &&
                passInput.value.trim() !== "" &&
                dobInput.value.trim() !== "" &&
                genderInput.value.trim() !== "" &&
                lookGenderInput.value.trim() !== "" &&
                zoneInput.value.trim() !== "" &&
                placeInput.value.trim() !== "" &&
               
                termsAgree.checked;

            registerBtn.disabled = !(
                allFilled &&
                areSelectsValid() &&
                state.nameValid &&
                state.emailValid &&
                state.passValid &&
                state.dobValid &&
                !submitting
            );

        }

        function checkNameRemote() {
            const name = nameInput.value.trim();

            if (!validateNameLocal(state.nameTouched)) {
                checkFormValidity();
                return;
            }

            const requestId = ++state.nameRequestId;

            fetch("/check_name.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "name=" + encodeURIComponent(name)
                })
                .then(res => res.json())
                .then(data => {
                    if (requestId !== state.nameRequestId) {
                        return;
                    }

                    if (data.valid === false) {
                        state.nameValid = false;
                        if (state.nameTouched) {
                            showError(nameError, "רק אנגלית ומספרים, עד 15 תווים");
                            setIcon(nameIcon, "error");
                        }
                    } else if (data.exists) {
                        state.nameValid = false;
                        if (state.nameTouched) {
                            showError(nameError, "שם המשתמש תפוס");
                            setIcon(nameIcon, "error");
                        }
                    } else {
                        state.nameValid = true;
                        if (state.nameTouched) {
                            clearError(nameError);
                            setIcon(nameIcon, "ok");
                        }
                    }

                    checkFormValidity();
                })
                .catch(() => {
                    if (requestId !== state.nameRequestId) {
                        return;
                    }

                    state.nameValid = false;
                    if (state.nameTouched) {
                        showError(nameError, "שגיאה בבדיקת שם משתמש");
                        setIcon(nameIcon, "error");
                    }
                    checkFormValidity();
                });
        }

        function checkEmailRemote() {
            const email = emailInput.value.trim();

            if (!validateEmailLocal(state.emailTouched)) {
                checkFormValidity();
                return;
            }

            const requestId = ++state.emailRequestId;

            fetch("/check_email.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "email=" + encodeURIComponent(email)
                })
                .then(res => res.json())
                .then(data => {
                    if (requestId !== state.emailRequestId) {
                        return;
                    }

                    if (data.exists) {
                        state.emailValid = false;
                        if (state.emailTouched) {
                            showError(emailError, "האימייל כבר קיים");
                            setIcon(emailIcon, "error");
                        }
                    } else {
                        state.emailValid = true;
                        if (state.emailTouched) {
                            clearError(emailError);
                            setIcon(emailIcon, "ok");
                        }
                    }

                    checkFormValidity();
                })
                .catch(() => {
                    if (requestId !== state.emailRequestId) {
                        return;
                    }

                    state.emailValid = false;
                    if (state.emailTouched) {
                        showError(emailError, "שגיאה בבדיקת אימייל");
                        setIcon(emailIcon, "error");
                    }
                    checkFormValidity();
                });
        }

        let nameTimeout = null;
        let emailTimeout = null;

        nameInput.addEventListener("input", function() {
            clearTimeout(nameTimeout);
            state.nameValid = false;

            if (nameInput.value.trim() === "") {
                clearError(nameError);
                setIcon(nameIcon, null);
                checkFormValidity();
                return;
            }

            if (state.nameTouched) {
                if (!validateNameLocal(true)) {
                    checkFormValidity();
                    return;
                }
            } else {
                validateNameLocal(false);
            }

            nameTimeout = setTimeout(checkNameRemote, 350);
            checkFormValidity();
        });

        nameInput.addEventListener("blur", function() {
            state.nameTouched = true;

            clearTimeout(nameTimeout);

            if (nameInput.value.trim() === "") {
                showError(nameError, "יש למלא שם משתמש");
                setIcon(nameIcon, "error");
                state.nameValid = false;
                checkFormValidity();
                return;
            }

            if (!validateNameLocal(true)) {
                state.nameValid = false;
                checkFormValidity();
                return;
            }

            checkNameRemote();
        });

        emailInput.addEventListener("input", function() {
            clearTimeout(emailTimeout);
            state.emailValid = false;

            if (emailInput.value.trim() === "") {
                clearError(emailError);
                setIcon(emailIcon, null);
                checkFormValidity();
                return;
            }

            if (state.emailTouched) {
                if (!validateEmailLocal(true)) {
                    checkFormValidity();
                    return;
                }
            } else {
                validateEmailLocal(false);
            }

            emailTimeout = setTimeout(checkEmailRemote, 350);
            checkFormValidity();
        });

        emailInput.addEventListener("blur", function() {
            state.emailTouched = true;

            clearTimeout(emailTimeout);

            if (emailInput.value.trim() === "") {
                showError(emailError, "יש למלא אימייל");
                setIcon(emailIcon, "error");
                state.emailValid = false;
                checkFormValidity();
                return;
            }

            if (!validateEmailLocal(true)) {
                state.emailValid = false;
                checkFormValidity();
                return;
            }

            checkEmailRemote();
        });

        passInput.addEventListener("input", function() {
            state.passValid = false;

            if (passInput.value.trim() === "") {
                clearError(passError);
                setIcon(passIcon, null);
                checkFormValidity();
                return;
            }

            if (state.passTouched) {
                validatePassLocal(true);
            } else {
                validatePassLocal(false);
            }

            checkFormValidity();
        });

        passInput.addEventListener("blur", function() {
            state.passTouched = true;

            if (passInput.value.trim() === "") {
                showError(passError, "יש למלא סיסמה");
                setIcon(passIcon, "error");
                state.passValid = false;
                checkFormValidity();
                return;
            }

            validatePassLocal(true);
            checkFormValidity();
        });

        dobInput.addEventListener("input", function() {
            state.dobValid = false;

            if (dobInput.value.trim() === "") {
                clearError(dobError);
                setIcon(dobIcon, null);
                dobAgeBox.innerText = "";
                checkFormValidity();
                return;
            }

            if (state.dobTouched) {
                validateDOB(true);
            } else {
                validateDOB(false);
            }

            checkFormValidity();
        });

        dobInput.addEventListener("blur", function() {
            state.dobTouched = true;

            if (dobInput.value.trim() === "") {
                showError(dobError, "יש למלא תאריך לידה");
                setIcon(dobIcon, "error");
                dobAgeBox.innerText = "";
                state.dobValid = false;
                checkFormValidity();
                return;
            }

            validateDOB(true);
            checkFormValidity();
        });

        document.querySelectorAll(".register-form input, .register-form select").forEach(function(el) {
            el.addEventListener("change", checkFormValidity);
        });

        if (termsAgree) {
            termsAgree.addEventListener("change", checkFormValidity);
        }

        if (togglePass) {
            togglePass.addEventListener("click", function() {
                if (passInput.type === "password") {
                    passInput.type = "text";
                    togglePass.textContent = "🙈";
                } else {
                    passInput.type = "password";
                    togglePass.textContent = "👁";
                }
            });
        }

        if (registerTermsLink) {
            registerTermsLink.addEventListener("click", function() {
                if (typeof openTermsPopup === "function") {
                    openTermsPopup();
                }
            });
        }

        if (form) {
            form.addEventListener("submit", function(e) {
                state.nameTouched = true;
                state.emailTouched = true;
                state.passTouched = true;
                state.dobTouched = true;

                clearTimeout(nameTimeout);
                clearTimeout(emailTimeout);

                const nameOk = validateNameLocal(true);
                const emailOk = validateEmailLocal(true);
                const passOk = validatePassLocal(true);
                const dobOk = validateDOB(true);

                checkFormValidity();

                if (!nameOk || !emailOk || !passOk || !dobOk || !areSelectsValid() || !termsAgree.checked) {
    e.preventDefault();
    checkFormValidity();
    return;
}
                submitting = true;
                registerBtn.disabled = true;
                registerBtn.textContent = "שולח...";
            });
        }

        window.addEventListener("load", function() {
            if (nameInput.value.trim() !== "") {
                state.nameTouched = true;
                checkNameRemote();
            }

            if (emailInput.value.trim() !== "") {
                state.emailTouched = true;
                checkEmailRemote();
            }

            if (passInput.value.trim() !== "") {
                state.passTouched = true;
                validatePassLocal(true);
            }

            if (dobInput.value.trim() !== "") {
                state.dobTouched = true;
                validateDOB(true);
            }

            checkFormValidity();
        });
    });
</script>