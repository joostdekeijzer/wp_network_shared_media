<?php
/**
 * @package Netword_Shared_Media
 * @version 0.9.6
 */
define('WP_ADMIN', FALSE);
define('WP_LOAD_IMPORTERS', FALSE);

require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-admin/admin.php' );

if (!current_user_can('upload_files'))
	wp_die(__('You do not have permission to upload files.'));

// $blog_id is global var in WP

if( isset( $_POST['send'] ) ) {
	$nsm_blog_id = (int) $_GET['blog_id'];
	reset( $_POST['send'] );
	$nsm_send_id = (int) key( $_POST['send'] );
}

/* copied from media.php media_upload_form_handler */
if ( isset( $nsm_blog_id ) && isset( $nsm_send_id ) ) {
	switch_to_blog( $nsm_blog_id );
	if (!current_user_can('upload_files')) {
		$current_blog_name = get_bloginfo('name');
		restore_current_blog();
		wp_die(__('You do not have permission to upload files to site: ')  . $current_blog_name );
	}

	add_filter('media_send_to_editor', 'image_media_send_to_editor', 10, 3);

	$attachment = stripslashes_deep( $_POST['attachments'][$nsm_send_id] );

	$html = $attachment['post_title'];
	if ( !empty($attachment['url']) ) {
		$rel = '';
		if ( strpos($attachment['url'], 'attachment_id') || get_attachment_link($nsm_send_id) == $attachment['url'] )
			$rel = " rel='attachment wp-att-" . esc_attr($nsm_send_id) . "'";
		$html = "<a href='{$attachment['url']}'$rel>$html</a>";
	}

	$attachment_id = $nsm_send_id;

	/** copied from media.php image_media_send_to_editor **/
	$url = $attachment['url'];
	$align = !empty($attachment['align']) ? $attachment['align'] : 'none';
	$size = !empty($attachment['image-size']) ? $attachment['image-size'] : 'medium';
	$alt = !empty($attachment['image_alt']) ? $attachment['image_alt'] : '';
	$rel = ( $url == get_attachment_link($attachment_id) );

	$html = get_image_send_to_editor($attachment_id, $attachment['post_excerpt'], $attachment['post_title'], $align, $url, $rel, $size, $alt);

	if( isset($_POST['chromeless']) && $_POST['chromeless'] ) {
		// WP3.5+ media browser is identified by the 'chromeless' parameter
		exit($html);
	} else {
		return media_send_to_editor($html);
	}
}
