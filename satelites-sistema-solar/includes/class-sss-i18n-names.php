<?php
/**
 * Traducciones al español de los nombres de planetas y de los satélites
 * más conocidos.
 *
 * La API pública no ofrece nombres en español, así que se traducen "a
 * mano" aquí. Los 8 planetas están completos; de los satélites solo se
 * cubren los más conocidos (los que tienen un nombre propio asentado en
 * español). El resto —principalmente satélites irregulares pequeños, la
 * mayoría sin nombre oficial y solo con una designación provisional como
 * "S/2003 J 2", igual en cualquier idioma— se muestra con su nombre
 * internacional (en inglés) tal cual lo da la API.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_I18n_Names {

	/**
	 * Nombre en inglés (tal y como lo da la API) => nombre en español.
	 *
	 * @var array
	 */
	private static $planets = array(
		'Mercury' => 'Mercurio',
		'Venus'   => 'Venus',
		'Earth'   => 'Tierra',
		'Mars'    => 'Marte',
		'Jupiter' => 'Júpiter',
		'Saturn'  => 'Saturno',
		'Uranus'  => 'Urano',
		'Neptune' => 'Neptuno',
	);

	/**
	 * Nombre en inglés (tal y como lo da la API) => nombre en español,
	 * solo para los satélites más conocidos.
	 *
	 * @var array
	 */
	private static $moons = array(
		// Tierra.
		'Moon'      => 'Luna',
		// Marte.
		'Phobos'    => 'Fobos',
		'Deimos'    => 'Deimos',
		// Júpiter (lunas galileanas y otras conocidas).
		'Io'        => 'Ío',
		'Europa'    => 'Europa',
		'Ganymede'  => 'Ganímedes',
		'Callisto'  => 'Calisto',
		'Amalthea'  => 'Amaltea',
		'Himalia'   => 'Himalia',
		// Saturno.
		'Titan'     => 'Titán',
		'Enceladus' => 'Encélado',
		'Mimas'     => 'Mimas',
		'Tethys'    => 'Tetis',
		'Dione'     => 'Dione',
		'Rhea'      => 'Rea',
		'Iapetus'   => 'Jápeto',
		'Hyperion'  => 'Hiperión',
		'Phoebe'    => 'Febe',
		// Urano.
		'Miranda'   => 'Miranda',
		'Ariel'     => 'Ariel',
		'Umbriel'   => 'Umbriel',
		'Titania'   => 'Titania',
		'Oberon'    => 'Oberón',
		// Neptuno.
		'Triton'    => 'Tritón',
		'Nereid'    => 'Nereida',
		'Proteus'   => 'Proteo',
	);

	/**
	 * Traduce el nombre de un planeta al español si está en el diccionario.
	 *
	 * @param string $name Nombre en inglés.
	 * @return string
	 */
	public static function translate_planet( $name ) {
		return self::$planets[ $name ] ?? $name;
	}

	/**
	 * Traduce el nombre de un satélite al español si está en el diccionario.
	 * Si no se conoce (la inmensa mayoría de satélites pequeños o sin
	 * nombre oficial), se devuelve el nombre tal cual.
	 *
	 * @param string $name Nombre en inglés (o designación provisional).
	 * @return string
	 */
	public static function translate_moon( $name ) {
		return self::$moons[ $name ] ?? $name;
	}
}
