<?php
/**
 * @package Netword_Shared_Media
 * @version 1.0.beta
 *
 * Voor WP3.5 Backbone systeem:
 * Zie /wp-includes/js/media*.js files en ook de volgende ur's:
 * http://wordpress.stackexchange.com/questions/78198/3-5-media-manager-add-css-js-to-new-tab-iframe-content/
 * en http://wordpress.stackexchange.com/questions/76980/add-a-menu-item-to-wordpress-3-5-media-manager/
 * en http://wordpress.stackexchange.com/questions/86884/3-5-media-manager-callout-in-metaboxes
 * misschien algemeen: http://wordpress.stackexchange.com/questions/tagged/media-library
 *
 * ook: http://shibashake.com/wordpress-theme/how-to-add-the-wordpress-3-5-media-manager-interface
 * en http://shibashake.com/wordpress-theme/how-to-add-the-wordpress-3-5-media-manager-interface-part-2
 *
 * verder ook http://scribu.net/wordpress/putting-some-backbone-into-posts-2-posts.html
 */
/*
Plugin Name: Network Shared Media
Plugin URI: http://wordpress.org/extend/plugins/network-shared-media/
Description: This plugin adds a new tab to the "Add Media" window, allowing you to access media in other sites. Based on an idea of Aaron Eaton
Author: Joost de Keijzer
Author URI: http://dekeijzer.org/
Version: 1.0.beta
Licence: GPLv2 or later
*/

// Add filter that inserts our new tab
function network_shared_media_menu($tabs) {
	$newtab = array('shared_media' => __('Network Shared Media', 'networksharedmedia'));
	return array_merge($tabs, $newtab);
}

// Load media_nsm_process() into the existing iframe
function network_shared_media_upload_shared_media() {
	$nsm = new network_shared_media();
	return wp_iframe(array( $nsm, 'media_upload_shared_media' ), array());
}

function network_shared_media_view_settings( $settings ) {
	return $settings;
}

function network_shared_media_view_strings( $strings ) {
	$strings['nsmTitle'] = __('Network Shared Media', 'networksharedmedia');

	return $strings;
}

function network_shared_media_print_templates() {
	echo <<<EOH
	<script type="text/javascript">
		jQuery(window).on('load', function() {
			// models
			(function(\$) {
				var media   = window.wp.media,
				Attachment  = media.model.Attachment,
				Attachments = media.model.Attachments,
				Query       = media.model.Query,
				l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

				var nsmAttachment = media.model.nsmAttachment = media.model.Attachment.extend({
					sync: function() { console.log( 'nsmAttachment sync' ); media.model.Attachment.prototype.sync.apply( this, arguments ); },
					fetch: function() { console.log( 'nsmAttachment fetch' ); media.model.Attachment.prototype.fetch.apply( this, arguments ); }
				});

				var nsmAttachments = media.model.nsmAttachments = media.model.Attachments.extend({
					model: nsmAttachment,
					initialize: function( models, options ) {
						media.model.Attachments.prototype.initialize.apply( this, arguments );
						this.props.on( 'change:blog', this._changeBlog, this );
						this.props.set( _.extend( { blog: 2 }, this.props.attributes ) );
					},

					_changeBlog: function( model, blog ) { console.log( 'change blog for mode: ' + model + ' to blog: ' + blog ); },

					sync: function() { console.log( 'nsmAttachments sync' ); media.model.Attachments.prototype.sync.apply( this, arguments ); },
					fetch: function() { console.log( 'nsmAttachments fetch' ); media.model.Attachments.prototype.fetch.apply( this, arguments ); },
					parse: function( resp, xhr ) {
console.log( 'nsmAttachments parse' );
						if ( ! _.isArray( resp ) ) {
							resp = [resp];
						}

						return _.map( resp, function( attrs ) {
							var id, attachment, newAttributes;

							if ( attrs instanceof Backbone.Model ) {
								id = attrs.get( 'id' );
								attrs = attrs.attributes;
							} else {
								id = attrs.id;
							}

							attachment = nsmAttachment.get( id );
							newAttributes = attachment.parse( attrs, xhr );

							if ( ! _.isEqual( attachment.attributes, newAttributes ) ) {
								attachment.set( newAttributes );
							}

							return attachment;
						});
					}
				});
			}(jQuery));

			// views & controllers
			(function($) {
				var media   = window.wp.media,
				Attachment  = media.model.Attachment,
				nsmAttachment  = media.model.nsmAttachment,
				Attachments = media.model.Attachments,
				nsmAttachments = media.model.nsmAttachments,
				Query       = media.model.Query,
				l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

				media.view.nsmAttachmentsBrowser = media.view.AttachmentsBrowser.extend({
					createAttachments: function() {
						//this.removeContent();

						this.attachments = new media.view.Attachments({
							controller: this.controller,
							collection: this.collection,
							selection:  this.options.selection,
							model:      this.model,
							sortable:   this.options.sortable,

							// The single `Attachment` view to be used in the `Attachments` view.
							AttachmentView: this.options.AttachmentView
						});

						this.views.add( this.attachments );
					}
				});

				media.view.nsmAttachment = media.view.Attachment.extend({
					className: 'attachment nsm-attachment',

					initialize: function() {
	console.log('nsmAttachment initialize');
						media.view.Attachment.prototype.initialize.apply( this, arguments );
					},

					/**
					 * Only the read method is allowed
					 */
					sync: function( method, model, options ) {
	console.log('nsmAttachment sync');
						// If the attachment does not yet have an `id`, return an instantly
						// rejected promise. Otherwise, all of our requests will fail.
						if ( _.isUndefined( this.id ) ) {
							return $.Deferred().rejectWith( this ).promise();
						}

						// Overload the `read` request so Attachment.fetch() functions correctly.
						if ( 'read' === method ) {
							options = options || {};
							options.context = this;
							options.data = _.extend( options.data || {}, {
								action: 'get-attachment',
								id: this.id
							});
							return media.ajax( options );
						}
						return $.Deferred().rejectWith( this ).promise();
					}
				});

				nsmView = {
					bindHandlers: media.view.MediaFrame.Select.prototype.bindHandlers,
					createStates: function( selectView ) {
						var options = selectView.options;

						selectView.states.add([
							new media.controller.Library({
								id:        'nsm-library',
								library:   new nsmAttachments( null, {
									props: options.library
								}),
								multiple:  options.multiple,
								title:     options.title,
								priority:  60
							})
						]);
					},

					browseRouter: function( view ) {
						view.set({
							nsm_browse: {
								text:     l10n.nsmTitle,
								priority: 60
							}
						});
					},
					createView: function( content ) {
						console.log('create NSM');

						//if ( ! this.get('nsmLibrary') ) {
						//	this.set( 'nsmLibrary', media.query() );
						//}

						var state = this.state('nsm-library');

						this.\$el.removeClass('hide-toolbar');

						content.view = new media.view.nsmAttachmentsBrowser({
							controller: this,
							collection: state.get('library'),
							selection:  state.get('selection'),
							model:      state,
							sortable:   state.get('sortable'),
							search:     state.get('searchable'),
							filters:    state.get('filterable'),
							display:    state.get('displaySettings'),
							dragInfo:   state.get('dragInfo'),

							AttachmentView: media.view.nsmAttachment
						});
					},
					renderView: function() { console.log('render NSM'); },
					activateView: function() { console.log('activate NSM'); },
					deactivateView: function() { console.log('deactivate NSM'); },
				};
				_.extend(nsmView, media.view.MediaFrame.prototype);

				media.view.MediaFrame.Select.prototype.bindHandlers = function() {
					nsmView.createStates( this );
					nsmView.bindHandlers.call( this );

					this.on( 'router:render:browse', nsmView.browseRouter, this );

					this.on( 'content:create:nsm_browse', nsmView.createView, this );
					this.on( 'content:render:nsm_browse', nsmView.renderView, this );
					this.on( 'content:activate:nsm_browse', nsmView.activateView, this );
					this.on( 'content:deactivate:nsm_browse', nsmView.deactivateView, this );
				};
			}(jQuery));
		});
	</script>
EOH;
}

function network_shared_media_init() {
	if ( current_user_can('upload_files') ) {
		load_plugin_textdomain( 'networksharedmedia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_filter('media_upload_tabs', 'network_shared_media_menu');
		add_action('media_upload_shared_media', 'network_shared_media_upload_shared_media');

		// WP >= 3.5
		add_filter('media_view_settings', 'network_shared_media_view_settings');
		add_filter('media_view_strings', 'network_shared_media_view_strings');
	}
}
add_action( 'init', 'network_shared_media_init' );

function network_shared_media_admin_init() {
	add_action('wp_ajax_query-attachments', 'nsm_wp_ajax_query_attachments_0', 0);
}
function nsm_wp_ajax_query_attachments_0() {
	$query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
	if(isset($query['blog_id']) && $query['blog_id'] !== $GLOBALS['blog_id']) {
		switch_to_blog( $query['blog_id'] );
		add_action('wp_ajax_query-attachments', 'nsm_wp_ajax_query_attachments_2', 2);
	}
}
function nsm_wp_ajax_query_attachments_2() {
	restore_current_blog();
}
add_action( 'admin_init', 'network_shared_media_admin_init');

class network_shared_media {
	var $blogs = array();
	var $media_items = '';
	var $current_blog_id;

	function __construct() {
		$this->current_blog_id = $GLOBALS['blog_id'];

		/* copied from depricated get_blog_list */
		global $wpdb;
		$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );

		$this->blogs = array();
		$sort_array = array();

foreach ( (array) $blogs as $details ) {
	switch_to_blog( $details['blog_id'] );
	restore_current_blog();
	//switch_to_blog( $details['blog_id'] );
	$sites[] = get_blog_option( $details['blog_id'] , 'blogname' );
	//restore_current_blog();
}
error_log( print_r( $sites, true ) );

		foreach ( (array) $blogs as $details ) {
			if ( $details['blog_id'] == $this->current_blog_id || false || !current_user_can_for_blog( (int) $details['blog_id'], 'upload_files') ) continue;

			$details['name'] = get_blog_option( (int) $details['blog_id'], 'blogname' );
			$this->blogs[] = $details;
			$sort_array[] = strtolower ( $details['name'] );
		}
		array_multisort( $sort_array, SORT_ASC, $this->blogs );

	}

	function get_media_items( $post_id, $errors ) {
		$output = get_media_items( $post_id, $errors );

		// remove edit button
		$output = preg_replace( "%<p><input type='button' id='imgedit-open-btn.+?class='imgedit-wait-spin'[^>]+></p>%s", '', $output );

		// remove delete link
		$output = preg_replace( "%<a href='#' class='del-link' onclick=.+?</a>%s", '', $output );
		$output = preg_replace( "%<div id='del_attachment_.+?</div>%s", '', $output );

		return $output;
	}

	/**
	 * {@internal Missing Short Description}}
	 *
	 * @since 2.5.0
	 *
	 * @param unknown_type $errors
	 */
	function media_upload_shared_media($errors) {
		global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types, $blog_id;
	
		media_upload_header();

		if( count( $this->blogs ) == 0 ) {
			echo '<form><h3 class="media-title">' . __("You don't have access to any other sites media...", 'networksharedmedia' ) . '</h3></form>';
			return;
		}

		// set the first part of the form action url now, to the current active site, to prevent X-Frame-Options problems
		$form_action_url = plugins_url( 'media-upload.php', __FILE__ );

		$nsm_blog_id = null;
		if( !array_key_exists( 'blog_id', $_GET ) ) $_GET['blog_id'] = null;

		foreach( $this->blogs as $blog ) {
			if( $_GET['blog_id'] == $blog['blog_id'] ) {
				$nsm_blog_id = $blog['blog_id'];
				break;
			}
		}

		if( null == $nsm_blog_id )
			$nsm_blog_id = $this->blogs[0]['blog_id'];

		switch_to_blog( $nsm_blog_id );
?>

<?php
		$post_id = intval($_REQUEST['post_id']);

		// fix to make get_media_item add "Insert" button
		unset($_GET['post_id']);

		$form_action_url .= "?type=$type&tab=library&post_id=$post_id&blog_id=$blog_id";
		$form_action_url = apply_filters('media_upload_form_url', $form_action_url, $type);

		$form_class = 'media-upload-form validate';
	
		$_GET['paged'] = isset( $_GET['paged'] ) ? intval($_GET['paged']) : 0;
		if ( $_GET['paged'] < 1 )
			$_GET['paged'] = 1;
		$start = ( $_GET['paged'] - 1 ) * 10;
		if ( $start < 1 )
			$start = 0;
		add_filter( 'post_limits', create_function( '$a', "return 'LIMIT $start, 10';" ) );
	
		list($post_mime_types, $avail_post_mime_types) = wp_edit_attachments_query();
?>
	
	<form id="filter" action="" method="get">
	<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>" />
	<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />
	<input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>" />
	<input type="hidden" name="blog_id" value="<?php echo (int) $blog_id; ?>" />
	<input type="hidden" name="post_mime_type" value="<?php echo isset( $_GET['post_mime_type'] ) ? esc_attr( $_GET['post_mime_type'] ) : ''; ?>" />

	<style type="text/css">
		#media-upload #filter .nsm-site-select { float: none; width: 100%; margin: 0 1em 2em 1em; white-space: normal; }
	</style>

	<ul class="subsubsub nsm-site-select">
	<?php

	if( count( $this->blogs ) == 1 ) {
		$blog = reset( $this->blogs );
		echo "<li>" . __('Selected site:', 'networksharedmedia' ) . "</li>" . "<li><a href='" . esc_url(add_query_arg(array('blog_id'=>$blog['blog_id'], 'paged'=>false))) . "' class='current'>" . $blog['name'] . '</a>' . '</li>';
	} else {
		$all_blog_names = array();
		foreach ( $this->blogs as $blog ) {
			$all_blog_names[] = $blog['name'];
		}
		if( strlen( __('Select site:', 'networksharedmedia' ) . ' ' . implode( ' | ', $all_blog_names ) ) < 71 ) {
			$blog_links = array();
			foreach ( $this->blogs as $blog ) {
				$class = '';
				
				if ( $blog['blog_id'] == $blog_id )
					$class = ' class="current"';
			
				$blog_links[] = "<li><a href='" . esc_url(add_query_arg(array('blog_id'=>$blog['blog_id'], 'paged'=>false))) . "'$class>" . $blog['name'] . '</a>';
			}
			echo "<li>" . __('Select site:', 'networksharedmedia' ) . " </li>" . implode(' | </li>', $blog_links ) . '</li>';
			unset($blog_links);
		} else {
			$blog_options = array();
			foreach ( $this->blogs as $blog ) {
				$selected = '';
				
				if ( $blog['blog_id'] == $blog_id )
					$selected = ' selected="selected"';
			
				$blog_options[] = "<option value='{$blog['blog_id']}'{$selected}>" . $blog['name'] . '</option>';
			}
			echo "<li>" . __('Select site:', 'networksharedmedia' ) . "</li><li> <select name='blog_id'>" . implode('', $blog_options ) . '</select></li><li> ' . get_submit_button( __( 'Select &#187;', 'networksharedmedia' ), 'secondary', 'nsm-post-query-submit', false ) . '</li>';
			unset($blog_options);
		}
		unset($all_blog_names);
	}
	?>
	</ul>

	<p id="media-search" class="search-box">
		<label class="screen-reader-text" for="media-search-input"><?php _e('Search Media');?>:</label>
		<input type="text" id="media-search-input" name="s" value="<?php the_search_query(); ?>" />
		<?php submit_button( __( 'Search Media' ), 'button', '', false ); ?>
	</p>
	
	<ul class="subsubsub">
	<?php
	$type_links = array();
	$_num_posts = (array) wp_count_attachments();
	$matches = wp_match_mime_types(array_keys($post_mime_types), array_keys($_num_posts));
	foreach ( $matches as $_type => $reals )
		foreach ( $reals as $real )
			if ( isset($num_posts[$_type]) )
				$num_posts[$_type] += $_num_posts[$real];
			else
				$num_posts[$_type] = $_num_posts[$real];
	// If available type specified by media button clicked, filter by that type
	if ( empty($_GET['post_mime_type']) && !empty($num_posts[$type]) ) {
		$_GET['post_mime_type'] = $type;
		list($post_mime_types, $avail_post_mime_types) = wp_edit_attachments_query();
	}
	if ( empty($_GET['post_mime_type']) || $_GET['post_mime_type'] == 'all' )
		$class = ' class="current"';
	else
		$class = '';
	$type_links[] = "<li><a href='" . esc_url(add_query_arg(array('post_mime_type'=>'all', 'paged'=>false, 'm'=>false))) . "'$class>".__('All Types')."</a>";
	foreach ( $post_mime_types as $mime_type => $label ) {
		$class = '';
	
		if ( !wp_match_mime_types($mime_type, $avail_post_mime_types) )
			continue;
	
		if ( isset($_GET['post_mime_type']) && wp_match_mime_types($mime_type, $_GET['post_mime_type']) )
			$class = ' class="current"';
	
		$type_links[] = "<li><a href='" . esc_url(add_query_arg(array('post_mime_type'=>$mime_type, 'paged'=>false))) . "'$class>" . sprintf( translate_nooped_plural( $label[2], $num_posts[$mime_type] ), "<span id='$mime_type-counter'>" . number_format_i18n( $num_posts[$mime_type] ) . '</span>') . '</a>';
	}
	echo implode(' | </li>', apply_filters( 'media_upload_mime_type_links', $type_links ) ) . '</li>';
	unset($type_links);
	?>
	</ul>
	
	<div class="tablenav">
	
	<?php
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => ceil($wp_query->found_posts / 10),
		'current' => $_GET['paged']
	));
	
	if ( $page_links )
		echo "<div class='tablenav-pages'>$page_links</div>";
	?>
	
	<div class="alignleft actions">
	<?php
	
	$arc_query = "SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts WHERE post_type = 'attachment' ORDER BY post_date DESC";
	
	$arc_result = $wpdb->get_results( $arc_query );
	
	$month_count = count($arc_result);
	
	if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) { ?>
	<select name='m'>
	<option<?php selected( @$_GET['m'], 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
	<?php
	foreach ($arc_result as $arc_row) {
		if ( $arc_row->yyear == 0 )
			continue;
		$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );
	
		if ( isset($_GET['m']) && ( $arc_row->yyear . $arc_row->mmonth == $_GET['m'] ) )
			$default = ' selected="selected"';
		else
			$default = '';
	
		echo "<option$default value='" . esc_attr( $arc_row->yyear . $arc_row->mmonth ) . "'>";
		echo esc_html( $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear" );
		echo "</option>\n";
	}
	?>
	</select>
	<?php } ?>
	
	<?php submit_button( __( 'Filter &#187;' ), 'secondary', 'post-query-submit', false ); ?>
	
	</div>
	
	<br class="clear" />
	</div>
	</form>
	
	<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr($form_action_url); ?>" class="<?php echo $form_class; ?>" id="library-form">
	
	<?php wp_nonce_field('media-form'); ?>
	<?php //media_upload_form( $errors ); ?>
	
	<script type="text/javascript">
	<!--
	jQuery(function($){
		var preloaded = $(".media-item.preloaded");
		if ( preloaded.length > 0 ) {
			preloaded.each(function(){prepareMediaItem({id:this.id.replace(/[^0-9]/g, '')},'');});
			updateMediaForm();
		}
	});
	-->
	</script>
	
	<div id="media-items">
	<?php add_filter('attachment_fields_to_edit', 'media_post_single_attachment_fields_to_edit', 10, 2); ?>
	<?php echo $this->get_media_items(null, $errors); ?>
	</div>
	<p class="ml-submit"></p>
	</form>
	<?php
	}
}

class netword_shared_media_backbonejs {
	protected $blogs = array();
	protected $current_blog_id;
	protected $debug = true;

	public function __construct() {
		add_action( 'init', array($this, 'init') );
	}

	public function init() {
		$this->current_blog_id = $GLOBALS['blog_id'];

		/* copied from depricated get_blog_list */
		global $wpdb;
		$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );

		$this->blogs = array();
		$sort_array = array();

		foreach ( (array) $blogs as $details ) {
			if ( $details['blog_id'] == $this->current_blog_id || false || !current_user_can_for_blog( (int) $details['blog_id'], 'upload_files') ) continue;

			$details['name'] = get_blog_option( (int) $details['blog_id'], 'blogname' );
			$this->blogs[] = $details;
			$sort_array[] = strtolower ( $details['name'] );
		}
		array_multisort( $sort_array, SORT_ASC, $this->blogs );

		add_filter( 'media_view_strings', array($this, 'media_view_strings') );
		add_filter( 'ajax_query_attachments_args', array($this, 'ajax_query_attachments_args') );
	}

	public function media_view_strings( $strings ) {
		$strings['nsmTitle'] = __('Network Shared Media', 'networksharedmedia');
		add_action( 'admin_print_footer_scripts', array($this, 'print_media_templates') );
		return $strings;
	}

	public function print_media_templates() {
		if( $this->debug ) {
			// disable heartbeat
			echo "<script type=\"text/javascript\">jQuery(function(\$) { \$(window).trigger('unload.wp-heartbeat'); });</script>";
		}

// !!! http://wordpress.stackexchange.com/questions/85235/extending-wp-media-model-query-media-from-different-blog-on-network-and-refresh
?>
<script type="text/javascript">
window.wp = window.wp || {};
var blogs = window.blogs || {
    current_blog: 2,
    UserBlogs: [
        {userblog_id: 1, domain: 'main'},
        {userblog_id: 2, domain: 'nl'},
        {userblog_id: 3, domain: 'es'},
        {userblog_id: 4, domain: 'sc'},
    ]};
(function ($) {
	wp.media.editor.on('open', function() {
		console.log('open');
		console.log(arguments);
	});
return;
    "use strict"; // jshint ;_;
    var current = blogs['current_blog'];
    var Blogs = blogs['UserBlogs'];

    $(function() {
        var media = wp.media.editor.add('content');
        media.on('render', function() {
            var html = $("<select>", {name:'blog_id', id: 'blog_id'});
            $.each(Blogs, function (index, blog) {
                html.append($("<option>", {value:blog.userblog_id, html:blog.domain}).prop({ selected: blog.userblog_id == current}));
            });
        $(".attachment-filters").after(html);

        $("select#blog_id").change(function () {
            var str = "";
            $("select#blog_id option:selected").each(function () {
                str += $(this).val();
                var options = {
                    type: 'POST',
                    url: ajaxurl,
                    data: {
                        blog: str
                    }
                };
                wp.media.ajax('switch_blog', options );
                var query = wp.media.query();
                console.log(query);
            });
        })
    });
});
}(jQuery));

// models
( function($, _) {
return;
	var media = wp.media,
		Attachment  = media.model.Attachment,
		Attachments = media.model.Attachments,
		Query       = media.model.Query,
		l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

		var nsmAttachment = media.model.nsmAttachment = media.model.Attachment.extend({
			sync: function() { console.log( 'nsmAttachment sync' ); media.model.Attachment.prototype.sync.apply( this, arguments ); },
			fetch: function() { console.log( 'nsmAttachment fetch' ); media.model.Attachment.prototype.fetch.apply( this, arguments ); }
		});

		var nsmAttachments = media.model.nsmAttachments = media.model.Attachments.extend({
			model: nsmAttachment,
			initialize: function( models, options ) {
				media.model.Attachments.prototype.initialize.apply( this, arguments );
				this.props.on( 'change:blog', this._changeBlog, this );
				this.props.set( _.extend( { blog_id: 2 }, this.props.attributes ) ); // TODO: insert correct blog id
			},

			_changeBlog: function( model, blog ) { console.log( 'change blog for mode: ' + model + ' to blog: ' + blog ); },

			sync: function() { console.log( 'nsmAttachments sync' ); media.model.Attachments.prototype.sync.apply( this, arguments ); },
			fetch: function() { console.log( 'nsmAttachments fetch' ); media.model.Attachments.prototype.fetch.apply( this, arguments ); },
			parse: function( resp, xhr ) {
console.log( 'nsmAttachments parse' );
				if ( ! _.isArray( resp ) ) {
					resp = [resp];
				}

				return _.map( resp, function( attrs ) {
					var id, attachment, newAttributes;

					if ( attrs instanceof Backbone.Model ) {
						id = attrs.get( 'id' );
						attrs = attrs.attributes;
					} else {
						id = attrs.id;
					}

					attachment = nsmAttachment.get( id );
					newAttributes = attachment.parse( attrs, xhr );

					if ( ! _.isEqual( attachment.attributes, newAttributes ) ) {
						attachment.set( newAttributes );
					}

					return attachment;
				});
			}
	});

}(jQuery, _));

// controllers & views
( function($, _) {
return;
	var media = wp.media,
		Attachment  = media.model.Attachment,
		nsmAttachment  = media.model.nsmAttachment,
		Attachments = media.model.Attachments,
		nsmAttachments = media.model.nsmAttachments,
		Query       = media.model.Query,
		l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

	media.view.nsmAttachmentsBrowser = media.view.AttachmentsBrowser.extend({
		createAttachments: function() {
			//this.removeContent();

			this.attachments = new media.view.Attachments({
				controller: this.controller,
				collection: this.collection,
				selection:  this.options.selection,
				model:      this.model,
				sortable:   this.options.sortable,

				// The single `Attachment` view to be used in the `Attachments` view.
				AttachmentView: this.options.AttachmentView
			});

			this.views.add( this.attachments );
		}
	});

	media.view.nsmAttachment = media.view.Attachment.extend({
		className: 'attachment nsm-attachment',

		initialize: function() {
console.log('nsmAttachment initialize');
			media.view.Attachment.prototype.initialize.apply( this, arguments );
		},

		/**
		 * Only the read method is allowed
		 */
		sync: function( method, model, options ) {
console.log('nsmAttachment sync');
			// If the attachment does not yet have an `id`, return an instantly
			// rejected promise. Otherwise, all of our requests will fail.
			if ( _.isUndefined( this.id ) ) {
				return $.Deferred().rejectWith( this ).promise();
			}

			// Overload the `read` request so Attachment.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'get-attachment',
					id: this.id
				});
				return media.ajax( options );
			}
			return $.Deferred().rejectWith( this ).promise();
		}
	});

	_.extend( media.view.MediaFrame.Select.prototype, {
		bindHandlers: function() {
			console.log('bindHandlers overloaded');
			// lines below copied from media.view.MediaFrame.Select.bindHandlers
			this.on( 'router:create:browse', this.createRouter, this );
			this.on( 'router:render:browse', this.browseRouter, this );
			this.on( 'content:create:browse', this.browseContent, this );
			this.on( 'content:render:upload', this.uploadContent, this );
			this.on( 'toolbar:create:select', this.createSelectToolbar, this );

			// add NSM tab in router
			this.on( 'router:render:browse', this.browseNsmRouter, this );

			this.on( 'content:create:browse_nsm', this.browseNsmContent, this );
			this.on( 'content:render:browse_nsm', this.browseNsmRender, this );
			this.on( 'content:activate:browse_nsm', this.browseNsmActivate, this );
			this.on( 'content:deactivate:browse_nsm', this.browseNsmDeactivate, this );

			// Quick Fix: create NSM States
			this.createNsmStates();
		},
		createNsmStates: function() {
			var options = this.options;

			if ( this.options.states ) {
				return;
			}

			// Add the default states.
			this.states.add([
				// NSM states.
				new media.controller.Library({
					id:        'nsm-library',
					library:   new nsmAttachments( null, {
						props: options.library
					}),
					multiple:  options.multiple,
					title:     options.title,
					priority:  60
				})
			]);
		},
		browseNsmRouter: function( routerView ) {
			console.log('browseNsmRouter');
			routerView.set({
				browse_nsm: {
					text:     l10n.nsmTitle,
					priority: 60
				},
			});
		},
		browseNsmContent: function( contentRegion ) {
			console.log('browseNsmContent');

			//if ( ! this.get('nsmLibrary') ) {
			//	this.set( 'nsmLibrary', media.query() );
			//}

			var state = this.state('nsm-library');

			this.$el.removeClass('hide-toolbar');

			content.view = new media.view.nsmAttachmentsBrowser({
				controller: this,
				collection: state.get('library'),
				selection:  state.get('selection'),
				model:      state,
				sortable:   state.get('sortable'),
				search:     state.get('searchable'),
				filters:    state.get('filterable'),
				display:    state.get('displaySettings'),
				dragInfo:   state.get('dragInfo'),

				AttachmentView: state.get('AttachmentView')
			});
		},
		browseNsmRender: function( routerView ) { console.log('browseNsmRender'); },
		browseNsmActivate: function( routerView ) { console.log('browseNsmActivate'); },
		browseNsmDeactivate: function( routerView ) { console.log('browseNsmDeactivate'); }
	});
}(jQuery, _));
</script>
<?php
	}

	public function _print_media_templates() {
		if( $this->debug ) {
			// disable heartbeat
			echo "<script type=\"text/javascript\">jQuery(function(\$) { \$(window).trigger('unload.wp-heartbeat'); });</script>";
		}
		$firstBlog = reset($this->blogs);
		echo <<<EOH
		<script type="text/javascript">
			jQuery(window).on('load', function() {
				// models
				(function(\$) {
					var media   = window.wp.media,
					Attachment  = media.model.Attachment,
					Attachments = media.model.Attachments,
					Query       = media.model.Query,
					l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

					var nsmAttachment = media.model.nsmAttachment = media.model.Attachment.extend({
						sync: function() { console.log( 'nsmAttachment sync' ); media.model.Attachment.prototype.sync.apply( this, arguments ); },
						fetch: function() { console.log( 'nsmAttachment fetch' ); media.model.Attachment.prototype.fetch.apply( this, arguments ); }
					});

					var nsmAttachments = media.model.nsmAttachments = media.model.Attachments.extend({
						model: nsmAttachment,
						initialize: function( models, options ) {
							media.model.Attachments.prototype.initialize.apply( this, arguments );
							this.props.on( 'change:blog', this._changeBlog, this );
							this.props.set( _.extend( { blog: {$firstBlog['blog_id']} }, this.props.attributes ) );
						},

						_changeBlog: function( model, blog ) { console.log( 'change blog for mode: ' + model + ' to blog: ' + blog ); },

						sync: function() { console.log( 'nsmAttachments sync' ); media.model.Attachments.prototype.sync.apply( this, arguments ); },
						fetch: function() { console.log( 'nsmAttachments fetch' ); media.model.Attachments.prototype.fetch.apply( this, arguments ); },
						parse: function( resp, xhr ) {
console.log( 'nsmAttachments parse' );
							if ( ! _.isArray( resp ) ) {
								resp = [resp];
							}

							return _.map( resp, function( attrs ) {
								var id, attachment, newAttributes;

								if ( attrs instanceof Backbone.Model ) {
									id = attrs.get( 'id' );
									attrs = attrs.attributes;
								} else {
									id = attrs.id;
								}

								attachment = nsmAttachment.get( id );
								newAttributes = attachment.parse( attrs, xhr );

								if ( ! _.isEqual( attachment.attributes, newAttributes ) ) {
									attachment.set( newAttributes );
								}

								return attachment;
							});
						}
					});
				}(jQuery));

				// views & controllers
				(function(\$) {
					var media   = window.wp.media,
					Attachment  = media.model.Attachment,
					nsmAttachment  = media.model.nsmAttachment,
					Attachments = media.model.Attachments,
					nsmAttachments = media.model.nsmAttachments,
					Query       = media.model.Query,
					l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

					media.view.nsmAttachmentsBrowser = media.view.AttachmentsBrowser.extend({
						createAttachments: function() {
							//this.removeContent();

							this.attachments = new media.view.Attachments({
								controller: this.controller,
								collection: this.collection,
								selection:  this.options.selection,
								model:      this.model,
								sortable:   this.options.sortable,

								// The single `Attachment` view to be used in the `Attachments` view.
								AttachmentView: this.options.AttachmentView
							});

							this.views.add( this.attachments );
						}
					});

					media.view.nsmAttachment = media.view.Attachment.extend({
						className: 'attachment nsm-attachment',

						initialize: function() {
console.log('nsmAttachment initialize');
							media.view.Attachment.prototype.initialize.apply( this, arguments );
						},

						/**
						 * Only the read method is allowed
						 */
						sync: function( method, model, options ) {
console.log('nsmAttachment sync');
							// If the attachment does not yet have an `id`, return an instantly
							// rejected promise. Otherwise, all of our requests will fail.
							if ( _.isUndefined( this.id ) ) {
								return \$.Deferred().rejectWith( this ).promise();
							}

							// Overload the `read` request so Attachment.fetch() functions correctly.
							if ( 'read' === method ) {
								options = options || {};
								options.context = this;
								options.data = _.extend( options.data || {}, {
									action: 'get-attachment',
									id: this.id
								});
								return media.ajax( options );
							}
							return \$.Deferred().rejectWith( this ).promise();
						}
					});

					nsmView = {
						bindHandlers: media.view.MediaFrame.Select.prototype.bindHandlers,
						createStates: function( selectView ) {
							var options = selectView.options;

							selectView.states.add([
								new media.controller.Library({
									id:        'nsm-library',
									library:   new nsmAttachments( null, {
										props: options.library
									}),
									multiple:  options.multiple,
									title:     options.title,
									priority:  60
								})
							]);
						},

						browseRouter: function( view ) {
							view.set({
								nsm_browse: {
									text:     l10n.nsmTitle,
									priority: 60
								}
							});
						},
						createView: function( content ) {
							console.log('create NSM');

							//if ( ! this.get('nsmLibrary') ) {
							//	this.set( 'nsmLibrary', media.query() );
							//}

							var state = this.state('nsm-library');

							this.\$el.removeClass('hide-toolbar');

							content.view = new media.view.nsmAttachmentsBrowser({
								controller: this,
								collection: state.get('library'),
								selection:  state.get('selection'),
								model:      state,
								sortable:   state.get('sortable'),
								search:     state.get('searchable'),
								filters:    state.get('filterable'),
								display:    state.get('displaySettings'),
								dragInfo:   state.get('dragInfo'),

								AttachmentView: state.get('AttachmentView')
							});
						},
						renderView: function() { console.log('render NSM'); },
						activateView: function() { console.log('activate NSM'); },
						deactivateView: function() { console.log('deactivate NSM'); },
					};
					_.extend(nsmView, media.view.MediaFrame.prototype);

					media.view.MediaFrame.Select.prototype.bindHandlers = function() {
						nsmView.createStates( this );
						nsmView.bindHandlers.call( this );

						this.on( 'router:render:browse', nsmView.browseRouter, this );

						this.on( 'content:create:nsm_browse', nsmView.createView, this );
						this.on( 'content:render:nsm_browse', nsmView.renderView, this );
						this.on( 'content:activate:nsm_browse', nsmView.activateView, this );
						this.on( 'content:deactivate:nsm_browse', nsmView.deactivateView, this );

						\$(window).trigger('unload.wp-heartbeat');
					};
				}(jQuery));
			});
		</script>
EOH;
	}

	public function ajax_query_attachments_args( $query ) {
		if( isset( $_REQUEST['query'] ) && array_key_exists( 'blog', $_REQUEST['query'] ) ) {
			$blog = (int) $_REQUEST['query']['blog'];
			switch_to_blog( $blog );
		}
		return $query;
	}
}
$netword_shared_media_backbonejs = new netword_shared_media_backbonejs();
