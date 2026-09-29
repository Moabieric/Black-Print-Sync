<?php

/*
|--------------------------------------------------------------------------
| Canonical Primary Image Resolver Test
|--------------------------------------------------------------------------
|
| Read-only isolated test for:
|
|   BlackPrint\Commerce\Projection\Media\CanonicalPrimaryImageResolver
|
| This test:
|
| - does not load WordPress
| - does not load WooCommerce
| - does not perform HTTP requests
| - does not download media
| - does not modify the database
| - does not modify products
|
*/

require_once dirname(__DIR__)
    . '/projection/media/CanonicalPrimaryImageResolver.php';

use BlackPrint\Commerce\Projection\Media\CanonicalPrimaryImageResolver;

$resolver =
    new CanonicalPrimaryImageResolver();

$tests = [];

/*
|--------------------------------------------------------------------------
| Test 1 — Actual Amrod normalized structure
|--------------------------------------------------------------------------
*/

$tests[] = [
    'name' => 'Actual Amrod default image structure',
    'images' => [
        [
            'name' => 'DEFAULT',
            'isDefault' => 1,
            'urls' => [
                [
                    'url' =>
                        'https://amrcdn.amrod.co.za/amrodprod-blob/ProductImages/AF-AM-7-D/DEFAULT_1024X1024.jpg',
                    'width' => 1024,
                    'height' => 1024,
                ],
            ],
            'hasLogo' => false,
            'angle' => null,
            'type' => 'defaultUnbrandedImage',
        ],
        [
            'name' => 'AF-AM-7-D-0-0',
            'isDefault' => false,
            'urls' => [
                [
                    'url' =>
                        'https://amrcdn.amrod.co.za/amrodprod-blob/ProductImages/AF-AM-7-D/AF-AM-7-D-0-0_1024X1024.jpg',
                    'width' => 1024,
                    'height' => 1024,
                ],
            ],
            'hasLogo' => false,
            'angle' => null,
            'type' => 'additionalImagesWithoutLogos',
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Test 2 — No default image
|--------------------------------------------------------------------------
*/

$tests[] = [
    'name' => 'No default image',
    'images' => [
        [
            'name' => 'IMAGE-1',
            'isDefault' => false,
            'urls' => [
                [
                    'url' =>
                        'https://example.com/image-1.jpg',
                ],
            ],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Test 3 — Multiple default images
|--------------------------------------------------------------------------
*/

$tests[] = [
    'name' => 'Multiple default images',
    'images' => [
        [
            'name' => 'DEFAULT-1',
            'isDefault' => true,
            'urls' => [
                [
                    'url' =>
                        'https://example.com/default-1.jpg',
                ],
            ],
        ],
        [
            'name' => 'DEFAULT-2',
            'isDefault' => true,
            'urls' => [
                [
                    'url' =>
                        'https://example.com/default-2.jpg',
                ],
            ],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Test 4 — Default image without usable URL
|--------------------------------------------------------------------------
*/

$tests[] = [
    'name' => 'Default image without usable URL',
    'images' => [
        [
            'name' => 'DEFAULT',
            'isDefault' => true,
            'urls' => [],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Test 5 — Default represented as string "true"
|--------------------------------------------------------------------------
*/

$tests[] = [
    'name' => 'String true default flag',
    'images' => [
        [
            'name' => 'DEFAULT',
            'isDefault' => 'true',
            'urls' => [
                [
                    'url' =>
                        'https://example.com/default.jpg',
                ],
            ],
        ],
    ],
];

/*
|--------------------------------------------------------------------------
| Execute tests
|--------------------------------------------------------------------------
*/

echo PHP_EOL;
echo "==============================================" . PHP_EOL;
echo "Canonical Primary Image Resolver Test" . PHP_EOL;
echo "==============================================" . PHP_EOL;

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    $result =
        $resolver->resolve(
            $test['images']
        );

    $name = $test['name'];

    $expected = null;

    switch ($name) {
        case 'Actual Amrod default image structure':
            $expected = [
                'success' => true,
                'status' => 'RESOLVED',
                'url' =>
                    'https://amrcdn.amrod.co.za/amrodprod-blob/ProductImages/AF-AM-7-D/DEFAULT_1024X1024.jpg',
            ];
            break;

        case 'No default image':
            $expected = [
                'success' => false,
                'status' => 'NO_DEFAULT_IMAGE',
                'url' => '',
            ];
            break;

        case 'Multiple default images':
            $expected = [
                'success' => false,
                'status' => 'AMBIGUOUS_PRIMARY_IMAGE',
                'url' => '',
            ];
            break;

        case 'Default image without usable URL':
            $expected = [
                'success' => false,
                'status' => 'PRIMARY_IMAGE_URL_UNAVAILABLE',
                'url' => '',
            ];
            break;

        case 'String true default flag':
            $expected = [
                'success' => true,
                'status' => 'RESOLVED',
                'url' =>
                    'https://example.com/default.jpg',
            ];
            break;
    }

    $actual = [
        'success' => $result['success'],
        'status' => $result['status'],
        'url' => $result['url'],
    ];

    if ($actual === $expected) {
        echo "[PASS] {$name}" . PHP_EOL;
        $passed++;
    } else {
        echo "[FAIL] {$name}" . PHP_EOL;

        echo "Expected:" . PHP_EOL;
        print_r($expected);

        echo "Actual:" . PHP_EOL;
        print_r($actual);

        $failed++;
    }
}

echo PHP_EOL;
echo "----------------------------------------------" . PHP_EOL;
echo "Passed: {$passed}" . PHP_EOL;
echo "Failed: {$failed}" . PHP_EOL;
echo "----------------------------------------------" . PHP_EOL;

if ($failed > 0) {
    exit(1);
}

echo "RESULT: PASS" . PHP_EOL;
echo "==============================================" . PHP_EOL;