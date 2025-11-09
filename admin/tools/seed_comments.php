<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$db = app('db');

if (!$db) {
    echo "Database connection is not configured.\n";
    exit(1);
}

try {
    $postIds = $db->query('SELECT id FROM posts ORDER BY COALESCE(updated_at, created_at) DESC LIMIT 5')
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $exception) {
    echo "Failed to fetch posts: " . $exception->getMessage() . "\n";
    exit(1);
}

if (empty($postIds)) {
    echo "No posts found. Please create at least one post before seeding comments.\n";
    exit(1);
}

$userIds = [];
try {
    $userIds = $db->query('SELECT id FROM users ORDER BY created_at ASC LIMIT 5')
        ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $exception) {
    echo "Failed to fetch users: " . $exception->getMessage() . "\n";
    exit(1);
}

$templates = [
    ['author' => 'Nihat Aslan', 'message' => 'Great breakdown of the new MotoBaku Academy module. The practical drills section was especially helpful!', 'post_index' => 0, 'use_user' => true, 'deleted' => false],
    ['author' => 'Leyla Guliyeva', 'message' => "Thanks for sharing the safety checklist. I printed it and pinned it to our garage wall. More of these, please!", 'post_index' => 0, 'use_user' => false, 'deleted' => false],
    ['author' => 'Coach Murad', 'message' => "Minor correction: the torque values for the brake calipers should be 28 Nm, not 30. Let's keep riders safe.", 'post_index' => 0, 'use_user' => true, 'deleted' => false],
    ['author' => 'Rauf H.', 'message' => "After trying the suspension tips from this article my cornering feels so much more confident. Appreciate the level of detail.", 'post_index' => 1, 'use_user' => true, 'deleted' => false],
    ['author' => 'Guest Rider', 'message' => "Could you add a short video showing the counter-steering drill? Text is good but visuals would help beginners.", 'post_index' => 1, 'use_user' => false, 'deleted' => false],
    ['author' => 'Samira', 'message' => "This comment was reported by a few students so I hid it while we investigate. Leaving it marked as hidden for now.", 'post_index' => 1, 'use_user' => true, 'deleted' => true],
    ['author' => 'Ilkin', 'message' => "Loved the track-day packing list. I always forget chain lube so that reminder made me chuckle.", 'post_index' => 2, 'use_user' => false, 'deleted' => false],
    ['author' => 'Arzu', 'message' => "Adding this to our WhatsApp group. Having the warm-up routine spelled out keeps everyone on schedule.", 'post_index' => 2, 'use_user' => true, 'deleted' => false],
    ['author' => 'Moderator Bot', 'message' => "Thread locked temporarily because of off-topic replies. We will reopen once the class Q&A starts.", 'post_index' => 0, 'use_user' => false, 'deleted' => true],
    ['author' => 'Elvin', 'message' => "Is there an advanced follow-up to the braking clinic? I would love to see drills for wet conditions.", 'post_index' => 3, 'use_user' => true, 'deleted' => false],
];

$now = new DateTimeImmutable();
$inserted = 0;

$db->beginTransaction();

try {
    $stmt = $db->prepare(
        'INSERT INTO comments (post_id, user_id, author_name, message, created_at, is_deleted)
         VALUES (:post_id, :user_id, :author_name, :message, :created_at, :is_deleted)'
    );

    foreach ($templates as $index => $template) {
        $postId = $postIds[$template['post_index'] % count($postIds)];
        $userId = null;

        if (!empty($userIds) && $template['use_user']) {
            $userId = $userIds[$index % count($userIds)];
        }

        $createdAt = $now->modify(sprintf('-%d hours', ($index + 1) * 6));

        $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':author_name' => $template['author'],
            ':message' => $template['message'],
            ':created_at' => $createdAt?->format('Y-m-d H:i:s'),
            ':is_deleted' => $template['deleted'] ? 1 : 0,
        ]);

        $inserted++;
    }

    $db->commit();
} catch (Throwable $exception) {
    $db->rollBack();
    echo "Failed to seed comments: " . $exception->getMessage() . "\n";
    exit(1);
}

echo "Seeded {$inserted} comments across " . count($postIds) . " posts.\n";
