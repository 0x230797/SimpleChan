<?php
    session_start();
    require_once 'config.php';
    require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleChan - Imageboard Anónimo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>
<body>
    <header>
        <h1>SimpleChan</h1>
        <p>Imageboard Anónimo Simple</p>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="reglas.php">Reglas</a>
            <?php if (is_admin()): ?>
                <a href="admin.php">Administración</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>

        <!-- Lista de posts -->
        <section>
            <h2>Reglas</h2>
            <article>
                <div class="rules-header">
                    <span class="rules-title">Bienvenidos</span>
                </div>
                <div class="rules-message">
                    SimpleChan es un imageboard anónimo simple y libre. Para mantener un ambiente respetuoso y seguro para todos los usuarios, hemos establecido las siguientes reglas.
                </div>
            </article>

            <article>
                <div class="rules-header">
                    <span class="rules-title">Reglas generales</span>
                </div>
                <div class="rules-message">
                    <div>
                        <h4>Respeto mutuo</h4>
                        <span>Mantén un tono respetuoso hacia otros usuarios. No se toleran insultos personales, discriminación por raza, género, religión, orientación sexual o nacionalidad.</span>
                    </div>
                    <div>
                        <h4>Contenido apropiado</h4>
                        <span>Trata de publicar algo, no tran prohibido, no queremos problemas aquí.</span>
                    </div>
                    <div>
                        <h4>No spam ni flood</h4>
                        <span>No publiques el mismo mensaje repetidas veces, no hagas posts vacíos o sin sentido, y no abuses del sistema de respuestas.</span>
                    </div>
                    <div>
                        <h4>Uso correcto de imágenes</h4>
                        <span>Solo se permiten archivos de imagen (JPG, PNG, GIF, WebP) de máximo 5MB. No subas imágenes ofensivas, con virus, o que violen derechos de autor.</span>
                    </div>
                    <div>
                        <h4>No publicidad ni autopromoción</h4>
                        <span>Prohibido hacer spam de enlaces externos, promocionar productos/servicios, o usar el imageboard para publicidad personal.</span>
                    </div>
                    <div>
                        <h4>No trolling destructivo</h4>
                        <span>Aunque se permite el humor y las bromas, no hagas posts con la única intención de provocar, molestar o arruinar conversaciones.</span>
                    </div>
                    <div>
                        <h4>Respeta la temática de la publicación</h4>
                        <span>Mantén tus posts relevantes y coherentes. No hagas posts completamente fuera de lugar o random sin contexto.</span>
                    </div>
                    <div>
                        <h4>No evasión de bans</h4>
                        <span>Si recibes un ban, no intentes evadirlo usando proxies, VPNs, o cambiando de IP. Espera a que expire o contacta al administrador.</span>
                    </div>
                </div>
                <div class="rules-header">
                    <span class="rules-title">Publicaciones</span>
                </div>
                <div class="rules-message">
                    <div>
                        <h4>Formatos de texto</h4>
                        <span>Me complace anunciar que agregamos nuevos formatos de texto para hacer que las publicaciones y respuestas se vean mucho mejor.</span>
                        <br><br>
                        <div>• ¿Cómo escribir en <b>negrita</b>?: **tu texto aquí**</div>
                        <div>• ¿Cómo subrayar?: _tu texto aquí_</div>
                        <div>• ¿Cómo escribir en <em>cursiva</em>?: *tu texto aquí*</div>
                        <div>• ¿Cómo escribir en <s>tachado</s>?: ~~tu texto aquí~~</div>
                        <div>• ¿Cómo escribir <span class="greentext">>texto verde</span>?: Escribe ">" (sin las comillas) delante del texto. El texto verde sirve para citar, ironizar o abreviar una historia, no lo uses sólo "para que quede más bonito en verde".</div>
                        <div>• ¿Cómo escribir <span class="pinktext">< texto rosa</span>?: Escribe "<" (sin las comillas) delante del texto. El texto rosa/rojo sirve para enfatizar algo, no lo uses sólo "para que quede más bonito en rosa".</div>
                    </div>
            </article>

            <article class="danger">
                <div class="rules-header">
                    <span class="rules-title">Advertencia</span>
                </div>
                <div class="rules-message">
                    El incumplimiento de estas reglas puede resultar en la eliminación de posts, advertencias, o baneos temporales/permanentes. Los administradores se reservan el derecho de modificar estas reglas en cualquier momento para mantener un ambiente seguro y respetuoso.
                </div>
            </article>

        </section>

    </main>

    <footer>
        <p>&copy; 2025 SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>

</body>
</html>