<?php
define('WP_ADMIN', FALSE);
define('WP_LOAD_IMPORTERS', FALSE);
require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-admin/admin.php' );

// $blog_id is global var in WP

if( isset( $_POST['send'] ) ) {
	$nsm_blog_id = (int) reset( array_keys( $_POST['send'] ) );
	$nsm_send_id = (int) reset( array_keys( $_POST['send'][$nsm_blog_id] ) );
}

/* copied from media.php media_upload_form_handler */
if ( isset( $nsm_blog_id ) && isset( $nsm_send_id ) ) {
	switch_to_blog( $nsm_blog_id );
	add_filter('media_send_to_editor', 'image_media_send_to_editor', 10, 3);

	$attachment = stripslashes_deep( $_POST['attachments'][$nsm_blog_id][$nsm_send_id] );

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

	return media_send_to_editor($html);
}

/** maybe a different way ? **/
exit;

if( isset( $_POST['send'] ) ) {
	// first array_key is blog_id
	$media_blog_id = reset( array_keys( $_POST['send'] ) );
	$_POST['send'] = $_POST['send'][$media_blog_id];
	$_POST['attachments'] = $_POST['attachments'][$media_blog_id];
	switch_to_blog( $media_blog_id );

	/** copied from media-upload.php **/

	// upload type: image, video, file, ..?
	if ( isset($_GET['type']) )
		$type = strval($_GET['type']);
	else
		$type = apply_filters('media_upload_default_type', 'file');

	// tab: gallery, library, or type-specific
	if ( isset($_GET['tab']) )
		$tab = strval($_GET['tab']);
	else
		$tab = apply_filters('media_upload_default_tab', 'type');

	$body_id = 'media-upload';

	// let the action code decide how to handle the request
	if ( $tab == 'type' || $tab == 'type_url' || ! array_key_exists( $tab , media_upload_tabs() ) ) {
		do_action("media_upload_$type");
	} else {
		do_action("media_upload_$tab");
	}
}
