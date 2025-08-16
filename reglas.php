<?php
/**
 * SimpleChan - Página de Reglas
 * Muestra las reglas y formatos de texto del sitio
 */

session_start();
require_once 'config.php';
require_once 'functions.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reglas - SimpleChan - Imageboard Anónimo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/themes.css">
    <link id="site-favicon" rel="shortcut icon" href="assets/favicon/favicon.ico" type="image/x-icon">
</head>

<body>
    <header>
        <a href="index.php">
            <img id="site-logo" src="assets/imgs/logo.png" alt="SimpleChan">
        </a>
    </header>

    <main>
        <section>
            <h2>Reglas</h2>

            <!-- BIENVENIDA -->
            <article>
                <div class="rules-header">
                    <span class="rules-title">Bienvenidos</span>
                </div>
                <div class="rules-message">
                    SimpleChan es un imageboard anónimo simple y libre. Para mantener un ambiente 
                    respetuoso y seguro para todos los usuarios, hemos establecido las siguientes reglas.
                </div>
            </article>

            <!-- REGLAS GENERALES -->
            <article>
                <div class="rules-header">
                    <span class="rules-title">Reglas generales</span>
                </div>
                <div class="rules-message">
                    <?php 
                    // Array de reglas generales
                    $general_rules = [
                        [
                            'title' => 'Respeto mutuo',
                            'description' => 'Mantén un tono respetuoso hacia otros usuarios. No se toleran insultos personales, discriminación por raza, género, religión, orientación sexual o nacionalidad.'
                        ],
                        [
                            'title' => 'Contenido apropiado',
                            'description' => 'Trata de publicar algo, no tran prohibido, no queremos problemas aquí.'
                        ],
                        [
                            'title' => 'No spam ni flood',
                            'description' => 'No publiques el mismo mensaje repetidas veces, no hagas posts vacíos o sin sentido, y no abuses del sistema de respuestas.'
                        ],
                        [
                            'title' => 'Uso correcto de imágenes',
                            'description' => 'Solo se permiten archivos de imagen (JPG, PNG, GIF, WebP) de máximo 5MB. No subas imágenes ofensivas, con virus, o que violen derechos de autor.'
                        ],
                        [
                            'title' => 'No publicidad ni autopromoción',
                            'description' => 'Prohibido hacer spam de enlaces externos, promocionar productos/servicios, o usar el imageboard para publicidad personal.'
                        ],
                        [
                            'title' => 'No trolling destructivo',
                            'description' => 'Aunque se permite el humor y las bromas, no hagas posts con la única intención de provocar, molestar o arruinar conversaciones.'
                        ],
                        [
                            'title' => 'Respeta la temática de la publicación',
                            'description' => 'Mantén tus posts relevantes y coherentes. No hagas posts completamente fuera de lugar o random sin contexto.'
                        ],
                        [
                            'title' => 'No evasión de bans',
                            'description' => 'Si recibes un ban, no intentes evadirlo usando proxies, VPNs, o cambiando de IP. Espera a que expire o contacta al administrador.'
                        ]
                    ];

                    // Mostrar las reglas
                    foreach ($general_rules as $rule): ?>
                        <div>
                            <h4><?php echo htmlspecialchars($rule['title']); ?></h4>
                            <span><?php echo htmlspecialchars($rule['description']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <!-- FORMATOS DE TEXTO -->
            <article>
                <div class="rules-header">
                    <span class="rules-title">Publicaciones</span>
                </div>
                <div class="rules-message">
                    <div>
                        <h4>Formatos de texto</h4>
                        <span>Me complace anunciar que agregamos nuevos formatos de texto para hacer que las publicaciones y respuestas se vean mucho mejor.</span>
                        <br><br>

                        <?php 
                        // Array de formatos de texto
                        $text_formats = [
                            [
                                'name' => 'negrita',
                                'syntax' => '**tu texto aquí**',
                                'example' => '<b>negrita</b>'
                            ],
                            [
                                'name' => 'subrayar',
                                'syntax' => '_tu texto aquí_',
                                'example' => '<u>subrayar</u>'
                            ],
                            [
                                'name' => 'cursiva',
                                'syntax' => '*tu texto aquí*',
                                'example' => '<em>cursiva</em>'
                            ],
                            [
                                'name' => 'tachado',
                                'syntax' => '~tu texto aquí~',
                                'example' => '<s>tachado</s>'
                            ],
                            [
                                'name' => 'texto verde',
                                'syntax' => '"> (sin las comillas) delante del texto',
                                'example' => '<span class="greentext">&gt;texto verde</span>',
                                'note' => 'El texto verde sirve para citar, ironizar o abreviar una historia, no lo uses sólo "para que quede más bonito en verde".'
                            ],
                            [
                                'name' => 'texto rosa',
                                'syntax' => '"< (sin las comillas) delante del texto',
                                'example' => '<span class="pinktext">&lt;texto rosa</span>',
                                'note' => 'El texto rosa sirve para enfatizar algo, no lo uses sólo "para que quede más bonito en rosa".'
                            ]
                        ];

                        // Mostrar los formatos
                        foreach ($text_formats as $format): ?>
                            <div>
                                • ¿Cómo escribir en <?php echo $format['example']; ?>?: <?php echo htmlspecialchars($format['syntax']); ?>
                                <?php if (isset($format['note'])): ?>
                                    <?php echo htmlspecialchars($format['note']); ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <!-- ADVERTENCIA -->
            <article class="danger">
                <div class="rules-header">
                    <span class="rules-title">Advertencia</span>
                </div>
                <div class="rules-message">
                    El incumplimiento de estas reglas puede resultar en la eliminación de posts, 
                    advertencias, o baneos temporales/permanentes. Los administradores se reservan 
                    el derecho de modificar estas reglas en cualquier momento para mantener un 
                    ambiente seguro y respetuoso.
                </div>
            </article>

        </section>
    </main>

    <!-- TEMAS -->
    <div class="theme-selector" style="margin:0 var(--spacing-sm);">
        <label for="theme-select">Selecciona un tema:</label>
        <select id="theme-select" onchange="changeTheme(this.value)">
            <option value="yotsuba">Yotsuba</option>
            <option value="yotsubab">Yotsuba Blue</option>
            <option value="dark">Dark</option>
        </select>
    </div>

    <!-- FOOTER -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> SimpleChan - Imageboard Simple y Anónimo</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>