<?php

class ReplyController {
    
    public function validatePostId() {
        $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
        return ($post_id > 0) ? $post_id : false;
    }

    public function validateMainPost($post_id) {
        $post = get_post($post_id);
        
        // Debe existir y no ser una respuesta
        if (!$post || $post['parent_id'] !== null) {
            return false;
        }
        
        return $post;
    }

    public function isPostLocked($post) {
        return $post['is_locked'] && !is_admin();
    }

    public function redirectToHome() {
        header('Location: index.php');
        exit;
    }

    private function redirectWithSuccess($url) {
        header('Location: ' . $url);
        exit;
    }

    public function handleRequest($post_id) {
        $error = null;
        $success_message = null;

        // Mostrar mensaje de éxito si viene de redirección
        if (isset($_GET['success']) && $_GET['success'] == '1') {
            $success_message = 'Respuesta creada exitosamente.';
        }

        // Mostrar mensaje de éxito para reportes
        if (isset($_GET['report_success']) && $_GET['report_success'] == '1') {
            $success_message = 'Gracias por reportar! El reporte ha sido enviado al administrador.';
        }

        // Procesar nueva respuesta
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post'])) {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $error = 'Token CSRF inv\u00e1lido.';
            } else {
                $error = $this->processNewReply($post_id);
            }
        }

        // Procesar reporte
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $error = 'Token CSRF inv\u00e1lido.';
            } else {
                $result = $this->processReport();
                if ($result['success']) {
                    $this->redirectWithSuccess('reply.php?post_id=' . $post_id . '&report_success=1');
                } else {
                    $error = $result['error'];
                }
            }
        }

        return [
            'error' => $error,
            'success_message' => $success_message
        ];
    }

    private function processNewReply($post_id) {
        // Obtener datos del tablón para la respuesta
        $post = get_post($post_id);
        $board_id = $post['board_id'] ?? null;
        
        // Sanitizar y validar datos de entrada
        $name = clean_input($_POST['name'] ?? '');
        $name = empty(trim($name)) ? 'Anónimo' : $name;
        $message = clean_input($_POST['message'] ?? '');
        
        // Validar mensaje obligatorio
        if (empty($message)) {
            return 'El mensaje no puede estar vacío.';
        }
        
        // Procesar imagen subida
        $image_data = $this->processImageUpload();
        if ($image_data['error']) {
            return $image_data['error'];
        }
        
        // Crear la respuesta en la base de datos
        $reply_created = create_post(
            $name, 
            '', // Las respuestas no tienen subject
            $message, 
            $image_data['filename'], 
            $image_data['original_name'], 
            $post_id, // parent_id
            $board_id
        );
        
        if ($reply_created) {
            $this->redirectWithSuccess('reply.php?post_id=' . $post_id . '&success=1');
        } else {
            return 'Error al crear la respuesta.';
        }
        
        return null;
    }

    private function processImageUpload() {
        $result = [
            'filename' => null,
            'original_name' => null,
            'error' => null
        ];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['image']);
            if ($upload_result['success']) {
                $result['filename'] = $upload_result['filename'];
                $result['original_name'] = $upload_result['original_name'];
            } else {
                $result['error'] = $upload_result['error'];
            }
        }
        
        return $result;
    }

    private function processReport() {
        $post_id = isset($_POST['report_post_id']) ? (int)$_POST['report_post_id'] : 0;
        $reason = clean_input($_POST['report_reason'] ?? '');
        $details = clean_input($_POST['report_details'] ?? '');
        
        if ($post_id <= 0) {
            return ['success' => false, 'error' => 'ID de post inválido'];
        }
        
        if (empty($reason)) {
            return ['success' => false, 'error' => 'El motivo es requerido'];
        }
        
        $reporter_ip = get_user_ip();
        
        if (create_report($post_id, $reason, $details, $reporter_ip)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Error al crear el reporte'];
        }
    }

    public function loadReplyPageData($post_id) {
        return [
            'replies' => get_replies($post_id),
            'boards_by_category' => $this->organizeBoardsByCategory(),
            'random_banner' => $this->getRandomBanner(),
            'board' => $this->getBoardFromPost($post_id)
        ];
    }

    private function organizeBoardsByCategory() {
        $boards_by_category = [];
        $all_boards = get_all_boards();
        
        foreach ($all_boards as $board) {
            $category = $board['category'] ?? 'Sin categoría';
            if (!isset($boards_by_category[$category])) {
                $boards_by_category[$category] = [];
            }
            $boards_by_category[$category][] = $board;
        }
        
        return $boards_by_category;
    }

    private function getRandomBanner() {
        $banner_dir = 'assets/banners/';
        if (!is_dir($banner_dir)) {
            return null;
        }
        
        $banners = array_diff(scandir($banner_dir), ['.', '..']);
        return $banners ? $banner_dir . $banners[array_rand($banners)] : null;
    }

    private function getBoardFromPost($post_id) {
        $post = get_post($post_id);
        return get_board_by_id($post['board_id']);
    }
}
?>