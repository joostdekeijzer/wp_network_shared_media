function NsmSetAsThumbnail(id, blog_id, nonce){
	var $link = jQuery('a#wp-post-thumbnail-' + id);

	$link.text( setPostThumbnailL10n.saving );
	jQuery.post(ajaxurl, {
		action:"nsm-set-post-thumbnail", post_id: post_id, thumbnail_id: id, blog_id: blog_id, _ajax_nonce: nonce, cookie: encodeURIComponent(document.cookie)
	}, function(str){
		var win = window.dialogArguments || opener || parent || top;
		$link.text( setPostThumbnailL10n.setThumbnail );
		if ( str == '0' ) {
			alert( setPostThumbnailL10n.error );
		} else {
			jQuery('a.wp-post-thumbnail').show();
			$link.text( setPostThumbnailL10n.done );
			$link.fadeOut( 2000 );
			win.WPSetThumbnailID(id);
			win.WPSetThumbnailHTML(str);
		}
	}
	);
}
