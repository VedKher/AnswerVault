<?php
require_once 'auth.php';
checkAuth();

$base_dir = realpath(__DIR__ . '/../src/pages');
$current_rel_path = $_GET['path'] ?? '';
$current_full_path = realpath($base_dir . '/' . $current_rel_path);

// Security check
if (!$current_full_path || strpos($current_full_path, $base_dir) !== 0) {
    $current_full_path = $base_dir;
    $current_rel_path = '';
}

// Get items
$items = scandir($current_full_path);
$folders = [];
$files = [];

foreach ($items as $item) {
    if ($item === '.' || $item === '..' || $item === '.order.json') continue;
    $path = $current_full_path . '/' . $item;
    if (is_dir($path)) {
        $folders[] = $item;
    } else {
        $files[] = $item;
    }
}

// Load custom order if exists
$order_file = $current_full_path . '/.order.json';
if (file_exists($order_file)) {
    $order = json_decode(file_get_contents($order_file), true);
    if (is_array($order)) {
        // Sort folders by custom order
        if (!empty($order['folders'])) {
            usort($folders, function($a, $b) use ($order) {
                $posA = array_search($a, $order['folders']);
                $posB = array_search($b, $order['folders']);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
            });
        }
        // Sort files by custom order
        if (!empty($order['files'])) {
            usort($files, function($a, $b) use ($order) {
                $posA = array_search($a, $order['files']);
                $posB = array_search($b, $order['files']);
                if ($posA === false) $posA = 999;
                if ($posB === false) $posB = 999;
                return $posA - $posB;
            });
        }
    }
}

// Calculate stats
$total_folders = 0;
$total_files = 0;
function countRecursive($dir, &$folders, &$files) {
    $items = @scandir($dir) ?: [];
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.order.json') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $folders++;
            countRecursive($path, $folders, $files);
        } else {
            $files++;
        }
    }
}
countRecursive($base_dir, $total_folders, $total_files);

// Feedback count
$feedback_file = __DIR__ . '/feedback.json';
$feedback_count = 0;
if (file_exists($feedback_file)) {
    $fb_data = json_decode(file_get_contents($feedback_file), true);
    $feedback_count = count($fb_data['feedback'] ?? []);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .sortable-ghost { opacity: 0.4; }
        .sortable-chosen { transform: scale(1.05); box-shadow: 0 10px 40px rgba(0,255,255,0.3); }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 font-sans min-h-screen">
    <div class="flex flex-col md:flex-row h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-slate-800 border-b md:border-b-0 md:border-r border-slate-700 flex flex-col shrink-0">
            <div class="p-4 md:p-6 border-b border-slate-700 flex justify-between items-center md:block">
                <h1 class="text-xl font-bold text-cyan-400"><i class="fas fa-shield-alt mr-2"></i>Admin</h1>
                <button class="md:hidden text-slate-400 hover:text-white" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <div id="mobile-nav" class="hidden md:flex flex-1 flex-col">
                <nav class="flex-1 p-4 space-y-2">
                    <a href="dashboard.php" class="block p-3 rounded bg-slate-700 text-white"><i class="fas fa-folder mr-3"></i>Browse Pages</a>
                    <a href="view_feedback.php" class="block p-3 rounded hover:bg-slate-700 transition-colors"><i class="fas fa-comments mr-3 text-green-400"></i>View Feedback</a>
                    <a href="../index.html" target="_blank" class="block p-3 rounded hover:bg-slate-700 transition-colors"><i class="fas fa-external-link-alt mr-3"></i>Visit Site</a>
                </nav>
                <div class="p-4 border-t border-slate-700">
                    <a href="logout.php" class="block w-full text-center py-2 px-4 rounded bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white transition-colors text-sm font-medium mb-3">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                    <p class="text-xs text-slate-500 text-center">Logged in as Admin</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Toolbar -->
            <div class="bg-slate-800 border-b border-slate-700 p-4 flex flex-col md:flex-row justify-between items-start md:items-center shadow-md z-10 gap-4">
                <div class="flex items-center space-x-2 text-sm">
                    <a href="dashboard.php" class="text-slate-400 hover:text-white"><i class="fas fa-home"></i></a>
                    <?php
                    $parts = array_filter(explode('/', $current_rel_path));
                    $build_path = '';
                    foreach ($parts as $part): 
                        $build_path .= ($build_path ? '/' : '') . $part;
                    ?>
                        <span class="text-slate-600">/</span>
                        <a href="?path=<?= urlencode($build_path) ?>" class="text-cyan-400 hover:text-cyan-300 font-medium"><?= htmlspecialchars($part) ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="flex space-x-3 flex-wrap gap-2">
                    <button onclick="toggleBulkMode()" id="bulkModeBtn" class="bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 rounded text-sm font-medium shadow transition-colors">
                        <i class="fas fa-check-double mr-2"></i>Select
                    </button>
                    <button onclick="bulkDelete()" id="bulkDeleteBtn" class="hidden bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded text-sm font-medium shadow transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete Selected
                    </button>
                     <button onclick="showModal('createFolderModal')" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded text-sm font-medium shadow transition-colors">
                        <i class="fas fa-folder-plus mr-2"></i>New Folder
                    </button>
                    <button onclick="showModal('createFileModal')" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded text-sm font-medium shadow transition-colors">
                        <i class="fas fa-file-alt mr-2"></i>New Page
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="bg-slate-800/50 border-b border-slate-700 px-6 py-3 flex flex-wrap gap-6 text-sm">
                <div class="flex items-center gap-2">
                    <i class="fas fa-folder text-yellow-500"></i>
                    <span class="text-slate-400">Total Folders:</span>
                    <span class="text-white font-bold"><?= $total_folders ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-file text-cyan-500"></i>
                    <span class="text-slate-400">Total Pages:</span>
                    <span class="text-white font-bold"><?= $total_files ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-comments text-green-500"></i>
                    <span class="text-slate-400">Feedback:</span>
                    <span class="text-white font-bold"><?= $feedback_count ?></span>
                </div>
            </div>

            <!-- Explorer Grid -->
            <div class="flex-1 overflow-auto p-6 bg-slate-900">
                
                <?php if ($current_rel_path): ?>
                    <a href="?path=<?= urlencode(dirname($current_rel_path) === '.' ? '' : dirname($current_rel_path)) ?>" 
                       class="inline-flex items-center text-slate-400 hover:text-white mb-6 group">
                        <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-slate-700 transition-colors">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                        <span class="font-medium">Up one level</span>
                    </a>
                <?php endif; ?>

                <!-- Folders Container -->
                <div id="folders-container" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-4 mb-6">
                    <!-- Folders -->
                    <?php foreach ($folders as $folder): ?>
                        <div data-name="<?= htmlspecialchars($folder) ?>" data-type="folder" class="sortable-item selectable-item block group p-4 rounded-xl bg-slate-800 border border-slate-700 hover:border-cyan-500 hover:bg-slate-750 transition-all text-center relative">
                            <input type="checkbox" class="bulk-checkbox hidden absolute top-2 left-2 w-5 h-5 accent-cyan-500">
                            <div class="drag-handle absolute top-2 right-2 text-slate-600 hover:text-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <button onclick="deleteItem('<?= htmlspecialchars(addslashes($folder)) ?>')" class="delete-btn hidden absolute top-2 right-8 text-red-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <a href="?path=<?= urlencode(($current_rel_path ? $current_rel_path . '/' : '') . $folder) ?>" class="block">
                                <div class="text-4xl text-yellow-500 mb-3 drop-shadow-lg group-hover:scale-110 transition-transform">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="text-sm font-medium text-slate-300 truncate group-hover:text-cyan-400"><?= htmlspecialchars($folder) ?></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Files Container -->
                <div id="files-container" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-4">

                    <!-- Files -->
                    <?php foreach ($files as $file): ?>
                        <div data-name="<?= htmlspecialchars($file) ?>" data-type="file" class="sortable-item selectable-item block group p-4 rounded-xl bg-slate-800 border border-slate-700 hover:border-cyan-500 hover:bg-slate-750 transition-all text-center relative">
                            <input type="checkbox" class="bulk-checkbox hidden absolute top-2 left-2 w-5 h-5 accent-cyan-500">
                            <div class="drag-handle absolute top-2 right-2 text-slate-600 hover:text-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-grip-vertical"></i>
                            </div>
                            <button onclick="deleteItem('<?= htmlspecialchars(addslashes($file)) ?>')" class="delete-btn hidden absolute top-2 right-8 text-red-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <a href="editor.php?file=<?= urlencode(($current_rel_path ? $current_rel_path . '/' : '') . $file) ?>" class="block">
                                <div class="text-4xl text-cyan-600 mb-3 drop-shadow-lg group-hover:scale-110 transition-transform">
                                    <i class="fas fa-file-code"></i>
                                </div>
                                <div class="text-sm font-medium text-slate-300 truncate group-hover:text-cyan-400 mb-2"><?= htmlspecialchars($file) ?></div>
                            </a>
                            <div class="flex justify-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="qna_editor.php?file=<?= urlencode(($current_rel_path ? $current_rel_path . '/' : '') . $file) ?>" title="QnA Builder" class="text-xs bg-green-600 hover:bg-green-500 text-white px-2 py-1 rounded">
                                    <i class="fas fa-magic"></i> Builder
                                </a>
                                <a href="editor.php?file=<?= urlencode(($current_rel_path ? $current_rel_path . '/' : '') . $file) ?>" title="Code Editor" class="text-xs bg-blue-600 hover:bg-blue-500 text-white px-2 py-1 rounded">
                                    <i class="fas fa-code"></i> Code
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($folders) && empty($files)): ?>
                        <div class="col-span-full text-center py-20 text-slate-600">
                            <i class="fas fa-folder-open text-6xl mb-4 opacity-50"></i>
                            <p class="text-lg">This folder is empty</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="createFolderModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-slate-800 p-6 rounded-lg w-96 border border-slate-600 shadow-2xl">
            <h3 class="text-lg font-bold text-white mb-4">Create New Folder</h3>
            <input type="text" id="newFolderName" placeholder="Folder Name (e.g., Chapter 1)" class="w-full p-2 mb-4 bg-slate-700 border border-slate-600 rounded text-white focus:outline-none focus:border-cyan-500">
            <div class="flex justify-end space-x-2">
                <button onclick="closeModal('createFolderModal')" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                <button onclick="createItem('create_folder')" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded">Create</button>
            </div>
        </div>
    </div>

    <div id="createFileModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-slate-800 p-6 rounded-lg w-96 border border-slate-600 shadow-2xl">
            <h3 class="text-lg font-bold text-white mb-4">Create New Page</h3>
            <input type="text" id="newFileName" placeholder="Page Name (e.g., exercise-1.1)" class="w-full p-2 mb-4 bg-slate-700 border border-slate-600 rounded text-white focus:outline-none focus:border-cyan-500">
            <div class="flex justify-end space-x-2">
                <button onclick="closeModal('createFileModal')" class="px-4 py-2 text-slate-400 hover:text-white">Cancel</button>
                <button onclick="createItem('create_file')" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded">Create</button>
            </div>
        </div>
    </div>

    <script>
        const currentPath = "<?= addslashes($current_rel_path) ?>";

        function showModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

        async function createItem(action) {
            const inputId = action === 'create_folder' ? 'newFolderName' : 'newFileName';
            const name = document.getElementById(inputId).value;
            if (!name) return;

            const formData = new FormData();
            formData.append('action', action);
            formData.append('path', currentPath);
            formData.append('name', name);

            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) location.reload();
                else alert('Error: ' + (data.error || 'Unknown error'));
            } catch (e) {
                alert('Request failed');
            }
        }
        
        async function deleteItem(itemName) {
            if (!confirm(`Are you sure you want to delete "${itemName}"? This cannot be undone!`)) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('path', currentPath || '.');
            formData.append('item', itemName);
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) location.reload();
                else alert('Error: ' + (data.error || 'Failed to delete'));
            } catch (e) {
                alert('Delete failed: ' + e.message);
            }
        }
        
        let bulkMode = false;
        
        function toggleBulkMode() {
            bulkMode = !bulkMode;
            const btn = document.getElementById('bulkModeBtn');
            const deleteBtn = document.getElementById('bulkDeleteBtn');
            const checkboxes = document.querySelectorAll('.bulk-checkbox');
            const deleteBtns = document.querySelectorAll('.delete-btn');
            
            if (bulkMode) {
                btn.innerHTML = '<i class="fas fa-times mr-2"></i>Cancel';
                btn.classList.remove('bg-purple-600', 'hover:bg-purple-500');
                btn.classList.add('bg-slate-600', 'hover:bg-slate-500');
                deleteBtn.classList.remove('hidden');
                checkboxes.forEach(cb => cb.classList.remove('hidden'));
                deleteBtns.forEach(db => db.classList.add('block'));
            } else {
                btn.innerHTML = '<i class="fas fa-check-double mr-2"></i>Select';
                btn.classList.add('bg-purple-600', 'hover:bg-purple-500');
                btn.classList.remove('bg-slate-600', 'hover:bg-slate-500');
                deleteBtn.classList.add('hidden');
                checkboxes.forEach(cb => {
                    cb.classList.add('hidden');
                    cb.checked = false;
                });
                deleteBtns.forEach(db => db.classList.remove('block'));
            }
        }
        
        async function bulkDelete() {
            const checked = document.querySelectorAll('.bulk-checkbox:checked');
            if (checked.length === 0) {
                alert('No items selected!');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${checked.length} item(s)? This cannot be undone!`)) return;
            
            for (const cb of checked) {
                const item = cb.closest('.selectable-item');
                const itemName = item.dataset.name;
                
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('path', currentPath || '.');
                formData.append('item', itemName);
                
                try {
                    await fetch('api.php', { method: 'POST', body: formData });
                } catch (e) {
                    console.error('Failed to delete:', itemName);
                }
            }
            
            location.reload();
        }
        
        // Drag and Drop Reordering
        async function saveOrder() {
            const foldersContainer = document.getElementById('folders-container');
            const filesContainer = document.getElementById('files-container');
            
            const folders = [];
            const files = [];
            
            if (foldersContainer) {
                foldersContainer.querySelectorAll('.sortable-item').forEach(el => {
                    folders.push(el.dataset.name);
                });
            }
            
            if (filesContainer) {
                filesContainer.querySelectorAll('.sortable-item').forEach(el => {
                    files.push(el.dataset.name);
                });
            }
            
            const formData = new FormData();
            formData.append('action', 'save_order');
            formData.append('path', currentPath || '.');
            formData.append('folders', JSON.stringify(folders));
            formData.append('files', JSON.stringify(files));
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) {
                    console.error('Failed to save order:', data.error);
                }
            } catch (e) {
                console.error('Save order failed:', e);
            }
        }
        
        // Initialize SortableJS on both containers
        document.addEventListener('DOMContentLoaded', function() {
            const foldersContainer = document.getElementById('folders-container');
            const filesContainer = document.getElementById('files-container');
            
            if (foldersContainer && foldersContainer.children.length > 0) {
                new Sortable(foldersContainer, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: saveOrder
                });
            }
            
            if (filesContainer && filesContainer.children.length > 0) {
                new Sortable(filesContainer, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: saveOrder
                });
            }
        });
    </script>
</body>
</html>
    