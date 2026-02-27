<?php
// Prevent any HTML error output from corrupting JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
header('Content-Type: application/json');

try {
    checkAuth();

    $base_path = realpath(__DIR__ . '/../src/pages');
    $action = $_POST['action'] ?? '';
    $path = $_POST['path'] ?? ''; 
    $name = $_POST['name'] ?? '';

    // Sanitize Input (Remove bad chars like : * ? " < > |)
    $name = preg_replace('/[<>:"\/\\|?*]/', '', $name);
    $name = trim($name);

    if (empty($name) && $action !== 'delete' && $action !== 'save_order') {
        throw new Exception("Name cannot be empty or contain invalid characters.");
    }

    // Security: Prevent directory traversal
    // Handle empty or "." path as base directory
    if (empty($path) || $path === '.') {
        $full_path_dir = $base_path;
    } else {
        $full_path_dir = realpath($base_path . '/' . $path);
    }
    
    if (!$full_path_dir || strpos($full_path_dir, $base_path) !== 0) {
        throw new Exception("Invalid directory path.");
    }

    $response = ['success' => false];

    switch ($action) {
        case 'create_folder':
            $new_folder = $full_path_dir . '/' . $name;
            if (file_exists($new_folder)) {
                throw new Exception("Folder already exists.");
            }
            if (!mkdir($new_folder)) {
                $error = error_get_last();
                throw new Exception("Failed to create folder. " . ($error['message'] ?? ''));
            }
            $response['success'] = true;
            break;
            
        case 'create_file':
            if (!str_ends_with($name, '.html')) $name .= '.html';
            $new_file = $full_path_dir . '/' . $name;
            if (file_exists($new_file)) {
                throw new Exception("File already exists.");
            }
            
            $title = htmlspecialchars(str_replace('.html', '', $name));
            // Basic template
            $content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>{$title}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-cyan-400 mb-6 border-b border-gray-700 pb-4">{$title}</h1>
        <div class="space-y-6">
            <!-- Add Content Here -->
        </div>
    </div>
</body>
</html>
HTML;
            if (file_put_contents($new_file, $content) === false) {
                 throw new Exception("Failed to create file.");
            }
            $response['success'] = true;
            break;

        case 'delete':
            // Get item to delete from POST
            $item = trim($_POST['item'] ?? '');
            if (empty($item)) {
                throw new Exception("No item specified for deletion.");
            }
            
            $delete_path = $full_path_dir . '/' . $item;
            
            // Security check
            $real_delete_path = realpath($delete_path);
            if (!$real_delete_path || strpos($real_delete_path, $base_path) !== 0) {
                throw new Exception("Invalid path.");
            }
            
            // Recursive delete function
            function deleteRecursive($path) {
                if (is_file($path)) {
                    return unlink($path);
                } elseif (is_dir($path)) {
                    $items = scandir($path);
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        deleteRecursive($path . '/' . $item);
                    }
                    return rmdir($path);
                }
                return false;
            }
            
            if (deleteRecursive($real_delete_path)) {
                $response['success'] = true;
            } else {
                throw new Exception("Failed to delete.");
            }
            break;
        
        case 'save_order':
            // Save custom order for folders and files
            $folders = json_decode($_POST['folders'] ?? '[]', true);
            $files = json_decode($_POST['files'] ?? '[]', true);
            
            if (!is_array($folders)) $folders = [];
            if (!is_array($files)) $files = [];
            
            $order_file = $full_path_dir . '/.order.json';
            $order_data = json_encode([
                'folders' => $folders,
                'files' => $files
            ], JSON_PRETTY_PRINT);
            
            if (file_put_contents($order_file, $order_data) === false) {
                throw new Exception("Failed to save order.");
            }
            $response['success'] = true;
            break;
            
        default:
            throw new Exception("Invalid action.");
    }

} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response);
?>
