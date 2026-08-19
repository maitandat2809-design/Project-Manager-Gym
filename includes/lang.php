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

// Include the translation file into global scope
$lang_file = __DIR__ . "/../languages/{$current_lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include __DIR__ . "/../languages/en.php"; // Fallback
}

if (isset($lang) && is_array($lang)) {
    $GLOBALS['lang_dict'] = $lang;
    $GLOBALS['lang'] = $lang;
}

// Global function for translation
if (!function_exists('__')) {
    function __($key) {
        global $lang;
        if (isset($lang) && is_array($lang) && isset($lang[$key])) {
            return $lang[$key];
        }
        if (isset($GLOBALS['lang_dict']) && is_array($GLOBALS['lang_dict']) && isset($GLOBALS['lang_dict'][$key])) {
            return $GLOBALS['lang_dict'][$key];
        }
        if (isset($GLOBALS['lang']) && is_array($GLOBALS['lang']) && isset($GLOBALS['lang'][$key])) {
            return $GLOBALS['lang'][$key];
        }
        return $key;
    }
}

if (!function_exists('getLocalizedCoachMsg')) {
    function getLocalizedCoachMsg($msg) {
        if (empty($msg)) return '';
        $curr_lang = $_SESSION['lang'] ?? 'en';
        $sub_vi = 'Phân công HLV dạy thế cho buổi tập bị hủy đột xuất';
        $sub_en = 'Assigned substitute coach for emergency cancelled session';
        
        $trimmed = trim($msg);
        if ($trimmed === $sub_vi || $trimmed === $sub_en) {
            return ($curr_lang === 'en') ? $sub_en : $sub_vi;
        }
        return $msg;
    }
}
?>
