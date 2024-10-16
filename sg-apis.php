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
// Los comentarios de más arriba son necesarios para que se reconozca el plugin. Solo con eso, ya aparecerá en la sección plugins del panel de control.

/**
 * Admin section.
 */
function sgapi_menu_administracion() {
	// Esta función sirve para que aparezca el plugin en el panel de control de WordPress
	add_menu_page(
		'Sg Api',
		'sgapi',
		'activate_plugins',
		'sgapi-view',
		'sgapi_view', // Aquí colocamos el nombre de la función de más abajo
		'dashicons-randomize',
		'5'
	);
}
add_action( 'admin_menu', 'sgapi_menu_administracion' );

/**
 * Function view.
 */
function sgapi_view() {
	// Esta función sirve para la sección del plugin
	?>
	<!-- Este es el anchor que se verá para hacer las traducciones -->
	<a href="admin.php?page=sgapi-view&llamada=1">Llamada API</a>
	<?php
	if ( isset( $_GET['llamada'] ) ) { // Si en la URL aparece la variable llamada...
		$url     = 'https://api.groq.com/openai/v1/chat/completions';
		$api_key = 'gsk_wejgfwjefljwlfjnwelfnlwfenlwefnk';
		// Nunca pongáis la Api key de este modo, no es seguro. Lo he puesto así para que sea más visual.

		$args_posts     = array(
			'post_status' => 'publish',
		);
		$post_published = get_posts( $args_posts ); // Obetener todos los posts publicados.

		foreach ( $post_published as $p ) { // Aquí recorremos cada post publicado y lo nombramos $p.
			$text_translate = $p->post_content; // Obtenemos contenido del post.
			$post_title     = $p->post_title; // Obtenemos título del post.

			$request_args   = array( // $request_args son los datos necesarios para realizar la llamada concreta.
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key, // Autorización mediante la clave API obtenida.
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( // Codificamos a formato JSON para que la API lo interprete.
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
						'model'    => 'llama3-70b-8192', // Modelo de IA al que estamos llamando.
					),
				),
			);

			$response = wp_remote_post( $url, $request_args ); // Hacemos la llamada a la API con los argumentos y la URL
			$decoded  = json_decode( wp_remote_retrieve_body( $response ) ); // Decodificamos el fromato JSON para trabajar con los datos.

			$translated_text = $decoded->choices[0]->message->content; // Obtenemos el contenido traducido.

			$args_new_post = array( //Argumentos necesarios para crear el nuevo post
				'post_title'   => $post_title . ' (ES)',
				'post_content' => $translated_text,
				// por defecto aparecen los artículos en borrador.
				// Si queremos podemos añadir 'post_status' => 'publish' para que aparezcan publicados
			);
			$inserted      = wp_insert_post( $args_new_post ); // Creamos el post.
			// La variable $inserted tendrá un número entero con el ID del artículo, lo podremos usar para verificar que todo ha ido bien.
		}
	}
}
// Hay que tener en cuenta que existe un límite de ejecución de scripts, si tenemos muchos posts solo traducirá unos pocos.
// Dependiendo del servidor, podremos usar set_time_limit, pero no es lo más recomendable.