<?php
/**
 * Plugin Name: SG APIs
 * Plugin Uri: https://divergentes.es/
 * Description: Plugin para conectar con la API de Llama a través de Groq
 * Author: Álvaro Torres
 * Author URI: https://siteground.es/
 * Version: 1.0
 * License: GPLv2 or later
 * Text Domain: sgapi
 *
 * @package WordPress
 */

/**
 * Admin section.
 */
function sgapi_menu_administracion() {
	add_menu_page(
		'Sg Api',
		'sgapi',
		'activate_plugins',
		'sgapi-view',
		'sgapi_view',
		'dashicons-randomize',
		'5'
	);
}
add_action( 'admin_menu', 'sgapi_menu_administracion' );

/**
 * Function view.
 */
function sgapi_view() {
	?>
	<a href="admin.php?page=sgapi-view&llamada=1">Llamada API</a>
	<?php
	if ( isset( $_GET['llamada'] ) ) { // phpcs:ignore
		$url     = 'https://api.groq.com/openai/v1/chat/completions';
		$api_key = 'gsk_wejgfwjefljwlfjnwelfnlwfenlwefnk';
		// Nunca pongáis la Api key de este modo, no es seguro.

		$args_posts     = array(
			'post_status' => 'publish',
		);
		$post_published = get_posts( $args_posts );

		foreach ( $post_published as $p ) {
			$text_translate = $p->post_content;
			$post_title     = $p->post_title;

			$request_args   = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'messages' => array(
							array(
								'role'    => 'system',
								'content' => 'Eres un experto en HTML y traductor de inglés y español',
							),
							array(
								'role'    => 'user',
								'content' => 'Mantén todas las etiquetas HTML y traduce al español el siguiente texto:' . $text_translate,
							),
						),
						'model'    => 'llama3-70b-8192',
					),
				),
			);

			$response = wp_remote_post( $url, $request_args );
			$decoded  = json_decode( wp_remote_retrieve_body( $response ) );

			$translated_text = $decoded->choices[0]->message->content;

			$args_new_post = array(
				'post_title'   => $post_title . ' (ES)',
				'post_content' => $translated_text,
			);
			$inserted      = wp_insert_post( $args_new_post );
		}
	}
}
