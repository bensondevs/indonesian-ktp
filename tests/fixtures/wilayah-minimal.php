<?php

declare(strict_types=1);

/** Minimal subset for tests — same shape as data/wilayah.php */
return [
    'provinces' => [
        [
            'code' => '33',
            'name' => 'Jawa Tengah',
        ],
    ],
    'regencies' => [
        [
            'code' => '33.15',
            'province_code' => '33',
            'name' => 'Kabupaten Grobogan',
        ],
    ],
    'districts' => [
        [
            'code' => '33.15.13',
            'regency_code' => '33.15',
            'name' => 'Purwodadi',
        ],
    ],
];
