<?php
/*
Plugin Name: Show Pro Posts
Plugin URI: http://premium.wpmudev.org/
Description: Show posts from Pro Sites (WPMU) sites
Author: Ashok (WPMU DEV)
Version: 1.0.0
Author URI: http://premium.wpmudev.org/
*/

/**
 * Protect direct access
 */
if ( ! defined( 'ABSPATH' ) ) wp_die( 'Sorry Cowboy! Find a different place to hack!' );

if( ! defined( 'ALLOW_SUBSITE_PRO_POSTS' ) ) define( 'ALLOW_SUBSITE_PRO_POSTS', false );

if( ! is_multisite() ) {
	add_action( 'admin_notices', 'not_compatible_notice' );
	function not_compatible_notice() {
		?>
		<div class="error">
		    <p><?php echo "Show Pro Posts plugin is only multisite compatible."; ?></p>
		</div>
		<?php
	}
}
elseif( ! class_exists( 'Show_Pro_Posts' ) ){
	class Show_Pro_Posts{
		
		private $db;
		
		public function __construct() {
			
			global $wpdb;
			$this->db = $wpdb;
			
			add_action( 'init', array( &$this, 'plugin_init' ) );
			add_shortcode( 'network_pro_posts', array( &$this, 'network_pro_posts_cb' ) );
		}
		
		// Do something in plugin initialization
		public function plugin_init() {
			do_action( 'show_pro_posts_init' );
		}
		
		public function network_pro_posts_cb( $atts ) {
			
			if( ! is_main_site() && ! ALLOW_SUBSITE_PRO_POSTS ){
				return 'Sorry! This shortcode is only available for main site.';
			}
			
			$defaults = array(
					'posts_per_page' => 10,
					'post_type' => 'post',
					'randomize' => false,
					'include_main_site' => false,
					'pro_level' => 'all'
					);
			
			extract(shortcode_atts($defaults, $atts));
			
			$posts = $sites = array();
			
			if( $pro_level == 'all' ){
				$levels = get_site_option( 'psts_levels' );
			}
			else{
				$levels = array( $pro_level => 1 );
			}
			
			foreach( $levels as $key => $value ){
				$sql = 'SELECT * from ' . $this->db->base_prefix . "pro_sites where level = '". $key ."'";
				$sites = $this->db->get_results( $sql, OBJECT );
			}
			
			if( $include_main_site ) {
				$main_site = new stdClass();
				$main_site->blog_ID = 1;
				array_unshift( $sites, $main_site );
			}
			
			foreach( $sites as $site ){
				if( $site->blog_ID == 0 ) continue;
				if( ! is_pro_site( $site->blog_ID ) ) continue;
				
				$sql = "SELECT * from " . $this->db->base_prefix . "network_posts where BLOG_ID = '". $site->blog_ID ."' AND post_type = '". $post_type ."' LIMIT 0, " . $posts_per_page;
				$subsite_posts = $this->db->get_results( $sql, OBJECT );
				foreach( $subsite_posts as $subsite_post ){
					array_push( $posts, $subsite_post );
				}
			}
			
			if( $randomize ){
				shuffle( $posts );
			}
			
			$html = '<div class="pro_sites_posts">';
				$html .= '<ul>';
				foreach( $posts as $post ){
					$html .= '<li>';
						$html .= '<h3><a href="' . network_get_permalink( $post->BLOG_ID, $post->ID ) . '">'. $post->post_title .'</a></h3>';
					$html .= '</li>';
				}
				$html .= '</ul>';
			$html .= '</div>';
			
			return $html;
			
		}
		
	}
	
	add_action( 'plugins_loaded', 'check_pro_site' );
	function check_pro_site() {
		if( ! class_exists( 'ProSites' ) ){
			add_action( 'network_admin_notices', 'enable_pro_notice' );
			if( is_main_site() ){
				add_action( 'admin_notices', 'enable_pro_notice' );
			}
		}elseif( ! class_exists( 'postindexermodel' ) ){
			add_action( 'network_admin_notices', 'enable_pi_notice' );
			if( is_main_site() ){
				add_action( 'admin_notices', 'enable_pi_notice' );
			}
		}else{
			new Show_Pro_Posts();
		}
	}
	
	function enable_pro_notice() {
		?>
		<div class="error">
		    <p><?php echo "Please enable Pro Sites to use Show Pro Posts plugin."; ?></p>
		</div>
		<?php
	}
	
	function enable_pi_notice() {
		?>
		<div class="error">
		    <p><?php echo "Please enable Post Indexer to use Show Pro Posts plugin."; ?></p>
		</div>
		<?php
	}
}else{
	echo 'There is a compatibility issue, please run a conflict test.';
}
