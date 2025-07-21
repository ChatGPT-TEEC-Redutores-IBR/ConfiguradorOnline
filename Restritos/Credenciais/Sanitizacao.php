<?php
/** Sanitize global input arrays */
function sanitize_recursive(&$data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            sanitize_recursive($data[$key]);
        } else {
            $clean = preg_replace('/[^\P{C}\n]+/u', '', $value);
            $trimmed = trim($clean);
            if (function_exists('mb_substr')) {
                $clean = mb_substr($trimmed, 0, 500);
            } else {
                $clean = substr($trimmed, 0, 500);
            }
            $data[$key] = $clean;
        }
    }
}

sanitize_recursive($_GET);
sanitize_recursive($_POST);
?>