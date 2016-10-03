<?php

namespace _s;

/**
 * Register post type
 */
function _s_theme_header() {
	register_post_type(
		'theme_header',
		[
			'labels'       => [
				'name'          => __( 'Headers' ),
				'singular_name' => __( 'Header' )
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'themes.php',
			'supports'     => array( 'title', 'editor' ),
		]
	);
}

add_action( 'init', '_s_theme_header' );

/**
 * Get header for a template
 *
 * @param null $template_slug Some template slug (default: current).
 *
 * @return \WP_Post|null
 */
function _s_get_template_header( $template_slug = null ) {
	if ( null == $template_slug ) {
		$template_slug = get_page_template_slug();
	}

	$transient_name = 'theme_header_' . md5( $template_slug ); // max 45 chars
	$header         = get_site_transient( $transient_name );

	if ( $header ) {
		return $header;
	}

	$headers = get_posts(
		[
			'post_type'  => 'theme_header',
			'meta_key'   => '_page_template',
			'meta_value' => $template_slug
		]
	);

	if ( ! $headers ) {
		$headers = get_posts(
			[
				'post_type'  => 'theme_header',
				'meta_key'   => '_page_template',
				'meta_value' => '_default'
			]
		);
	}

	if ( ! $headers ) {
		return null;
	}

	$header = current( $headers );
	set_site_transient( $transient_name, $header );

	return $header;
}

/**
 * Add header entry to the "Customize" part of the admin bar.
 *
 * If a header for the current template exists,
 * then it will be an edit link.
 * Otherwise this will lead to a new header for the current template.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function _s_admin_bar_menu_theme_header( $wp_admin_bar ) {

	$header = _s_get_template_header();

	$query = http_build_query(
		array(
			'post_type' => 'theme_header',
			'template'  => get_page_template_slug()
		)
	);

	$href = admin_url( 'post-new.php?' . $query );
	if ( $header ) {
		$href = get_edit_post_link( $header->ID, 'theme_header' );
	}

	$wp_admin_bar->add_node(
		array(
			'parent' => 'customize',
			'id'     => 'theme_header',
			'title'  => __( 'Header', '_s' ),
			'href'   => $href,
			'meta'   => array(),
		)
	);
}

add_action( 'admin_bar_menu', '_s_admin_bar_menu_theme_header' );

/**
 * Add a template column to the header list in the backend.
 *
 * @param string[] $columns List of columns.
 *
 * @return array
 */
function _s_edit_theme_header_columns( $columns ) {
	$columns['template'] = __( 'Template', '_s' );

	return $columns;
}

add_filter( 'manage_edit-theme_header_columns', '_s_edit_theme_header_columns' );

/**
 * Print target template in header list in the backend.
 *
 * @param string $column  Name of the current column.
 * @param int    $post_id ID of the listed header.
 */
function _s_manage_theme_header_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'template':
			$page_template = get_post_meta( $post_id, '_page_template', true );

			$key = array_search( $page_template, get_page_templates() );

			echo $key;
			break;
	}
}

add_action( 'manage_theme_header_posts_custom_column', '_s_manage_theme_header_columns', 10, 2 );

/**
 * Add meta-boxes to the theme header.
 */
function _s_add_meta_boxes_theme_header() {
	add_meta_box(
		'theme_header_template_chooser',
		__( 'For which template is this header?' ),
		function ( $post ) {
			include _s_get_theme_directory( 'inc/cpt-header-meta.phtml' );
		},
		'theme_header'
	);
}

add_action( 'add_meta_boxes', '_s_add_meta_boxes_theme_header' );

/**
 *
 * @param $post_id
 */
function _s_save_post_theme_header( $post_id ) {

	// Check if our nonce is set.
	if ( ! isset( $_POST['cpt_header_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce(
		$_POST['cpt_header_meta_box_nonce'],
		'cpt_header_meta_box'
	)
	) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( ! current_user_can( 'edit_posts', $post_id ) ) {
		return;
	}

	/* OK, it's safe for us to save the data now. */

	// Make sure that it is set.
	if ( ! isset( $_POST['_page_template'] ) ) {
		return;
	}

	$previous = get_post_meta( $post_id, '_page_template', true );
	if ( $previous ) {
		$transient_name = 'theme_header_' . md5( $previous );
		delete_site_transient( $transient_name );
	}

	// Update the meta field in the database.
	update_post_meta(
		$post_id,
		'_page_template',
		$_POST['_page_template']
	);
}


add_action( 'save_post_theme_header', '_s_save_post_theme_header' );

/**
 * Remove "New Header" from admin-bar.
 *
 * Header are controlled via theme options / customize.
 *
 */
function _s_remove_admin_bar_new_header() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'new-theme_header' );
}

add_action( 'wp_before_admin_bar_render', '_s_remove_admin_bar_new_header' );