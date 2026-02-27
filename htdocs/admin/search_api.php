<?php
// Search API - searches file names in pages directory
header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$results = [];

if (strlen($query) >= 2) {
    $base_dir = realpath(__DIR__ . '/../src/pages');
    
    function searchRecursive($dir, $query, $base_dir, &$results, $max = 20) {
        if (count($results) >= $max) return;
        
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.order.json') continue;
            if (count($results) >= $max) return;
            
            $path = $dir . '/' . $item;
            $rel_path = str_replace($base_dir . '/', '', $path);
            
            // Check if name matches query
            if (stripos($item, $query) !== false) {
                if (is_dir($path)) {
                    $results[] = [
                        'title' => str_replace(['-', '_'], ' ', ucwords($item)),
                        'path' => $rel_path,
                        'url' => '/' . $rel_path
                    ];
                } else if (str_ends_with($item, '.html')) {
                    $name = str_replace('.html', '', $item);
                    $results[] = [
                        'title' => str_replace(['-', '_'], ' ', ucwords($name)),
                        'path' => $rel_path,
                        'url' => '/' . str_replace('.html', '', $rel_path)
                    ];
                }
            }
            
            // Recurse into directories
            if (is_dir($path)) {
                searchRecursive($path, $query, $base_dir, $results, $max);
            }
        }
    }
    
    searchRecursive($base_dir, $query, $base_dir, $results);
}

echo json_encode(['results' => $results]);
