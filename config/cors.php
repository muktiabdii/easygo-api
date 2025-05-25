<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Path yang diizinkan
    'allowed_methods' => ['*'], // Method yang diizinkan (GET, POST, dll.)
    'allowed_origins' => ['http://localhost:5173'], // Domain yang diizinkan (gunakan '*' untuk semua domain)
    'allowed_origins_patterns' => [], // Pola domain yang diizinkan
    'allowed_headers' => ['*'], // Header yang diizinkan
    'exposed_headers' => [], // Header yang diekspos
    'max_age' => 0, // Waktu cache preflight request (dalam detik)
    'supports_credentials' => true, // Apakah mendukung credentials (cookies, auth headers)
];