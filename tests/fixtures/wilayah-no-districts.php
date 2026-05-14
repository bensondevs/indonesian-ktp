<?php

declare(strict_types=1);

/** Province + regency only — NIK district 33.15.13 will not resolve (proves custom binding). */
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
    'districts' => [],
];
