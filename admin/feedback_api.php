<?php
// Feedback API - Receives and stores feedback
header('Content-Type: application/json');

// No auth required for feedback submission
$response = ['success' => false];

try {
    $rating = intval($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $page = trim($_POST['page'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        throw new Exception("Invalid rating.");
    }
    
    $feedback_file = __DIR__ . '/feedback.json';
    
    // Load existing feedback
    $feedback_data = [];
    if (file_exists($feedback_file)) {
        $feedback_data = json_decode(file_get_contents($feedback_file), true) ?: [];
    }
    
    // Add new feedback
    $feedback_data['feedback'][] = [
        'id' => count($feedback_data['feedback'] ?? []) + 1,
        'rating' => $rating,
        'message' => $message,
        'page' => $page,
        'timestamp' => date('c'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Save feedback
    if (file_put_contents($feedback_file, json_encode($feedback_data, JSON_PRETTY_PRINT)) === false) {
        throw new Exception("Failed to save feedback.");
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
