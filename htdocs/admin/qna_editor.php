<?php
require_once 'auth.php';
checkAuth();

$base_dir = realpath(__DIR__ . '/../src/pages');
$file_rel_path = $_GET['file'] ?? '';
$file_full_path = realpath($base_dir . '/' . $file_rel_path);

if (!$file_full_path || !file_exists($file_full_path)) {
    die("File not found");
}

$current_content = file_get_contents($file_full_path);

// --- HELPER FUNCTIONS ---

function imgToMd($text) {
    return preg_replace('/<img[^>]*src=["\'](.*?)["\'][^>]*alt=["\'](.*?)["\'][^>]*>/i', '![$2]($1)', $text);
}

function mdToImg($text) {
    return preg_replace(
        '/!\[(.*?)\]\((.*?)\)/', 
        '<img src="$2" alt="$1" class="my-4 rounded-lg shadow-lg max-w-full md:max-w-md border border-gray-700 block" loading="lazy">', 
        $text
    );
}

function tableToMd($text) {
    // Basic converting of HTML tables back to Markdown
    // Matches <table ...> ... </table>
    return preg_replace_callback('/<table[^>]*>(.*?)<\/table>/s', function($matches) {
        $inner = $matches[1];
        
        // Extract headers
        $headers = [];
        if (preg_match_all('/<th[^>]*>(.*?)<\/th>/s', $inner, $h_matches)) {
            foreach ($h_matches[1] as $h) $headers[] = trim(strip_tags($h));
        }
        
        // Extract rows
        $rows = [];
        // Find tbody if exists, else text
        if (preg_match('/<tbody[^>]*>(.*?)<\/tbody>/s', $inner, $tbody)) {
            $inner_rows = $tbody[1];
        } else {
            $inner_rows = $inner;
        }
        
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $inner_rows, $r_matches)) {
            foreach ($r_matches[1] as $row_html) {
                $cols = [];
                // Skip header row if it was inside tr/th
                if (strpos($row_html, '<th') !== false) continue; 
                
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/s', $row_html, $d_matches)) {
                    foreach ($d_matches[1] as $d) $cols[] = trim(strip_tags($d));
                    $rows[] = $cols;
                }
            }
        }
        
        if (empty($headers) && empty($rows)) return $matches[0]; // Fail safe
        
        $md = "\n";
        // Header line
        $md .= "| " . implode(" | ", $headers) . " |\n";
        // Separator line
        $md .= "| " . implode(" | ", array_fill(0, count($headers), "---")) . " |\n";
        // Body
        foreach ($rows as $row) {
            // Ensure row matches header count (basic pad)
            $padded = array_pad($row, count($headers), "");
            $md .= "| " . implode(" | ", $padded) . " |\n";
        }
        $md .= "\n";
        
        return $md;
    }, $text);
}

function mdToTable($text) {
    // Find Markdown table blocks
    return preg_replace_callback('/((?:\|.*\|\r?\n)+)/', function($matches) {
        $block = trim($matches[1]);
        $lines = explode("\n", $block);
        
        if (count($lines) < 2) return $matches[0];
        
        // Helper to split by pipe
        $parseLine = function($line) {
            $line = trim($line);
            if (empty($line)) return [];
            $line = trim($line, '|');
            return array_map('trim', explode('|', $line));
        };
        
        $headers = $parseLine($lines[0]);
        
        // Parse separator line for widths: |---{150}|---{200}|
        $separatorLine = $lines[1];
        if (strpos($separatorLine, '-') === false) return $matches[0];
        
        $colWidths = [];
        $separatorParts = $parseLine($separatorLine);
        foreach ($separatorParts as $part) {
            // Extract width from format: ---{150}
            if (preg_match('/---\{(\d+)\}/', $part, $m)) {
                $colWidths[] = intval($m[1]);
            } else {
                $colWidths[] = null; // No width specified
            }
        }
        
        $html = '<div class="my-4"><table class="w-full text-left text-sm text-gray-300 border border-gray-700 rounded-lg overflow-hidden">';
        
        // Head
        $html .= '<thead class="bg-gray-800 text-cyan-400 font-bold uppercase">';
        $html .= '<tr>';
        foreach ($headers as $idx => $h) {
            // Handle both single and double-encoded br tags
            $headerText = htmlspecialchars($h);
            $headerText = str_replace(['&amp;lt;br&amp;gt;', '&lt;br&gt;'], '<br>', $headerText);
            $html .= '<th class="px-4 py-3 border-b border-gray-700 break-words">' . $headerText . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Body
        $html .= '<tbody class="divide-y divide-gray-700">';
        for ($i = 2; $i < count($lines); $i++) {
            $cols = $parseLine($lines[$i]);
            if (empty($cols)) continue;
            
            $html .= '<tr class="hover:bg-gray-800/50 transition-colors">';
            foreach ($cols as $idx => $c) {
                // Handle both single and double-encoded br tags
                $cellText = htmlspecialchars($c);
                $cellText = str_replace(['&amp;lt;br&amp;gt;', '&lt;br&gt;'], '<br>', $cellText);
                $html .= '<td class="px-4 py-3 border-r border-gray-700 last:border-r-0 break-words whitespace-normal">' . $cellText . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        
        return $html;
    }, $text);
}


// --- PARSING EXISTING CONTENT ---

$existing_main_q = '';
$existing_pairs = [];

// Main Heading
if (preg_match('/<h2[^>]*bg-clip-text[^>]*>(.*?)<\/h2>/', $current_content, $matches)) {
    $existing_main_q = htmlspecialchars_decode($matches[1]);
}

// Question/Answer Pairs
$pair_pattern = '/<p[^>]*whitespace-pre-wrap[^>]*>(.*?)<\/p>.*?<div[^>]*whitespace-pre-wrap[^>]*>(.*?)<\/div>/s';

if (preg_match_all($pair_pattern, $current_content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        
        // PROCESS QUESTION
        $q_html = htmlspecialchars_decode($match[1]);
        $q_md = tableToMd($q_html);
        $q_md = imgToMd($q_md);
        
        // PROCESS ANSWER
        $a_html = $match[2]; 
        $a_md = tableToMd($a_html); 
        $a_md = imgToMd($a_md);     
        
        // CLEANUP: Only trim leading/trailing newlines/spaces from the whole block
        $clean_q = trim(strip_tags($q_md));
        $clean_a = trim(strip_tags($a_md));

        $existing_pairs[] = [
            'q' => $clean_q,
            'a' => $clean_a
        ];
    }
} elseif (preg_match_all('/<div class=\'pl-4 border-l-2 border-slate-700\'>\s*<p[^>]*>Q\.\s*(.*?)<\/p>\s*<div[^>]*>(.*?)<\/div>\s*<\/div>/s', $current_content, $matches, PREG_SET_ORDER)) {
    // Fallback for old structure
    foreach ($matches as $match) {
        $existing_pairs[] = [
            'q' => trim(imgToMd(htmlspecialchars_decode($match[1]))),
            'a' => trim(imgToMd(strip_tags(str_replace('<br />', "\n", $match[2]), '<img>')))
        ];
    }
}


// --- SAVE LOGIC ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $main_q = $_POST['main_question'];
    $sub_qs = $_POST['questions'] ?? [];
    $answers = $_POST['answers'] ?? [];
    
    $html_block = "<div class='mb-8 p-6 bg-gray-800/40 backdrop-blur-md rounded-xl border border-gray-700 shadow-xl qna-block'>"; 
    $html_block .= "<h2 class='text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 mb-6'>" . htmlspecialchars($main_q) . "</h2>";
    $html_block .= "<div class='space-y-6'>";
    
    for($i = 0; $i < count($sub_qs); $i++) {
        $q = htmlspecialchars($sub_qs[$i]);
        $a = $answers[$i]; 
        $a = htmlspecialchars($a);
        
        // Markdown Conversions (Ordered)
        // 1. Images
        $q = mdToImg($q);
        $a = mdToImg($a);
        // 2. Tables
        $q = mdToTable($q);
        $a = mdToTable($a);
        
        $html_block .= "<div class='mb-6 rounded-xl overflow-hidden border border-gray-700 shadow-lg group hover:border-cyan-500/50 transition-all'>";
        // Question
        $html_block .= "<div class='bg-gray-800/80 p-5 border-b border-gray-700 flex items-start gap-4'>";
        $html_block .= "<div class='shrink-0 w-8 h-8 rounded-full bg-cyan-500/10 flex items-center justify-center text-cyan-400 font-bold border border-cyan-500/20'>Q</div>";
        $html_block .= "<p class='text-lg text-white font-medium leading-relaxed pt-0.5 whitespace-pre-wrap'>$q</p>";
        $html_block .= "</div>";
        // Answer
        $html_block .= "<div class='bg-gray-900/60 p-5 flex items-start gap-4'>";
        $html_block .= "<div class='shrink-0 w-8 text-right text-green-400 font-bold text-sm tracking-wide pt-1 opacity-80'>ANS</div>";
        $html_block .= "<div class='text-gray-300 leading-relaxed whitespace-pre-wrap w-full'>$a</div>";
        $html_block .= "</div>";
        $html_block .= "</div>";
    }
    
    $html_block .= "</div></div>\n";
    
    // Replacement Logic
    $title_display = $main_q;
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/', $current_content, $t_match)) {
        $title_display = $t_match[1];
    }
    
    $new_container_inner = "\n        <h1 class=\"text-3xl font-bold text-cyan-400 mb-6 border-b border-gray-700 pb-4\">$title_display</h1>\n";
    $new_container_inner .= "        <div class=\"space-y-6\">\n";
    $new_container_inner .= "            <!-- Add Content Here -->\n";
    $new_container_inner .= "            $html_block";
    $new_container_inner .= "        </div>\n    ";
    
    $pattern_reset = '/<div class="max-w-4xl mx-auto">.*?<\/body>/s';
    $replacement_reset = "<div class=\"max-w-4xl mx-auto\">" . $new_container_inner . "</div>\n</body>";
    
    if (preg_match($pattern_reset, $current_content)) {
        $new_file_content = preg_replace($pattern_reset, $replacement_reset, $current_content);
    } else {
        $new_file_content = str_replace('</body>', $new_container_inner . "</body>", $current_content);
    }
    
    file_put_contents($file_full_path, $new_file_content);
    $success = "Updated successfully!";
    
    // Update local variables to reflect save
    $existing_main_q = $main_q;
    $existing_pairs = [];
     for($i = 0; $i < count($sub_qs); $i++) {
        $existing_pairs[] = ['q' => $sub_qs[$i], 'a' => $answers[$i]];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QnA Builder</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dropdown-menu { display: none; }
        .dropdown-open .dropdown-menu { display: flex; }
    </style>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen">

    <div class="max-w-4xl mx-auto p-6">
        <div class="flex items-center justify-between mb-8">
             <a href="dashboard.php?path=<?= urlencode(dirname($file_rel_path)) ?>" class="text-slate-400 hover:text-white">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <h1 class="text-2xl font-bold text-cyan-400">QnA Builder</h1>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="p-4 mb-6 bg-green-500/20 text-green-400 rounded">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <!-- Main Question -->
            <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-slate-400">Main Question / Heading</label>
                    
                    <!-- Image Toolbar -->
                    <div class="flex gap-2">
                        <div class="relative inline-block">
                            <button type="button" onclick="toggleDropdown(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors">
                                <i class="fas fa-image"></i>
                            </button>
                            <div class="dropdown-menu absolute right-0 mt-1 w-24 bg-slate-700 rounded shadow-xl border border-slate-600 flex-col z-20">
                                <button type="button" onclick="triggerUpload(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload</button>
                                <button type="button" onclick="triggerLink(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-link mr-2"></i>Link</button>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="text" name="main_question" value="<?= htmlspecialchars($existing_main_q) ?>" class="w-full bg-slate-900 border border-slate-600 rounded p-3 text-white focus:border-cyan-500 focus:outline-none" required>
            </div>
            
            <!-- Questions List -->
            <div id="questions-container" class="space-y-4">
                <?php foreach ($existing_pairs as $pair): ?>
                    <div class="question-item bg-slate-800 p-6 rounded-xl border border-slate-700 relative group">
                        <button type="button" onclick="removeItem(this)" class="absolute top-4 right-4 text-slate-500 hover:text-red-400 z-10">
                            <i class="fas fa-trash"></i>
                        </button>
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Left: Question -->
                            <div>
                                 <div class="flex items-center justify-between mb-2">
                                     <label class="block text-sm font-medium text-slate-400">Sub Question</label>
                                     
                                     <!-- Toolbar -->
                                     <div class="flex gap-2">
                                         <!-- Image Dropdown -->
                                         <div class="relative inline-block">
                                             <button type="button" onclick="toggleDropdown(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors">
                                                 <i class="fas fa-image"></i>
                                             </button>
                                             <div class="dropdown-menu absolute right-0 mt-1 w-24 bg-slate-700 rounded shadow-xl border border-slate-600 flex-col z-20">
                                                 <button type="button" onclick="triggerUpload(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload</button>
                                                 <button type="button" onclick="triggerLink(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-link mr-2"></i>Link</button>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 <textarea name="questions[]" rows="4" class="w-full bg-slate-900 border border-slate-600 rounded p-3 text-white focus:border-cyan-500 focus:outline-none font-mono text-sm" required><?= htmlspecialchars($pair['q']) ?></textarea>
                            </div>
                            <!-- Right: Answer -->
                            <div>
                                 <div class="flex items-center justify-between mb-2">
                                     <label class="block text-sm font-medium text-slate-400">Answer</label>
                                     <!-- Toolbar -->
                                     <div class="flex gap-2">
                                         <!-- Table Button -->
                                         <button type="button" onclick="insertTable(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors" title="Insert Table">
                                             <i class="fas fa-table"></i>
                                         </button>
                                         
                                         <!-- Image Dropdown -->
                                         <div class="relative inline-block">
                                             <button type="button" onclick="toggleDropdown(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors">
                                                 <i class="fas fa-image"></i>
                                             </button>
                                             <div class="dropdown-menu absolute right-0 mt-1 w-24 bg-slate-700 rounded shadow-xl border border-slate-600 flex-col z-20">
                                                 <button type="button" onclick="triggerUpload(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload</button>
                                                 <button type="button" onclick="triggerLink(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-link mr-2"></i>Link</button>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 <textarea name="answers[]" rows="4" class="w-full bg-slate-900 border border-slate-600 rounded p-3 text-white focus:border-cyan-500 focus:outline-none font-mono text-sm" required><?= htmlspecialchars($pair['a']) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" onclick="addQuestion()" class="w-full py-3 border-2 border-dashed border-slate-600 rounded-xl text-slate-400 hover:text-white hover:border-slate-500 hover:bg-slate-800 transition-all">
                <i class="fas fa-plus mr-2"></i> Add Sub-Question
            </button>

            <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-4 rounded-xl shadow-lg mt-8">
                Save & Update Page
            </button>
        </form>
    </div>

    <!-- Template -->
    <template id="q-template">
        <div class="question-item bg-slate-800 p-6 rounded-xl border border-slate-700 relative group">
            <button type="button" onclick="removeItem(this)" class="absolute top-4 right-4 text-slate-500 hover:text-red-400 z-10">
                <i class="fas fa-trash"></i>
            </button>
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Left -->
                <div>
                     <div class="flex items-center justify-between mb-2">
                         <label class="block text-sm font-medium text-slate-400">Sub Question</label>
                         <div class="flex gap-2">
                             <div class="relative inline-block">
                                 <button type="button" onclick="toggleDropdown(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors">
                                     <i class="fas fa-image"></i>
                                 </button>
                                 <div class="dropdown-menu absolute right-0 mt-1 w-24 bg-slate-700 rounded shadow-xl border border-slate-600 flex-col z-20">
                                     <button type="button" onclick="triggerUpload(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload</button>
                                     <button type="button" onclick="triggerLink(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-link mr-2"></i>Link</button>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <textarea name="questions[]" rows="4" class="w-full bg-slate-900 border border-slate-600 rounded p-3 text-white focus:border-cyan-500 focus:outline-none font-mono text-sm" required></textarea>
                </div>
                <!-- Right -->
                <div>
                     <div class="flex items-center justify-between mb-2">
                         <label class="block text-sm font-medium text-slate-400">Answer</label>
                         <div class="flex gap-2">
                             <button type="button" onclick="insertTable(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors" title="Insert Table">
                                 <i class="fas fa-table"></i>
                             </button>
                             <div class="relative inline-block">
                                 <button type="button" onclick="toggleDropdown(this)" class="text-xs bg-slate-700 px-2 py-1 rounded hover:bg-slate-600 text-cyan-400 transition-colors">
                                     <i class="fas fa-image"></i>
                                 </button>
                                 <div class="dropdown-menu absolute right-0 mt-1 w-24 bg-slate-700 rounded shadow-xl border border-slate-600 flex-col z-20">
                                     <button type="button" onclick="triggerUpload(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload</button>
                                     <button type="button" onclick="triggerLink(this)" class="text-left px-3 py-2 text-xs hover:bg-slate-600 text-white"><i class="fas fa-link mr-2"></i>Link</button>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <textarea name="answers[]" rows="4" class="w-full bg-slate-900 border border-slate-600 rounded p-3 text-white focus:border-cyan-500 focus:outline-none font-mono text-sm" required></textarea>
                </div>
            </div>
        </div>
    </template>

    <!-- Table Builder Modal -->
    <div id="table-modal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-slate-700 flex justify-between items-center">
                <h3 class="text-xl font-bold text-white"><i class="fas fa-table mr-2 text-cyan-400"></i>Table Builder</h3>
                <button onclick="closeTableModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="p-6 flex-1 overflow-auto">
                <!-- Step 1: Config -->
                <div id="table-step-1" class="flex items-end gap-4 mb-6">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Columns</label>
                        <input type="number" id="tbl-cols" value="3" min="1" max="10" class="bg-slate-900 border border-slate-600 rounded p-2 text-white w-24 focus:border-cyan-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Rows</label>
                        <input type="number" id="tbl-rows" value="3" min="1" max="20" class="bg-slate-900 border border-slate-600 rounded p-2 text-white w-24 focus:border-cyan-500 outline-none">
                    </div>
                    <button onclick="generateGrid()" class="bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 rounded font-medium transition-colors">
                        Generate Grid
                    </button>
                </div>

                <!-- Step 2: Grid -->
                <div id="table-grid-container" class="overflow-auto border border-slate-700 rounded-lg hidden">
                    <table class="w-full text-sm text-left text-gray-300">
                        <thead id="tbl-head" class="text-xs text-cyan-400 uppercase bg-slate-900"></thead>
                        <tbody id="tbl-body"></tbody>
                    </table>
                </div>
            </div>
            
            <div class="p-6 border-t border-slate-700 flex justify-end gap-3 bg-slate-800 rounded-b-xl">
                <button onclick="closeTableModal()" class="px-5 py-2 text-gray-400 hover:text-white transition-colors">Cancel</button>
                <button onclick="insertTableToEditor()" class="px-5 py-2 bg-green-600 hover:bg-green-500 text-white rounded font-bold shadow-lg transition-all hover:scale-105">Insert Table</button>
            </div>
        </div>
    </div>

    <input type="file" id="global-upload" class="hidden" accept="image/*">
    <script>
        let correctTargetInput = null;

        function addQuestion() {
            const template = document.getElementById('q-template');
            const clone = template.content.cloneNode(true);
            document.getElementById('questions-container').appendChild(clone);
        }
        
        function removeItem(btn) {
            if(confirm('Delete this question pair?')) {
                btn.closest('.question-item').remove();
            }
        }
        
        // --- TOOLBAR ACTIONS ---
        
        function toggleDropdown(btn) {
            document.querySelectorAll('.dropdown-open').forEach(el => {
                if(el !== btn.parentElement) el.classList.remove('dropdown-open');
            });
            btn.parentElement.classList.toggle('dropdown-open');
        }
        
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-menu') && !event.target.matches('.dropdown-menu *') && !event.target.closest('.relative')) {
                 document.querySelectorAll('.dropdown-open').forEach(el => el.classList.remove('dropdown-open'));
            }
        }
        
        function getTargetInput(btn) {
             const colDiv = btn.closest('.flex.items-center.justify-between').parentElement;
             return colDiv.querySelector('textarea, input[type="text"]');
        }

        function triggerUpload(btn) {
            correctTargetInput = getTargetInput(btn);
            document.getElementById('global-upload').click();
            btn.closest('.dropdown-open').classList.remove('dropdown-open');
        }
        
        function triggerLink(btn) {
            correctTargetInput = getTargetInput(btn);
            const url = prompt("Enter the Direct Image URL:");
            if (url) insertText(correctTargetInput, `![Image](${url})`);
            btn.closest('.dropdown-open').classList.remove('dropdown-open');
        }
        
        // --- TABLE BUILDER LOGIC ---
        
        function insertTable(btn) {
            correctTargetInput = getTargetInput(btn);
            document.getElementById('table-modal').classList.remove('hidden');
            // Reset grid if needed or keep previous state? Let's reset for fresh start
            document.getElementById('table-grid-container').classList.add('hidden');
        }

        function closeTableModal() {
            document.getElementById('table-modal').classList.add('hidden');
        }

        function generateGrid() {
            const cols = parseInt(document.getElementById('tbl-cols').value);
            const rows = parseInt(document.getElementById('tbl-rows').value);
            const thead = document.getElementById('tbl-head');
            const tbody = document.getElementById('tbl-body');
            
            thead.innerHTML = '';
            tbody.innerHTML = '';
            
            // Header Row with Resize Handles
            let hRow = '<tr>';
            for(let c=0; c<cols; c++) {
                hRow += `
                    <th class="p-1 relative col-${c}" style="min-width: 150px; max-width: 150px;">
                        <input type="text" placeholder="Header ${c+1}" 
                               class="w-full bg-slate-800 border-none focus:ring-1 focus:ring-cyan-500 rounded px-2 py-1 text-center font-bold text-white relative z-10">
                        <div class="resize-handle absolute top-0 right-0 w-2 h-full cursor-col-resize hover:bg-cyan-500/50 transition-colors z-20"
                             onmousedown="startResize(event, ${c})" title="Drag to resize"></div>
                    </th>`;
            }
            hRow += '</tr>';
            thead.innerHTML = hRow;
            
            // Body Rows
            let bodyHTML = '';
            for(let r=0; r<rows; r++) {
                let row = '<tr class="border-b border-gray-700 last:border-0">';
                for(let c=0; c<cols; c++) {
                    row += `
                        <td class="p-1 border-r border-gray-700 last:border-0 col-${c}" style="min-width: 150px; max-width: 150px;">
                            <textarea rows="2" 
                                      class="w-full bg-transparent outline-none px-2 py-1 text-gray-300 hover:bg-slate-700/50 focus:bg-slate-800 transition-colors resize-none overflow-hidden"
                                      oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px'"></textarea>
                        </td>`;
                }
                row += '</tr>';
                bodyHTML += row;
            }
            tbody.innerHTML = bodyHTML;
            
            document.getElementById('table-grid-container').classList.remove('hidden');
        }

        // Excel-style Column Resizing
        let resizingCol = null;
        let startX = 0;
        let startWidth = 0;

        function startResize(e, colIndex) {
            e.preventDefault();
            resizingCol = colIndex;
            startX = e.pageX;
            
            const firstCell = document.querySelector(`.col-${colIndex}`);
            startWidth = firstCell.offsetWidth;
            
            document.addEventListener('mousemove', doResize);
            document.addEventListener('mouseup', stopResize);
            
            // Visual feedback
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        }

        function doResize(e) {
            if (resizingCol === null) return;
            
            const diff = e.pageX - startX;
            const newWidth = Math.max(50, startWidth + diff);
            
            const cells = document.querySelectorAll(`.col-${resizingCol}`);
            cells.forEach(cell => {
                cell.style.width = newWidth + 'px';
                cell.style.minWidth = newWidth + 'px';
                cell.style.maxWidth = newWidth + 'px';
            });
        }

        function stopResize() {
            resizingCol = null;
            document.removeEventListener('mousemove', doResize);
            document.removeEventListener('mouseup', stopResize);
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }

        function insertTableToEditor() {
            const thead = document.getElementById('tbl-head');
            const tbody = document.getElementById('tbl-body');
            
            if (thead.children.length === 0) return;
            
            // Extract Headers and Column Widths
            const headerCells = thead.querySelectorAll('th');
            let headers = [];
            let widths = [];
            
            headerCells.forEach(th => {
                const input = th.querySelector('input[type="text"]');
                const text = (input.value.trim() || input.placeholder).replace(/\n/g, '<br>');
                headers.push(text);
                const width = th.offsetWidth || 150;
                widths.push(width);
            });
            
            // Extract Rows
            let rows = [];
            const trs = tbody.querySelectorAll('tr');
            trs.forEach(tr => {
                let rowData = [];
                tr.querySelectorAll('textarea').forEach(textarea => {
                    // Replace newlines with <br> tags for Markdown compatibility
                    const text = textarea.value.trim().replace(/\n/g, '<br>');
                    rowData.push(text);
                });
                rows.push(rowData);
            });
            
            // Construct Markdown with Width in Separator (hidden format)
            let md = "\n| " + headers.join(" | ") + " |\n";
            // Embed widths in separator using format: |---{150}|---{200}|
            let separator = "| " + widths.map(w => `---{${w}}`).join(" | ") + " |\n";
            md += separator;
            
            rows.forEach(row => {
               md += "| " + row.join(" | ") + " |\n"; 
            });
            md += "\n";
            
            insertText(correctTargetInput, md);
            closeTableModal();
        }

        
        function insertText(inputElem, text) {
            const start = inputElem.selectionStart;
            const end = inputElem.selectionEnd;
            const original = inputElem.value;
            inputElem.value = original.substring(0, start) + text + original.substring(end);
            inputElem.selectionStart = inputElem.selectionEnd = start + text.length;
            inputElem.focus();
        }
        
        // Upload Logic
        document.getElementById('global-upload').addEventListener('change', async function(e) {
            if (!this.files || !this.files[0]) return;
            const file = this.files[0];
            const formData = new FormData();
            formData.append('file', file);
            
            const originalPlaceholder = correctTargetInput.placeholder;
            correctTargetInput.placeholder = "Uploading...";
            
            try {
                const res = await fetch('upload.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.url) {
                   insertText(correctTargetInput, `![Image](${data.url})`);
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Upload error');
            }
            
            this.value = ''; 
            correctTargetInput.placeholder = originalPlaceholder;
        });
        
        if(document.getElementById('questions-container').children.length === 0) {
            addQuestion();
        }
    </script>
</body>
</html>
