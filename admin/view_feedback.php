<?php
require_once 'auth.php';
checkAuth();

// Load feedback data
$feedback_file = __DIR__ . '/feedback.json';
$feedback_data = [];
if (file_exists($feedback_file)) {
    $feedback_data = json_decode(file_get_contents($feedback_file), true) ?: [];
}
$feedbacks = $feedback_data['feedback'] ?? [];

// Sort by newest first
usort($feedbacks, fn($a, $b) => strtotime($b['timestamp'] ?? 0) - strtotime($a['timestamp'] ?? 0));

// Calculate stats
$total = count($feedbacks);
$avg_rating = $total > 0 ? array_sum(array_column($feedbacks, 'rating')) / $total : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback - Admin</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-900 text-slate-200 font-sans min-h-screen">
    <div class="max-w-6xl mx-auto p-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-cyan-400"><i class="fas fa-comments mr-3"></i>User Feedback</h1>
                <p class="text-slate-500 mt-1">View all feedback submitted by users</p>
            </div>
            <a href="dashboard.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg text-sm transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
                <div class="text-sm text-slate-500 mb-1">Total Feedback</div>
                <div class="text-3xl font-bold text-white"><?= $total ?></div>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
                <div class="text-sm text-slate-500 mb-1">Average Rating</div>
                <div class="text-3xl font-bold text-yellow-400">
                    <?= $total > 0 ? number_format($avg_rating, 1) : '-' ?> 
                    <span class="text-lg">★</span>
                </div>
            </div>
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-5">
                <div class="text-sm text-slate-500 mb-1">5-Star Reviews</div>
                <div class="text-3xl font-bold text-green-400">
                    <?= count(array_filter($feedbacks, fn($f) => ($f['rating'] ?? 0) === 5)) ?>
                </div>
            </div>
        </div>
        
        <!-- Feedback List -->
        <?php if (empty($feedbacks)): ?>
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-12 text-center">
                <i class="fas fa-inbox text-6xl text-slate-600 mb-4"></i>
                <p class="text-slate-400">No feedback received yet</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($feedbacks as $fb): ?>
                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 hover:border-slate-600 transition-colors">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div class="flex-1">
                                <!-- Stars -->
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="text-yellow-400 text-lg">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?= $i <= ($fb['rating'] ?? 0) ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-slate-500">(<?= $fb['rating'] ?? 0 ?>/5)</span>
                                </div>
                                
                                <!-- Message -->
                                <?php if (!empty($fb['message'])): ?>
                                    <p class="text-white mb-3"><?= htmlspecialchars($fb['message']) ?></p>
                                <?php else: ?>
                                    <p class="text-slate-500 italic mb-3">No message provided</p>
                                <?php endif; ?>
                                
                                <!-- Meta -->
                                <div class="flex flex-wrap gap-4 text-xs text-slate-500">
                                    <span><i class="fas fa-clock mr-1"></i><?= date('M j, Y g:i A', strtotime($fb['timestamp'] ?? 'now')) ?></span>
                                    <?php if (!empty($fb['page'])): ?>
                                        <span><i class="fas fa-link mr-1"></i><?= htmlspecialchars(substr($fb['page'], 0, 50)) ?><?= strlen($fb['page']) > 50 ? '...' : '' ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($fb['ip'])): ?>
                                        <span><i class="fas fa-globe mr-1"></i><?= htmlspecialchars($fb['ip']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
