<?php
// $std_id, $subject_id, $std_num are available
$subject_name = ucwords(str_replace('-', ' ', $subject_id));
$subdir_path = CONTENT_PATH . "/$std_id/$subject_id";

// Auto-list Chapters
$chapters = [];
if (is_dir($subdir_path)) {
    $items = scandir($subdir_path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === "$subject_id.html" || $item === '.order.json') continue;
        if (is_dir("$subdir_path/$item")) {
             $chapters[] = $item; // e.g. "Chapter 1 Sets"
        }
    }
    
    // Load custom order if exists
    $order_file = $subdir_path . '/.order.json';
    if (file_exists($order_file)) {
        $order = json_decode(file_get_contents($order_file), true);
        if (is_array($order) && !empty($order['folders'])) {
            usort($chapters, function($a, $b) use ($order) {
                $posA = array_search($a, $order['folders']);
                $posB = array_search($b, $order['folders']);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
            });
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center space-x-2 text-sm text-gray-400 mb-8">
        <a href="/" class="hover:text-cyan-400">Home</a>
        <span>/</span>
        <a href="/std-<?= $std_num ?>" class="hover:text-cyan-400">Std <?= $std_num ?></a>
        <span>/</span>
        <span class="text-white"><?= $subject_name ?></span>
    </div>

    <h1 class="text-4xl font-bold text-white mb-8 border-b border-gray-800 pb-4">
        <span class="text-brand-accent"><?= $subject_name ?></span> <span class="text-2xl text-gray-500 ml-2">Std <?= $std_num ?></span>
    </h1>
    
    <div class="space-y-4">
        <?php if (empty($chapters)): ?>
             <p class="text-gray-500 italic">No chapters added yet. Use Admin Panel to create.</p>
        <?php else: ?>
            <?php foreach ($chapters as $chap): ?>
                <a href="/<?= $std_id ?>/<?= $subject_id ?>/<?= $chap ?>" class="block p-6 bg-gray-900 border border-gray-800 rounded-xl hover:border-cyan-500/30 transition-all cursor-pointer group">
                    <div class="flex items-center">
                        <i class="fas fa-folder text-blue-500 text-2xl mr-4"></i>
                        <h3 class="text-xl font-semibold text-white group-hover:text-cyan-400 transition-colors">
                            <?= ucwords(str_replace(['-', '_'], ' ', $chap)) ?>
                        </h3>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
