<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => ':attribute dan konfirmasinya tidak cocok.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.',
    'current_password' => 'Kata sandi saat ini salah.',
    'in' => ':attribute yang dipilih tidak valid.',

    'max' => [
        'string' => ':attribute maksimal :max karakter.',
    ],

    'min' => [
        'string' => ':attribute minimal :min karakter.',
    ],

    'letters' => ':attribute harus mengandung setidaknya satu huruf.',
    'mixed' => ':attribute harus mengandung setidaknya satu huruf besar dan satu huruf kecil.',
    'numbers' => ':attribute harus mengandung setidaknya satu angka.',
    'symbols' => ':attribute harus mengandung setidaknya satu simbol.',
    'uncompromised' => ':attribute muncul dalam kebocoran data. Pilih :attribute lain.',

    'attributes' => [
        'login' => 'Username atau email',
        'username' => 'Username',
        'email' => 'Email',
        'password' => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'current_password' => 'Kata sandi saat ini',
        'display_name' => 'Nama tampilan',
        'role' => 'Tipe akun',
        'token' => 'Token',
    ],
];
