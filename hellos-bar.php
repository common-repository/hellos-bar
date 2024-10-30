<?php
/**
 * Plugin Name: Hellos Bar
 * Plugin URI: http://austinpassy.com/wordpress-plugins/hellos-bar
 * Description: A fixed position (header) HTML and jQuery pop-up announcemnet bar. <em>currently <strong>&alpha;</strong>lpha testing</em>
 * Version: 0.2.2
 * Author: Austin Passy
 * Author URI: http://austinpassy.com
 *
 * @copyright 2009 - 2011
 * @author Austin Passy
 * @link http://frostywebdesigns.com/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package HellosBar
 */

if ( !class_exists( 'Hellos_Bar' ) ) {
	class Hellos_Bar {
		
		const domain	= 'hellos-bar';
		const version	= '0.2.2';
		
		function Hellos_Bar() {
			$this->__construct();
		}
		
		/**
		 * Sets up the Hellos_Bar plugin and loads files at the appropriate time.
		 *
		 * @since 0.2
		 */
		function __construct() {
			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
			register_uninstall_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
			
			/* Define constants */
			add_action( 'plugins_loaded', array( __CLASS__, 'constants' ) );
			
			add_action( 'plugins_loaded', array( __CLASS__, 'required' ) );
			add_action( 'admin_init', array( __CLASS__, 'localize' ) );
			
			/* Print script */
			add_action( 'wp_print_scripts', array( __CLASS__, 'enqueue_script' ) );
			
			/* Print style */
			add_action( 'wp_print_styles', array( __CLASS__, 'enqueue_style' ) );
			
			/* Register post_types & multiple templates */
			add_action( 'init', array( __CLASS__, 'register_post_type' ) );
			
			/* Column manager */
			add_filter( 'manage_posts_columns', array( __CLASS__, 'columns' ), 10, 2 );
			add_action( 'manage_posts_custom_column', array( __CLASS__, 'column_data' ), 10, 2 );			
			
			/* Save the meta data */	
			add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
			
			add_action( 'template_redirect', array( __CLASS__, 'count_and_redirect' ) ) ;
			
			/* Add HTML */
			add_action( 'wp_footer', array( __CLASS__, 'html' ), 999 );
		
			do_action( 'hellos_bar_loaded' );
		}
		
		function activate() {
			self::register_post_type();
			flush_rewrite_rules();
		}
		
		function deactivate() {
			flush_rewrite_rules();
		}
		
		function constants() {		
			/* Set constant path to the Cleaner Gallery plugin directory. */
			define( 'HELLO_BAR_DIR', plugin_dir_path( __FILE__ ) );
			define( 'HELLO_BAR_ADMIN', trailingslashit( HELLO_BAR_DIR ) . 'admin/' );
		
			/* Set constant path to the Cleaner Gallery plugin URL. */
			define( 'HELLO_BAR_URL', plugin_dir_url( __FILE__ ) );
			define( 'HELLO_BAR_CSS', HELLO_BAR_URL . 'css/' );
			define( 'HELLO_BAR_JS', HELLO_BAR_URL . 'js/' );
			
			/* Set the post type */
			define( 'HELLOS_BAR_POST_TYPE', apply_filters( 'hellos_bar_post_type', 'hellosbar' ) );
		}
		
		function required() {
			if ( is_admin() )
			require_once( trailingslashit( HELLO_BAR_ADMIN ) . 'admin.php' );
		}
		
		function localize() {
			load_plugin_textdomain( self::domain, null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Function for quickly grabbing settings for the plugin without having to call get_option() 
		 * every time we need a setting.
		 *
		 * @since 0.1
		 */
		function get_setting( $option = '' ) {
			global $hellos_bar;
		
			if ( !$option )
				return false;
		
			if ( !isset( $hellos_bar->settings ) )
				$hellos_bar->settings = get_option( 'hellos_bar_settings' );
		
			return $hellos_bar->settings[$option];
		}

		/**
		 * WordPress 3.x check
		 *
		 * @since 0.01
		 */
		function is_version( $version = '3.0' ) {
			global $wp_version;
			
			if ( version_compare( $wp_version, $version, '<' ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Add script
		 * @since 0.01
		 */
		function enqueue_script() {
			global $hellos_bar;
			
			if ( !is_admin() && self::get_setting( 'activate' ) == true )
				wp_enqueue_script( self::domain, HELLO_BAR_JS . 'hellos.js', array( 'jquery' ), '0.1', true );
		}

		/**
		 * Add stylesheet
		 * @since 0.01
		 */
		function enqueue_style() {
			global $hellos_bar;
			
			if ( !is_admin() && self::get_setting( 'activate' ) == true )
				wp_enqueue_style( self::domain, HELLO_BAR_CSS . 'hellos.css.php', false, 0.1, 'screen' );
		}
		
		/**
		 * Fire this during init
		 * @ref http://wordpress.pastebin.com/VCeaJBt8
		 * Thanks to @_mfields
		 */
		function register_post_type() {
			global $hellos_bar;
			
			$slug = sanitize_title_with_dashes( self::get_setting( 'slug' ) );
			
			if ( !empty( $slug ) )
				$rewrite['slug'] = $slug;
			
			/* Labels for the helloss post type. */
			$labels = array(
				'menu_name'				=> __( 'Hellos Bar', self::domain ),
				'name'					=> __( 'Hellos Bar', self::domain ),
				'singular_name'			=> __( 'Hello Bar', self::domain ),
				'add_new'				=> __( 'Add New', self::domain ),
				'add_new_item'			=> __( 'Add New Hellosbar post', self::domain ),
				'edit'					=> __( 'Edit', self::domain ),
				'edit_item'				=> __( 'Edit a Hellosbar post', self::domain ),
				'new_item'				=> __( 'New Hellosbar post', self::domain ),
				'view'					=> __( 'View Hellosbars', self::domain ),
				'view_item'				=> __( 'View Hellosbar post', self::domain ),
				'search_items'			=> __( 'Search Hellosbar posts', self::domain ),
				'not_found'				=> __( 'No hellosbar posts found', self::domain ),
				'not_found_in_trash'	=> __( 'No hellosbar posts found in Trash', self::domain ),
			);
		
			/* Arguments for the helloss post type. */
			$args = array(
				'labels'				=> $labels,
				'has_archive'			=> false,
				'capability_type'		=> 'post',
				'public'				=> true,
				'can_export'			=> true,
				'query_var'				=> true,
				'rewrite'				=> array( 'slug' => $slug, 'with_front' => false ),
				'menu_icon'				=> plugins_url( 'admin/hellos.png', __FILE__ ),
				'supports'				=> array( 'title', 'entry-views' ),
				'register_meta_box_cb'	=> array( __CLASS__, 'add_meta_box' ),
			);
		
			/* Register the helloss post type. */
			register_post_type( HELLOS_BAR_POST_TYPE, $args );
		}
		
		function columns( $columns, $post_type ) {				
			if ( HELLOS_BAR_POST_TYPE == $post_type ) {
				$columns = array(
					'cb'			=> '<input type="checkbox" />',
					'title'			=> 'Title', //So an edit link shows. :P
					'author'		=> 'Author',
					'link'			=> 'Link',
					'count'			=> 'Hits',
					'date'			=> 'Date'
				);
			}				
			return $columns;
		}
		
		function column_data( $column_name, $post_id ) {
			global $post_type, $post, $user;
			
			if( HELLOS_BAR_POST_TYPE == $post_type ) {
				if( 'email' == $column_name ) :
					$email =  get_the_author_meta( $user_email, $userID );
					$default = '';
					$size = 40;
					$gravatar = 'http://www.gravatar.com/avatar/' . md5( strtolower( trim( $email ) ) ) . '?d=' . $default . '&s=' . $size;
					echo '<img alt="" src="'.$gravatar.'" />';
				elseif( 'link' == $column_name ) :
					$perm	= get_permalink( $post->ID );
					$url	= get_post_meta( $post->ID, '_hellos_link', true );		
					//echo make_clickable( esc_url( $perm ? $perm : '' ) );
					echo '<a href="'.$perm.'">'.esc_url( $url ? $url : $perm ).'</a>';
				elseif( 'count' == $column_name ) :
					$count = get_post_meta( $post->ID, '_hellos_count', true );
					echo esc_html( $count ? $count : 0 );
				endif;
			}
		}
		
		/**
		 * Register the metaboxes
		 */
		function add_meta_box() {	
			add_meta_box( 'hellosbar-meta-box', __( 'Hellos', self::domain ), array( __CLASS__, 'meta_box_settings' ), HELLOS_BAR_POST_TYPE, 'normal', 'default' );
		}
		
		/**
		 * The hellos metabox
		 */
		function meta_box_settings() {
			global $post;
			
			$hello	= get_post_meta( $post->ID, '_hellos_content', 	true );
			$count	= get_post_meta( $post->ID, '_hellos_count', 	true );
			$link	= get_post_meta( $post->ID, '_hellos_link', 	true ); ?>
			
			<input type="hidden" name="<?php echo 'hellos_meta_box_nonce'; ?>" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />
			<table class="form-table">
				<tr>
					<td style="width:10%;vertical-align:top"><label for="content"><?php _e( 'Content:', self::domain ); ?></label></td>
					<td colspan="3"><textarea name="_hellos_content" id="_hellos_content" rows="4" cols="80" tabindex="30" style="width:97%"><?php echo esc_html( $hello ); ?></textarea>
                    <br />
					<span class="description"><?php _e( 'Please enter your plain text content here.', self::domain ); ?></span></td>
				</tr>
				<tr>
					<td style="width:10%;vertical-align:top"><label for="cite"><?php _e( 'Link:', self::domain ); ?></label></td>
					<td>
						<input type="text" name="_hellos_link" id="_hellos_link" value="<?php echo esc_url( $link ); ?>" size="30" tabindex="30" style="width:90%" />
                        <br />
						<?php $counter = isset( $post->ID ) ? $count : 0; ?>
						<span class="description"><?php echo sprintf( __( 'This URL has been accessed <strong>%d</strong> times.', self::domain ), esc_attr( $counter ) ); ?></span>
					</td>
				</tr>
			</table><!-- .form-table --><?php
		}
		
		/**
		 * Save the metabox aata
		 */
		function save_meta_box( $post_id, $post ) {
				
			/* Make sure the form is valid. */
			if ( !isset( $_POST['hellos_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['hellos_meta_box_nonce'], basename( __FILE__ ) ) )
				return $post_id;
			
			// Is the user registered as a subscriber.
			if ( !current_user_can( 'manage_links', $post_id ) )
				return $post_id;
				
			$meta['_hellos_content']	= esc_html( $_POST['_hellos_content'] );
			$meta['_hellos_link']		= esc_url( $_POST['_hellos_link'] );
			
			foreach ( $meta as $key => $value ) {
				if( $post->post_type == 'revision' )
					return;
				$value = implode( ',', (array)$value );
				if ( get_post_meta( $post_id, $key, FALSE ) ) {
					update_post_meta( $post_id, $key, $value );
				} else {
					add_post_meta( $post_id, $key, $value );
				}
				if ( !$value ) delete_post_meta( $post_id, $key );
			}
		}
		
		function count_and_redirect() {
				
			if ( !is_singular( HELLOS_BAR_POST_TYPE ) )
				return;
		
			global $wp_query;
			
			// Update the count
			$count = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, '_hellos_count', true ) : 0;
			update_post_meta( $wp_query->post->ID, '_hellos_count', $count + 1 );
		
			// Handle the redirect
			$redirect = isset( $wp_query->post->ID ) ? get_post_meta( $wp_query->post->ID, '_hellos_link', true ) : '';
		
			if ( !empty( $redirect ) ) {
				wp_redirect( esc_url_raw( $redirect ), 301 );
				exit;
			}
			else {
				wp_redirect( home_url(), 302 );
				exit;
			}
			
		}
		
		/**
		 * Add the HTML
		 */
		function html() {
			global $post, $hellos_bar;
			
			if ( self::get_setting( 'activate' ) == true ) {
			
				query_posts( array( 'post_type' => HELLOS_BAR_POST_TYPE, 'posts_per_page' => '1', 'orderby' => 'rand' ) ); ?>
				
				<div id="hellobar-container" class="show-if-no-js">
					
					<div class="tab">
						<div class="toggle">
							<a class="open" title="<?php _e( 'Show panel', self::domain ); ?>" style="display: none;"><?php _e( '<span class="arrow">&darr;</span>', self::domain ); ?></a>
							<a class="close" title="<?php _e( 'Hide panel', self::domain ); ?>"><?php _e( '<span class="arrow">&uarr;</span>', self::domain ); ?></a>
						</div><!-- /.toggle -->
					</div><!-- /.tab -->
				
					<div id="hellobar" class="show-if-no-js"><?php
						if ( have_posts() ) : while ( have_posts() ) : the_post();
							$content = get_post_meta( $post->ID, '_hellos_content', true );
							$thelink = get_post_meta( $post->ID, '_hellos_link', true );
							$prelink = get_permalink( $post->ID ); ?>
                            
						<div id="hellobar-<?php the_ID(); ?>" class="hellos">
                        
							<p><?php echo wp_specialchars_decode( stripslashes( $content ), 1, 0, 1 ); 
							
							if ( $thelink ) echo '&nbsp;<a href="' . $prelink . '">' . $thelink . '</a>'; ?></p>
                            
						</div>
                        
                        <?php endwhile; else : ?>
                        
                        <div id="hellobar-0" class="hellos">
                        
							<p><?php echo sprintf( __( 'Please add a <a href="%s">post</a>. Powered by <a href="%s">Hellos Bar</a>', self::domain ), admin_url( 'post-new.php?post_type=' . HELLOS_BAR_POST_TYPE  ), 'http://austinpassy.com/wordpress-plugins/hellos-bar' ); ?></p>
                            
						</div>
                        
						<?php endif; ?>
						
						<div class="branding">
							<a class="branding" href="http://austinpassy.com/wordpress-plugins/hellos-bar" rel="bookmark" title="Plugin by Austin &ldquo;Frosty&rdquo; Passy">&#9731;</a>
						</div><!-- /.branding -->
						
					</div><!-- /#hellobar -->
					
				</div><!-- /#hellobar-container --><?php
			}
		}
		
	}
};

$hello = new Hellos_Bar; ?>