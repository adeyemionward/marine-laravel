<?php

return [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'secure' => env('CLOUDINARY_SECURE', true),
    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
    
    // Default transformation settings
    'transformations' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ],
        'medium' => [
            'width' => 400,
            'height' => 300,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ],
        'large' => [
            'width' => 800,
            'height' => 600,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ],
        'hero' => [
            'width' => 1200,
            'height' => 600,
            'crop' => 'fill',
            'quality' => 'auto',
            'fetch_format' => 'auto'
        ]
    ],
    
    // Folders for different image types
    'folders' => [
        'equipment' => 'marine/equipment',
        'profiles' => 'marine/profiles',
        'documents' => 'marine/documents',
        'banners' => 'marine/banners'
    ]
];