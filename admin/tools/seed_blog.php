<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\PostRepository;

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

/** @var PostRepository|null $posts */
$posts = app('posts');

if (!$posts instanceof PostRepository) {
    echo "Post repository is not available.\n";
    exit(1);
}

$fixtures = [
    [
        'slug' => 'safe-riding-basics',
        'title' => 'Safe riding basics',
        'summary' => 'Three simple habits to build confidence before you ride in traffic.',
        'content' => <<<HTML
<p>Warm up your bike, warm up your body, and warm up your mind. Start each session with slow-speed balance drills in an empty lot before you merge into traffic.</p>
<p>Keep your eyes up, scan further than you think you need to, and practice smooth brake application with both brakes.</p>
HTML,
        'cover' => 'https://motobakuacademy.az/assets/images/gallery/gallery1.webp',
        'published_at' => '2024-08-01 10:00:00',
    ],
    [
        'slug' => 'cornering-checklist',
        'title' => 'Cornering checklist',
        'summary' => 'A short routine to steady the bike before every turn.',
        'content' => <<<HTML
<p>Slow in, look through, roll on. Set your speed before the turn, look where you want to exit, and add gentle throttle to keep the bike stable.</p>
<p>Practice on a familiar loop and add only one variable at a time so you can feel the difference.</p>
HTML,
        'cover' => 'https://motobakuacademy.az/assets/images/gallery/gallery2.webp',
        'published_at' => '2024-08-05 15:30:00',
    ],
    [
        'slug' => 'rain-ride-essentials',
        'title' => 'Rain ride essentials',
        'summary' => 'Prep steps for wet roads that keep you upright and relaxed.',
        'content' => <<<HTML
<p>Lower your speed, widen your following distance, and be extra smooth with throttle and brakes.</p>
<p>Avoid painted lines and shiny patches, and remember the first 15 minutes of rain are the slickest.</p>
HTML,
        'cover' => 'https://motobakuacademy.az/assets/images/gallery/gallery3.webp',
        'published_at' => '2024-08-10 09:15:00',
    ],
];

$created = 0;

foreach ($fixtures as $fixture) {
    if ($posts->existsBySlug($fixture['slug'])) {
        echo "Skipping existing slug: {$fixture['slug']}\n";
        continue;
    }

    try {
        $posts->create([
            'slug' => $fixture['slug'],
            'title_az' => $fixture['title'],
            'title_ru' => $fixture['title'],
            'title_en' => $fixture['title'],
            'summary_az' => $fixture['summary'],
            'summary_ru' => $fixture['summary'],
            'summary_en' => $fixture['summary'],
            'content_az' => $fixture['content'],
            'content_ru' => $fixture['content'],
            'content_en' => $fixture['content'],
            'cover_image_az' => $fixture['cover'],
            'cover_image_ru' => $fixture['cover'],
            'cover_image_en' => $fixture['cover'],
            'graphic_content_az' => null,
            'graphic_content_ru' => null,
            'graphic_content_en' => null,
            'accepts_comments' => true,
            'status' => 'published',
            'published_at' => $fixture['published_at'],
            'author_name' => 'MotoBaku Academy',
            'categories' => [],
        ]);
        echo "Created post: {$fixture['slug']}\n";
        $created++;
    } catch (Throwable $exception) {
        echo "Failed to create {$fixture['slug']}: " . $exception->getMessage() . "\n";
    }
}

echo "Seed complete. Created {$created} new posts.\n";
