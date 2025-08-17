<?php

/**
 * Maneja las redirecciones y mensajes de éxito
 */
class RedirectHandler 
{
    /**
     * Maneja redirecciones de mensajes de éxito
     */
    public static function handleSuccessRedirects(): void
    {
        if (isset($_GET['post_success']) && $_GET['post_success'] == 1) {
            $_SESSION['success_message'] = '¡Post creado exitosamente!';
            $url = self::buildCleanUrl(['post_success']);
            header("Location: $url");
            exit;
        }

        if (isset($_GET['report_success']) && $_GET['report_success'] == 1) {
            $_SESSION['success_message'] = '¡Gracias por reportar! El reporte ha sido enviado al administrador.';
            $url = self::buildCleanUrl(['report_success']);
            header("Location: $url");
            exit;
        }
    }

    /**
     * Construye URL limpia removiendo parámetros específicos
     */
    private static function buildCleanUrl(array $remove_params): string
    {
        $query_params = $_GET;
        
        foreach ($remove_params as $param) {
            unset($query_params[$param]);
        }
        
        $query_string = http_build_query($query_params);
        return $_SERVER['PHP_SELF'] . ($query_string ? '?' . $query_string : '');
    }
}
?>