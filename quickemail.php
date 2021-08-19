<?php

/**
 * @package QuickEmail
 */

/*
Plugin Name: Quick Email
Description: A quick solution for when you just need simple contact form entries.
Version: 1.0.0
Author: Dustin Stubbs
License GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class QuickEmail
{

	public $plugin;

	//Passing variable to __construct for classes
	function __construct() {
		$this->plugin = plugin_basename( __FILE__ );
		add_action ( 'init', array( $this, 'QuickEmailPostType' ) );
		add_action( 'init', array( $this, 'QuickEmailCategories') );
		add_action( 'init', array( $this, 'QuickEmailTags') );
	}

	function register() {
		// Admin panel css
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		// frontend css
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Add admin pages
		add_action( 'admin_menu', array( $this, 'AdminPages' ) );

		// Add plugin page links
		add_filter( "plugin_action_links_$this->plugin", array( $this, 'settingsLink') );

		// Post columns
		add_filter( "manage_entry_posts_columns", array( $this, 'QuickEmail_posts_columns') );

		add_action( "manage_entry_posts_custom_column", array( $this, 'QuickEmail_posts_column' ), 10, 2 );

		add_filter( 'manage_edit-entry_sortable_columns', array( $this, 'QuickEmail_sortable_columns' ) );

		add_action( 'pre_get_posts', array( $this, 'QuickEmail_orderby' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );

		add_shortcode( 'application_form', array( $this, 'application_code' ) );

		add_shortcode( 'contact_form', array( $this, 'contact_code' ) );

	}

	public function add_admin_page() {
		add_submenu_page(
			'QuickEmail_plugin', // Top level slug
			'QuickEmail Send', // Page title
			'Send Mail', // menu_title
			'manage_options', // capability
			'QuickEmail_plugin' // menu_slug
		);
	}

	public function application_code() {
		$all_options = get_option( 'quick_email_settings_option_name' );
		$formCode = $all_options['form_code_1'];

		// Handle posts
		if (!empty($_POST)){

			if ($_POST['contact_check'] != '' ) {
				return '<div style="border-left: solid 4px #e82727;background: #ffbfbf;" class="p-3">Sending Failed. Please contact site administrator.</div>';
			}

			if ($_POST['contact_email'] == '' ) {
				return '<div style="border-left: solid 4px #e82727;background: #ffbfbf;" class="p-3">Sending Failed. Please contact site administrator.</div>';
			}

			$my_post = array(
				'post_type'      => 'entry',
				'post_title'     => wp_strip_all_tags( $_POST['contact_name'] ),
				'post_status'    => 'publish'
			  );
			   
			// Insert the post into the database
			$entry_id = wp_insert_post( $my_post );

			$form_content = '<h3>Appointment:</h3>';

			foreach ($_POST as $form_field => $form_entry){
				if ($form_entry == 'on'){
				  $form_content .= '<br><strong>' . $form_field . '</strong>: Checked';
				}elseif ($form_field == 'contact_message') {
				  $form_content .= '<br><br><strong>Message:</strong><br><pre>' . $form_entry . '<pre>';
				}else{
				  $form_content .= '<br>' . $form_entry;
				}
			}

			add_post_meta( $entry_id, 'Entry_email', wp_strip_all_tags( $_POST['contact_email'] ), true );
			add_post_meta( $entry_id, 'Entry_content', $form_content, true );
			add_post_meta( $entry_id, 'Entry_status', 'Pending', true );

			if( $entry_id == true ) {
				return '<div style="border-left: solid 4px #29a746;background: #cdf1d5;" class="p-3">Email sent! I will follow up with you as soon as possible.</div>';
			  }else {
				return '<div style="border-left: solid 4px #e82727;background: #ffbfbf;" class="p-3">Sending Failed. Please contact site administrator.</div>';
			  }
		}

		// If no post, show form code
		return $formCode;
	}

	public function contact_code() {
		$all_options = get_option( 'quick_email_settings_option_name' );
		$formCode = $all_options['form_code_2'];

		// Handle posts
		if (!empty($_POST)){

			if ($_POST['contact_check'] != '' ) {
				return '<div style="border-left: solid 4px #e82727;background: #ffbfbf;" class="p-3">Sending Failed. Please contact site administrator.</div>';
			}

			if ($_POST['contact_email'] == '' ) {
				return '<div style="border-left: solid 4px #e82727;background: #ffbfbf;" class="p-3">Sending Failed. Please contact site administrator.</div>';
			}

			$my_post = array(
				'post_type'      => 'entry',
				'post_title'     => wp_strip_all_tags( $_POST['contact_name'] ),
				'post_status'    => 'publish'
			  );
			   
			// Insert the post into the database
			$entry_id = wp_insert_post( $my_post );

			$form_content = '<h3>Appointment:</h3>';

			foreach ($_POST as $form_field => $form_entry){
				if ($form_entry == 'on'){
				  $form_content .= '<br><strong>' . $form_field . '</strong>: Checked';
				}elseif ($form_field == 'contact_message') {
				  $form_content .= '<br><br><strong>Message:</strong><br><pre>' . $form_entry . '<pre>';
				}else{
				  $form_content .= '<br>' . $form_entry;
				}
			}

			add_post_meta( $entry_id, 'Entry_email', wp_strip_all_tags( $_POST['contact_email'] ), true );
			add_post_meta( $entry_id, 'Entry_content', $form_content, true );
			add_post_meta( $entry_id, 'Entry_status', 'Pending', true );

			if( $entry_id == true ) {
				return '<div style="border-left: solid 4px #29a746;background: #cdf1d5;" class="p-3">Email sent! I will follow up with you as soon as possible.</div>';
			  }else {
				return '<div style="border-left: solid 4px #e82727;background: #ffbfbf;" class="p-3">Sending Failed. Please contact site administrator.</div>';
			  }
		}

		// If no post, show form code
		return $formCode;
	}

	function QuickEmail_orderby( $query ) {
	  if( ! is_admin() || ! $query->is_main_query() ) {
	    return;
	  }

	  if ( 'status' === $query->get( 'orderby') ) {
	    $query->set( 'meta_key', 'Entry_status' );
	    $query->set( 'orderby', 'meta_value' );
	  }
	}

	public function QuickEmail_sortable_columns( $columns ) {
	  $columns['status'] = 'status';

	  return $columns;
	}

	public function QuickEmail_posts_column( $column, $post_id ) {

	  if ( 'email' == $column ) {
	    echo '<a target="_blank" href="mailto:' . get_post_meta( $post_id, 'Entry_email', true ) . '">' . get_post_meta( $post_id, 'Entry_email', true ) . ' <span class="dashicons dashicons-email"></span></a>';
	  }
	  if ( 'status' == $column ) {
	  	$Entriestatus = get_post_meta( $post_id, 'Entry_status', true );
	    
	  	switch ( $Entriestatus ) {
	  		case 'Sent':
	  			echo '<span style="color:#00b528"><strong>' . $Entriestatus . '</strong></span>';
	  			break;
  			case 'Pending':
  				echo '<span style="color:#0d039a"><strong>' . $Entriestatus . '</strong></span>';
  				break;
	  		default:
	  		echo '<strong>' . $Entriestatus . '</strong>';
	  	}

	  }
	  
	}

	public function QuickEmail_posts_columns( $columns ) {
	  $columns = array(
	        'cb' => $columns['cb'],
	        'title' => __( 'Title' ),
	        'email' => __( 'Email', 'entry' ),
	        'status' => __( 'Status', 'entry' ),
	      );
	  return $columns;
	}

	public function settingsLink( $links ) {
		$settingsLink = '<a href="tools.php?page=QuickEmail-settings">Settings</a>';
		$sendLink = '<a href="admin.php?page=QuickEmail_plugin">Send</a>';
		array_push( $links, $settingsLink );
		array_push( $links, $sendLink );
		return $links;
	}

	public function AdminPages() {
		add_menu_page( 'QuickEmail', 'Quick Email', 'manage_options', 'QuickEmail_plugin', array( $this, 'adminIndex' ), 'dashicons-email', 2 );
	}

	public function adminIndex() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';
	}

	function activate() {
		// generate a CPT for QuickEmail Entries
		$this->QuickEmailPostType();

		flush_rewrite_rules();
	}

	function deactivate() {
		flush_rewrite_rules();
	}

	public function QuickEmailPostType() {
		$labels = array(
		    'name'               => _x( 'Entries', 'post type general name' ),
		    'singular_name'      => _x( 'entry', 'post type singular name' ),
		    'add_new'            => _x( 'Add New', 'entry' ),
		    'add_new_item'       => __( 'Add New entry' ),
		    'edit_item'          => __( 'Edit entry' ),
		    'new_item'           => __( 'New entry' ),
		    'all_items'          => __( 'All Entries' ),
		    'view_item'          => __( 'View Entries' ),
		    'search_items'       => __( 'Search Entries' ),
		    'not_found'          => __( 'No Entries found' ),
		    'not_found_in_trash' => __( 'No Entries found in the Trash' ), 
		    'parent_item_colon'  => '’',
		    'menu_name'          => 'Entries'
		);

		$args = array(
			'public' => true,
			'label' => 'Entries',
			'hierarchical' => true,
			'labels' => $labels,
			'show_in_menu' =>
			'QuickEmail_plugin',
			'supports' => array(
				'title',
				'revisions'
			), 
			'has_archive'   => false, 
			'publicly_queryable'  => false
		);

		register_post_type( 'entry', $args );
	}

	public function QuickEmailCategories() {
		$labels = array(
		    'name'              => _x( 'entry Categories', 'taxonomy general name' ),
		    'singular_name'     => _x( 'entry Category', 'taxonomy singular name' ),
		    'search_items'      => __( 'Search entry Categories' ),
		    'all_items'         => __( 'All entry Categories' ),
		    'parent_item'       => __( 'Parent entry Category' ),
		    'parent_item_colon' => __( 'Parent entry Category:' ),
		    'edit_item'         => __( 'Edit entry Category' ), 
		    'update_item'       => __( 'Update entry Category' ),
		    'add_new_item'      => __( 'Add New entry Category' ),
		    'new_item_name'     => __( 'New entry Category' ),
		    'menu_name'         => __( 'entry Categories' ),
		);
		$args = array(
			'labels' => $labels,
		    'hierarchical' => true,
		    'show_ui' => true,
		    'show_admin_column' => true,
		    'show_in_menu' => true
		);
		register_taxonomy( 'Entry_category', 'entry', $args );
	}

	public function QuickEmailTags() {
		$labels = array(
		    'name'              => _x( 'entry Tags', 'taxonomy general name' ),
		    'singular_name'     => _x( 'entry Tag', 'taxonomy singular name' ),
		    'search_items'      => __( 'Search entry Tags' ),
		    'all_items'         => __( 'All entry Tags' ),
		    'parent_item'       => __( 'Parent entry Tag' ),
		    'parent_item_colon' => __( 'Parent entry Tag:' ),
		    'edit_item'         => __( 'Edit entry Tag' ), 
		    'update_item'       => __( 'Update entry Tag' ),
		    'add_new_item'      => __( 'Add New entry Tag' ),
		    'new_item_name'     => __( 'New entry Tag' ),
		    'menu_name'         => __( 'entry Tags' ),
		);
		$args = array( 'labels' => $labels );
		register_taxonomy( 'Entry_tag', 'entry', $args );
	}

	function enqueue() {
		//enqueue all of our scripts
		wp_enqueue_style( 'QuickEmailstyle', plugins_url( '/assets/style.css', __FILE__ ) );
		wp_enqueue_script( 'QuickEmailscript', plugins_url( '/assets/main.js', __FILE__ ) );
	}

}

if ( class_exists( 'QuickEmail' ) ) {
	$QuickEmail = new QuickEmail();
	$QuickEmail->register();
}

require_once plugin_dir_path( __FILE__ ) . 'templates/post-meta.php';

require_once plugin_dir_path( __FILE__ ) . 'templates/settings.php';

// activation
register_activation_hook( __FILE__, array( $QuickEmail, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $QuickEmail, 'deactivate' ) );



