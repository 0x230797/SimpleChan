<?php

/**
 * Controlador principal del tablón
 * 
 * Maneja toda la lógica de negocio relacionada con los tablones:
 * - Validación de usuarios
 * - Procesamiento de posts y reportes
 * - Búsquedas y paginación
 */
class BoardController 
{
    // Propiedades principales
    private ?array $board = null;
    private ?int $board_id = null;
    private int $current_page = 1;
    private int $posts_per_page = 10;
    
    // Estado de la aplicación
    private array $messages = [];
    private ?array $search_results = null;
    
    /**
     * Constructor - Inicializa el controlador
     */
    public function __construct() 
    {
        $this->validateUserAccess();
        $this->initializeBoard();
        $this->initializePagination();
        $this->processRequests();
    }
    
    // ==========================================
    // MÉTODOS DE INICIALIZACIÓN
    // ==========================================
    
    /**
     * Valida que el usuario tenga acceso
     */
    private function validateUserAccess(): void 
    {
        $ban_info = is_user_banned();
        if ($ban_info) {
            $this->handleError('ban.php');
        }
    }
    
    /**
     * Inicializa el tablón actual
     */
    private function initializeBoard(): void 
    {
        $board_name = $_GET['board'] ?? null;
        $this->board_id = $_GET['board_id'] ?? null;
        
        if ($board_name) {
            $this->initializeBoardByName($board_name);
        } elseif ($this->board_id) {
            $this->initializeBoardById((int)$this->board_id);
        } else {
            $this->handleError('No se especificó un tablón válido.');
        }
    }
    
    /**
     * Inicializa el tablón por nombre
     */
    private function initializeBoardByName(string $board_name): void 
    {
        $board_name = urldecode(trim($board_name));
        
        // Buscar por short_id primero, luego por name
        $this->board = get_board_by_short_id($board_name);
        if (!$this->board) {
            $this->board = get_board_by_name($board_name);
        }
        
        if ($this->board) {
            $this->board_id = $this->board['id'];
        } else {
            $this->handleError('El tablón no existe.');
        }
    }
    
    /**
     * Inicializa el tablón por ID
     */
    private function initializeBoardById(int $board_id): void 
    {
        $this->board_id = $board_id;
        $this->board = get_board_by_id($this->board_id);
        
        if (!$this->board) {
            $this->handleError('El tablón no existe.');
        }
    }
    
    /**
     * Inicializa la paginación
     */
    private function initializePagination(): void 
    {
        $this->current_page = max(1, (int)($_GET['page'] ?? 1));
        
        $total_posts = count_posts_by_board($this->board_id);
        $total_pages = ceil($total_posts / $this->posts_per_page);
        
        // Ajustar página actual si excede el total
        if ($this->current_page > $total_pages && $total_pages > 0) {
            $this->current_page = $total_pages;
        }
    }
    
    // ==========================================
    // PROCESAMIENTO DE REQUESTS
    // ==========================================
    
    /**
     * Procesa todas las peticiones HTTP
     */
    private function processRequests(): void 
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processPostRequests();
        }
        
        $this->processGetRequests();
    }
    
    /**
     * Procesa peticiones POST
     */
    private function processPostRequests(): void 
    {
        if (isset($_POST['submit_report'])) {
            $this->processReport();
        } elseif (isset($_POST['submit_post'])) {
            $this->processPost();
        }
    }
    
    /**
     * Procesa peticiones GET
     */
    private function processGetRequests(): void 
    {
        if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
            $this->processSearch();
        }
    }
    
    // ==========================================
    // PROCESAMIENTO ESPECÍFICO
    // ==========================================
    
    /**
     * Procesa búsquedas de posts
     */
    private function processSearch(): void 
    {
        $query = clean_input($_GET['query']);
        $this->search_results = search_posts($query, $this->board_id);
        
        if (empty($this->search_results)) {
            $this->addMessage('error', 'No se encontraron publicaciones para la búsqueda: ' . htmlspecialchars($query));
        } else {
            $count = count($this->search_results);
            $plural = $count != 1 ? 's' : '';
            $message = "Resultados para: " . htmlspecialchars($query) . " ({$count} publicación{$plural} encontrada{$plural})";
            $this->addMessage('success', $message);
        }
    }
    
    /**
     * Procesa reportes de posts
     */
    private function processReport(): void 
    {
        $report_data = $this->validateReportData();
        
        if ($this->hasErrors() || !$report_data) {
            return;
        }
        
        if (create_report($report_data['post_id'], $report_data['reason'], $report_data['details'], $report_data['ip'])) {
            $redirect_url = $this->buildUrl(['report_success' => 1]);
            $this->redirect($redirect_url);
        } else {
            $this->addMessage('error', 'Error al enviar el reporte.');
        }
    }
    
    /**
     * Valida los datos del reporte
     */
    private function validateReportData(): ?array 
    {
        $post_id = (int)($_POST['report_post_id'] ?? 0);
        $reason = clean_input($_POST['report_reason'] ?? '');
        $details = clean_input($_POST['report_details'] ?? '');
        $reporter_ip = get_user_ip();
        
        if ($post_id <= 0) {
            $this->addMessage('error', 'ID de post inválido.');
            return null;
        }
        
        if (empty($reason)) {
            $this->addMessage('error', 'El motivo del reporte es requerido.');
            return null;
        }
        
        return [
            'post_id' => $post_id,
            'reason' => $reason,
            'details' => $details,
            'ip' => $reporter_ip
        ];
    }
    
    /**
     * Procesa la creación de posts
     */
    private function processPost(): void 
    {
        $post_data = $this->validatePostData();
        if ($this->hasErrors() || !$post_data) {
            return;
        }
        
        $image_result = $this->processImage();
        if ($this->hasErrors() || !$image_result) {
            return;
        }
        
        if ($this->createPost($post_data, $image_result)) {
            $redirect_url = $this->buildUrl(['post_success' => 1], 1);
            $this->redirect($redirect_url);
        } else {
            $this->addMessage('error', 'Error al crear el post.');
        }
    }
    
    /**
     * Valida los datos del post
     */
    private function validatePostData(): ?array 
    {
        $name = clean_input($_POST['name'] ?? '');
        $subject = clean_input($_POST['subject'] ?? '');
        $message = clean_input($_POST['message'] ?? '');
        $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        
        // Si el nombre está vacío, usar "Anónimo"
        if (empty(trim($name))) {
            $name = 'Anónimo';
        }
        
        if (empty($message)) {
            $this->addMessage('error', 'El mensaje no puede estar vacío.');
            return null;
        }
        
        return [
            'name' => $name,
            'subject' => $subject,
            'message' => $message,
            'parent_id' => $parent_id
        ];
    }
    
    /**
     * Procesa la imagen subida
     */
    private function processImage(): ?array 
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            // Para usuarios no admin, la imagen es requerida
            if (!is_admin()) {
                $this->addMessage('error', 'La imagen es requerida.');
                return null;
            }
            return $this->getEmptyImageResult();
        }
        
        $upload_result = upload_image($_FILES['image']);
        
        if (!$upload_result['success']) {
            $this->addMessage('error', $upload_result['error']);
            return null;
        }
        
        return $this->buildImageResult($upload_result);
    }
    
    /**
     * Retorna resultado vacío para imagen
     */
    private function getEmptyImageResult(): array 
    {
        return [
            'filename' => null,
            'original_name' => null,
            'size' => null,
            'dimensions' => null
        ];
    }
    
    /**
     * Construye el resultado de la imagen procesada
     */
    private function buildImageResult(array $upload_result): array 
    {
        $image_path = UPLOAD_DIR . $upload_result['filename'];
        $image_size = filesize($image_path);
        $image_dimensions = getimagesize($image_path);
        
        return [
            'filename' => $upload_result['filename'],
            'original_name' => $upload_result['original_name'],
            'size' => $image_size,
            'dimensions' => $image_dimensions[0] . 'x' . $image_dimensions[1]
        ];
    }
    
    /**
     * Crea el post en la base de datos
     */
    private function createPost(array $post_data, array $image_result): bool 
    {
        $result = create_post(
            $post_data['name'],
            $post_data['subject'],
            $post_data['message'],
            $image_result['filename'],
            $image_result['original_name'],
            $post_data['parent_id'],
            $this->board_id
        );

        if ($result) {
            $this->enforcePostLimit();
        }

        return $result;
    }
    
    /**
     * Aplica el límite de posts por tablón
     */
    private function enforcePostLimit(): void 
    {
        $total_posts = count_posts_by_board($this->board_id);
        
        if ($total_posts > 100) {
            $oldest_post = get_oldest_post($this->board_id);
            
            if ($oldest_post) {
                delete_post($oldest_post['id']);
                
                if (!empty($oldest_post['image_filename'])) {
                    $image_path = UPLOAD_DIR . $oldest_post['image_filename'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
        }
    }
    
    // ==========================================
    // MÉTODOS PÚBLICOS PARA LA VISTA
    // ==========================================
    
    /**
     * Obtiene los posts del tablón
     */
    public function getPosts(): array 
    {
        // Si hay resultados de búsqueda, retornarlos
        if ($this->search_results !== null) {
            return $this->search_results;
        }
        
        // Posts normales con paginación
        $offset = ($this->current_page - 1) * $this->posts_per_page;
        return get_posts_by_board($this->board_id, $this->posts_per_page, $offset);
    }
    
    /**
     * Obtiene información de paginación
     */
    public function getPaginationInfo(): array 
    {
        $total_posts = count_posts_by_board($this->board_id);
        $total_pages = ceil($total_posts / $this->posts_per_page);
        
        return [
            'current_page' => $this->current_page,
            'total_pages' => $total_pages,
            'total_posts' => $total_posts,
            'posts_per_page' => $this->posts_per_page
        ];
    }
    
    /**
     * Obtiene todos los boards organizados por categoría
     */
    public function getAllBoardsByCategory(): array 
    {
        $all_boards = get_all_boards();
        $boards_by_category = [];
        
        foreach ($all_boards as $nav_board) {
            $category = $nav_board['category'] ?? 'Sin categoría';
            if (!isset($boards_by_category[$category])) {
                $boards_by_category[$category] = [];
            }
            $boards_by_category[$category][] = $nav_board;
        }
        
        return $boards_by_category;
    }
    
    /**
     * Obtiene un banner aleatorio
     */
    public function getRandomBanner(): ?string 
    {
        $banner_dir = 'assets/banners/';
        
        if (!is_dir($banner_dir)) {
            return null;
        }
        
        $banners = array_diff(scandir($banner_dir), array('..', '.'));
        
        if (empty($banners)) {
            return null;
        }
        
        return $banner_dir . $banners[array_rand($banners)];
    }
    
    // Getters simples
    public function getBoard(): ?array { return $this->board; }
    public function getMessages(): array { return $this->messages; }
    public function getSearchResults(): ?array { return $this->search_results; }
    
    // ==========================================
    // MÉTODOS UTILITARIOS PRIVADOS
    // ==========================================
    
    /**
     * Construye URL con parámetros
     */
    private function buildUrl(array $extra_params = [], ?int $page = null): string 
    {
        $params = ['board' => $this->board['short_id']];
        
        if ($page === null) {
            $page = $this->current_page;
        }
        
        if ($page > 1) {
            $params['page'] = $page;
        }
        
        $params = array_merge($params, $extra_params);
        
        return $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    }
    
    /**
     * Verifica si hay errores
     */
    private function hasErrors(): bool 
    {
        return isset($this->messages['error']);
    }
    
    /**
     * Añade un mensaje
     */
    private function addMessage(string $type, string $message): void 
    {
        $this->messages[$type] = $message;
    }
    
    /**
     * Maneja errores fatales
     */
    private function handleError(string $message): void 
    {
        error_log("BoardController Error: " . $message);
        header("Location: 404.php");
        exit;
    }
    
    /**
     * Redirige a una URL
     */
    private function redirect(string $url): void 
    {
        header("Location: $url");
        exit;
    }
}
?>