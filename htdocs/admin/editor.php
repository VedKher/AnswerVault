<?php
require_once 'auth.php';
checkAuth();

$base_dir = realpath(__DIR__ . '/../src/pages');
$file_rel_path = $_GET['file'] ?? '';
$file_full_path = realpath($base_dir . '/' . $file_rel_path);

// Security
if (!$file_full_path || strpos($file_full_path, $base_dir) !== 0 || !is_file($file_full_path)) {
    die("Invalid file access");
}

$content = file_get_contents($file_full_path);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_content = $_POST['content'] ?? '';
    file_put_contents($file_full_path, $new_content);
    $saved = true;
    $content = $new_content;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editing: <?= htmlspecialchars(basename($file_full_path)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- CodeMirror for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
</head>
<body class="bg-slate-900 text-slate-200 h-screen flex flex-col">

    <!-- Header -->
    <div class="bg-slate-800 p-4 border-b border-slate-700 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <a href="dashboard.php?path=<?= urlencode(dirname($file_rel_path)) ?>" class="text-slate-400 hover:text-white">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <h1 class="text-lg font-bold text-cyan-400">
                <i class="fas fa-edit mr-2"></i><?= htmlspecialchars(basename($file_full_path)) ?>
            </h1>
            <?php if (isset($saved)): ?>
                <span class="bg-green-600 text-white text-xs px-2 py-1 rounded fade-out">Saved Successfully!</span>
            <?php endif; ?>
        </div>
        <div class="flex items-center space-x-3">
             <button type="submit" form="editorForm" class="bg-cyan-600 hover:bg-cyan-500 text-white px-6 py-2 rounded font-bold shadow-lg transition-transform active:scale-95">
                <i class="fas fa-save mr-2"></i>Save Changes
            </button>
        </div>
    </div>

    <!-- Editor -->
    <form id="editorForm" method="POST" class="flex-1 flex overflow-hidden">
        <textarea id="code" name="content"><?= htmlspecialchars($content) ?></textarea>
    </form>

    <script>
        var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
            mode: "htmlmixed",
            theme: "dracula",
            lineNumbers: true,
            autoCloseTags: true,
            lineWrapping: true
        });
        editor.setSize("100%", "100%");
        
        // Hide success message after 3s
        setTimeout(() => {
            const msg = document.querySelector('.fade-out');
            if(msg) msg.style.opacity = '0';
        }, 3000);
    </script>
</body>
</html>
