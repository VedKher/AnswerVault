<?php
// $full_path contains the absolute path to this folder (e.g. .../std-9/math-1/1.sets)
// $rel_path contains relative path for links (e.g. std-9/math-1/1.sets)

$title = basename($full_path);
$items = scandir($full_path);
$files_list = [];
$folders_list = [];

foreach ($items as $item) {
    if ($item === '.' || $item === '..' || $item === '.order.json') continue;
    if ($item === 'data.json') continue; // Ignore internal

    $path = $full_path . '/' . $item;
    if (is_dir($path)) {
        $folders_list[] = $item;
    } else {
         if (str_ends_with($item, '.html')) {
             $files_list[] = str_replace('.html', '', $item);
         }
    }
}

// Load custom order if exists
$order_file = $full_path . '/.order.json';
if (file_exists($order_file)) {
    $order = json_decode(file_get_contents($order_file), true);
    if (is_array($order)) {
        // Sort folders by custom order
        if (!empty($order['folders'])) {
            usort($folders_list, function($a, $b) use ($order) {
                $posA = array_search($a, $order['folders']);
                $posB = array_search($b, $order['folders']);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
            });
        }
        // Sort files by custom order (match by name without .html)
        if (!empty($order['files'])) {
            usort($files_list, function($a, $b) use ($order) {
                // Files in order have .html, files_list doesn't
                $posA = array_search($a . '.html', $order['files']);
                $posB = array_search($b . '.html', $order['files']);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
            });
        }
    }
}

// Dynamic Breadcrumb Generation
$parts = explode('/', $rel_path);
$breadcrumbs = [];
$path_accum = '';

foreach ($parts as $part) {
    $path_accum .= ($path_accum ? '/' : '') . $part;
    
    // Nice Display Name logic
    $display = ucwords(str_replace(['-', '_'], ' ', $part));
    if (preg_match('/^std-(\d+)$/', $part, $m)) $display = "Std " . $m[1];
    
    $breadcrumbs[] = [
        'name' => $display,
        'link' => '/' . $path_accum
    ];
}
?>

<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb (Dynamic) -->
    <div class="flex flex-wrap items-center space-x-2 text-sm text-gray-400 mb-8">
        <a href="/" class="hover:text-cyan-400 transition-colors">Home</a>
        
        <?php foreach ($breadcrumbs as $crumb): ?>
            <span>/</span>
            <!-- Last item usually not a link, but for consistency lets make it one or plain text -->
            <a href="<?= $crumb['link'] ?>" class="hover:text-cyan-400 transition-colors <?= $crumb === end($breadcrumbs) ? 'text-white font-medium' : '' ?>">
                <?= $crumb['name'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <h1 class="text-3xl font-bold text-white mb-8 border-b border-gray-800 pb-4">
        <?= ucwords(str_replace(['-', '_'], ' ', $title)) ?>
    </h1>

    <div class="space-y-4">
         <?php if (empty($files_list) && empty($folders_list)): ?>
             <p class="text-gray-500 italic">This folder is empty.</p>
        <?php endif; ?>

        <!-- Folders -->
        <?php foreach ($folders_list as $folder): ?>
            <a href="/<?= $rel_path ?>/<?= $folder ?>" class="block p-6 bg-gray-900 border border-gray-800 rounded-xl hover:border-blue-500/30 transition-all group">
                <div class="flex items-center">
                    <i class="fas fa-folder text-blue-500 text-2xl mr-4"></i>
                    <h3 class="text-lg font-semibold text-white group-hover:text-blue-400 transition-colors">
                        <?= ucwords(str_replace(['-', '_'], ' ', $folder)) ?>
                    </h3>
                </div>
            </a>
        <?php endforeach; ?>

        <!-- Files -->
         <?php foreach ($files_list as $file): ?>
            <a href="/<?= $rel_path ?>/<?= $file ?>" class="block p-6 bg-gray-900 border border-gray-800 rounded-xl hover:border-cyan-500/30 transition-all group">
                <div class="flex items-center">
                    <i class="fas fa-file-alt text-cyan-500 text-2xl mr-4"></i>
                    <h3 class="text-lg font-semibold text-white group-hover:text-cyan-400 transition-colors">
                        <?= ucwords(str_replace(['-', '_'], ' ', $file)) ?>
                    </h3>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
