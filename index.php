<?php
// SMART CACHE CONTROL - Fast Load + Instant Updates
header("Cache-Control: no-cache, public, must-revalidate");

// Global Configuration
define('BASE_PATH', __DIR__);
define('CONTENT_PATH', BASE_PATH . '/src/pages');

// ROUTING LOGIC via REQUEST_URI (Robust)
$request_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'] ?? '/';
$path = trim($path, '/');
$decoded_path = urldecode($path);

// Debug mode if needed? No.

ob_start();

if ($decoded_path === 'index.php' || $decoded_path === '') {
    require_once 'src/templates/home.php';
} elseif ($decoded_path === 'whats-new') {
    require_once 'src/templates/whats_new.php';
} elseif ($decoded_path === 'feedback') {
    require_once 'src/templates/feedback.php';
} elseif ($decoded_path === 'about') {
    require_once 'src/templates/about.php';
} else {
    $rel_path = $decoded_path;
    $full_path = CONTENT_PATH . '/' . $rel_path;
    
    if (file_exists($full_path) && is_dir($full_path)) {
        // FOLDER LOGIC
        $parts = explode('/', $rel_path);
        $depth = count($parts);
        
        if ($depth === 1 && preg_match('/^std-\d+$/', $parts[0])) {
            $std_num = str_replace('std-', '', $parts[0]);
            require_once 'src/templates/standard_view.php';
        } elseif ($depth === 2) {
            $std_id = $parts[0];
            $std_num = str_replace('std-', '', $std_id);
            $subject_id = $parts[1];
            require_once 'src/templates/subject_view.php';
        } else {
            require_once 'src/templates/chapter_view.php';
        }
        
    } elseif (file_exists($full_path . '.html')) {
        // PAGE LOGIC
        // We only output the CONTENT. layout.php will wrap it.
        // Detect Title relative to this specific page if possible, otherwise use filename
        $page_title = basename($rel_path);
        
        // 1. GENERATE BREADCRUMBS
        $parts = explode('/', $rel_path);
        $bc_html = '<div class="max-w-4xl mx-auto mb-8"><div class="flex flex-wrap items-center space-x-2 text-sm text-gray-400">';
        $bc_html .= '<a href="/" class="hover:text-cyan-400 transition-colors">Home</a>';
        $path_accum = '';
        foreach ($parts as $part) {
            $path_accum .= ($path_accum ? '/' : '') . $part;
            $display = ucwords(str_replace(['-', '_'], ' ', $part));
            if (preg_match('/^std-(\d+)$/', $part, $m)) $display = "Std " . $m[1];
            
            // Check if this is the last part (the current page)
            if ($part === end($parts)) {
                 $bc_html .= '<span>/</span><span class="text-white font-medium">' . $display . '</span>';
            } else {
                 $bc_html .= '<span>/</span><a href="/' . $path_accum . '" class="hover:text-cyan-400 transition-colors">' . $display . '</a>';
            }
        }
        $bc_html .= '</div></div>';

        // 2. GENERATE PREV/NEXT BUTTONS
        $parent_dir = dirname($full_path . '.html');
        $parent_url = dirname($rel_path);
        // If parent is dot (top level), fix it
        if ($parent_url === '.') $parent_url = '';
        
        $files = scandir($parent_dir);
        $page_files = [];
        foreach($files as $f) {
            if(str_ends_with($f, '.html')) {
                $page_files[] = str_replace('.html', '', $f);
            }
        }
        // Natural Sort for 1.1, 1.2, 1.10 etc
        natsort($page_files);
        $page_files = array_values($page_files); // re-index
        
        $current_name = basename($rel_path);
        $idx = array_search($current_name, $page_files);
        
        $prev_link = null;
        $next_link = null;
        
        if ($idx !== false) {
            if ($idx > 0) $prev_link = $page_files[$idx - 1];
            if ($idx < count($page_files) - 1) $next_link = $page_files[$idx + 1];
        }
        
        $nav_html = '<div class="max-w-4xl mx-auto mt-12 pt-8 border-t border-gray-800 flex justify-between items-center">';
        
        // Prev Button
        if ($prev_link) {
             $nav_html .= '<a href="/' . ($parent_url ? $parent_url . '/' : '') . $prev_link . '" class="px-6 py-3 bg-gray-800 rounded-lg text-white hover:bg-gray-700 transition-colors flex items-center shadow-lg border border-gray-700">';
             $nav_html .= '<i class="fas fa-arrow-left mr-3 text-cyan-400"></i> Previous Question</a>';
        } else {
             $nav_html .= '<span class="px-6 py-3 bg-gray-800/50 rounded-lg text-gray-500 cursor-not-allowed flex items-center border border-gray-800">';
             $nav_html .= '<i class="fas fa-arrow-left mr-3"></i> Previous Question</span>';
        }
        
        // Next Button
        if ($next_link) {
             $nav_html .= '<a href="/' . ($parent_url ? $parent_url . '/' : '') . $next_link . '" class="px-6 py-3 bg-cyan-600 rounded-lg text-white hover:bg-cyan-500 transition-colors flex items-center shadow-md shadow-cyan-500/20">';
             $nav_html .= 'Next Question <i class="fas fa-arrow-right ml-3"></i></a>';
        } else {
             // Maybe hide next if end? Or disabled. User asked for 2 buttons. 
             // "previous button shall be with reduced opactity" (implied logic for disabled).
             // Let's hide Next if none, or disabled? Use disabled for consistency.
             $nav_html .= '<span class="px-6 py-3 bg-gray-800/50 rounded-lg text-gray-500 cursor-not-allowed flex items-center border border-gray-800">';
             $nav_html .= 'Next Question <i class="fas fa-arrow-right ml-3"></i></span>';
        }
        $nav_html .= '</div>';


        // OUTPUT
        $raw = file_get_contents($full_path . '.html');
        $body_content = '';
        if(preg_match('/<body[^>]*>(.*?)<\/body>/is', $raw, $matches)) {
            $body_content = $matches[1];
        } else {
            $body_content = $raw;
        }
        
        // Check if content has a max-w wrapper. The standard template has <div class="max-w-4xl mx-auto">
        // Breadcrumbs and Nav should probably be OUTSIDE that wrapper if the wrapper is inside the body.
        // BUT my current template wraps EVERYTHING in the body in `max-w-4xl`.
        // So I should probably inject INSIDE?
        // Actually, `body_content` will contain `<div class="max-w-4xl...">...</div>`.
        // If I append/prepend to `$body_content`, I'll have multiple 4xl containers. That's fine.
        // It allows them to stack vertically.
        
        echo $bc_html;
        echo $body_content;
        echo $nav_html;
        
    } else {
        http_response_code(404);
        require_once 'src/templates/404.php'; // Ensure this exists or fallback
    }
}

$content = ob_get_clean();

// Load the Main Layout
require_once 'src/templates/layout.php';
?>
