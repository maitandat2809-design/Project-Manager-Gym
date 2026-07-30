<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language switch
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] == 'en' ? 'en' : 'vi';
    // Remove lang from URL to keep it clean
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    $query = $_GET;
    unset($query['lang']);
    if (count($query) > 0) {
        $url .= '?' . http_build_query($query);
    }
    header("Location: " . $url);
    exit();
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

$current_lang = $_SESSION['lang'];

// Include the translation file
$lang_file = __DIR__ . "/../languages/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include __DIR__ . "/../languages/en.php"; // Fallback
}

// Global function for translation
function __($key) {
    global $lang;
    return $lang[$key] ?? $key;
}
