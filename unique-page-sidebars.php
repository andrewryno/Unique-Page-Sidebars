<?php
/*
Plugin Name: Unique Page Sidebars
Plugin URI: http://andrewryno.com/plugins/page-specific-sidebars-widgets/
Text Domain: Unique_Page_Sidebars
Domain Path: /lang
Description: Allows for the creation of sidebars on a per-page basis all from a single dynamic_sidebar() call from where they should appear.
Author: Andrew Ryno
Version: 0.3
Author URI: http://andrewryno.com/
*/

class Unique_Page_Sidebars {
	const LANG_DIR = '/lang/'; // Defaut lang dirctory
	const TEXT_DOMAIN = 'unique-page-sidebars';

	/**
	 * Register all hooks.
	 */
	public function __construct() {

		load_plugin_textdomain(self::TEXT_DOMAIN,false, dirname(plugin_basename( __FILE__ ) ) . self::LANG_DIR );

		// The following two hooks need to be called on each request, one to
		// register the sidebars and another to display them.
		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'ups_sidebar', array( $this, 'display_sidebar' ) );

		// Everything else can be loaded in the admin only (options pages).
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Registers all sidebars for use on the front-end and Widgets page
	 */
	public function init() {
		$sidebars = get_option( 'ups_sidebars' );

		if ( is_array( $sidebars ) ) {
			foreach ( (array) $sidebars as $id => $sidebar ) {
				unset( $sidebar['pages'] ); // Backwards compat only
				unset( $sidebar['location'] );
				$sidebar['id'] = $id;
				register_sidebar( $sidebar );
			}
		}
	}

	/**
	 * Displays the sidebar which is attached to the page being viewed.
	 */
	public function display_sidebar( $default_sidebar ) {
		global $post;

		$sidebars = get_option( 'ups_sidebars' );
		foreach ( $sidebars as $id => $sidebar ) {
			// Make sure this sidebar has locations registerd
			if ( ! isset( $sidebar['locations'] ) )
				continue;

			foreach ( $sidebar['locations'] as $location => $ids ) {
				// Check to see if child posts should be displayed
				if ( isset( $sidebar['children'] ) && $sidebar['children'] == 'on' ) {
					$child = array_key_exists( $post->post_parent, $sidebar['pages'] );
				} else {
					$child = false;
				}

				// If this post is set to be displayed (or it's a child of one)
				// then return the ID of the sidebar it is in.
				if ( in_array( $post->ID, $ids ) || $child ) {
					return $id;
				}
			}
		}

		return $default_sidebar;
	}

	/**
	 * Add the options page to the "Appearance" admin menu.
	 */
	public function add_page() {
		add_theme_page(
				__('Manage Sidebars', self::TEXT_DOMAIN),
				__('Manage Sidebars', self::TEXT_DOMAIN),
				'edit_theme_options',
				'ups_sidebars',
				array( $this, 'admin_page' )
			);
	}

	/**
	 * Adds the metaboxes to the main options page for the sidebars in the database.
	 */
	public function admin_init() {
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );

		// Register setting to store all the sidebar options in the *_options table
		register_setting( 'ups_sidebars_options', 'ups_sidebars', array( $this, 'validate' ) );

		$sidebars = get_option( 'ups_sidebars' );
		if ( is_array( $sidebars ) && count ( $sidebars ) > 0 ) {
			foreach ( $sidebars as $id => $sidebar ) {
				add_meta_box(
					esc_attr( $id ),
					esc_html( $sidebar['name'] ),
					array( $this, 'meta_box' ),
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
			add_meta_box( 'ups-sidebar-no-sidebars', __('No sidebars', self::TEXT_DOMAIN), array( $this, 'no_sidebars' ), 'ups_sidebars', 'normal', 'default' );
		}

		// Sidebar metaboxes
		add_meta_box( 'ups-sidebar-add-new-sidebar', __('Add New Sidebar', self::TEXT_DOMAIN) , array( $this, 'new_sidebar' ), 'ups_sidebars', 'side', 'default' );
		add_meta_box( 'ups-sidebar-about-the-plugin', __('About the Plugin', self::TEXT_DOMAIN) , array( $this, 'about' ), 'ups_sidebars', 'side', 'default' );
	}

	/**
	 * Outputs error message when no sidebars have been added.
	 */
	public function no_sidebars() {
		echo '<p>'. __('You haven&rsquo;t added any sidebars yet. Add one using the form on the right hand side!',self::TEXT_DOMAIN) .'</p>';
	}

	/**
	 * Callback function which creates the theme page and adds a spot for the metaboxes.
	 */
	public function admin_page() {
		if ( ! isset( $_REQUEST['settings-updated'] ) )
			$_REQUEST['settings-updated'] = false;
		?>
		<div class="wrap">
			<h2><?php _e('Manage Sidebars', self::TEXT_DOMAIN) ?></h2>
			<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
			<div class="updated fade"><p><strong><?php _e('Sidebar settings saved.', self::TEXT_DOMAIN) ?></strong> <?php printf( __('You can now go manage the <a href="%swidgets.php">widgets</a> for your sidebars.', self::TEXT_DOMAIN), get_admin_url()) ?></p></div>
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
	 * Callback function which adds the content of the metaboxes for each sidebar.
	 */
	public function meta_box( $post, $metabox ) {
		$sidebars = get_option( 'ups_sidebars' );
		$sidebar_id = esc_attr( $metabox['args']['id'] );
		$sidebar = $sidebars[$sidebar_id];

		$options_fields = array(
			'name' => 'Name',
			'description' => __('Description', self::TEXT_DOMAIN),
			'before_title' => __('Before Title', self::TEXT_DOMAIN),
			'after_title' => __('After Title', self::TEXT_DOMAIN),
			'before_widget' => __('Before Widget', self::TEXT_DOMAIN),
			'after_widget' => __('After Widget', self::TEXT_DOMAIN),
			'children' => __('Child Behavior', self::TEXT_DOMAIN),
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
					$items = new WP_Query( array(
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
					if ( $items->have_posts() ) : ?>
						<ul id="<?php echo esc_attr( $post_type->name ); ?>checklist" class="categorychecklist form-no-clear">
							<?php while ( $items->have_posts() ) : $items->the_post(); ?>
								<li>
									<label>
									<?php $name = 'ups_sidebars[' . $sidebar_id . '][locations][' . $post_type->name . '][' . get_the_ID() . ']'; ?>
									<input type="checkbox" class="menu-item-checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( get_the_title( get_the_ID() ) ); ?>" <?php echo ( isset( $sidebar['locations'][$post_type->name] ) && array_key_exists( get_the_ID(), $sidebar['locations'][$post_type->name] ) ) ? 'checked="checked"' : ''; ?> />
									<?php echo esc_html( get_the_title( get_the_ID() ) ); ?>
									</label>
								</li>
							<?php endwhile; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php ++$i; endforeach; ?>
		</div>
		<script>
		jQuery(document).ready( function($) {
			// WP tabs
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
						<span class="description"><?php _e('Set page children to use the parent page sidebar by default?', self::TEXT_DOMAIN) ?></span>
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
			<input type="submit" class="button-primary" value="<?php _e('Save all sidebars', self::TEXT_DOMAIN) ?>" />&nbsp;&nbsp;&nbsp;
			<label><input type="checkbox" name="ups_sidebars[delete]" value="<?php echo esc_attr( $sidebar_id ); ?>" /> <?php _e('Delete this sidebar?', self::TEXT_DOMAIN) ?></label>
		</div>
		<?php
	}

	/**
	 * Validates and handles all the post data (adding, updating, deleting sidebars).
	 */
	public function validate( $input ) {
		if ( isset( $input['add_sidebar'] ) ) {
			$input = $this->add_sidebar( $input['add_sidebar'] );
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
	 * Adds a sidebar to the database.
	 *
	 * @param  string $name
	 * @return array
	 */
	public function add_sidebar( $name ) {
		$sidebars = get_option( 'ups_sidebars' );
		if ( empty( $name ) ) {
			return false;
		}

		// Get the last sidebar ID from the database
		$sidebar_num = get_option( 'ups_sidebars_last_id', -1 );
		if ( $sidebar_num < 0 ) {
			// Backward compatibility for existing sidebars
			if ( is_array( $sidebars ) ) {
				$last_id = end( array_keys( $sidebars ) );
				$last_num = end( explode( '-', $last_id ) );
				$sidebar_num = intval( $last_num );
			} else {
				$sidebar_num = 0;
			}
		}

		// Increment the sidebar number and save it
		$sidebar_num += 1;
		update_option( 'ups_sidebars_last_id', $sidebar_num );

		$sidebars['ups-sidebar-' . $sidebar_num] = array(
			'name' => esc_html( $name ),
			'description' => '',
			'before_title' => '',
			'after_title' => '',
			'before_widget' => '',
			'after_widget' => '',
			'children' => 'off',
			'locations' => array()
		);

		return $sidebars;
	}

	/**
	 * Handles the content of the metabox which allows adding new sidebars.
	 */
	public function new_sidebar() {
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
				<input type="submit" class="button-primary" value="<?php _e('Add Sidebar', self::TEXT_DOMAIN) ?>" />
			</p>
		</form>
		<?php
	}

	/**
	 * Handles the content of the metabox that describes the plugin.
	 */
	public function about() {
		?>
		<p><?php _e('This plugin was developed by <a href="http://andrewryno.com/">Andrew Ryno</a>, a WordPress developer based in Phoenix, AZ who never found a decent solution to having sidebars on different pages.', self::TEXT_DOMAIN) ?></p>
		<p><?php _e('Like the plugin? Think it could be improved? Feel free to contribute over at <a href="http://github.com/andrewryno">GitHub</a>!', self::TEXT_DOMAIN) ?></p>
		<p><?php _e('If you have any other feedback or need help, go ahead and <a href="http://andrewryno.com/plugins/unique-page-sidebars/">leave a comment</a>.', self::TEXT_DOMAIN) ?></p>
		<?php
	}

}

$GLOBALS['unique_page_sidebars'] = new Unique_Page_Sidebars;
