<?php

namespace _s;

/**
 * Register post type
 */
function _s_theme_footer() {
	register_post_type(
		'theme_footer',
		[
			'labels'       => [
				'name'          => __( 'Footers' ),
				'singular_name' => __( 'Footer' )
			],
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'themes.php',
			'supports'     => array( 'title', 'editor' ),
		]
	);
}

add_action( 'init', '_s_theme_footer' );

/**
 * Get footer for a template
 *
 * @param null $template_slug Some template slug (default: current).
 *
 * @return \WP_Post|null
 */
function _s_get_template_footer( $template_slug = null ) {
	if ( null == $template_slug ) {
		$template_slug = get_page_template_slug();
	}

	$transient_name = 'theme_footer_' . md5( $template_slug ); // max 45 chars
	$footer         = get_site_transient( $transient_name );

	if ( $footer ) {
		return $footer;
	}

	$footers = get_posts(
		[
			'post_type'  => 'theme_footer',
			'meta_key'   => '_page_template',
			'meta_value' => $template_slug
		]
	);

	if ( ! $footers ) {
		$footers = get_posts(
			[
				'post_type'  => 'theme_footer',
				'meta_key'   => '_page_template',
				'meta_value' => '_default'
			]
		);
	}

	set_site_transient( $transient_name, $footers );

	if ( ! $footers ) {
		return null;
	}

	return current( $footers );
}

/**
 * Add footer entry to the "Customize" part of the admin bar.
 *
 * If a footer for the current template exists,
 * then it will be an edit link.
 * Otherwise this will lead to a new footer for the current template.
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function _s_admin_bar_menu_theme_footer( $wp_admin_bar ) {

	$footer = _s_get_template_footer();

	$query = http_build_query(
		array(
			'post_type' => 'theme_footer',
			'template'  => get_page_template_slug()
		)
	);

	$href = admin_url( 'post-new.php?' . $query );
	if ( $footer ) {
		$href = get_edit_post_link( $footer->ID, 'theme_footer' );
	}

	$wp_admin_bar->add_node(
		array(
			'parent' => 'customize',
			'id'     => 'theme_footer',
			'title'  => __( 'Footer', '_s' ),
			'href'   => $href,
			'meta'   => array(),
		)
	);
}

add_action( 'admin_bar_menu', '_s_admin_bar_menu_theme_footer' );

/**
 * Add a template column to the footer list in the backend.
 *
 * @param string[] $columns List of columns.
 *
 * @return array
 */
function _s_edit_theme_footer_columns( $columns ) {
	$columns['template'] = __( 'Template', '_s' );

	return $columns;
}

add_filter( 'manage_edit-theme_footer_columns', '_s_edit_theme_footer_columns' );

/**
 * Print target template in footer list in the backend.
 *
 * @param string $column  Name of the current column.
 * @param int    $post_id ID of the listed footer.
 */
function _s_manage_theme_footer_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'template':
			$page_template = get_post_meta( $post_id, '_page_template', true );

			$key = array_search( $page_template, get_page_templates() );

			echo $key;
			break;
	}
}

add_action( 'manage_theme_footer_posts_custom_column', '_s_manage_theme_footer_columns', 10, 2 );

/**
 * Add meta-boxes to the theme footer.
 */
function _s_add_meta_boxes_theme_footer() {
	add_meta_box(
		'theme_footer_template_chooser',
		__( 'For which template is this footer?' ),
		function ( $post ) {
			include _s_get_theme_directory( 'inc/cpt-footer-meta.phtml' );
		},
		'theme_footer'
	);
}

add_action( 'add_meta_boxes', '_s_add_meta_boxes_theme_footer' );

/**
 *
 * @param $post_id
 */
function _s_save_post_theme_footer( $post_id ) {

	// Check if our nonce is set.
	if ( ! isset( $_POST['cpt_footer_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce(
		$_POST['cpt_footer_meta_box_nonce'],
		'cpt_footer_meta_box'
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
		$transient_name = 'theme_footer_' . md5( $previous );
		delete_site_transient( $transient_name );
	}

	// Update the meta field in the database.
	update_post_meta(
		$post_id,
		'_page_template',
		$_POST['_page_template']
	);
}


add_action( 'save_post_theme_footer', '_s_save_post_theme_footer' );

/**
 * Remove "New Footer" from admin-bar.
 *
 * Footer are controlled via theme options / customize.
 *
 */
function _s_remove_admin_bar_new_footer() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'new-theme_footer' );
}

add_action( 'wp_before_admin_bar_render', '_s_remove_admin_bar_new_footer' );