<?php
/* seed_users.php */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';

/* =========================
   הגדרות
========================= */
const TOTAL_USERS = 40;
const MALE_COUNT  = 20;
const FEMALE_COUNT = 20;

$email = 'davc22@gmail.con';
$plainPassword = '123rik';
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

/* =========================
   נתונים לדוגמה
========================= */
$maleNames = [
    'אורי', 'דניאל', 'רועי', 'נועם', 'עידו',
    'טל', 'גיא', 'שחר', 'אלון', 'יובל',
    'אופק', 'בר', 'יונתן', 'ליאור', 'נדב',
    'רז', 'איתי', 'עומר', 'אריאל', 'יהב'
];

$femaleNames = [
    'נועה', 'מיה', 'שירה', 'יעל', 'תמר',
    'רותם', 'איה', 'מור', 'ליה', 'דנה',
    'אביב', 'אדל', 'רוני', 'מאי', 'אליה',
    'בר', 'עמית', 'הדר', 'אופיר', 'לין'
];

$maleAbout = [
    'בחור קליל שאוהב ים, טיולים ושיחות טובות.',
    'אוהב מוזיקה, ספורט ואנשים עם אנרגיה טובה.',
    'מחפש קשר אמיתי עם הרבה כנות והומור.',
    'נהנה מערבים שקטים, סרט טוב וקפה.',
    'אוהב לטייל, להכיר מקומות חדשים ולבלות עם חברים.'
];

$femaleAbout = [
    'אוהבת מוזיקה, טיולים ואנשים עם לב טוב.',
    'בחורה קלילה עם חיוך, הומור ואהבה לחיים.',
    'נהנית מערב שקט, סרט טוב ושיחה עמוקה.',
    'אוהבת ים, קפה, טיולים קטנים וספונטניות.',
    'מחפשת קשר נעים, מכבד ואמיתי.'
];

$lookingForTexts = [
    'מחפש/ת קשר רציני עם כנות, כימיה ותקשורת טובה.',
    'רוצה להכיר מישהו/י איכותי/ת עם לב טוב.',
    'מחפש/ת חיבור אמיתי, שיחות טובות והרבה חיוכים.',
    'פתוח/ה לקשר משמעותי עם אדם נעים ומכבד.',
    'מחפש/ת זוגיות טובה, כנה וזורמת.'
];

$relationTexts = [
    'קשר שמבוסס על אמון, כבוד ותקשורת.',
    'זוגיות שיש בה חברות, צחוק והקשבה.',
    'קשר בריא עם כימיה, הדדיות ופרגון.',
    'חיבור עמוק עם הרבה פתיחות ואמת.',
    'זוגיות רגועה, נעימה ומלאת תשומת לב.'
];

$hobbiesTexts = [
    'ים, הליכות, מוזיקה, בתי קפה',
    'ספורט, סרטים, חברים, טיולים',
    'בישול, מוזיקה, נסיעות, צילום',
    'ריצה, קריאה, טבע, בילויים',
    'כושר, סדרות, טיולים קצרים, קפה'
];

$spendingTexts = [
    'בתי קפה, מסעדות וטיולים קצרים',
    'בילוי עם חברים, סרטים וים',
    'מוזיקה, נסיעות וסופי שבוע רגועים',
    'מסעדות, טבע, טיולים וספורט',
    'קפה טוב, סרטים וערבים שקטים'
];

$zones = ['צפון', 'מרכז', 'דרום', 'חיפה', 'שרון'];
$places = ['חיפה', 'תל אביב', 'קריות', 'נתניה', 'ראשון לציון'];
$familyStatuses = ['רווק/ה', 'פנוי/ה'];
$economics = ['ממוצע', 'יציב'];
$occupations = ['סטודנט/ית', 'חייל/ת משוחרר/ת', 'ברמן/ית', 'שירות לקוחות', 'מוכר/ת'];
$childsPos = ['ללא ילדים'];
$childsNum = ['0'];
$educations = ['תיכון', 'בגרות מלאה', 'סטודנט/ית'];
$religions = ['חילוני/ת', 'מסורתי/ת'];
$religionRef = ['לא חשוב', 'מסורתי/ת'];
$origins = ['ישראלי/ת'];
$politics = ['מרכז'];
$smoking = ['לא מעשן/ת', 'לעיתים רחוקות'];
$drinking = ['לא שותה/ה', 'שותה/ה לעיתים'];
$veg = ['הכל', 'צמחוני/ת'];
$heights = ['160', '162', '165', '168', '170', '172', '175', '178', '180'];
$weights = ['50', '55', '60', '65', '70', '75'];
$hairTypes = ['חלק', 'גלי'];
$hairColors = ['חום', 'שחור', 'בלונד'];
$bodyTypes = ['רזה', 'ממוצע', 'אתלטי'];
$lookTypes = ['מטופח/ת', 'רגיל/ה'];
$movies = ['קומדיה', 'אקשן', 'דרמה', 'רומנטי'];
$tv = ['ריאליטי', 'סדרות מתח', 'קומדיה'];
$books = ['התפתחות אישית', 'רומנים', 'ספרי מתח'];
$zodiacs = ['טלה', 'שור', 'תאומים', 'סרטן', 'אריה', 'בתולה'];

function pick(array $items): string
{
    return $items[array_rand($items)];
}

function randomDobFromAge(int $age): string
{
    $currentYear = (int)date('Y');
    $year = $currentYear - $age;
    $month = rand(1, 12);
    $day = rand(1, 28);
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

$insertSql = "
INSERT INTO users_profile (
    Open_Date,
    Gender_Id,
    Gender_Str,
    DOB,
    Name,
    Pass,
    Email,
    Zone_Id,
    Zone_Str,
    Place_Id,
    Place_Str,
    Email_Validation,
    Family_Status_Id,
    Family_Status_Str,
    Economic_Id,
    Economic_Str,
    Occupation_Id,
    Occupation_Str,
    Childs_Pos_Id,
    Childs_Pos_Str,
    Childs_Num_Id,
    Childs_Num_Str,
    Education_Id,
    Education_Str,
    Religion_Id,
    Religion_Str,
    Religion_Ref_Id,
    Religion_Ref_Str,
    Origin_Id,
    Origin_Str,
    Hobbies,
    Spending,
    Politics_Id,
    Politics_Str,
    Smoking_Habbit_Id,
    Smoking_Habbit_Str,
    Drinking_Habbit_Id,
    Drinking_Habbit_Str,
    Vegitrain_Id,
    Vegitrain_Str,
    Height_Id,
    Height_Str,
    Weight_Id,
    Weight_Str,
    Hair_Type_Id,
    Hair_Type_Str,
    Hair_Color_Id,
    Hair_Color_Str,
    Body_Type_Id,
    Body_Type_Str,
    Look_Type_Id,
    Look_Type_Str,
    Who_Am_I,
    I_Looking_For,
    Ideal_Relation_Is,
    Age,
    Login_Date,
    Login_Time,
    Send_Me_Mail_For_Msg,
    Favorite_Movies,
    Favorite_TV,
    Favorite_Books,
    zodiac,
    email_verified,
    verification_token,
    verification_sent_at
) VALUES (
    :Open_Date,
    :Gender_Id,
    :Gender_Str,
    :DOB,
    :Name,
    :Pass,
    :Email,
    :Zone_Id,
    :Zone_Str,
    :Place_Id,
    :Place_Str,
    :Email_Validation,
    :Family_Status_Id,
    :Family_Status_Str,
    :Economic_Id,
    :Economic_Str,
    :Occupation_Id,
    :Occupation_Str,
    :Childs_Pos_Id,
    :Childs_Pos_Str,
    :Childs_Num_Id,
    :Childs_Num_Str,
    :Education_Id,
    :Education_Str,
    :Religion_Id,
    :Religion_Str,
    :Religion_Ref_Id,
    :Religion_Ref_Str,
    :Origin_Id,
    :Origin_Str,
    :Hobbies,
    :Spending,
    :Politics_Id,
    :Politics_Str,
    :Smoking_Habbit_Id,
    :Smoking_Habbit_Str,
    :Drinking_Habbit_Id,
    :Drinking_Habbit_Str,
    :Vegitrain_Id,
    :Vegitrain_Str,
    :Height_Id,
    :Height_Str,
    :Weight_Id,
    :Weight_Str,
    :Hair_Type_Id,
    :Hair_Type_Str,
    :Hair_Color_Id,
    :Hair_Color_Str,
    :Body_Type_Id,
    :Body_Type_Str,
    :Look_Type_Id,
    :Look_Type_Str,
    :Who_Am_I,
    :I_Looking_For,
    :Ideal_Relation_Is,
    :Age,
    :Login_Date,
    :Login_Time,
    :Send_Me_Mail_For_Msg,
    :Favorite_Movies,
    :Favorite_TV,
    :Favorite_Books,
    :zodiac,
    :email_verified,
    :verification_token,
    :verification_sent_at
)
";

$stmt = $pdo->prepare($insertSql);

$pdo->beginTransaction();

try {
    for ($i = 0; $i < TOTAL_USERS; $i++) {
        $isMale = $i < MALE_COUNT;

        $genderId = $isMale ? 1 : 2;
        $genderStr = $isMale ? 'גבר' : 'אישה';
        $name = $isMale ? $maleNames[$i] : $femaleNames[$i - MALE_COUNT];
        $age = rand(18, 20);

        $stmt->execute([
            ':Open_Date' => date('Y-m-d'),
            ':Gender_Id' => $genderId,
            ':Gender_Str' => $genderStr,
            ':DOB' => randomDobFromAge($age),
            ':Name' => $name . ' ' . ($i + 1),
            ':Pass' => $hashedPassword,
            ':Email' => $email,
            ':Zone_Id' =>1,
            ':Zone_Str' => pick($zones),
            ':Place_Id' => rand(1, 5),
            ':Place_Str' => pick($places),
           ':Email_Validation' => chr(1),
            ':Family_Status_Id' => 1,
            ':Family_Status_Str' => pick($familyStatuses),
            ':Economic_Id' => 1,
            ':Economic_Str' => pick($economics),
            ':Occupation_Id' => rand(1, 5),
            ':Occupation_Str' => pick($occupations),
            ':Childs_Pos_Id' => 1,
            ':Childs_Pos_Str' => pick($childsPos),
            ':Childs_Num_Id' => 0,
            ':Childs_Num_Str' => pick($childsNum),
            ':Education_Id' => rand(1, 3),
            ':Education_Str' => pick($educations),
            ':Religion_Id' => rand(1, 2),
            ':Religion_Str' => pick($religions),
            ':Religion_Ref_Id' => rand(1, 2),
            ':Religion_Ref_Str' => pick($religionRef),
            ':Origin_Id' => 1,
            ':Origin_Str' => pick($origins),
            ':Hobbies' => pick($hobbiesTexts),
            ':Spending' => pick($spendingTexts),
            ':Politics_Id' => 1,
            ':Politics_Str' => pick($politics),
            ':Smoking_Habbit_Id' => rand(1, 2),
            ':Smoking_Habbit_Str' => pick($smoking),
            ':Drinking_Habbit_Id' => rand(1, 2),
            ':Drinking_Habbit_Str' => pick($drinking),
            ':Vegitrain_Id' => rand(1, 2),
            ':Vegitrain_Str' => pick($veg),
            ':Height_Id' => pick($heights),
            ':Height_Str' => pick($heights) . ' ס"מ',
            ':Weight_Id' => rand(1, 6),
            ':Weight_Str' => pick($weights) . ' ק"ג',
            ':Hair_Type_Id' => rand(1, 2),
            ':Hair_Type_Str' => pick($hairTypes),
            ':Hair_Color_Id' => rand(1, 3),
            ':Hair_Color_Str' => pick($hairColors),
            ':Body_Type_Id' => rand(1, 3),
            ':Body_Type_Str' => pick($bodyTypes),
            ':Look_Type_Id' => rand(1, 2),
            ':Look_Type_Str' => pick($lookTypes),
            ':Who_Am_I' => $isMale ? pick($maleAbout) : pick($femaleAbout),
            ':I_Looking_For' => pick($lookingForTexts),
            ':Ideal_Relation_Is' => pick($relationTexts),
            ':Age' => $age,
            ':Login_Date' => date('Y-m-d'),
            ':Login_Time' => date('H:i:s'),
            ':Send_Me_Mail_For_Msg' => 1,
            ':Favorite_Movies' => pick($movies),
            ':Favorite_TV' => pick($tv),
            ':Favorite_Books' => pick($books),
            ':zodiac' => pick($zodiacs),
            ':email_verified' => 1,
            ':verification_token' => null,
            ':verification_sent_at' => null
        ]);
    }

    $pdo->commit();
    echo 'נוספו 40 משתמשים בהצלחה';
} catch (Throwable $e) {
    $pdo->rollBack();
    echo 'שגיאה: ' . $e->getMessage();
}