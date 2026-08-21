<?php
/**
 * Catálogo de destinos posibles de una misión y su representación visual.
 *
 * Cada sonda referencia una de estas claves en su campo "destination".
 * Para añadir un destino nuevo basta con añadir una entrada aquí; el
 * badge y la leyenda se generan automáticamente a partir de este catálogo.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'sol'          => array(
		'label' => __( 'Sol', 'sondas-espaciales-timeline' ),
		'code'  => '☉',
		'color' => '#f5b301',
	),
	'mercurio'     => array(
		'label' => __( 'Mercurio', 'sondas-espaciales-timeline' ),
		'code'  => '☿',
		'color' => '#9c9c9c',
	),
	'venus'        => array(
		'label' => __( 'Venus', 'sondas-espaciales-timeline' ),
		'code'  => '♀',
		'color' => '#d99a44',
	),
	'luna'         => array(
		'label' => __( 'Luna', 'sondas-espaciales-timeline' ),
		'code'  => '☾',
		'color' => '#9aa5b1',
	),
	'tierra'       => array(
		'label' => __( 'Órbita terrestre', 'sondas-espaciales-timeline' ),
		'code'  => '⊕',
		'color' => '#3ea6ff',
	),
	'marte'        => array(
		'label' => __( 'Marte', 'sondas-espaciales-timeline' ),
		'code'  => '♂',
		'color' => '#c1440e',
	),
	'jupiter'      => array(
		'label' => __( 'Júpiter', 'sondas-espaciales-timeline' ),
		'code'  => '♃',
		'color' => '#c8874a',
	),
	'saturno'      => array(
		'label' => __( 'Saturno', 'sondas-espaciales-timeline' ),
		'code'  => '♄',
		'color' => '#c9a227',
	),
	'urano'        => array(
		'label' => __( 'Urano', 'sondas-espaciales-timeline' ),
		'code'  => '♅',
		'color' => '#4fb8bf',
	),
	'neptuno'      => array(
		'label' => __( 'Neptuno', 'sondas-espaciales-timeline' ),
		'code'  => '♆',
		'color' => '#3b5ba5',
	),
	'asteroide'    => array(
		'label' => __( 'Asteroide', 'sondas-espaciales-timeline' ),
		'code'  => '◆',
		'color' => '#8a8a8a',
	),
	'cometa'       => array(
		'label' => __( 'Cometa', 'sondas-espaciales-timeline' ),
		'code'  => '☄',
		'color' => '#66c2ff',
	),
	'multiple'     => array(
		'label' => __( 'Varios destinos', 'sondas-espaciales-timeline' ),
		'code'  => '✳',
		'color' => '#8e6bd9',
	),
	'interestelar' => array(
		'label' => __( 'Espacio interestelar', 'sondas-espaciales-timeline' ),
		'code'  => '✦',
		'color' => '#3f3f6b',
	),
	'heliofisica'  => array(
		'label' => __( 'Heliofísica / observación general', 'sondas-espaciales-timeline' ),
		'code'  => '✺',
		'color' => '#b9962f',
	),
);
