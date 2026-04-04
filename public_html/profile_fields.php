<?php

return [

    /* -----------------------------
       צד ימין
    ----------------------------- */
    'Gender_Str' => [
        'side'      => 'right',
        'label'     => 'מין',
        'type'      => 'display',
        'read_only' => true,
    ],

    'Age_Computed' => [
        'side'      => 'right',
        'label'     => 'גיל',
        'type'      => 'computed',
        'read_only' => true,
    ],

    'Height_Str' => [
        'side'   => 'right',
        'label'  => 'גובה',
        'type'   => 'select',
        'table'  => 'height',
        'column' => 'Height_Str',
    ],

    'Family_Status_Str' => [
        'side'   => 'right',
        'label'  => 'סטטוס',
        'type'   => 'select',
        'table'  => 'family_status',
        'column' => 'Family_Status_Str',
    ],

    'Childs_Num_Str' => [
        'side'         => 'right',
        'label'        => 'מספר ילדים',
        'type'         => 'select',
        'table'        => 'childs_num',
        'column'       => 'Childs_Num_Str',
        'zero_as_none' => true,
    ],

    'Zone_Str' => [
        'side'   => 'right',
        'label'  => 'אזור',
        'type'   => 'select',
        'table'  => 'zone',
        'column' => 'Zone_Str',
    ],

    'Place_Str' => [
        'side'   => 'right',
        'label'  => 'מקום',
        'type'   => 'select',
        'table'  => 'place',
        'column' => 'Place_Str',
    ],

    'Education_Str' => [
        'side'   => 'right',
        'label'  => 'השכלה',
        'type'   => 'select',
        'table'  => 'education',
        'column' => 'Education_Str',
    ],

    'Occupation_Str' => [
        'side'   => 'right',
        'label'  => 'תפקיד',
        'type'   => 'select',
        'table'  => 'occupation',
        'column' => 'Occupation_Str',
    ],

    'Vegitrain_Str' => [
        'side'   => 'right',
        'label'  => 'צמחונות',
        'type'   => 'select',
        'table'  => 'vegitrain',
        'column' => 'Vegitrain_Str',
    ],

    'Smoking_Habbit_Str' => [
        'side'   => 'right',
        'label'  => 'הרגלי עישון',
        'type'   => 'select',
        'table'  => 'smoking_habbit',
        'column' => 'Smoking_Habbit_Str',
    ],

    'Drinking_Habbit_Str' => [
        'side'   => 'right',
        'label'  => 'הרגלי שתייה',
        'type'   => 'select',
        'table'  => 'drinking_habbit',
        'column' => 'Drinking_Habbit_Str',
    ],

    'Religion_Str' => [
        'side'   => 'right',
        'label'  => 'דת',
        'type'   => 'select',
        'table'  => 'religion',
        'column' => 'Religion_Str',
    ],

    'Religion_Ref_Str' => [
        'side'   => 'right',
        'label'  => 'זיקה לדת',
        'type'   => 'select',
        'table'  => 'religion_ref',
        'column' => 'Religion_Ref_Str',
    ],

    'Hair_Color_Str' => [
        'side'   => 'right',
        'label'  => 'צבע שיער',
        'type'   => 'select',
        'table'  => 'hair_color',
        'column' => 'Hair_Color_Str',
    ],

    'Hair_Type_Str' => [
        'side'   => 'right',
        'label'  => 'סוג שיער',
        'type'   => 'select',
        'table'  => 'hair_type',
        'column' => 'Hair_Type_Str',
    ],

    'Body_Type_Str' => [
        'side'   => 'right',
        'label'  => 'מבנה גוף',
        'type'   => 'select',
        'table'  => 'body_type',
        'column' => 'Body_Type_Str',
    ],

    'Look_Type_Str' => [
        'side'   => 'right',
        'label'  => 'מראה',
        'type'   => 'select',
        'table'  => 'look_type',
        'column' => 'Look_Type_Str',
    ],

    /* -----------------------------
       צד שמאל
    ----------------------------- */
    'Who_Am_I' => [
        'side'  => 'left',
        'label' => 'קצת על עצמי',
        'type'  => 'textarea',
    ],



    'Hobbies' => [
        'side'  => 'left',
        'label' => 'תחביבים',
        'type'  => 'input',
    ],

    'Favorite_Movies' => [
        'side'  => 'left',
        'label' => 'סרטים אהובים',
        'type'  => 'textarea',
    ],

    'Favorite_TV' => [
        'side'  => 'left',
        'label' => 'תוכניות טלוויזיה אהובות',
        'type'  => 'input',
    ],

    'Favorite_Books' => [
        'side'  => 'left',
        'label' => 'ספרים אהובים',
        'type'  => 'input',
    ],

    'Sport' => [
        'side'  => 'left',
        'label' => 'ספורט',
        'type'  => 'input',
    ],



    'I_Looking_For' => [
        'side'  => 'left',
        'label' => ' את מי אני מחפש/ת ',
        'type'  => 'textarea',
    ],
];
