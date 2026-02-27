<?php
require_once 'auth.php';

// If already logged in, go to dashboard
if (isset($_SESSION['logged_in']) || isset($_COOKIE[COOKIE_NAME])) {
    // CheckAuth logic will verify validity, if valid, redirect
    if (isset($_SESSION['logged_in'])) {
        header("Location: dashboard.php");
        exit;
    }
     // If only cookie exists, checkAuth() called manually would verify it, 
     // but here we can just verify briefly or let the user login again if unsure.
     // To keep simple: let's verify via checkAuth's logic usage:
     // logic in checkAuth handles redirection if fail, so:
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remember = isset($_POST['remember']);
    if (login($_POST['password'], $remember)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Incorrect Password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 flex items-center justify-center h-screen">
    <div class="bg-slate-800 p-8 rounded-xl shadow-2xl w-96 border border-slate-700">
        <h2 class="text-2xl font-bold text-cyan-400 mb-6 text-center">Admin Access</h2>
        <?php if (isset($error)): ?>
            <p class="text-red-500 text-sm mb-4 text-center"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-6">
                <input type="password" name="password" placeholder="Enter Password" 
                       class="w-full p-3 rounded bg-slate-700 text-white border border-slate-600 focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 transition-all placeholder-slate-400" autofocus required>
            </div>
            
             <div class="flex items-center mb-6">
                <input id="remember" name="remember" type="checkbox" class="w-4 h-4 text-cyan-600 bg-slate-700 border-gray-600 rounded focus:ring-cyan-500 focus:ring-2">
                <label for="remember" class="ml-2 text-sm font-medium text-slate-300">Remember me</label>
            </div>

            <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-bold py-3 rounded transition-colors text-lg shadow-lg">
                Unlock
            </button>
        </form>
    </div>
</body>
</html>
