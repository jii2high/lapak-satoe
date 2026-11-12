<?php

return [

    'order' => [
        'remind_after_hours' => 12,
        'expire_after_hours' => 24,
        'auto_confirm_after_hours' => 24,
    ],

    'otp' => [
        'length' => 6,
        'expires_minutes' => 5,
        'max_attempts' => 5,
        'resend_seconds' => 60,
    ],

    'files' => [
        'product_photos' => [
            'max_files' => 3,
            'max_kb' => 3072,
            'mimes' => ['jpg', 'jpeg', 'png'],
        ],
        'student_card' => [
            'max_files' => 1,
            'max_kb' => 3072,
            'mimes' => ['jpg', 'jpeg', 'png'],
        ],
        'product_image' => [
            'max_files' => 5,
            'max_kb' => 3072,
            'mimes' => ['jpg', 'jpeg', 'png'],
            'max_dimension' => 1200,
        ],
        'store_image' => [
            'max_kb' => 3072,
            'mimes' => ['jpg', 'jpeg', 'png'],
        ],
    ],

    'violation_types' => [
        'barang_terlarang' => 'Barang terlarang',
        'penipuan' => 'Penipuan/tidak sesuai deskripsi',
        'konten_tidak_pantas' => 'Konten tidak pantas',
        'harga_tidak_wajar' => 'Harga tidak wajar',
        'lainnya' => 'Lainnya',
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin Lapak Satoe'),
        'username' => env('ADMIN_USERNAME', 'admin'),
        'email' => env('ADMIN_EMAIL', 'admin@lapaksatoe.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    'pagination' => [
        'per_page_options' => [20, 60],
    ],

    'search' => [
        'product_limit' => 8,
        'store_limit' => 3,
        'recent_limit' => 8,
    ],

];
