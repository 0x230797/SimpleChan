<?php
require_once 'config.php';
require_once 'functions.php';

session_start();

// Verificar si el usuario es administrador
if (!is_admin()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

    if ($post_id > 0) {
        if (isset($_POST['lock_post'])) {
            lock_post($post_id);
        } elseif (isset($_POST['unlock_post'])) {
            unlock_post($post_id);
        } elseif (isset($_POST['pin_post'])) {
            pin_post($post_id);
        } elseif (isset($_POST['unpin_post'])) {
            unpin_post($post_id);
        }
    }
}

header('Location: index.php');
exit;
