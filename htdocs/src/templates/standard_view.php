<?php
// $std_num is passed from index.php

// Auto-list Subjects based on directory structure
$std_dir = CONTENT_PATH . "/std-$std_num";
$subjects = [];

if (is_dir($std_dir)) {
    $items = scandir($std_dir);
    $subject_names = [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.order.json') continue;
        if (is_dir("$std_dir/$item")) {
            $subject_names[] = $item;
        }
    }
    
    // Load custom order if exists
    $order_file = $std_dir . '/.order.json';
    if (file_exists($order_file)) {
        $order = json_decode(file_get_contents($order_file), true);
        if (is_array($order) && !empty($order['folders'])) {
            usort($subject_names, function($a, $b) use ($order) {
                $posA = array_search($a, $order['folders']);
                $posB = array_search($b, $order['folders']);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
            });
        }
    }
    
    // Build subjects array
    foreach ($subject_names as $item) {
        $name = ucwords(str_replace('-', ' ', $item));
        $subjects[] = [
            'name' => $name,
            'slug' => $item
        ];
    }
}
?>

<div class="max-w-6xl mx-auto">
    <h1 class="text-4xl md:text-5xl font-bold text-center text-white mb-4">
        Standard <span class="text-brand-accent"><?= $std_num ?></span>
    </h1>
    <p class="text-center text-brand-text mb-16 max-w-2xl mx-auto">Select a subject to view solutions.</p>
    
    <?php if (empty($subjects)): ?>
        <p class="text-center text-gray-500">No subjects found. Add folders in Admin Panel.</p>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($subjects as $subj): ?>
                <a href="/std-<?= $std_num ?>/<?= $subj['slug'] ?>" 
                   class="group relative block p-1 rounded-2xl transition-all duration-500 hover:scale-105 active:scale-95">
                    <div class="absolute inset-0 bg-gradient-to-r from-cyan-400 to-blue-600 rounded-2xl opacity-0 group-hover:opacity-75 blur-md transition-opacity duration-500"></div>
                    <div class="relative h-full bg-gray-900 border border-gray-800 rounded-xl p-6 flex flex-col items-center justify-center overflow-hidden min-h-[160px]">
                        <div class="absolute inset-0 bg-gradient-to-tr from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        <h3 class="text-xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-br from-white to-gray-400 group-hover:from-cyan-300 group-hover:to-blue-500 transition-all duration-300">
                            <?= $subj['name'] ?>
                        </h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
