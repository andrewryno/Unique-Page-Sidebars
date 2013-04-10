<?php
/*
Plugin Name: Unique Page Sidebars
Plugin URI: http://andrewryno.com/plugins/unique-page-sidebars/
Description: Allows for the creation of sidebars on a per-page basis all from a single dynamic_sidebar() call from where they should appear.
Author: Andrew Ryno
Version: 0.3
Author URI: http://andrewryno.com/
*/

/**
 * Register all hooks
 */
add_filter( 'ups_sidebar', 'ups_display_sidebar' );
add_action( 'init', 'ups_options_init' );
add_action( 'admin_init', 'ups_options_admin_init' );
add_action( 'admin_menu', 'ups_options_add_page' );

/**
 * Displays the sidebar which is attached to the page being viewed.
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_display_sidebar( $default_sidebar ) {
	global $post;

	$sidebars = get_option( 'ups_sidebars' );
	foreach ( $sidebars as $id => $sidebar ) {
		if ( array_key_exists( 'pages', $sidebar ) ) {
			if ( array_key_exists( 'children', $sidebar ) && $sidebar['children'] == 'on' ) {
				$child = array_key_exists( $post->post_parent, $sidebar['pages'] );
			} else {
				$child = false;
			}
			if ( array_key_exists( $post->ID, $sidebar['pages'] ) || $child ) {
				return $id;
			}
		}
	}

	return $default_sidebar;
}

/**
 * Add the options page to the "Appearance" admin menu
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_options_add_page() {
	add_theme_page( 'Manage Sidebars', 'Manage Sidebars', 'edit_theme_options', 'ups_sidebars', 'ups_sidebars_do_page' );
}

/**
 * Registers all sidebars for use on the front-end and Widgets page
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_options_init() {
	$sidebars = get_option( 'ups_sidebars' );

	if ( is_array( $sidebars ) ) {
		foreach ( (array) $sidebars as $id => $sidebar ) {
			unset( $sidebar['pages'] );
			$sidebar['id'] = $id;
			register_sidebar( $sidebar );
		}
	}
}

/**
 * Adds the metaboxes to the main options page for the sidebars in the database.
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_options_admin_init() {
	wp_enqueue_script('common');
	wp_enqueue_script('wp-lists');
	wp_enqueue_script('postbox');

	// Register setting to store all the sidebar options in the *_options table
	register_setting( 'ups_sidebars_options', 'ups_sidebars', 'ups_sidebars_validate' );

	$sidebars = get_option( 'ups_sidebars' );
	if ( is_array( $sidebars ) && count ( $sidebars ) > 0 ) {
		foreach ( $sidebars as $id => $sidebar ) {
			add_meta_box(
				esc_attr( $id ),
				esc_html( $sidebar['name'] ),
				'ups_sidebar_do_meta_box',
				'ups_sidebars',
				'normal',
				'default',
				array(
					'id' => esc_attr( $id ),
					'sidebar' => $sidebar
				)
			);

			unset( $sidebar['pages'] );
			$sidebar['id'] = esc_attr( $id );
			register_sidebar( $sidebar );
		}
	} else {
		add_meta_box( 'ups-sidebar-no-sidebars', 'No sidebars', 'ups_sidebar_no_sidebars', 'ups_sidebars', 'normal', 'default' );
	}

	// Sidebar metaboxes
	add_meta_box( 'ups-sidebar-add-new-sidebar', 'Add New Sidebar', 'ups_sidebar_add_new_sidebar', 'ups_sidebars', 'side', 'default' );
	add_meta_box( 'ups-sidebar-about-the-plugin', 'About the Plugin', 'ups_sidebar_about_the_plugin', 'ups_sidebars', 'side', 'default' );
}

function ups_sidebar_no_sidebars() {
	?>
	<p>You haven&rsquo;t added any sidebars yet. Add one using the form on the right hand side!</p>
	<?php
}

/**
 * Callback function which creates the theme page and adds a spot for the metaboxes
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_sidebars_do_page() {
	if ( ! isset( $_REQUEST['settings-updated'] ) )
		$_REQUEST['settings-updated'] = false;
	?>
	<div class="wrap">
		<?php screen_icon(); ?><h2>Manage Sidebars</h2>
		<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
		<div class="updated fade"><p><strong>Sidebar settings saved.</strong> You can now go manage the <a href="<?php echo get_admin_url(); ?>widgets.php">widgets</a> for your sidebars.</p></div>
		<?php endif; ?>
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div id="post-body" class="has-sidebar">
				<div id="post-body-content" class="has-sidebar-content">
					<form method="post" action="options.php">
						<?php settings_fields( 'ups_sidebars_options' ); ?>
						<?php do_meta_boxes( 'ups_sidebars', 'normal', null ); ?>
					</form>
				</div>
			</div>
			<div id="side-info-column" class="inner-sidebar">
				<?php do_meta_boxes( 'ups_sidebars', 'side', null ); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Callback function which adds the content of the metaboxes for each sidebar
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_sidebar_do_meta_box( $post, $metabox ) {
	$sidebars = get_option( 'ups_sidebars' );
	$sidebar_id = esc_attr( $metabox['args']['id'] );
	$sidebar = $sidebars[$sidebar_id];

	if ( ! isset( $sidebar['pages'] ) ) {
		$sidebar['pages'] = array();
	}

	$options_fields = array(
		'name' => 'Name',
		'description' => 'Description',
		'before_title' => 'Before Title',
		'after_title' => 'After Title',
		'before_widget' => 'Before Widget',
		'after_widget' => 'After Widget',
		'children' => 'Child Behavior'
	);

	$post_types = get_post_types( array( '_builtin' => false ), 'objects' );
	$post_types = array_merge( $post_types, array( 'page' => get_post_type_object( 'page' ), 'post' => get_post_type_object( 'post' ) ) );
	?>
	<div style="float: left; width: 25%;">
		<ul class="wp-tab-bar">
			<?php $i = 0; foreach ( $post_types as $post_type ) : ?>
				<li <?php echo ($i == 0) ? 'class="wp-tab-active"' : ''; ?>>
					<a href="#post-type-<?php echo esc_attr( $post_type->name ); ?>">
						<?php echo esc_html( $post_type->labels->name ); ?>
					</a>
				</li>
			<?php ++$i; endforeach; ?>
		</ul>
		<?php $i = 0; foreach ( $post_types as $post_type ) : ?>
			<div class="wp-tab-panel" id="post-type-<?php echo esc_attr( $post_type->name ); ?>" <?php echo ($i > 0) ? 'style="display: none;"' : ''; ?>>
				<?php
				$wpquery = new WP_Query;
				$items = $wpquery->query( array(
					'offset' => 0,
					'order' => 'ASC',
					'orderby' => 'title',
					'posts_per_page' => -1,
					'post_type' => $post_type->name,
					'suppress_filters' => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'no_found_posts' => true,
				) );
				?>
				<ul id="<?php echo esc_attr( $post_type->name ); ?>checklist" class="categorychecklist form-no-clear">
					<?php foreach ( $items as $item ) : ?>
					<li>
						<label>
						<?php $name = 'ups_sidebars[' . $sidebar_id . '][pages][' . $item->ID . ']'; ?>
						<input type="checkbox" class="menu-item-checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $item->post_title ); ?>" <?php echo array_key_exists( $item->ID, $sidebar['pages'] ) ? 'checked="checked"' : ''; ?> />
						<?php echo esc_html( $item->post_title ); ?>
						</label>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php ++$i; endforeach; ?>
	</div>
	<script>
	jQuery(document).ready( function($) {
		// wp tabs
		$('.wp-tab-bar a').click(function(event){
			event.preventDefault();
			// Limit effect to the container element.
			var context = $(this).parents('.wp-tab-bar').first().parent();
			$('.wp-tab-bar li', context).removeClass('wp-tab-active');
			$(this).parents('li').first().addClass('wp-tab-active');
			$('.wp-tab-panel', context).hide();
			$( $(this).attr('href'), context ).show();
		});
		// Make setting wp-tab-active optional.
		$('.wp-tab-bar').each(function(){
			if ( $('.wp-tab-active', this).length )
				$('.wp-tab-active', this).click();
			else $('a', this).first().click();
		});
	});
	</script>
	<div style="float: right; width: 70%;">
		<table class="form-table">
			<?php foreach ( $options_fields as $id => $label ) : ?>
			<tr valign="top">
				<th scope="row"><label for="ups_sidebars[<?php echo esc_attr( $sidebar_id ); ?>][<?php echo esc_attr( $id ); ?>]"><?php echo esc_html( $label ); ?></label></th>
				<td>
				<?php if ( 'children' == $id ) : ?>
					<?php
					$checked = '';
					if ( array_key_exists( 'children', $sidebar ) && $sidebar['children'] == 'on' ) {
						$checked = ' checked="checked"';
					}
					?>
					<label>
					<input type="checkbox" name="ups_sidebars[<?php echo esc_attr( $sidebar_id ); ?>][<?php echo esc_attr( $id ); ?>]" value="on" id="ups_sidebars[<?php echo esc_attr( $sidebar_id ); ?>][<?php echo esc_attr( $id ); ?>]"<?php echo $checked; ?> />
					<span class="description">Set page children to use the parent page sidebar by default?</span>
					</label>
				<?php else : ?>
					<input id="ups_sidebars[<?php echo esc_attr( $sidebar_id ); ?>][<?php echo esc_attr( $id ); ?>]" class="regular-text" type="text" name="ups_sidebars[<?php echo esc_attr( $sidebar_id ); ?>][<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_html( $sidebar[$id] ); ?>" />
				<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>
	<div class="clear submitbox">
		<input type="submit" class="button-primary" value="Save all sidebars" />&nbsp;&nbsp;&nbsp;
		<label><input type="checkbox" name="ups_sidebars[delete]" value="<?php echo esc_attr( $sidebar_id ); ?>" /> Delete this sidebar?</label>
	</div>
	<?php
}

/**
 * Validates and handles all the post data (adding, updating, deleting sidebars)
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_sidebars_validate( $input ) {
	if ( isset( $input['add_sidebar'] ) ) {
		$sidebars = get_option( 'ups_sidebars' );
		if ( '' != $input['add_sidebar'] ) {
			$sidebar_num = count( $sidebars ) + 1;
			$sidebars['ups-sidebar-' . $sidebar_num] = array(
				'name' => esc_html( $input['add_sidebar'] ),
				'description' => '',
				'before_title' => '',
				'after_title' => '',
				'before_widget' => '',
				'after_widget' => '',
				'pages' => array(),
				'children' => 'off'
			);
		}
		return $sidebars;
	}

	if ( isset( $input['delete'] ) ) {
		foreach ( (array) $input['delete'] as $delete_id ) {
			unset( $input[$delete_id] );
		}
		unset( $input['delete'] );
		return $input;
	}

	return $input;
}

/**
 * Handles the content of the metabox which allows adding new sidebars
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_sidebar_add_new_sidebar() {
	?>
	<form method="post" action="options.php" id="add-new-sidebar">
		<?php settings_fields( 'ups_sidebars_options' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Name</th>
				<td>
					<input id="ups_sidebars[add_sidebar]" class="text" type="text" name="ups_sidebars[add_sidebar]" value="" />
				</td>
			</tr>
		</table>
		<p class="submit" style="padding: 0;">
			<input type="submit" class="button-primary" value="Add Sidebar" />
		</p>
	</form>
	<?php
}

/**
 * Handles the content of the metabox that describes the plugin
 *
 * @since Unique Page Sidebars 0.1
 */
function ups_sidebar_about_the_plugin() {
	?>
	<p>This plugin was developed by <a href="http://andrewryno.com/">Andrew Ryno</a>, a WordPress developer based in Phoenix, AZ who never found a decent solution to having sidebars on different pages.</p>
	<p>Like the plugin? Think it could be improved? Feel free to contribute over at <a href="http://github.com/andrewryno">GitHub</a>!</p>
	<p>If you have any other feedback or need help, go ahead and <a href="http://andrewryno.com/plugins/unique-page-sidebars/">leave a comment</a>.</p>
	<?php
}