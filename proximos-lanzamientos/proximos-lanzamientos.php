<?php
/**
 * Plugin Name: Proximos Lanzamientos Espaciales
 * Description: Muestra proximos lanzamientos de cohetes con el shortcode [proximos_lanzamientos].
 * Version: 1.0.6
 * Author: Pedro León con Codex
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PLE_PLUGIN_VERSION', '1.0.6');
define('PLE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLE_PLUGIN_PATH', plugin_dir_path(__FILE__));

function ple_register_assets() {
    wp_register_style(
        'ple-launches-style',
        PLE_PLUGIN_URL . 'assets/css/proximos-lanzamientos.css',
        array(),
        PLE_PLUGIN_VERSION
    );

    wp_register_script(
        'ple-launches-script',
        PLE_PLUGIN_URL . 'assets/js/proximos-lanzamientos.js',
        array(),
        PLE_PLUGIN_VERSION,
        true
    );

    wp_localize_script(
        'ple-launches-script',
        'pleLaunchesConfig',
        array(
            'apiUrl' => 'https://ll.thespacedevs.com/2.3.0/launches/upcoming/?limit=12&ordering=net&format=json',
            'refreshInterval' => 10 * 60 * 1000,
            'statusLoading' => 'Cargando próximos lanzamientos...',
            'statusUpdated' => 'Actualizado:',
            'localTimeNotice' => 'Todas las horas se muestran en tu hora local.',
            'statusError' => 'No se pudieron cargar los lanzamientos. Revisa la conexión o inténtalo más tarde.',
            'emptyMessage' => 'No hay lanzamientos próximos disponibles ahora mismo.',
            'fallbackText' => 'Pendiente de confirmar',
            'launchCountLabel' => 'lanzamientos cargados',
            'countdownLabel' => 'hasta el próximo lanzamiento',
            'sourceLabel' => 'Datos de',
            'sourceName' => 'Launch Library 2',
            'sourceUrl' => 'https://thespacedevs.com/llapi',
            'dateLabel' => 'Fecha y hora',
            'agencyLabel' => 'Agencia',
            'rocketLabel' => 'Cohete',
            'padLabel' => 'Plataforma',
            'pendingDate' => 'Fecha pendiente',
            'pendingLocation' => 'Ubicación pendiente',
            'missionFallback' => 'La misión todavía no tiene descripción pública.',
            'videoLabel' => 'Video',
            'webLabel' => 'Web',
            'officialWebLabel' => 'Web oficial',
        )
    );
}
add_action('wp_enqueue_scripts', 'ple_register_assets');

function ple_render_shortcode() {
    wp_enqueue_style('ple-launches-style');
    wp_enqueue_script('ple-launches-script');

    ob_start();
    ?>
    <div class="ple-launches-widget" data-ple-launches>
        <section class="ple-topbar" aria-labelledby="ple-page-title">
            <div>
                <p class="ple-eyebrow">Agenda espacial en directo</p>
                <h2 id="ple-page-title" class="ple-title">Próximos lanzamientos de cohetes</h2>
            </div>
            <div class="ple-summary" aria-label="Resumen">
                <div class="ple-stat">
                    <strong data-ple-launch-count>--</strong>
                    <span>lanzamientos cargados</span>
                </div>
                <div class="ple-stat">
                    <strong data-ple-next-countdown>--</strong>
                    <span>hasta el próximo lanzamiento</span>
                </div>
            </div>
        </section>

        <section class="ple-controls" aria-label="Controles">
            <p class="ple-time-notice">Todas las horas se muestran en tu hora local.</p>
            <p class="ple-status" data-ple-status role="status">Cargando próximos lanzamientos...</p>
        </section>

        <section class="ple-launch-grid" data-ple-launches-list aria-live="polite"></section>

        <footer class="ple-page-footer">
            <p class="ple-source">
                Datos de <a href="https://thespacedevs.com/llapi" target="_blank" rel="noreferrer">Launch Library 2</a>.
            </p>
        </footer>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('proximos_lanzamientos', 'ple_render_shortcode');
