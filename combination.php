<?php
/*
Plugin Name: Combination
Plugin URI: http://www.wpstriker.com/plugins
Description: Plugin for Combination
Version: 1.0
Author: wpstriker
Author URI: http://www.wpstriker.com
License: GPLv2
Copyright 2019 wpstriker (email : wpstriker@gmail.com)
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('COMBINATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COMBINATION_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once COMBINATION_PLUGIN_DIR . 'functions.php';
require_once COMBINATION_PLUGIN_DIR . 'combination_list_table.php';
require_once COMBINATION_PLUGIN_DIR . 'combination_manager.php';

global $is_combination_deleted;
$is_combination_deleted	= false;

if( ! class_exists( 'Custom_Combination' ) ) :

class Custom_Combination {
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'combination_scripts' ) );
		add_action('init', array($this,'combination_custom_post_type'));
		
		//add_action( 'acf/init', array( $this, 'combination_acf_op_init' ) );
		//add_shortcode( 'combination', array( $this, 'render_combination' ) );
		
		//add_action( 'wp_ajax_nopriv_fetch_colors_from_finishes', array( $this, 'fetch_colors_from_finishes' ) );
		//add_action( 'wp_ajax_fetch_colors_from_finishes', array( $this, 'fetch_colors_from_finishes' ) );
		//add_action( 'wp_ajax_nopriv_generate_new_image', array( $this, 'generate_new_image' ) );
		//add_action( 'wp_ajax_generate_new_image', array( $this, 'generate_new_image' ) );
	   
	   add_action( 'admin_menu', array( $this, 'combination_sub_menu_add' ), 99 );		 	
	   add_action( 'wp', array( $this, 'wpdebug' ) ); 
	   add_shortcode( 'all-combination', array( $this, 'render_all_combination' ) );
	   add_action( 'wp_head', array( $this, 'ajax_url' ) );	
	   
	   add_action( 'combination_category_add_form_fields', array ( $this, 'add_combination_category_image' ), 10, 2 );
	   add_action( 'created_combination_category', array ( $this, 'save_combination_category_image' ), 10, 2 );
	   add_action( 'combination_category_edit_form_fields', array ( $this, 'update_combination_category_image' ), 10, 2 );
	   add_action( 'edited_combination_category', array ( $this, 'updated_combination_category_image' ), 10, 2 );
	   add_action( 'admin_footer', array ( $this, 'add_combination_category_image_script' ) );
	 	   
	   //add_action('add_meta_boxes', array ( $this, 'add_combination_image_upload_metaboxes') );
	   //add_action('save_post', array ( $this, 'save_combination_image'), 10, 2 ); // save the custom fields
	   add_action( 'admin_footer', array ( $this, 'add_combination_image_script' ) );

	   add_action( 'wp_ajax_nopriv_generate_new_combination_image', array( $this, 'generate_new_combination_image' ) );
       add_action( 'wp_ajax_generate_new_combination_image', array( $this, 'generate_new_combination_image' ) );
	   
	   add_action( 'wp_ajax_nopriv_generate_new_varient_combination', array( $this, 'generate_new_varient_combination' ) );
       add_action( 'wp_ajax_generate_new_varient_combination', array( $this, 'generate_new_varient_combination' ) );
	   
	   add_action( 'admin_enqueue_scripts', array( $this, 'load_media' ) );
	   
	   add_action( 'wp_loaded', array( $this, 'maybe_cron_import_page' ) );
	    
	   add_action( 'wp_loaded', array( $this, 'maybe_cron_delete_page' ) );

	   add_action( 'add_meta_boxes', [$this, 'combination_metaboxes'] );

	   $cm = new Combination_Manager();
	   $cm->register_hooks();
	}

	public function combination_metaboxes() {
        $cm = new Combination_Manager();
        $cm->add_metabox_edit_post();
    }
		
	public function combination_scripts() {
		wp_enqueue_style( 'combination', COMBINATION_PLUGIN_URL . 'css/combination.css' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'combination', COMBINATION_PLUGIN_URL . 'js/combination.js', array(), '', true );
	}
	
	public function ajax_url() {
		?><script type="application/javascript">ajaxurl = "<?php echo admin_url( "admin-ajax.php" );?>";</script><?php
	}
	
	public function load_media() {
		wp_enqueue_media();
	}
	
	/*
	public function combination_acf_op_init() {
		if( function_exists( 'acf_add_options_page' ) ) {
			$option_page	= acf_add_options_page( array(
				'page_title'    => __( 'Combination' ),
				'menu_title'    => __( 'Combination' ),
				'menu_slug'     => 'combination-fields-settings',
				'post_id'		=> 'ach_combinations',
				'capability'    => 'manage_options',
				'redirect'      => false
			));
			
			$option_page	= acf_add_options_page( array( 
				'page_title' 	=> 'Finishes',
				'menu_title'	=> 'Finishes',
				'parent_slug'	=> 'combination-fields-settings',
				'menu_slug'		=> 'ach_finishes',
				'post_id'		=> 'ach_finishes',
			));
			
			$option_page	= acf_add_options_page( array( 
				'page_title' 	=> 'Models',
				'menu_title'	=> 'Models',
				'parent_slug'	=> 'combination-fields-settings',
				'menu_slug'		=> 'ach_models',
				'post_id'		=> 'ach_models',
			));
			
			$option_page	= acf_add_options_page( array( 
				'page_title' 	=> 'Category',
				'menu_title'	=> 'Category',
				'parent_slug'	=> 'combination-fields-settings',
				'menu_slug'		=> 'ach_categories',
				'post_id'		=> 'ach_categories',
			));
			
			$option_page	= acf_add_options_page( array( 
				'page_title' 	=> 'Colors',
				'menu_title'	=> 'Colors',
				'parent_slug'	=> 'combination-fields-settings',
				'menu_slug'		=> 'ach_colors',
				'post_id'		=> 'ach_colors',
			));
			
			$option_page	= acf_add_options_page( array( 
				'page_title' 	=> 'Collection',
				'menu_title'	=> 'Collection',
				'parent_slug'	=> 'combination-fields-settings',
				'menu_slug'		=> 'ach_collections',
				'post_id'		=> 'ach_collections',
			));
			
		}
	} 
	*/
	
	public function combination_sub_menu_add() {
	    // add_submenu_page( 'edit.php?post_type=combination', 'Import Category','Import category', 'administrator', 'category_imports', array( $this, 'category_imports_page' ) );		add_submenu_page( 'edit.php?post_type=combination', 'Import Collection', 'Import collection', 'administrator', 'collection_imports', array( $this, 'collection_imports_page' ) );
		// add_submenu_page( 'edit.php?post_type=combination', 'Import Model', 'Import model', 'administrator', 'model_imports', array( $this, 'model_imports_page' ) );
		// add_submenu_page( 'edit.php?post_type=combination', 'Import Combination', 'Import combination', 'administrator', 'combination_imports', array( $this, 'combination_imports_page' ) );

		// New pages
        $cm = new Combination_Manager();
        $cm->submenu_add_pages_cm();
	}

	function collection_imports_page() { 
	
		$is_imported		= false;
		$is_import_error	= false;
		
		if( isset( $_POST['collection_imports'] ) && $_POST['collection_imports'] != '' ) {
			
			if( $_FILES['collection_import_file'] && $_FILES['collection_import_file']['error'] == 0 ) {
								
				$collection_import_file	= $_FILES['collection_import_file']['tmp_name'];
						
				set_time_limit( 0 );
				ini_set( 'memory_limit', '2048M' );
				ini_set( 'post_max_size', '200M' );
				ini_set( 'upload_max_filesize', '200M' );
				ini_set( 'max_allowed_packet', '200M' ); 
				defined( 'WP_MEMORY_LIMIT' ) || define( 'WP_MEMORY_LIMIT', '2048M' );
								
				$this->collection_import_csv( $collection_import_file );
				$is_imported	= true;						
			} else {
				$is_import_error	= false;	
			}
		} 
				 
		?>
		 
		<div class="wrap">
            
            <?php if( $is_imported ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported successfully.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <?php if( $is_import_error ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported not imported please check csv file and format.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <h1>Collection Import</h1>
            
            <form method="post" enctype="multipart/form-data">
            	<h1></h1>
            	<input type="hidden" name="collection_import" value="1" />
	            <table class="form-table">
                
                    <tr>
                        <th scope="row"><label for="collection_import_file">Upload CSV</label></th>
                        <td><input type="file" name="collection_import_file" id="collection_import_file" accept=".csv" required /></td>
                    </tr>
                                      
                </table>
                
                <p class="submit"><input type="submit" name="collection_imports" id="submit" class="button button-primary" value="Save Changes"></p>
            </form> 
            
       	</div>
     	<?php
	}
	
	function category_imports_page() { 
	
		$is_imported		= false;
		$is_import_error	= false;
				
		if( isset( $_POST['category_imports'] ) && $_POST['category_imports'] != '' ) {
			if( $_FILES['category_import_file'] && $_FILES['category_import_file']['error'] == 0 ) {
								
				$category_import_file	= $_FILES['category_import_file']['tmp_name'];
						
				set_time_limit( 0 );
				ini_set( 'memory_limit', '2048M' );
				ini_set( 'post_max_size', '200M' );
				ini_set( 'upload_max_filesize', '200M' );
				ini_set( 'max_allowed_packet', '200M' ); 
				defined( 'WP_MEMORY_LIMIT' ) || define( 'WP_MEMORY_LIMIT', '2048M' );
								
				$this->category_import_csv( $category_import_file );
				$is_imported	= true;						
			} else {
				$is_import_error	= false;	
			}
		} 		
		?>
		<div class="wrap">
            
            <?php if( $is_imported ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported successfully.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <?php if( $is_import_error ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported not imported please check csv file and format.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <h1>Category Import</h1>
            
            <form method="post" enctype="multipart/form-data">
            	<h1></h1>
            	<input type="hidden" name="category_import" value="1" />
	            <table class="form-table">
                
                    <tr>
                        <th scope="row"><label for="category_import_file">Upload CSV</label></th>
                        <td><input type="file" name="category_import_file" id="category_import_file" accept=".csv" required /></td>
                    </tr>
                                      
                </table>
                
                <p class="submit"><input type="submit" name="category_imports" id="submit" class="button button-primary" value="Save Changes"></p>
            </form> 
            
       	</div>
     	<?php
	}
	
	function model_imports_page() { 
	
		$is_imported		= false;
		$is_import_error	= false;
		
		if( isset( $_POST['model_imports'] ) && $_POST['model_imports'] != '' ) {
			
			if( $_FILES['model_import_file'] && $_FILES['model_import_file']['error'] == 0 ) {
								
				$model_import_file	= $_FILES['model_import_file']['tmp_name'];
						
				set_time_limit( 0 );
				ini_set( 'memory_limit', '2048M' );
				ini_set( 'post_max_size', '200M' );
				ini_set( 'upload_max_filesize', '200M' );
				ini_set( 'max_allowed_packet', '200M' ); 
				defined( 'WP_MEMORY_LIMIT' ) || define( 'WP_MEMORY_LIMIT', '2048M' );
								
				$this->model_import_csv( $model_import_file );
				$is_imported	= true;						
			} else {
				$is_import_error	= false;	
			}
		} 
			 
		?>	 
		<div class="wrap">
            
            <?php if( $is_imported ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported successfully.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <?php if( $is_import_error ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported not imported please check csv file and format.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <h1>Model Import</h1>
            
            <form method="post" enctype="multipart/form-data">
            	<h1></h1>
            	<input type="hidden" name="model_import" value="1" />
	            <table class="form-table">
                
                    <tr>
                        <th scope="row"><label for="model_import_file">Upload CSV</label></th>
                        <td><input type="file" name="model_import_file" id="model_import_file" accept=".csv" required /></td>
                    </tr>
                                      
                </table>
                
                <p class="submit"><input type="submit" name="model_imports" id="submit" class="button button-primary" value="Save Changes"></p>
            </form>
            
       	</div>
     	<?php
	}
	
	function combination_imports_page() { 
	
		$is_imported		= false;
		$is_import_error	= false;
				
		if( isset( $_POST['combination_imports'] ) && $_POST['combination_imports'] != '' ) {
			
			if( $_FILES['combination_import_file'] && $_FILES['combination_import_file']['error'] == 0 ) {
								
				$combination_import_file	= $_FILES['combination_import_file']['tmp_name'];
						
				set_time_limit( 0 );
				ini_set( 'memory_limit', '2048M' );
				ini_set( 'post_max_size', '200M' );
				ini_set( 'upload_max_filesize', '200M' );
				ini_set( 'max_allowed_packet', '200M' ); 
				defined( 'WP_MEMORY_LIMIT' ) || define( 'WP_MEMORY_LIMIT', '2048M' );
								
				$this->combinations_import_csv( $combination_import_file );
				$is_imported	= true;						
			} else {
				$is_import_error	= false;	
			}
		} 
				
		?>
		<div class="wrap">
            
            <?php if( $is_imported ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported successfully.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <?php if( $is_import_error ){ ?>
            <div class="updated settings-error notice is-dismissible" style="margin: 0 0 20px; max-width: 845px;"> 
                <p><strong>CSV Imported not imported please check csv file and format.</strong></p>
                <button class="notice-dismiss" type="button">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
            <?php } ?>
            
            <h1>Combination Import</h1>
            
            <form method="post" enctype="multipart/form-data">
            	<h1></h1>
            	<input type="hidden" name="combination_import" value="1" />
	            <table class="form-table">
                
                    <tr>
                        <th scope="row"><label for="combination_import_file">Upload CSV</label></th>
                        <td><input type="file" name="combination_import_file" id="combination_import_file" accept=".csv" required /></td>
                    </tr>
                                      
                </table>
                
                <p class="submit"><input type="submit" name="combination_imports" id="submit" class="button button-primary" value="Save Changes"></p>
                
            </form> 
            
       	</div>
     	<?php
	}
	
	public function combinations_import_csv( $csv_file = array() ) {
		
		wp_cache_flush();
				
		$handle = fopen( $csv_file, 'r' );
		$i 		= 0;
		
		while ( ( $data = fgetcsv( $handle ) ) !== FALSE ) {
			
			$i++;
			if( $i == 1 ) {
				continue;
			}
			
			if( $data ) {				
				$combination= $data[0]; 
				$category	= $data[1];
				$collection	= $data[2];
				$model		= $data[3];
				$varient	= $data[4];
				$varient	= trim( $varient ) ? $varient : 'No Variant';
				$finish		= $data[5];
				$color		= $data[6]; 
				
				$this->_ilog( "" );
				$this->_ilog( "" );
				$this->_ilog( '===###*** combinations_import_csv ***###===' );
				$this->_ilog( 'combination: ' . $combination );
				$this->_ilog( 'category: ' . $category );
				$this->_ilog( 'collection: ' . $collection );
				$this->_ilog( 'model: ' . $model );
				$this->_ilog( 'varient: ' . $varient );
				$this->_ilog( 'finish: ' . $finish );
				$this->_ilog( 'color: ' . $color );
				
				$category_id					= false;
				$category_term_taxonomy_id		= false;
				$collection_id					= false;	
				$collection_term_taxonomy_id	= false;
				$model_id						= false;
				$model_term_taxonomy_id			= false;
				$varient_id						= false;
				$varient_term_taxonomy_id		= false;
				$finish_id						= false;
				$finish_term_taxonomy_id		= false;
				$color_id						= false;
				$color_term_taxonomy_id			= false;
				
				//category
				$category_term	= term_exists( $category, 'combination_category' ); 
				
				$this->_ilog( 'check category term_id: ' . ( isset( $category_term['term_id'] ) ? $category_term['term_id'] : false ) );
				$this->_ilog( 'check category term_taxonomy_id: ' . ( isset( $category_term['term_taxonomy_id'] ) ? $category_term['term_taxonomy_id'] : false ) );
				
				$category_id				= $category_term ? intval( $category_term['term_id'] ) : false;
				$category_term_taxonomy_id	= $category_term ? intval( $category_term['term_taxonomy_id'] ) : false;
				
				if( $category_id && ! empty( $collection ) ) {
					//collection
					$collection_term	= term_exists( $collection, 'combination_category', $category_id ); 
					
					$this->_ilog( 'check collection term_id: ' . ( isset( $collection_term['term_id'] ) ? $collection_term['term_id'] : false ) );
					$this->_ilog( 'check collection term_taxonomy_id: ' . ( isset( $collection_term['term_taxonomy_id'] ) ? $collection_term['term_taxonomy_id'] : false ) );
					
					$collection_id				= $collection_term ? intval( $collection_term['term_id'] ) : false;
					$collection_term_taxonomy_id= $collection_term ? intval( $collection_term['term_taxonomy_id'] ) : false;
					
					if( $collection_id && ! empty( $model ) ) {
						//model
						$model_term	= term_exists( $model, 'combination_category', $collection_id ); 
						
						$this->_ilog( 'check model term_id: ' . ( isset( $model_term['term_id'] ) ? $model_term['term_id'] : false ) );
						$this->_ilog( 'check model term_taxonomy_id: ' . ( isset( $model_term['term_taxonomy_id'] ) ? $model_term['term_taxonomy_id'] : false ) );
						
						$model_id				= $model_term ? intval( $model_term['term_id'] ) : false;
						$model_term_taxonomy_id	= $model_term ? intval( $model_term['term_taxonomy_id'] ) : false;
						
						if( $model_id && ! empty( $varient ) ) {
							//varient
							$varient_term	= term_exists( $varient, 'combination_category', $model_id ); 
							
							$this->_ilog( 'check varient term_id: ' . ( isset( $varient_term['term_id'] ) ? $varient_term['term_id'] : false ) );
							$this->_ilog( 'check varient term_taxonomy_id: ' . ( isset( $varient_term['term_taxonomy_id'] ) ? $varient_term['term_taxonomy_id'] : false ) );
							
							$varient_id					= $varient_term ? intval( $varient_term['term_id'] ) : false;
							$varient_term_taxonomy_id	= $varient_term ? intval( $varient_term['term_taxonomy_id'] ) : false;
							
							if( $varient_id && ! empty( $finish ) ) {
								// finish
								$finish_term	= term_exists( $finish, 'combination_category', $varient_id ); 
								
								$this->_ilog( 'check finish term_id: ' . ( isset( $finish_term['term_id'] ) ? $finish_term['term_id'] : false ) );
								$this->_ilog( 'check finish term_taxonomy_id: ' . ( isset( $finish_term['term_taxonomy_id'] ) ? $finish_term['term_taxonomy_id'] : false ) );
								
								$finish_id					= $finish_term ? intval( $finish_term['term_id'] ) : false;
								$finish_term_taxonomy_id	= $finish_term ? intval( $finish_term['term_taxonomy_id'] ) : false;
								
								if( $finish_id && ! empty( $color ) ) {
									// color
									$color_term	= term_exists( $color, 'combination_category', $finish_id ); 
									
									$this->_ilog( 'check color term_id: ' . ( isset( $color_term['term_id'] ) ? $color_term['term_id'] : false ) );
									$this->_ilog( 'check color term_taxonomy_id: ' . ( isset( $color_term['term_taxonomy_id'] ) ? $color_term['term_taxonomy_id'] : false ) );
									
									$color_id				= $color_term ? intval( $color_term['term_id'] ) : false;
									$color_term_taxonomy_id	= $color_term ? intval( $color_term['term_taxonomy_id'] ) : false;
																	
									if( $color_id ) {
										$check_combinations	= get_posts( array(
											  'post_status'	=> 'publish', 
											  'post_type'   => 'combination', 
											  'tax_query' 	=> array(
																	'taxonomy' => 'combination_category',
																	'field'    => 'term_id',
																	'terms'    => intval( $color_id ),   
																)
											)
										);
										
										//$this->_ilog( 'check_combination' );
										$this->_ilog( 'check_combinations ids: ' . $this->get_posts_string_ids( $check_combinations ) );
										
										if( $check_combinations ) {
											$this->_ilog( 'SKIP: ' . $combination . ' -- ' . $category . ' -- ' . $collection . ' -- ' . $model . ' -- ' . $varient . ' -- ' . $finish . ' -- ' . $color );
											continue;	// already exists	
										}											
									}
								}
							}
						}
					}
				}
				
				$this->_ilog( $combination . ' -- ' . $category . ' -- ' . $collection . ' -- ' . $model . ' -- ' . $varient . ' -- ' . $finish . ' -- ' . $color );
							
				$combination_args	= array(
					'post_title'	=> $combination . ' - ' . $category . ' - ' . $collection . ' - ' . $model . ' - ' . $varient . ' - ' . $finish . ' - ' . $color, 
					'post_status'   => 'publish', 
					'post_type'     => 'combination', 
				);
				
				// Insert the post into the database
				$combination_post_id	= wp_insert_post( $combination_args ); 
									
				if( ! is_wp_error( $combination_post_id ) ) {
					$this->_ilog( 'combination_post_id: ' . $combination_post_id );
					
					require_once(ABSPATH . 'wp-admin/includes/media.php');
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					require_once(ABSPATH . 'wp-admin/includes/image.php');
		
					// FOR PNG	
					if( trim( $data[7] ) ) {
						// First Find old image
						$this->_ilog( 'new combination PNG: ' . $data[7] );
						
						$png_image_id	= $this->find_image_by_name( $data[7] );
						$this->_ilog( 'new combination PNG find_image_by_name: ' . $png_image_id );
						
						if( $png_image_id ) {
							if( function_exists( 'update_field' ) ) {
								update_field( 'combination_png_image', $png_image_id, $combination_post_id );
							}
						} else {							
							$png_image_id	= $this->find_image_by_source_url( $data[7] );
							$this->_ilog( 'new combination PNG find_image_by_source_url: ' . $png_image_id );
							
							if( $png_image_id ) {
								if( function_exists( 'update_field' ) ) {
									update_field( 'combination_png_image', $png_image_id, $combination_post_id );
								}
							} else {
								$png_image_id	= media_sideload_image( $data[7], false, NULL, 'id' );
								
								if( ! is_wp_error( $png_image_id ) ) {		
									$this->_ilog( 'new combination PNG  media_sideload_image: ' . $png_image_id );
									if( function_exists( 'update_field' ) ) {
										update_field( 'combination_png_image', $png_image_id, $combination_post_id );
									}
								} else {
									$this->_ilog( 'new combination PNG media_sideload_image error' );
									$this->_ilog( $png_image_id->get_error_messages() );
								}
							}
						}
					} else {
						$this->_ilog( 'new combination image empty: ' . $data[7] );
					}
					
					// FOR JPG	
					if( trim( $data[8] ) ) {
						// First Find old image
						$this->_ilog( 'new combination JPG: ' . $data[8] );
						
						$jpg_image_id	= $this->find_image_by_name( $data[8] );
						$this->_ilog( 'new combination JPG find_image_by_name: ' . $jpg_image_id );
						
						if( $jpg_image_id ) {
							if( function_exists( 'update_field' ) ) {
								update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
							}
						} else {							
							$jpg_image_id	= $this->find_image_by_source_url( $data[8] );
							$this->_ilog( 'new combination JPG find_image_by_source_url: ' . $jpg_image_id );
							
							if( $jpg_image_id ) {
								if( function_exists( 'update_field' ) ) {
									update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
								}
							} else {
								$jpg_image_id	= media_sideload_image( $data[8], false, NULL, 'id' );
								
								if( ! is_wp_error( $jpg_image_id ) ) {		
									$this->_ilog( 'new combination JPG media_sideload_image: ' . $jpg_image_id );
									if( function_exists( 'update_field' ) ) {
										update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
									}
								} else {
									$this->_ilog( 'new combination JPG media_sideload_image error' );
									$this->_ilog( $jpg_image_id->get_error_messages() );
								}
							}
						}					
					} else {
						$this->_ilog( 'new combination image empty: ' . $data[8] );
					}
							
					/*$png_image_id	= media_sideload_image( $data[7], false, NULL, 'id' ); 
					
					if( ! is_wp_error( $png_image_id ) ) {
						//update_post_meta($combination_post_id, "combination_png_image", $png_image_id );
						
						if( function_exists( 'update_field' ) ) {
							update_field( 'combination_png_image', $png_image_id, $combination_post_id );
						}
					}
					
					$jpg_image_id	= media_sideload_image( $data[8], false, NULL, 'id' ); 		
					
					if( ! is_wp_error( $jpg_image_id ) ) {
						//set_post_thumbnail( $combination_post_id, $jpg_image_id );
						//update_post_meta( $combination_post_id, "combination_jpg_image", $jpg_image_id );
						
						if( function_exists( 'update_field' ) ) {
							update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
						}
					}*/
					
					// category		
					if( ! $category_id && ! empty( $category ) ) {
						$new_term	= wp_insert_term(
							$category,   		// the term 
							'combination_category' // the taxonomy
						);
						
						if( ! is_wp_error( $new_term ) ) {					
							$category_id				= $new_term['term_id'];
							$category_term_taxonomy_id	= $new_term['term_taxonomy_id'];
							
							$this->_ilog( 'combination category term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
							$this->_ilog( 'combination category term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
						} else {
							$this->_ilog( 'combination category term_error' );
							$this->_ilog( $new_term->get_error_messages() );
						}
					}
					
					if( $category_id ) {
						//wp_set_object_terms( $combination_post_id, intval( $category_id ), 'combination_category', true ); 
						
						$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' category_id: ' . intval( $category_id ) );
						
						// collection		
						if( ! $collection_id && ! empty( $collection ) ) {
							$new_term	= wp_insert_term(
								$collection,   		// the term 
								'combination_category', // the taxonomy
								array( 'parent' => intval( $category_id ) )
							);
							
							if( ! is_wp_error( $new_term ) ) {					
								$collection_id				= $new_term['term_id'];
								$collection_term_taxonomy_id= $new_term['term_taxonomy_id'];
								
								$this->_ilog( 'combination collection term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
								$this->_ilog( 'combination collection term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
							} else {
								$this->_ilog( 'combination collection term_error' );
								$this->_ilog( $new_term->get_error_messages() );
							}
						}
						
						if( $collection_id ) {
							//wp_set_object_terms( $combination_post_id, intval( $collection_id ), 'combination_category', true ); 
							
							$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' collection_id: ' . intval( $collection_id ) );
							
							// model		
							if( ! $model_id && ! empty( $model ) ) {
								$new_term	= wp_insert_term(
									$model,   		// the term 
									'combination_category', // the taxonomy
									array( 'parent' => intval( $collection_id ) )
								);
								
								if( ! is_wp_error( $new_term ) ) {					
									$model_id				= $new_term['term_id'];
									$model_term_taxonomy_id	= $new_term['term_taxonomy_id'];
									
									$this->_ilog( 'combination model term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
									$this->_ilog( 'combination model term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
								} else {
									$this->_ilog( 'combination model term_error' );
									$this->_ilog( $new_term->get_error_messages() );
								}
							}
							
							if( $model_id ) {
								//wp_set_object_terms( $combination_post_id, intval( $model_id ), 'combination_category', true ); 
								
								$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' model_id: ' . intval( $model_id ) );
								
								// varient		
								if( ! $varient_id && ! empty( $varient ) ) {
									$new_term	= wp_insert_term(
										$varient,   		// the term 
										'combination_category', // the taxonomy
										array( 'parent' => intval( $model_id ) )
									);
									
									if( ! is_wp_error( $new_term ) ) {					
										$varient_id					= $new_term['term_id'];
										$varient_term_taxonomy_id	= $new_term['term_taxonomy_id'];
										
										$this->_ilog( 'combination varient term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
										$this->_ilog( 'combination varient term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
									} else {
										$this->_ilog( 'combination varient term_error' );
										$this->_ilog( $new_term->get_error_messages() );
									}
								}
								
								if( $varient_id ) {
									//wp_set_object_terms( $combination_post_id, intval( $varient_id ), 'combination_category', true ); 
									
									$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' varient_id: ' . intval( $varient_id ) );
									
									// finish		
									if( ! $finish_id && ! empty( $finish ) ) {
										$new_term	= wp_insert_term(
											$finish,   		// the term 
											'combination_category', // the taxonomy
											array( 'parent' => intval( $varient_id ) )
										);
										
										if( ! is_wp_error( $new_term ) ) {					
											$finish_id					= $new_term['term_id'];
											$finish_term_taxonomy_id	= $new_term['term_taxonomy_id'];
											
											$this->_ilog( 'combination finish term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
											$this->_ilog( 'combination finish term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
										} else {
											$this->_ilog( 'combination finish term_error' );
											$this->_ilog( $new_term->get_error_messages() );
										}
									}
									
									if( $finish_id ) {
										//wp_set_object_terms( $combination_post_id, intval( $finish_id ), 'combination_category', true ); 
										
										$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' finish_id: ' . intval( $finish_id ) );
										
										// color		
										if( ! $color_id && ! empty( $color ) ) {
											$new_term	= wp_insert_term(
												$color,   		// the term 
												'combination_category', // the taxonomy
												array( 'parent' => intval( $finish_id ) )
											);
											
											if( ! is_wp_error( $new_term ) ) {					
												$color_id				= $new_term['term_id'];
												$color_term_taxonomy_id	= $new_term['term_taxonomy_id'];
												
												$this->_ilog( 'combination color term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
												$this->_ilog( 'combination color term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
											} else {
												$this->_ilog( 'combination color term_error' );
												$this->_ilog( $new_term->get_error_messages() );
											}
										}
										
										if( $color_id ) {
											//wp_set_object_terms( $combination_post_id, intval( $color_id ), 'combination_category', true );
											
											$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' color_id: ' . intval( $color_id ) ); 
											
											// Set all terms at once and discard previous.	
											wp_set_object_terms( 
												$combination_post_id, 
												array(
													intval( $category_id ), 
													intval( $collection_id ), 
													intval( $model_id ), 
													intval( $varient_id ), 
													intval( $finish_id ), 
													intval( $color_id ), 
												),
												'combination_category', 
												false 
												);
																										
										}																
									}															
								}													
							}												
						}												
					}				 					
				} else {
					$this->_ilog( 'new combination_post_id error' );
					$this->_ilog( $new_term->get_error_messages() );
				}	
			}
		}
	}  
	
	public function category_import_csv( $csv_file = array() ) {
		
		wp_cache_flush();
		
		$handle = fopen( $csv_file, 'r' );
		$i 		= 0;
		
		while ( ( $data = fgetcsv( $handle ) ) !== FALSE ) {
			
			$i++;
			
			if( $i == 1 ) {
				continue;
			}
			 
			if( $data ) {
				 
				// category
				$category	= $data[1];
				
				delete_option( 'combination_category_children' );
				
				$this->_ilog( "\n" );
				$this->_ilog( "\n" );
				$this->_ilog( '===###*** category_import_csv ***###===' );
				$this->_ilog( 'category: ' . $category );
				 
				$combination_category_term	= term_exists( $category, 'combination_category' );				
				
				$this->_ilog( 'category term_id: ' . ( isset( $combination_category_term['term_id'] ) ? $combination_category_term['term_id'] : false ) );
				$this->_ilog( 'category term_taxonomy_id: ' . ( isset( $combination_category_term['term_taxonomy_id'] ) ? $combination_category_term['term_taxonomy_id'] : false ) );
				
				$combination_category_term_id			= $combination_category_term ? $combination_category_term['term_id'] : false;
				$combination_category_term_taxonomy_id	= $combination_category_term ? $combination_category_term['term_taxonomy_id'] : false;
							 
				if( ! $combination_category_term_id ) {
					$new_term	= wp_insert_term(
						$category,   			// the term 
						'combination_category' 	// the taxonomy 
					);
					
					if( ! is_wp_error( $new_term ) ) {
						$this->_ilog( 'new category term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
						$this->_ilog( 'new category term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
					} else {
						$this->_ilog( 'new category term_error' );
						$this->_ilog( $new_term->get_error_messages() );
					}
				}	
			}
		} 	
	}   
	
	public function collection_import_csv( $csv_file = array() ) {
		
		wp_cache_flush();
		
		$handle = fopen( $csv_file, 'r' );
		$i 		= 0;
		
		while ( ( $data = fgetcsv( $handle ) ) !== FALSE ) {
			
			$i++;
			
			if( $i == 1 ) {
				continue;
			}
			 
			if( $data ) {
				$category	= $data[1]; 
				$collection	= $data[3]; 
				
				delete_option( 'combination_category_children' );
				
				$collection_category_term_id			= false;
				$collection_category_term_taxonomy_id	= false;
				
				$this->_ilog( "\n" );
				$this->_ilog( "\n" );
				$this->_ilog( '===###*** collection_import_csv ***###===' );
				$this->_ilog( 'category: ' . $category );
				$this->_ilog( 'collection: ' . $collection );
				
				// category
				$collection_category_term	= term_exists( $category, 'combination_category' );
				
				$this->_ilog( 'category term_id: ' . ( isset( $collection_category_term['term_id'] ) ? $collection_category_term['term_id'] : false ) );
				$this->_ilog( 'category term_taxonomy_id: ' . ( isset( $collection_category_term['term_taxonomy_id'] ) ? $collection_category_term['term_taxonomy_id'] : false ) );
				
				$collection_category_term_id			= $collection_category_term ? $collection_category_term['term_id'] : false;
				$collection_category_term_taxonomy_id	= $collection_category_term ? $collection_category_term['term_taxonomy_id'] : false;
								
				if( ! $collection_category_term_id ) {					
					$new_term	= wp_insert_term(
						$category,   		// the term 
						'combination_category' // the taxonomy
					); 
					
					if( ! is_wp_error( $new_term ) ) {
						$collection_category_term_id			= $new_term['term_id'];
						$collection_category_term_taxonomy_id	= $new_term['term_taxonomy_id'];
						
						$this->_ilog( 'new category term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
						$this->_ilog( 'new category term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
					} else {
						$this->_ilog( 'new category term_error' );
						$this->_ilog( $new_term->get_error_messages() );
					}				
				} 
				
				$this->_ilog( 'before collection collection_category_term_id: ' . $collection_category_term_id );
				$this->_ilog( 'before collection collection_category_term_taxonomy_id: ' . $collection_category_term_taxonomy_id );
				 
				// collection 			 
				if( $collection_category_term_id && ! empty( $collection ) ) {					
					$collection_term	= term_exists( $collection, 'combination_category', intval( $collection_category_term_id ) );
					
					$this->_ilog( 'collection term_id: ' . ( isset( $collection_term['term_id'] ) ? $collection_term['term_id'] : false ) );
					$this->_ilog( 'collection term_taxonomy_id: ' . ( isset( $collection_term['term_taxonomy_id'] ) ? $collection_term['term_taxonomy_id'] : false ) );
					
					$collection_term_id				= $collection_term ? $collection_term['term_id'] : false;
					$collection_term_taxonomy_id	= $collection_term ? $collection_term['term_taxonomy_id'] : false;
					
					if( ! $collection_term_id ) {					
						$new_term	= wp_insert_term(
							$collection,   		// the term 
							'combination_category', // the taxonomy
							array( 'parent' => intval( $collection_category_term_id ) )
						); 
						
						if( ! is_wp_error( $new_term ) ) {						
							$collection_term_id				= $new_term['term_id'];								
							$collection_term_taxonomy_id	= $new_term['term_taxonomy_id'];						
							
							$this->_ilog( 'new collection term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
							$this->_ilog( 'new collection term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
						
							// Insert Image
							if( trim( $data[4] ) ) {							
								// First Find old image
								$this->_ilog( 'new collection image: ' . $data[4] );
								
								$collection_image_id	= $this->find_image_by_name( $data[4] );
								$this->_ilog( 'new collection find_image_by_name: ' . $collection_image_id );
								
								if( $collection_image_id ) {
									update_term_meta( $collection_term_id, 'category-image-id', $collection_image_id );  			
								} else {							
									$collection_image_id	= $this->find_image_by_source_url( $data[4] );
									$this->_ilog( 'new collection find_image_by_source_url: ' . $collection_image_id );
									
									if( $collection_image_id ) {
										update_term_meta( $collection_term_id, 'category-image-id', $collection_image_id );  			
									} else {
										$collection_image_id 	= media_sideload_image( $data[4], false, NULL, 'id' ); 
										
										if( ! is_wp_error( $collection_image_id ) ) {		
											$this->_ilog( 'new collection media_sideload_image: ' . $collection_image_id );
											update_term_meta( $collection_term_id, 'category-image-id', $collection_image_id );  						 
										} else {
											$this->_ilog( 'new collection media_sideload_image error' );
											$this->_ilog( $collection_image_id->get_error_messages() );
										}
									}
								}							
							} else {
								$this->_ilog( 'new collection image empty: ' . $data[4] );
							}
						} else {
							$this->_ilog( 'new collection term_error' );
							$this->_ilog( $new_term->get_error_messages() );
						}				
					} else {
						// Check image exists or not?
						/*$collection_image_id	= get_term_meta( $collection_term_id, 'category-image-id', true );
						
						if( ! $collection_image_id ) {
							// Insert Image
							
							// Try WP Upload Method
							$collection_image_id 	= media_sideload_image( $data[4], false, NULL, 'id' );
								
							$this->_ilog( 'collection_image_id 2' );
							$this->_ilog( $collection_image_id );
							
							if( ! is_wp_error( $collection_image_id ) ) {
								update_term_meta( $collection_term_id, 'category-image-id', $collection_image_id );  			
							} else {
								// Try Manual Upload Method
								$collection_image_id	= $this->manual_image_download( $data[4] );							 
								
								$this->_ilog( 'collection_image_id 3' );
								$this->_ilog( $collection_image_id );
														
								if( ! is_wp_error( $collection_image_id ) ) {
									update_term_meta( $collection_term_id, 'category-image-id', $collection_image_id );  		
								} 	
							}								
						}*/
					}
				}	 							
			}
		} 	
	}    
	
	public function model_import_csv( $csv_file = array() ) {		
		
		wp_cache_flush();
		
		$handle = fopen( $csv_file, 'r' );
		$i 		= 0;
		
		while ( ( $data = fgetcsv( $handle ) ) !== FALSE ) {			
			$i++;
			
			if( $i == 1 ) {
				continue;
			}
			 	 
			if( $data ) { 				
				$category	= $data[1]; 
				$collection	= $data[3]; 
				$model		= $data[5]; 
				
				delete_option( 'combination_category_children' );
				
				$collection_category_term_id			= false;
				$collection_category_term_taxonomy_id	= false;				
				$collection_term_id						= false;
				$collection_term_taxonomy_id			= false;
				
				$this->_ilog( "\n" );
				$this->_ilog( "\n" );
				$this->_ilog( '===###*** model_import_csv ***###===' );
				$this->_ilog( 'category: ' . $category );
				$this->_ilog( 'collection: ' . $collection );
				$this->_ilog( 'model: ' . $model );
				
				// category
				$collection_category_term	= term_exists( $category, 'combination_category' );
				
				$this->_ilog( 'category term_id: ' . ( isset( $collection_category_term['term_id'] ) ? $collection_category_term['term_id'] : false ) );
				$this->_ilog( 'category term_taxonomy_id: ' . ( isset( $collection_category_term['term_taxonomy_id'] ) ? $collection_category_term['term_taxonomy_id'] : false ) );
				
				$collection_category_term_id			= $collection_category_term ? $collection_category_term['term_id'] : false;
				$collection_category_term_taxonomy_id	= $collection_category_term ? $collection_category_term['term_taxonomy_id'] : false;
				
				if( ! $collection_category_term_id ) {					
					$new_term	= wp_insert_term(
						$category,   		// the term 
						'combination_category' // the taxonomy
					); 
					
					if( ! is_wp_error( $new_term ) ) {
						$collection_category_term_id			= $new_term['term_id'];
						$collection_category_term_taxonomy_id	= $new_term['term_taxonomy_id'];				
						
						$this->_ilog( 'new category term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
						$this->_ilog( 'new category term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
					} else {
						$this->_ilog( 'new category term_error' );
						$this->_ilog( $new_term->get_error_messages() );
					}			
				}
				 
				$this->_ilog( 'before collection collection_category_term_id: ' . $collection_category_term_id );
				 
				// collection 			 
				if( $collection_category_term_id && ! empty( $collection )  ) {					
					$collection_term	= term_exists( $collection, 'combination_category', intval( $collection_category_term_id ) );
					
					$this->_ilog( 'collection term_id: ' . ( isset( $collection_term['term_id'] ) ? $collection_term['term_id'] : false ) );
					$this->_ilog( 'collection term_taxonomy_id: ' . ( isset( $collection_term['term_taxonomy_id'] ) ? $collection_term['term_taxonomy_id'] : false ) );
					
					$collection_term_id				= $collection_term ? $collection_term['term_id'] : false;
					$collection_term_taxonomy_id	= $collection_term ? $collection_term['term_taxonomy_id'] : false;
					
					if( ! $collection_term_id ) {					
						$new_term	= wp_insert_term(
							$collection,   		// the term 
							'combination_category', // the taxonomy
							array( 'parent' => intval( $collection_category_term_id ) )
						); 
						
						if( ! is_wp_error( $new_term ) ) {						
							$collection_term_id				= $new_term['term_id'];		
							$collection_term_taxonomy_id	= $new_term['term_taxonomy_id'];		
							
							$this->_ilog( 'new collection term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
							$this->_ilog( 'new collection term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );												
						} else {
							$this->_ilog( 'new collection term_error' );
							$this->_ilog( $new_term->get_error_messages() );
						}					
					}			
				}
				
				$this->_ilog( 'before model collection_term_id: ' . $collection_term_id );
				
				// model
				if( $collection_term_id && ! empty( $model )  ) {
					$model_term	= term_exists( $model, 'combination_category', intval( $collection_term_id ) );
					
					$this->_ilog( 'model term_id: ' . ( isset( $model_term['term_id'] ) ? $model_term['term_id'] : false ) );
					$this->_ilog( 'model term_taxonomy_id: ' . ( isset( $model_term['term_taxonomy_id'] ) ? $model_term['term_taxonomy_id'] : false ) );
					
					$model_term_id			= $model_term ? $model_term['term_id'] : false;
					$model_term_taxonomy_id	= $model_term ? $model_term['term_taxonomy_id'] : false;
					
					if( ! $model_term_id ) {					
						$new_term	= wp_insert_term(
							$model,   		// the term 
							'combination_category', // the taxonomy
							array( 'parent' => intval( $collection_term_id ) )
						); 
						
						if( ! is_wp_error( $new_term ) ) {
							$model_term_id			= $new_term['term_id'];
							$model_term_taxonomy_id	= $new_term['term_taxonomy_id'];
							
							$this->_ilog( 'new model term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
							$this->_ilog( 'new model term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );			
							
							// Insert Image
							if( trim( $data[6] ) ) {		
								// First Find old image
								$this->_ilog( 'new model image: ' . $data[6] );
								
								$model_image_id	= $this->find_image_by_name( $data[6] );
								$this->_ilog( 'new model find_image_by_name: ' . $model_image_id );
								
								if( $model_image_id ) {
									update_term_meta( $model_term_id, 'category-image-id', $model_image_id );   			
								} else {							
									$model_image_id	= $this->find_image_by_source_url( $data[6] );
									$this->_ilog( 'new model find_image_by_source_url: ' . $model_image_id );
									
									if( $model_image_id ) {
										update_term_meta( $model_term_id, 'category-image-id', $model_image_id );  	
									} else {
										$model_image_id = media_sideload_image( $data[6], false, NULL, 'id' ); 
										
										if( ! is_wp_error( $model_image_id ) ) {		
											$this->_ilog( 'new model media_sideload_image: ' . $model_image_id );
											update_term_meta( $model_term_id, 'category-image-id', $model_image_id );  				 
										} else {
											$this->_ilog( 'new model media_sideload_image error' );
											$this->_ilog( $model_image_id->get_error_messages() );
										}
									}
								}
							} else {
								$this->_ilog( 'new model image empty: ' . $data[6] );
							}
							
							/*$model_image_id = media_sideload_image( $data[6], false, NULL, 'id' );  
							
							if( ! is_wp_error( $model_image_id ) ) {
								update_term_meta( $model_term_id, 'category-image-id', $model_image_id );  
							}*/
													
							if( isset( $data[7] ) && $data[7] != '' && sanitize_title( $data[7] ) != sanitize_title( 'To be filled' ) ){
								update_term_meta( $model_term_id, 'model_pdf_file', $data[7] );   
							} 
							
							if( isset( $data[8] ) && $data[8] != '' && sanitize_title( $data[8] ) != sanitize_title( 'To be filled' ) ){
								update_term_meta( $model_term_id, 'model_dwg_file', $data[8] );   
							} 
							
							if( isset( $data[9] ) && $data[9] != '' && sanitize_title( $data[9] ) != sanitize_title( 'To be filled' ) ){
								update_term_meta( $model_term_id, 'model_stp_file', $data[9] );   
							}
							
							if( isset( $data[10] ) && $data[10] != '' && sanitize_title( $data[10] ) != sanitize_title( 'To be filled' ) ){
								update_term_meta( $model_term_id, 'model_stl_file', $data[10] );   
							}
							
							if( isset( $data[11] ) && $data[11] != '' && sanitize_title( $data[11] ) != sanitize_title( 'To be filled' ) ){
								update_term_meta( $model_term_id, 'model_revit_file', $data[11] );   
							}					
						} else {
							$this->_ilog( 'new model term_error' );
							$this->_ilog( $new_term->get_error_messages() );
						}
					} else {
						
						// Check image exists or not?
						
						/*$model_image_id	= get_term_meta( $model_term_id, 'category-image-id', true );
						
						$this->_ilog( 'model_image_id 1 ' );
						$this->_ilog( $model_image_id );
						
						if( ! $model_image_id ) {
							// Insert Image
							
							// Try WP Upload Method
							$model_image_id = media_sideload_image( $data[6], false, NULL, 'id' ); 
							
							$this->_ilog( 'model_image_id 2' );
							$this->_ilog( $model_image_id );
							
							if( ! is_wp_error( $model_image_id ) ) {
								update_term_meta( $model_term_id, 'category-image-id', $model_image_id );  
							} else {
								// Try Manaul Upload Method
								$model_image_id	= $this->manual_image_download( $data[6] );
								 
								$this->_ilog( 'model_image_id 3' );
								$this->_ilog( $model_image_id );
														
								if( ! is_wp_error( $model_image_id ) ) {
									update_term_meta( $model_term_id, 'category-image-id', $model_image_id );  
								} 	
							}
						}
												
						if( isset( $data[7] ) && $data[7] != '' && sanitize_title( $data[7] ) != sanitize_title( 'To be filled' ) ){
							update_term_meta( $model_term_id, 'model_pdf_file', $data[7] );   
						} 
						
						if( isset( $data[8] ) && $data[8] != '' && sanitize_title( $data[8] ) != sanitize_title( 'To be filled' ) ){
							update_term_meta( $model_term_id, 'model_dwg_file', $data[8] );   
						} 
						
						if( isset( $data[9] ) && $data[9] != '' && sanitize_title( $data[9] ) != sanitize_title( 'To be filled' ) ){
							update_term_meta( $model_term_id, 'model_stp_file', $data[9] );   
						}
						
						if( isset( $data[10] ) && $data[10] != '' && sanitize_title( $data[10] ) != sanitize_title( 'To be filled' ) ){
							update_term_meta( $model_term_id, 'model_stl_file', $data[10] );   
						}
						
						if( isset( $data[11] ) && $data[11] != '' && sanitize_title( $data[11] ) != sanitize_title( 'To be filled' ) ){
							update_term_meta( $model_term_id, 'model_revit_file', $data[11] );   
						}*/
					}		
				}			
			}
		} 	
	}  
	   	
	public function combination_custom_post_type() {
		register_post_type('combination',	
			   array(
				   'labels'      => array(
					   'name'          		=> __('Combination', 'textdomain'),
					   'singular_name' 		=> __('Combination', 'textdomain'),
					   'menu_name'          => __( 'Combinations', 'text_domain' ),
					   'name_admin_bar'     => __( 'Combinations', 'text_domain' ),
					   'archives'           => __( 'Combination Archives', 'text_domain' ),
					   'attributes'         => __( 'Combination Attributes', 'text_domain' ),
					   'parent_item_colon'  => __( 'Parent Combination:', 'text_domain' ),
					   'all_items'    		=> __( 'All Combinations', 'text_domain' ),
					   'add_new_item'   	=> __( 'New Combination', 'text_domain' ),
					   'add_new'            => __( 'Add Combination', 'text_domain' ),
					   'new_item'       	=> __( 'New Combination', 'text_domain' ),
					   'edit_item'      	=> __( 'Edit Combination', 'text_domain' ),
					   'update_item'    	=> __( 'Update Combination', 'text_domain' ),
					   'view_item'      	=> __( 'View Combination', 'text_domain' ),
					   'view_item'      	=> __( 'View Combination', 'text_domain' ),
					   'not_found'          => __( 'Not found', 'text_domain' ),
					   'not_found_in_trash' => __( 'Not found in Trash', 'text_domain' ),
					   'rewrite' 			=> array('slug' => '/combination/name'),
				   ),
				   'public'      => true,
				   'has_archive' => true,
				   'rewrite' 		=> array( 'slug' => 'combination' ),
				   'supports'    => array( 'title', 'editor', 'thumbnail' ),
				   'menu_icon'   => 'dashicons-grid-view'
			   )
		);
		
		// Add new taxonomy, NOT hierarchical (like tags)
		register_taxonomy( 'combination_category', 'combination', array(
			'hierarchical' 		=> true,
			'show_ui' 			=> true,
			'query_var' 		=> true,
			'show_admin_column' => true,
			'rewrite' 			=> array( 
										'slug' 			=> 'combination_category',
										'with_front'   	=> false,
										'hierarchical' 	=> true, 
									),
		));	 
		 
	}  
	
	public function add_combination_image_upload_metaboxes() {
		add_meta_box( 'combination_image_upload', 'File Upload', array( $this, 'combination_image_upload' ), 'combination', 'normal', 'default' );
	}
	
	public function combination_image_upload() { ?>
     	<div class="form_row" style="overflow: hidden;">
       	    
           <?php 
		   
		   $png_post_image_id = get_post_meta( get_the_ID(), 'combination_png_image', true );
		   $jpg_post_image_id = get_post_meta( get_the_ID(), 'combination_jpg_image', true );
		   
		   if( $png_post_image_id || $jpg_post_image_id ) {
		    	
				$png_image_id = wp_get_attachment_image_src( $png_post_image_id, 'thumbnail' );
				$jpg_image_id = wp_get_attachment_image_src( $jpg_post_image_id, 'thumbnail' );
 				 	  
				?>
					  
				<div class="">
                	<div class="" style="width:40%; float:left; margin-right: 50px; overflow: hidden;">
                    	
							<?php 	echo '<h4>PNG Image</h4>';
									echo '<a href="#" class="button button-secondary combination_image_upload" id="png_image_upload"><img src="' . $png_image_id[0] . '" /></a>
									  <a href="#" class="combination_image_upload_rmv">Remove image</a>
									  <input type="hidden" name="combination_png_image" id="combination_png_image" value="' . $png_post_image_id . '">'; 
							?>
                    </div>
                    <div class="" style="width:40%; float:left; margin-right: 50px; overflow: hidden;">
                        
							<?php 	echo '<h4>JPG Image</h4>';
									echo '<a href="#" class="button button-secondary combination_image_upload" id="jpg_image_upload"><img src="' . $jpg_image_id[0] . '" /></a>
									  <a href="#" class="combination_image_upload_rmv">Remove image</a>
									  <input type="hidden" name="combination_jpg_image" id="combination_jpg_image" value="' . $jpg_post_image_id . '">'; 
									
							?>
                        
                    </div>
                </div>
			 
			<?php } else {  ?>
            	
                <div class="">
                	<div class="" style="width:40%; float:left; margin-right: 50px; overflow: hidden;">

                    	
							<?php 	echo '<h4>PNG Image</h4>';
									echo '<a href="#" class="button button-secondary combination_image_upload" id="png_image_upload">Upload image</a>
									  <a href="#" class="combination_image_upload_rmv" style="display:none">Remove image</a>
									  <input type="hidden" name="combination_png_image" id="combination_png_image">'; 
							?>
                    </div>
                    <div class="" style="width:40%; float:left; margin-right: 50px; overflow: hidden;">
                        
							<?php 	echo '<h4>JPG Image</h4>';
									echo '<a href="#" class="button button-secondary combination_image_upload" id="jpg_image_upload">Upload image</a>
									  <a href="#" class="combination_image_upload_rmv" style="display:none">Remove image</a>
									  <input type="hidden" name="combination_jpg_image" id="combination_jpg_image">'; 
									
							?>
                        
                    </div>
                </div>
            	<?php
			 
			}
		    
           ?> 
       </div>
	 <?php
	 }
	  
	
	//Saving the file
	public function save_combination_image($post_id, $post) { 
		   
		
	  	 if( $_POST['combination_png_image'] ){ 
		  	update_post_meta($post_id, "combination_png_image", $_POST['combination_png_image'] );
		 }	 
		 
		 if( $_POST['combination_jpg_image'] ){ 
		  	update_post_meta($post_id, "combination_jpg_image", $_POST['combination_jpg_image'] );
		 }	 
	} 
	
	public function add_combination_image_script() { ?>
		<script>
			jQuery(function($){
 
			// on upload button click
			$('body').on( 'click', '.combination_image_upload', function(e){
		 
				e.preventDefault();
				var button_id = $(this).attr("id");
				 
				var button = $(this),
				custom_uploader = wp.media({
					title: 'Insert image',
					library : {
						// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
						type : 'image'
					},
					button: {
						text: 'Use this image' // button label text
					},
					multiple: false
				}).on('select', function() { // it also has "open" and "close" events
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					button.html('<img src="' + attachment.url + '">').next().show();
					if( button_id == "png_image_upload" ) { 
						jQuery("#combination_png_image").val(attachment.id);
					} 
					if( button_id == "jpg_image_upload" ) {
						jQuery("#combination_jpg_image").val(attachment.id);
					} 
				}).open();
		 		
			});
		 
			// on remove button click
			$('body').on('click', '.combination_image_upload_rmv', function(e){
		 
				e.preventDefault();
		 
				var button = $(this);
				button.next().val(''); // emptying the hidden field
				button.hide().prev().html('Upload image');
			});
			
			
		 
		});
		</script>
        
        <!-- Model File Upload ----->
        <script>
			jQuery(function($){
 
			// on upload button click
			$('body').on( 'click', '.model_file_upload', function(e){
		 
				e.preventDefault();
				var button_id = $(this).attr("id");
				 
				var button = $(this),
				custom_uploader = wp.media({
					title: 'Insert image',
					library : {
						// uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
						//type : 'image'
					},
					button: {
						text: 'Use this image' // button label text
					},
					multiple: false
				}).on('select', function() { // it also has "open" and "close" events
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					//button.html('<img src="' + attachment.url + '">').next().show();
					if( button_id == "model_pdf_upload" ) {
						jQuery("#model_pdf_file").val(attachment.url);
					}
					if( button_id == "model_dwg_upload" ) {
						jQuery("#model_dwg_file").val(attachment.url);
					}
					if( button_id == "model_stp_upload" ) {
						jQuery("#model_stp_file").val(attachment.url);
					}
					if( button_id == "model_stl_upload" ) {
						jQuery("#model_stl_file").val(attachment.url);
					}
					if( button_id == "model_revit_upload" ) {
						jQuery("#model_revit_file").val(attachment.url);
					}
				}).open();
		 		
			});
		 
			// on remove button click
			$('body').on('click', '.model_file_rmv', function(e){
		 
				e.preventDefault();
		 		
				var remove_button_id = $(this).attr("id");
				
				if( remove_button_id == "model_pdf_remove" ) {
					jQuery("#model_pdf_file").val('');
				}
				if( remove_button_id == "model_dwg_remove" ) {
					jQuery("#model_dwg_file").val('');
				}
				if( remove_button_id == "model_stp_remove" ) {
					jQuery("#model_stp_file").val('');
				}
				if( remove_button_id == "model_stl_remove" ) {
					jQuery("#model_stl_file").val('');
				}
				if( remove_button_id == "model_revit_remove" ) {
					jQuery("#model_revit_file").val('');
				}
				
				var button = $(this);
				button.next().val(''); // emptying the hidden field
				 
			});
			
			
		 
		});
		</script>
        <?php
	}
	
	public function render_all_combination( $atts = array() ){
		$content	= "";
		
		ob_start();	
		
		$is_debug	= false;
		if( isset( $_GET['_debug'] ) ) {
			$is_debug	= true;	
		}
		
		$level_1_combination_category	= get_terms( array(
			'taxonomy' 		=> 'combination_category',
			'hide_empty'	=> true,
			'parent'		=> 0,
			'orderby'		=> 'term_id'
		) );
		
		$level_2_terms	= array();
		
		foreach( $level_1_combination_category as $term ) {
			$child_terms	= get_terms( array(
				'taxonomy' 		=> 'combination_category',
				'hide_empty'	=> true,
				'parent'		=> $term->term_id 
			) );
			
			if( $child_terms ) {
				$level_2_terms	= array_merge( $level_2_terms, $child_terms ); 				
			} 
		}
		
		/*$level_3_terms	= array();
		foreach( $level_2_terms as $term ) {
			$child_terms	= get_terms( array(
				'taxonomy' 		=> 'combination_category',
				'hide_empty'	=> false,
				'parent'		=> $term->term_id 
			) );
			
			if( $child_terms ) {
				$level_3_terms	= array_merge( $level_3_terms, $child_terms ); 				
			}
		}*/
		
		/*$level_4_terms	= array();
		foreach( $level_3_terms as $term ) {
			$child_terms	= get_terms( array(
				'taxonomy' 		=> 'combination_category',
				'hide_empty'	=> false,
				'parent'		=> $term->term_id 
			) );
			
			if( $child_terms ) {
				$level_4_terms	= array_merge( $level_4_terms, $child_terms ); 				
			}
		}*/
		
		/*$level_5_terms	= array();
		foreach( $level_4_terms as $term ) {
			$child_terms	= get_terms( array(
				'taxonomy' 		=> 'combination_category',
				'hide_empty'	=> false,
				'parent'		=> $term->term_id 
			) );
			
			if( $child_terms ) {
				$level_5_terms	= array_merge( $level_5_terms, $child_terms ); 				
			}
		}*/
		
		/*$level_6_terms	= array();
		foreach( $level_5_terms as $term ) {
			$child_terms	= get_terms( array(
				'taxonomy' 		=> 'combination_category',
				'hide_empty'	=> false,
				'parent'		=> $term->term_id 
			) );
			
			if( $child_terms ) {
				$level_6_terms	= array_merge( $level_6_terms, $child_terms ); 				
			}
		}*/
		
		$page_url	= get_permalink( get_the_ID() ); 
		?>
         
		<div class="combination-main-section">
        	<form action="" method="post">
                <div class="combination_row">
                    <div class="category_sidebar">
                        <ul class="category">
                        	<h4 class="category_heading"> Category </h4>
                            <?php
							if( $level_1_combination_category ) {
                                foreach( $level_1_combination_category as $category ) { 
									?>
                                     <li><a href="<?php echo add_query_arg( array( 'category_id' => $category->term_id ), $page_url ); ?>"><?php echo $category->name; ?></a></li>
									 <?php 
								}
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="rightside_model_area">
                    	<div class="combination_row">
                    		<?php  
								$all_collections = get_terms('combination_category',array(
									'taxonomy'   => 'combination_category',
									'hide_empty' => true,
									//'parent' 	 => 0
								));
								
								if( $_GET['category_id'] && ! $_GET['collection_id'] ) {
								
									if( $all_collections ) {
										 
										foreach( $all_collections as $collection ) {  
											if( $_GET['category_id'] == $collection->parent ) { 
											
											?>
												<div class="col-sm-fourth-parts model_outer_div model_with_name">
													<?php
													$collection_image_id = get_term_meta ( $collection->term_id, 'category-image-id', true );
													 
													?>
													<a href="<?php echo add_query_arg( array( 'category_id' => $collection->parent, 'collection_id' => $collection->term_id ), $page_url ); ?>">
														<div class="model_image">
															<?php echo wp_get_attachment_image ( $collection_image_id, 'large' ); ?>
														</div> 
														<div class="model_name">
															<p class="text-center"><?php echo $collection->name ?></p>
														</div>  
													</a> 
												</div>
																								
											<?php } 
										}
									}
									
								} else if( $_GET['collection_id'] && ! $_GET['model_id'] ) {
									
									$category_id 	= $_GET['category_id'];
									$collection_id 	= $_GET['collection_id'];
									
									?>
                                    <div class="combination_row clear">
                                        <div class="back_btn"><a href="<?php echo add_query_arg( array( 'category_id' => $category_id ), $page_url ); ?>"> Back To Category </a></div>	
                                    </div>
                                    <?php
									
									$all_model = get_terms('combination_category',array(
										'taxonomy'   => 'combination_category',
										'hide_empty' => true,
										'parent'     => $collection_id,
										
									));
									
									 if( $all_model ) {
										
										foreach( $all_model as $model ) { 
										
											?>
                                    		
                                            <div class="col-sm-fourth-parts model_outer_div model_with_name">
                                                <?php
                                                $model_image_id = get_term_meta ( $model->term_id, 'category-image-id', true );
                                                 
                                                ?>
                                                <a href="<?php echo add_query_arg( array( 'collection_id' => $collection_id, 'model_id' => $model->term_id ), $page_url ); ?>">
                                                    <div class="model_image">
                                                        <?php echo wp_get_attachment_image ( $model_image_id, 'large' ); ?>
                                                    </div> 
                                                    <div class="model_name">
                                                        <p class="text-center"><?php echo $model->name ?></p>
                                                    </div>  
                                                </a> 
                                            </div>
										<?php	
										}
									}
									 
								
								} else if( $_GET['collection_id'] && $_GET['model_id'] ) { 
								
									$model_id 	   = $_GET['model_id'];
									$collection_id = $_GET['collection_id'];
									$model_name    = get_term_by('id', $model_id, 'combination_category');
									
									?>
									
									<div class="combination_row clear">
                                        <div class="back_btn"><a href="<?php echo add_query_arg( array( 'collection_id' => $collection_id ), $page_url ); ?>"> Back To Collection </a></div>	
                                    </div>
                                    
                                    <div class="combination_row clear">
                                        <div class="col-sm-fourth-parts model_outer_div">
                                        	<div class="model_with_name">
                                                <div class="model_image <?php echo ( $_GET['model_id'] ) ? 'single_model' : ''; ?>">
                                                    <?php
                                                    $model_image_id = get_term_meta ( $model_id, 'category-image-id', true );
                                                    echo $model_image = wp_get_attachment_image( $model_image_id, 'large' ); 
                                                    ?>
                                                </div> 
                                                <div class="model_name">
                                                    <p class="text-center"><?php echo $model_name->name ?></p>
                                                </div>
                                            </div>     
                                        </div>
                                        
                                        <div class="center_filter_btton">
                                            
                                            <div class="finishes"> 
                                                 <h6 class="label"> Variant </h6>
                                                 <?php
												 if( $is_debug ) {
												 	print_rr( $_GET );
												 }
												 
												 $all_model_variant = get_terms('combination_category', array(
														'taxonomy'   => 'combination_category',
														'hide_empty' => true,   
														'parent'   	 => intval( $model_id ),
													 ) ); 
												 
												 $is_no_variant			= false;
												 $is_no_variant_count	= 0;
												 
												 if( $is_debug ) {
												 	print_rr( $all_model_variant );
												 }
												 
												 if( $all_model_variant && ! is_wp_error( $all_model_variant ) /*&& count( $all_model_variant ) <= 1*/ ) {
												 	foreach( $all_model_variant as $variant ) {
														if( sanitize_title( 'No Variant' ) == sanitize_title( $variant->name ) ) {
															if( $variant->count >= $is_no_variant_count ) {
																$is_no_variant_count	= $variant->count; 
																$is_no_variant			= $variant->term_id;
															}
														}	
													}
												 }
												 ?>
                                                 <select name="selected_varient" id="selected_varient" class="form-controll" <?php if( ! $all_model_variant || $is_no_variant ) { echo 'disabled="disabled"'; } ?>  >				
                                                     <option value=""> -- Select Variant -- </option>
                                                     <?php
													 if( $all_model_variant && ! is_wp_error( $all_model_variant ) ) {
													 foreach( $all_model_variant as $variant ) { 
													 	if( sanitize_title( 'No Variant' ) == sanitize_title( $variant->name ) ) {
															continue;
														}
													 ?>
														 <option value="<?php echo $variant->term_id ?>"><?php echo $variant->name ?></option>
													 <?php
													 }
													 } ?>
                                                 </select>
											</div> 
                                            
                                            <?php													
											if( $is_no_variant ) {
												$all_finishes = get_terms('combination_category', array(
													'taxonomy'   => 'combination_category',
													'hide_empty' => true,   
													'parent'   	 => $is_no_variant,
												) );															
											} else {													
												$all_finishes = get_terms('combination_category', array(
													'taxonomy'   	=> 'combination_category',
													'hide_empty'	=> true,   
													'child_of'   	=> $model_id,
												) ); 
											}
											?>
                                            <div class="finishes"> 
                                                <h6 class="label"> Finish </h6>
                                                <select name="selected_finishes_and_color" id="selected_finishes_and_color"  class="form-controll" <?php if( ! $is_no_variant || ! $all_finishes ) { echo 'disabled="disabled"'; } ?>>
                                                    <option value=""> -- Select Finish -- </option>
                                                    <?php
													
													if( $all_finishes && ! is_wp_error( $all_finishes ) ) {
													//$all_varient = array(); 
													foreach( $all_finishes as $finishes ) {
														
														 $all_colors = get_terms('combination_category', array(
															'taxonomy'   => 'combination_category',
															'hide_empty' => true,   
															'parent'     => $finishes->term_id,
														 ) );
														 
														if( $all_colors && ! is_wp_error( $all_colors ) ) { 
														foreach( $all_colors as $color ) {
															//$all_varient[] = $color->term_id;
															if( $finishes->term_id == $color->parent ) { ?>
																<option model_color="<?php echo $color->term_id; ?>" value="<?php echo $finishes->term_id ?>"><?php echo $finishes->name ?> - <?php echo $color->name ?></option>
														<?php }
														}
														}
                                                    } 
													}?>
                                                </select>
                                            </div>  
                                             
                                            <div class="generate_image">
                                                <br>
                                                <input type="hidden" value="<?php echo $model_id ?>" name="model_name" id="model_name">
                                                <p class="error_msg" id="error_msg"></p>
                                                <img src="<?php echo COMBINATION_PLUGIN_URL ?>/images/ajax_loading.gif" class="loader_gif" id="loader_gif" style="display:none">
                                            </div>
                                        </div>
                                        <div class="right_side_generated_img col-sm-fourth-parts">
                                            <div class="model_image <?php echo ( $_GET['model_id'] ) ? 'single_model' : ''; ?>">
                                                <?php
												$model_image_id = get_term_meta ( $model_id, 'category-image-id', true );
												$model_image = wp_get_attachment_image_src( $model_image_id, 'full' );
												 
												?>
                                                <img src="<?php echo $model_image[0] ?>" id="generated_new_image">
                                            </div>  
                                            <div class="download_btn">
                                                <br>
                                                
                                                <?php $image_attributes = wp_get_attachment_image_src( $model_image_id , 'full' ); ?>
                                                
                                                <a href="<?php echo $image_attributes[0] ?>" target="_blank" download id="jpg_image_download_link">
                                                  <input type="button" value="Download JPG">
                                                </a>
                                                  <br><br>
                                                <a href="<?php echo $image_attributes[0] ?>" target="_blank" download id="png_image_download_link">
                                                  <input type="button" value="Download PNG">
                                                </a>
                                            </div> 
                                        </div>
                                    </div> 
							<div class="download_btn">
											<strong>CAD files</strong>
											<ul>
                                            	<li> <a href="<?php echo $this->append_nounce( get_term_meta ( $model_id, 'model_pdf_file', true ) ); ?>" target="_blank" download ><button type="button">Download PDF</button></a></li>
                                            	<li><a href="<?php echo $this->append_nounce( get_term_meta ( $model_id, 'model_dwg_file', true ) ); ?>" target="_blank" download><button type="button">Download DWG</button></a></li>
                                                <li><a href="<?php echo $this->append_nounce( get_term_meta ( $model_id, 'model_stp_file', true ) ); ?>" target="_blank" download><button type="button">Download STP</button></a></li>
                                                <li><a href="<?php echo $this->append_nounce( get_term_meta ( $model_id, 'model_stl_file', true ) ); ?>" target="_blank" download><button type="button">Download STL</button></a></li>
                                                <li><a href="<?php echo $this->append_nounce( get_term_meta ( $model_id, 'model_revit_file', true ) ); ?>" target="_blank" download><button type="button">Download REVIT</button></a></li>
												</ul>
                                            </div> 
									
								<?php } else {
									
									if( $level_2_terms ) {
										
										foreach( $level_2_terms as $collection ) {   ?>
                                    
                                            <div class="col-sm-fourth-parts model_outer_div model_with_name">
                                                <?php
                                                $collection_image_id = get_term_meta ( $collection->term_id, 'category-image-id', true );
                                                 
                                                ?>
                                                <a href="<?php echo add_query_arg( array( 'collection_id' => $collection->term_id ), $page_url ); ?>">
                                                    <div class="model_image">
                                                        <?php echo wp_get_attachment_image ( $collection_image_id, 'large' ); ?>
                                                    </div> 
                                                    <div class="model_name">
                                                        <p class="text-center"><?php echo $collection->name ?></p>
                                                    </div>  
                                                </a> 
                                            </div>
										<?php	
										}
									}
								}
                           ?>
                    	</div>
                	</div>	
                </div>
            </form>	
		</div>
		
		<?php
		
		$content	= ob_get_contents();
		
		ob_clean();
		
		return $content;	
	} 
	
	public function generate_new_combination_image() {
		
		$response	= array();
		
		if( $_POST['finishes_id'] && $_POST['colors_id'] ) {
			 
			$combination_args = array (
				'post_type' 	=> 'combination',
				'post_status' 	=> 'publish',	
				'orderby'     	=> 'date',
				'order'       	=> 'DESC',
				'tax_query' => array(
					array(
						'taxonomy'  => 'combination_category',
						'field'     => 'term_id',
						'terms'     => $_POST['colors_id'],
					)
				),
			);
			
			$get_all_combination = get_posts($combination_args); 
			
			$all_varient = get_terms('combination_category', array(
				'taxonomy'   => 'combination_category',
				'hide_empty' => false,   
				'parent'     => $_POST['colors_id'],
			 ) );
			
			$combination_image_array = array();
			 
			if( $get_all_combination ) {
				foreach( $get_all_combination as $combinations ) { 
						
						$response['success']   = '1';
						
						//$png_img_id = get_post_meta($combinations->ID, "combination_png_image", true );
						if( function_exists( 'get_field' ) ) {
						$png_img_id = get_field('combination_png_image', $combinations->ID, false);
						}
						$response['png_image'] = wp_get_attachment_url($png_img_id,'full');
						
						if( function_exists( 'get_field' ) ) {
						$jpg_img_id = get_field('combination_jpg_image', $combinations->ID, false);
						}
						$response['jpg_image'] = wp_get_attachment_url($jpg_img_id,'full'); 
					  	
						$response['combination_id']   = $combinations->ID;
				}	
			} 
			 
			if( $all_varient ) {
				foreach( $all_varient as $varient ) { 
						$response['varient_option'] .= "<option value=".$varient->term_id.">".$varient->name."</option>"; 
				}	
			} 
		}
		
		wp_send_json( $response );
		die();
	}
	
	public function generate_new_varient_combination() {
		
		$response	= array();
		
		if( $_POST['varient_id'] ) {
			 
			$combination_args = array (
				'post_type' 	=> 'combination',
				'post_status' 	=> 'publish',	
				'orderby'     	=> 'date',
				'order'       	=> 'DESC',
				'tax_query' => array(
					array(
						'taxonomy'  => 'combination_category',
						'field'     => 'term_id',
						'terms'     => $_POST['varient_id'],
					)
				),
			);
			
			$get_all_combination = get_posts($combination_args);  
			
			if( $get_all_combination ) {
				foreach( $get_all_combination as $combinations ) {
					 
					$response['success']   = '1';
					
					if( function_exists( 'get_field' ) ) {
						$png_img_id = get_field('combination_png_image', $combinations->ID, false);		
					}
					$response['png_image'] = wp_get_attachment_url($png_img_id,'full');
					
					if( function_exists( 'get_field' ) ) {
						$jpg_img_id = get_field('combination_jpg_image', $combinations->ID, false);
					}
					//$jpg_img_id = get_post_meta($combinations->ID, "combination_jpg_image", true );
					$response['jpg_image'] = wp_get_attachment_url($jpg_img_id,'full'); 
					
					$response['combination_id'] = $combinations->ID;
					$response['varient_id']	    = $_POST['varient_id'];
				}
				
				$all_finish = get_terms('combination_category', array(
					'taxonomy'   => 'combination_category',
					'hide_empty' => false,   
					'parent'     => intval( $_POST['varient_id'] ),
				 ) );
				 
				 $all_colors = array();
				 if( $all_finish ) {
					 foreach( $all_finish as $finish ) {
						 
						 $all_colors = get_terms('combination_category', array(
							'taxonomy'   => 'combination_category',
							'hide_empty' => false,   
							'parent'     => intval( $finish->term_id ),
						 ) );
						 
						 if( $all_colors )	{
							foreach( $all_colors as $finish_with_color ) {
								$response['finish_color_option'] .= "<option model_color=" . $finish_with_color->term_id . " value=".$finish->term_id.">" . $finish->name . " - " . $finish_with_color->name . "</option>"; 	
							}
						 }
					 }
				 }
			}
		}
		
		wp_send_json( $response );
		die();
	}
	  
	
	// Add Category Image
	
	/*
	 * Add a form field in the new category page
	 * @since 1.0.0
	 */
	 public function add_combination_category_image ( $taxonomy ) { ?>
	   <div class="form-field term-group">
		 <label for="category-image-id"><?php _e('Image', 'hero-theme'); ?></label>
		 <input type="hidden" id="category-image-id" name="category-image-id" class="custom_media_url" value="">
		 <div id="category-image-wrapper"></div>
		 <p>
		   <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Image', 'hero-theme' ); ?>" />
		   <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Remove Image', 'hero-theme' ); ?>" />
		</p>
	   </div>
       
       <div class="form-field term-group">
		   <?php echo '<label>Upload PDF</label>'; 
   		   		 echo '<input type="text" name="model_pdf_file" id="model_pdf_file">
		   			   <a href="#" class="button button-secondary model_file_upload" id="model_pdf_upload">Upload PDF</a>
                       <a href="#" class="button button-secondary model_file_rmv" id="model_pdf_remove">Remove</a>'; 
           ?>
       </div>
       
       <div class="form-field term-group">
		   <?php echo '<label>Upload DWG</label>'; 
                 echo '<input type="text" name="model_dwg_file" id="model_dwg_file">
				 	   <a href="#" class="button button-secondary model_file_upload" id="model_dwg_upload">Upload DWG</a>
                       <a href="#" class="button button-secondary model_file_rmv" id="model_dwg_remove">Remove</a>'; 
           ?>
       </div>
       
       <div class="form-field term-group">
		   <?php  echo '<label>Upload STP</label>';
		   		  echo '<input type="text" name="model_stp_file" id="model_stp_file">
		   			   <a href="#" class="button button-secondary model_file_upload" id="model_stp_upload">Upload STP</a>
                       <a href="#" class="button button-secondary model_file_rmv" id="model_stp_remove">Remove</a>'; 
           ?>
       </div>
       
       <div class="form-field term-group">
		   <?php  echo '<label>Upload STL</label>';
		   		  echo '<input type="text" name="model_stl_file" id="model_stl_file">
		   			   <a href="#" class="button button-secondary model_file_upload" id="model_stl_upload">Upload STL</a>
                       <a href="#" class="button button-secondary model_file_rmv" id="model_stl_remove">Remove</a>'; 
           ?>
       </div>
       
       <div class="form-field term-group">
		   <?php echo '<label>Upload REVIT</label>';
                 echo '<input type="text" name="model_revit_file" id="model_revit_file">
				 	   <a href="#" class="button button-secondary model_file_upload" id="model_revit_upload">Upload REVIT</a>
                       <a href="#" class="button button-secondary model_file_rmv" id="model_revit_remove">Remove</a>'; 
           ?>
       </div>
       
	 <?php
	 }
	 
	 /*
	  * Save the form field
	  * @since 1.0.0
	 */
	 public function save_combination_category_image ( $term_id, $tt_id ) {
	   if( isset( $_POST['category-image-id'] ) && '' !== $_POST['category-image-id'] ){
		 $image = $_POST['category-image-id'];
		 add_term_meta( $term_id, 'category-image-id', $image, true );
	   }
	   
	   // For Model
	   if( isset( $_POST['model_pdf_file'] ) && $_POST['model_pdf_file'] != '' ){
			update_term_meta ( $term_id, 'model_pdf_file', $_POST['model_pdf_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_pdf_file', '' );
	   }
	   
	   if( isset( $_POST['model_dwg_file'] ) && $_POST['model_dwg_file'] != '' ){
			update_term_meta ( $term_id, 'model_dwg_file', $_POST['model_dwg_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_dwg_file', '' );
	   }
	   
	   if( isset( $_POST['model_stp_file'] ) && $_POST['model_stp_file'] != '' ){
			update_term_meta ( $term_id, 'model_stp_file', $_POST['model_stp_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_stp_file', '' );
	   }
	   
	   if( isset( $_POST['model_stl_file'] ) && $_POST['model_stl_file'] != '' ){
			update_term_meta ( $term_id, 'model_stl_file', $_POST['model_stl_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_stl_file', '' );
	   }
	   
	   if( isset( $_POST['model_revit_file'] ) && $_POST['model_revit_file'] != '' ){
			update_term_meta ( $term_id, 'model_revit_file', $_POST['model_revit_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_revit_file', '' );
	   }
	   
	 }
	 
	 /*
	  * Edit the form field
	  * @since 1.0.0
	 */
	 public function update_combination_category_image ( $term, $taxonomy ) { ?>
	   <tr class="form-field term-group-wrap">
		 <th scope="row">
		   <label for="category-image-id"><?php _e( 'Image', 'hero-theme' ); ?></label>
		 </th>
		 <td>
		   <?php $image_id = get_term_meta ( $term -> term_id, 'category-image-id', true ); ?>
		   <input type="hidden" id="category-image-id" name="category-image-id" value="<?php echo $image_id; ?>">
		   <div id="category-image-wrapper">
			 <?php if ( $image_id ) { ?>
			   <?php echo wp_get_attachment_image ( $image_id, 'thumbnail' ); ?>
			 <?php } ?>
		   </div>
		   <p>
			 <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Image', 'hero-theme' ); ?>" />
			 <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Remove Image', 'hero-theme' ); ?>" />
		   </p>
           
           <br>
           
           <div class="form-field term-group">
			   <?php echo '<label>Upload PDF</label>'; 
                     echo '<input type="text" name="model_pdf_file" id="model_pdf_file" value="'. get_term_meta ( $term -> term_id, 'model_pdf_file', true ) .'">
                           <a href="#" class="button button-secondary model_file_upload" id="model_pdf_upload">Upload PDF</a>
                           <a href="#" class="button button-secondary model_file_rmv" id="model_pdf_remove">Remove</a>'; 
               ?>
           </div>
           <br>
           <div class="form-field term-group">
               <?php echo '<label>Upload DWG</label>'; 
                     echo '<input type="text" name="model_dwg_file" id="model_dwg_file" value="'. get_term_meta ( $term -> term_id, 'model_dwg_file', true ) .'">
                           <a href="#" class="button button-secondary model_file_upload" id="model_dwg_upload">Upload DWG</a>
                           <a href="#" class="button button-secondary model_file_rmv" id="model_dwg_remove">Remove</a>'; 
               ?>
           </div>
           <br>
           <div class="form-field term-group">
               <?php  echo '<label>Upload STP</label>';
                      echo '<input type="text" name="model_stp_file" id="model_stp_file" value="'. get_term_meta ( $term -> term_id, 'model_stp_file', true ) .'">
                           <a href="#" class="button button-secondary model_file_upload" id="model_stp_upload">Upload STP</a>
                           <a href="#" class="button button-secondary model_file_rmv" id="model_stp_remove">Remove</a>'; 
               ?>
           </div>
           <br>
           <div class="form-field term-group">
               <?php  echo '<label>Upload STL</label>';
                      echo '<input type="text" name="model_stl_file" id="model_stl_file" value="'. get_term_meta ( $term -> term_id, 'model_stl_file', true ) .'">
                           <a href="#" class="button button-secondary model_file_upload" id="model_stl_upload">Upload STL</a>
                           <a href="#" class="button button-secondary model_file_rmv" id="model_stl_remove">Remove</a>'; 
               ?>
           </div>
           <br>
           <div class="form-field term-group">
               <?php echo '<label>Upload REVIT</label>';
                     echo '<input type="text" name="model_revit_file" id="model_revit_file" value="'. get_term_meta ( $term -> term_id, 'model_revit_file', true ) .'">
                           <a href="#" class="button button-secondary model_file_upload" id="model_revit_upload">Upload REVIT</a>
                           <a href="#" class="button button-secondary model_file_rmv" id="model_revit_remove">Remove</a>'; 
               ?>
           </div>
		 </td>
	   </tr>
     <?php
	 }
	
	/*
	 * Update the form field value
	 * @since 1.0.0
	 */
	 public function updated_combination_category_image ( $term_id, $tt_id ) {
		 
	   if( isset( $_POST['category-image-id'] ) && '' !== $_POST['category-image-id'] ){
		 $image = $_POST['category-image-id'];
		 update_term_meta ( $term_id, 'category-image-id', $image );
	   } else {
		 update_term_meta ( $term_id, 'category-image-id', '' );
	   }
	   
	   if( isset( $_POST['model_pdf_file'] ) && $_POST['model_pdf_file'] != '' ){
			update_term_meta ( $term_id, 'model_pdf_file', $_POST['model_pdf_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_pdf_file', '' );
	   }
	   
	   if( isset( $_POST['model_dwg_file'] ) && $_POST['model_dwg_file'] != '' ){
			update_term_meta ( $term_id, 'model_dwg_file', $_POST['model_dwg_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_dwg_file', '' );
	   }
	   
	   if( isset( $_POST['model_stp_file'] ) && $_POST['model_stp_file'] != '' ){
			update_term_meta ( $term_id, 'model_stp_file', $_POST['model_stp_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_stp_file', '' );
	   }
	   
	   if( isset( $_POST['model_stl_file'] ) && $_POST['model_stl_file'] != '' ){
			update_term_meta ( $term_id, 'model_stl_file', $_POST['model_stl_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_stl_file', '' );
	   }
	   
	   if( isset( $_POST['model_revit_file'] ) && $_POST['model_revit_file'] != '' ){
			update_term_meta ( $term_id, 'model_revit_file', $_POST['model_revit_file'] );   
	   } else {
		 update_term_meta ( $term_id, 'model_revit_file', '' );
	   }
	   
	}
	
	/*
	 * Add script
	 * @since 1.0.0
	 */
	 public function add_combination_category_image_script() { ?>
	   <script>
		 jQuery(document).ready( function($) {
		   function ct_media_upload(button_class) {
			 var _custom_media = true,
			 _orig_send_attachment = wp.media.editor.send.attachment;
			 $('body').on('click', button_class, function(e) {
			   var button_id = '#'+$(this).attr('id');
			   var send_attachment_bkp = wp.media.editor.send.attachment;
			   var button = $(button_id);
			   _custom_media = true;
			   wp.media.editor.send.attachment = function(props, attachment){
				 if ( _custom_media ) {
				   $('#category-image-id').val(attachment.id);
				   $('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
				   $('#category-image-wrapper .custom_media_image').attr('src',attachment.url).css('display','block');
				 } else {
				   return _orig_send_attachment.apply( button_id, [props, attachment] );
				 }
				}
			 wp.media.editor.open(button);
			 return false;
		   });
		 }
		 ct_media_upload('.ct_tax_media_button.button'); 
		 $('body').on('click','.ct_tax_media_remove',function(){
		   $('#category-image-id').val('');
		   $('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
		 });
		 // Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-category-ajax-response
		 $(document).ajaxComplete(function(event, xhr, settings) {
		   var queryStringArr = settings.data.split('&');
		   if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
			 var xml = xhr.responseXML;
			 $response = $(xml).find('term_id').text();
			 if($response!=""){
			   // Clear the thumb image
			   $('#category-image-wrapper').html('');
			 }
		   }
		 });
	   });
	 </script>
	 <?php }

	
	public function wpdebug() {
		
		if($_GET['debug'] ) {
			
			//finishes_id: 382
			//colors_id: 383
			//model_name: 381	
			
			 
				$combination_args = array (
					'post_type' 	=> 'combination',
					'post_status' 	=> 'publish',
					'orderby'     	=> 'date',
					'order'       	=> 'DESC',
					'tax_query' => array(
						array(
							'taxonomy'  => 'combination_category',
							'field'     => 'term_id',
							'terms'     => '383',
						)
					),
				);
				
				$get_all_combination = get_posts($combination_args); 
				
				
				 
				if( $get_all_combination ) {
					foreach( $get_all_combination as $combinations ) { 
						if( $combinations->ID == '382' ) {
							echo json_encode( array( "success" => '1', 'png_image' => get_the_post_thumbnail_url($combinations->ID,'full'), 'jpg_image' => get_the_post_thumbnail_url($combinations->ID, 'full') ) );
						}
					}	
				}   
				
				$all_varient = get_terms('combination_category', array(
					'taxonomy'   => 'combination_category',
					'hide_empty' => false,   
					'parent'     => '383',
				 ) );
				
				$combination_image_array = array();
				 
				if( $get_all_combination ) {
					foreach( $get_all_combination as $combinations ) { 
							
							$png_img_id = get_post_meta($combinations->ID, "combination_png_image", true );
							
							$response['png_image'] = wp_get_attachment_url($png_img_id,'full');
							
							$response['success']   = '1'; 
						  
					}	
				}  
				 
				if( $all_varient ) {
					foreach( $all_varient as $varient ) { 
							$response['varient_option'] .= "<option value=".$varient->term_id.">".$varient->name."</option>";
							//echo "<option value=".$varient->term_id.">".$varient->name."</option>";
					}	
				} 
			 
			die(); 
		}
	}
	
	public function get_domain_name() {
		$domain = site_url( "/" ); 
		$domain = str_replace( array( 'http://', 'https://', 'www' ), '', $domain );
		$domain = explode( "/", $domain );
		$domain	= $domain[0] ? $domain[0] : $_SERVER['SERVER_ADDR'];	
		
		return $domain;
	}
	
	public function base64_url_encode( $data ) { 
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' ); 
	}
	
	public function base64_url_decode( $data ) { 
		return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) ); 
	}
	
	public function find_image_by_name( $file = '' ) {
		if( ! $file ) {
			return false;
		}
		
		$filename	= strtolower( pathinfo( $file, PATHINFO_FILENAME ) );
		$fileext	= strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		
		if( ! $filename || ! $fileext ) {
			return false;	
		}
			
		global $wpdb;
		
		$attachment_ids	= $wpdb->get_results( "SELECT ID, guid FROM " . $wpdb->posts . " WHERE LOWER(post_title) = '" . $filename . "' AND post_type = 'attachment'" );
		
		if( $attachment_ids ) {								
			foreach( $attachment_ids as $attachment ) {
				$attachment_ext	= strtolower( pathinfo( $attachment->guid, PATHINFO_EXTENSION ) );
				
				if( $attachment_ext != $fileext ) {
					continue;
				}
		
				$attachment_path	= get_attached_file( $attachment->ID );
				
				if( file_exists( $attachment_path ) ) {
					return $attachment->ID;
				}
			}
		}
			
		return false;	
	}
	
	public function find_image_by_source_url( $source_url = '' ) {
		global $wpdb;
		
		if( ! $source_url ) {
			return false;
		}
		
		$attachment_id	= $wpdb->get_var( "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_key = '_source_url' AND meta_value = '" . $source_url . "'" );
		
		if( $attachment_id ) {								
			$attachment_path	= get_attached_file( $attachment_id );
			if( file_exists( $attachment_path ) ) {
				return $attachment_id;
			}
		}
		
		return false;	
	}
	
	public function get_posts_string_ids( $posts = array() ) {
		$id_string	= '';
		
		if( $posts ) {
			foreach( $posts as $post ) {
				$id_string .= $post->ID . ', ';
			}
		}
		
		return trim( $id_string, ', ' );
	}
	
	public function maybe_cron_import_page() {
		if( ! isset( $_GET['_cron_page'] ) ) {
			return;
		}
		
		/*global $wpdb;
		$allposts	= $wpdb->get_col( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type = 'combination'" ); 
		
		if( $allposts ) {
			foreach( $allposts as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}
				
		$terms	= get_terms( array( 'taxonomy' => 'combination_category', 'hide_empty' => false ) );			
		if( $terms ) {
			foreach( $terms as $term ) {
				wp_delete_term( $term->term_id, 'combination_category' );	
			}
		}
			
		echo 'DONE';
		exit;*/
		
		$json	= file_get_contents( COMBINATION_PLUGIN_DIR . 'allcombinations.json' );
		
		$alldata	= json_decode( $json, true );
		
		$cron_last_imported_id	= file_get_contents( COMBINATION_PLUGIN_DIR . 'cron_last_imported_id.json' );
		$cron_last_imported_id	= $cron_last_imported_id ? json_decode( $cron_last_imported_id, true ) : 0;
		$cron_last_imported_id	= $cron_last_imported_id['count'] ? $cron_last_imported_id['count'] : 0;
		
		file_put_contents( COMBINATION_PLUGIN_DIR . 'cron_last_imported_id.json', json_encode( array( 'count' => $cron_last_imported_id + 1 ) ) );
				
		//$cron_last_imported_id	= file_get_contents( COMBINATION_PLUGIN_DIR . 'cron_last_imported_id.txt' );
		//$cron_last_imported_id	= $cron_last_imported_id ? intval( $cron_last_imported_id ) : 0;
		
		//file_put_contents( COMBINATION_PLUGIN_DIR . 'cron_last_imported_id.txt', $cron_last_imported_id + 1 );
		
		$all_imported	= file_get_contents( COMBINATION_PLUGIN_DIR . 'all_imported.json' );
		$all_imported	= $all_imported ? json_decode( $all_imported, true ) : false;
		$all_imported	= $all_imported ? $all_imported : array();
		
		$this->_log( 'cron_last_imported_id: ' . $cron_last_imported_id );
		
		if( in_array( $cron_last_imported_id, $all_imported ) ) {
			$this->_log( 'already imported: ' . $cron_last_imported_id );
			die();		
		}
		
		array_push( $all_imported, $cron_last_imported_id );		
		file_put_contents( COMBINATION_PLUGIN_DIR . 'all_imported.json', json_encode( $all_imported ) );
					
		$data	= isset( $alldata[ $cron_last_imported_id ] ) && $alldata[ $cron_last_imported_id ] ? $alldata[ $cron_last_imported_id ] : false;
			
		if( $data ) {				
			$combination= $data[0]; 
			$category	= $data[1];
			$collection	= $data[2];
			$model		= $data[3];
			$varient	= $data[4];
			$varient	= trim( $varient ) ? $varient : 'No Variant';
			$finish		= $data[5];
			$color		= $data[6]; 
			
			$this->_ilog( "" );
			$this->_ilog( "" );
			$this->_ilog( '===###*** combinations_import_cron ***###===' );
			$this->_ilog( 'combination: ' . $combination );
			$this->_ilog( 'category: ' . $category );
			$this->_ilog( 'collection: ' . $collection );
			$this->_ilog( 'model: ' . $model );
			$this->_ilog( 'varient: ' . $varient );
			$this->_ilog( 'finish: ' . $finish );
			$this->_ilog( 'color: ' . $color );
			
			$category_id					= false;
			$category_term_taxonomy_id		= false;
			$collection_id					= false;	
			$collection_term_taxonomy_id	= false;
			$model_id						= false;
			$model_term_taxonomy_id			= false;
			$varient_id						= false;
			$varient_term_taxonomy_id		= false;
			$finish_id						= false;
			$finish_term_taxonomy_id		= false;
			$color_id						= false;
			$color_term_taxonomy_id			= false;
			
			//category
			$category_term	= term_exists( $category, 'combination_category' ); 
			
			$this->_ilog( 'check category term_id: ' . ( isset( $category_term['term_id'] ) ? $category_term['term_id'] : false ) );
			$this->_ilog( 'check category term_taxonomy_id: ' . ( isset( $category_term['term_taxonomy_id'] ) ? $category_term['term_taxonomy_id'] : false ) );
			
			$category_id				= $category_term ? intval( $category_term['term_id'] ) : false;
			$category_term_taxonomy_id	= $category_term ? intval( $category_term['term_taxonomy_id'] ) : false;
			
			if( $category_id && ! empty( $collection ) ) {
				//collection
				$collection_term	= term_exists( $collection, 'combination_category', $category_id ); 
				
				$this->_ilog( 'check collection term_id: ' . ( isset( $collection_term['term_id'] ) ? $collection_term['term_id'] : false ) );
				$this->_ilog( 'check collection term_taxonomy_id: ' . ( isset( $collection_term['term_taxonomy_id'] ) ? $collection_term['term_taxonomy_id'] : false ) );
				
				$collection_id				= $collection_term ? intval( $collection_term['term_id'] ) : false;
				$collection_term_taxonomy_id= $collection_term ? intval( $collection_term['term_taxonomy_id'] ) : false;
				
				if( $collection_id && ! empty( $model ) ) {
					//model
					$model_term	= term_exists( $model, 'combination_category', $collection_id ); 
					
					$this->_ilog( 'check model term_id: ' . ( isset( $model_term['term_id'] ) ? $model_term['term_id'] : false ) );
					$this->_ilog( 'check model term_taxonomy_id: ' . ( isset( $model_term['term_taxonomy_id'] ) ? $model_term['term_taxonomy_id'] : false ) );
					
					$model_id				= $model_term ? intval( $model_term['term_id'] ) : false;
					$model_term_taxonomy_id	= $model_term ? intval( $model_term['term_taxonomy_id'] ) : false;
					
					if( $model_id && ! empty( $varient ) ) {
						//varient
						$varient_term	= term_exists( $varient, 'combination_category', $model_id ); 
						
						$this->_ilog( 'check varient term_id: ' . ( isset( $varient_term['term_id'] ) ? $varient_term['term_id'] : false ) );
						$this->_ilog( 'check varient term_taxonomy_id: ' . ( isset( $varient_term['term_taxonomy_id'] ) ? $varient_term['term_taxonomy_id'] : false ) );
						
						$varient_id					= $varient_term ? intval( $varient_term['term_id'] ) : false;
						$varient_term_taxonomy_id	= $varient_term ? intval( $varient_term['term_taxonomy_id'] ) : false;
						
						if( $varient_id && ! empty( $finish ) ) {
							// finish
							$finish_term	= term_exists( $finish, 'combination_category', $varient_id ); 
							
							$this->_ilog( 'check finish term_id: ' . ( isset( $finish_term['term_id'] ) ? $finish_term['term_id'] : false ) );
							$this->_ilog( 'check finish term_taxonomy_id: ' . ( isset( $finish_term['term_taxonomy_id'] ) ? $finish_term['term_taxonomy_id'] : false ) );
							
							$finish_id					= $finish_term ? intval( $finish_term['term_id'] ) : false;
							$finish_term_taxonomy_id	= $finish_term ? intval( $finish_term['term_taxonomy_id'] ) : false;
							
							if( $finish_id && ! empty( $color ) ) {
								// color
								$color_term	= term_exists( $color, 'combination_category', $finish_id ); 
								
								$this->_ilog( 'check color term_id: ' . ( isset( $color_term['term_id'] ) ? $color_term['term_id'] : false ) );
								$this->_ilog( 'check color term_taxonomy_id: ' . ( isset( $color_term['term_taxonomy_id'] ) ? $color_term['term_taxonomy_id'] : false ) );
								
								$color_id				= $color_term ? intval( $color_term['term_id'] ) : false;
								$color_term_taxonomy_id	= $color_term ? intval( $color_term['term_taxonomy_id'] ) : false;
																
								if( $color_id ) {
									$check_combinations	= get_posts( array(
										  'post_status'	=> 'publish', 
										  'post_type'   => 'combination', 
										  'tax_query' 	=> array(
																'taxonomy' => 'combination_category',
																'field'    => 'term_id',
																'terms'    => intval( $color_id ),   
															)
										)
									);
									
									//$this->_ilog( 'check_combination' );
									$this->_ilog( 'check_combinations ids: ' . $this->get_posts_string_ids( $check_combinations ) );
									
									if( $check_combinations ) {
										$this->_ilog( 'SKIP: ' . $combination . ' -- ' . $category . ' -- ' . $collection . ' -- ' . $model . ' -- ' . $varient . ' -- ' . $finish . ' -- ' . $color );
										$this->_log( 'cron_last_imported_id: ' . $cron_last_imported_id . ' already exists' );
										die();
										//continue;	// already exists	
									}											
								}
							}
						}
					}
				}
			}
			
			$this->_ilog( $combination . ' -- ' . $category . ' -- ' . $collection . ' -- ' . $model . ' -- ' . $varient . ' -- ' . $finish . ' -- ' . $color );
						
			$combination_args	= array(
				'post_title'	=> $combination . ' - ' . $category . ' - ' . $collection . ' - ' . $model . ' - ' . $varient . ' - ' . $finish . ' - ' . $color, 
				'post_status'   => 'publish', 
				'post_type'     => 'combination', 
			);
			
			// Insert the post into the database
			$combination_post_id	= wp_insert_post( $combination_args ); 
								
			if( ! is_wp_error( $combination_post_id ) ) {
				$this->_ilog( 'combination_post_id: ' . $combination_post_id );
				
				require_once(ABSPATH . 'wp-admin/includes/media.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/image.php');
	
				// FOR PNG	
				if( trim( $data[7] ) ) {
					// First Find old image
					$this->_ilog( 'new combination PNG: ' . $data[7] );
					
					$png_image_id	= $this->find_image_by_name( $data[7] );
					$this->_ilog( 'new combination PNG find_image_by_name: ' . $png_image_id );
					
					if( $png_image_id ) {
						if( function_exists( 'update_field' ) ) {
							update_field( 'combination_png_image', $png_image_id, $combination_post_id );
						}
					} else {							
						$png_image_id	= $this->find_image_by_source_url( $data[7] );
						$this->_ilog( 'new combination PNG find_image_by_source_url: ' . $png_image_id );
						
						if( $png_image_id ) {
							if( function_exists( 'update_field' ) ) {
								update_field( 'combination_png_image', $png_image_id, $combination_post_id );
							}
						} else {
							$png_image_id	= media_sideload_image( $data[7], false, NULL, 'id' );
							
							if( ! is_wp_error( $png_image_id ) ) {		
								$this->_ilog( 'new combination PNG  media_sideload_image: ' . $png_image_id );
								if( function_exists( 'update_field' ) ) {
									update_field( 'combination_png_image', $png_image_id, $combination_post_id );
								}
							} else {
								$this->_ilog( 'new combination PNG media_sideload_image error' );
								$this->_ilog( $png_image_id->get_error_messages() );
							}
						}
					}
				} else {
					$this->_ilog( 'new combination image empty: ' . $data[7] );
				}
				
				// FOR JPG	
				if( trim( $data[8] ) ) {
					// First Find old image
					$this->_ilog( 'new combination JPG: ' . $data[8] );
					
					$jpg_image_id	= $this->find_image_by_name( $data[8] );
					$this->_ilog( 'new combination JPG find_image_by_name: ' . $jpg_image_id );
					
					if( $jpg_image_id ) {
						if( function_exists( 'update_field' ) ) {
							update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
						}
					} else {							
						$jpg_image_id	= $this->find_image_by_source_url( $data[8] );
						$this->_ilog( 'new combination JPG find_image_by_source_url: ' . $jpg_image_id );
						
						if( $jpg_image_id ) {
							if( function_exists( 'update_field' ) ) {
								update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
							}
						} else {
							$jpg_image_id	= media_sideload_image( $data[8], false, NULL, 'id' );
							
							if( ! is_wp_error( $jpg_image_id ) ) {		
								$this->_ilog( 'new combination JPG media_sideload_image: ' . $jpg_image_id );
								if( function_exists( 'update_field' ) ) {
									update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
								}
							} else {
								$this->_ilog( 'new combination JPG media_sideload_image error' );
								$this->_ilog( $jpg_image_id->get_error_messages() );
							}
						}
					}					
				} else {
					$this->_ilog( 'new combination image empty: ' . $data[8] );
				}
						
				/*$png_image_id	= media_sideload_image( $data[7], false, NULL, 'id' ); 
				
				if( ! is_wp_error( $png_image_id ) ) {
					//update_post_meta($combination_post_id, "combination_png_image", $png_image_id );
					
					if( function_exists( 'update_field' ) ) {
						update_field( 'combination_png_image', $png_image_id, $combination_post_id );
					}
				}
				
				$jpg_image_id	= media_sideload_image( $data[8], false, NULL, 'id' ); 		
				
				if( ! is_wp_error( $jpg_image_id ) ) {
					//set_post_thumbnail( $combination_post_id, $jpg_image_id );
					//update_post_meta( $combination_post_id, "combination_jpg_image", $jpg_image_id );
					
					if( function_exists( 'update_field' ) ) {
						update_field( 'combination_jpg_image', $jpg_image_id, $combination_post_id );
					}
				}*/
				
				// category		
				if( ! $category_id && ! empty( $category ) ) {
					$new_term	= wp_insert_term(
						$category,   		// the term 
						'combination_category' // the taxonomy
					);
					
					if( ! is_wp_error( $new_term ) ) {					
						$category_id				= $new_term['term_id'];
						$category_term_taxonomy_id	= $new_term['term_taxonomy_id'];
						
						$this->_ilog( 'combination category term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
						$this->_ilog( 'combination category term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
					} else {
						$this->_ilog( 'combination category term_error' );
						$this->_ilog( $new_term->get_error_messages() );
					}
				}
				
				if( $category_id ) {
					//wp_set_object_terms( $combination_post_id, intval( $category_id ), 'combination_category', true ); 
					
					$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' category_id: ' . intval( $category_id ) );
					
					// collection		
					if( ! $collection_id && ! empty( $collection ) ) {
						$new_term	= wp_insert_term(
							$collection,   		// the term 
							'combination_category', // the taxonomy
							array( 'parent' => intval( $category_id ) )
						);
						
						if( ! is_wp_error( $new_term ) ) {					
							$collection_id				= $new_term['term_id'];
							$collection_term_taxonomy_id= $new_term['term_taxonomy_id'];
							
							$this->_ilog( 'combination collection term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
							$this->_ilog( 'combination collection term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );	
						} else {
							$this->_ilog( 'combination collection term_error' );
							$this->_ilog( $new_term->get_error_messages() );
						}
					}
					
					if( $collection_id ) {
						//wp_set_object_terms( $combination_post_id, intval( $collection_id ), 'combination_category', true ); 
						
						$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' collection_id: ' . intval( $collection_id ) );
						
						// model		
						if( ! $model_id && ! empty( $model ) ) {
							$new_term	= wp_insert_term(
								$model,   		// the term 
								'combination_category', // the taxonomy
								array( 'parent' => intval( $collection_id ) )
							);
							
							if( ! is_wp_error( $new_term ) ) {					
								$model_id				= $new_term['term_id'];
								$model_term_taxonomy_id	= $new_term['term_taxonomy_id'];
								
								$this->_ilog( 'combination model term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
								$this->_ilog( 'combination model term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
							} else {
								$this->_ilog( 'combination model term_error' );
								$this->_ilog( $new_term->get_error_messages() );
							}
						}
						
						if( $model_id ) {
							//wp_set_object_terms( $combination_post_id, intval( $model_id ), 'combination_category', true ); 
							
							$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' model_id: ' . intval( $model_id ) );
							
							// varient		
							if( ! $varient_id && ! empty( $varient ) ) {
								$new_term	= wp_insert_term(
									$varient,   		// the term 
									'combination_category', // the taxonomy
									array( 'parent' => intval( $model_id ) )
								);
								
								if( ! is_wp_error( $new_term ) ) {					
									$varient_id					= $new_term['term_id'];
									$varient_term_taxonomy_id	= $new_term['term_taxonomy_id'];
									
									$this->_ilog( 'combination varient term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
									$this->_ilog( 'combination varient term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
								} else {
									$this->_ilog( 'combination varient term_error' );
									$this->_ilog( $new_term->get_error_messages() );
								}
							}
							
							if( $varient_id ) {
								//wp_set_object_terms( $combination_post_id, intval( $varient_id ), 'combination_category', true ); 
								
								$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' varient_id: ' . intval( $varient_id ) );
								
								// finish		
								if( ! $finish_id && ! empty( $finish ) ) {
									$new_term	= wp_insert_term(
										$finish,   		// the term 
										'combination_category', // the taxonomy
										array( 'parent' => intval( $varient_id ) )
									);
									
									if( ! is_wp_error( $new_term ) ) {					
										$finish_id					= $new_term['term_id'];
										$finish_term_taxonomy_id	= $new_term['term_taxonomy_id'];
										
										$this->_ilog( 'combination finish term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
										$this->_ilog( 'combination finish term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
									} else {
										$this->_ilog( 'combination finish term_error' );
										$this->_ilog( $new_term->get_error_messages() );
									}
								}
								
								if( $finish_id ) {
									//wp_set_object_terms( $combination_post_id, intval( $finish_id ), 'combination_category', true ); 
									
									$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' finish_id: ' . intval( $finish_id ) );
									
									// color		
									if( ! $color_id && ! empty( $color ) ) {
										$new_term	= wp_insert_term(
											$color,   		// the term 
											'combination_category', // the taxonomy
											array( 'parent' => intval( $finish_id ) )
										);
										
										if( ! is_wp_error( $new_term ) ) {					
											$color_id				= $new_term['term_id'];
											$color_term_taxonomy_id	= $new_term['term_taxonomy_id'];
											
											$this->_ilog( 'combination color term_id: ' . ( isset( $new_term['term_id'] ) ? $new_term['term_id'] : false )  );
											$this->_ilog( 'combination color term_taxonomy_id: ' . ( isset( $new_term['term_taxonomy_id'] ) ? $new_term['term_taxonomy_id'] : false ) );
										} else {
											$this->_ilog( 'combination color term_error' );
											$this->_ilog( $new_term->get_error_messages() );
										}
									}
									
									if( $color_id ) {
										//wp_set_object_terms( $combination_post_id, intval( $color_id ), 'combination_category', true );
										
										$this->_ilog( 'set_object_terms: post_id: ' . $combination_post_id . ' color_id: ' . intval( $color_id ) ); 
										
										// Set all terms at once and discard previous.	
										wp_set_object_terms( 
											$combination_post_id, 
											array(
												intval( $category_id ), 
												intval( $collection_id ), 
												intval( $model_id ), 
												intval( $varient_id ), 
												intval( $finish_id ), 
												intval( $color_id ), 
											),
											'combination_category', 
											false 
										);																																		
									}																
								}															
							}													
						}												
					}												
				}				 					
			} else {
				$this->_ilog( 'new combination_post_id error' );
				//$this->_ilog( $new_term->get_error_messages() );
			}
			
			$this->_log( 'cron_last_imported_id: ' . $cron_last_imported_id . ' Imported' );			
		}
				
		die( 'Imported' );
	}
	
	public function curl_image_save( $image_url, $image_file ){
		/*$image	= file_get_contents( $image_url );
		file_put_contents( $image_file, $image );
		$this->log( 'isset image fileget: ' . ( $image ? 1 : 0 ) );
		return;*/
		
		$ch = curl_init ($image_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		$raw	= curl_exec($ch);
		curl_close($ch);
			
		$fp	= fopen($image_file,'x');
		fwrite($fp, $raw);
		fclose($fp);
		return;
		
		$fp = fopen ($image_file, 'w+');              // open file handle
	
		$ch = curl_init($image_url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // enable if you want
		curl_setopt($ch, CURLOPT_FILE, $fp);          // output to file
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1000);      // some large value to allow curl to run for a long time
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_setopt($ch, CURLOPT_VERBOSE, true);   // Enable this line to see debug prints
		curl_exec($ch);
	
		curl_close($ch);                              // closing curl handle
		fclose($fp);                                  // closing file handle
	}
	
	public function manual_image_download( $file = false, $post_id = false, $desc = '' ) {
		$upload_dir	= wp_upload_dir();
		
		// Set variables for storage, fix file filename for query strings.
        preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
 
        if ( ! $matches ) {
            return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL.' ) );
        }
		
		$this->log( 'curl_image_save: ' . $file );
		
		$file_path	= $upload_dir['basedir'] . '/' . md5( rand( 111, 999 ) . $file ) . wp_basename( $file ); 
		
		$this->curl_image_save( $file, $file_path );        
		
		if( ! file_exists( $file_path ) ) {
			return new WP_Error( 'fallback_image_download_failed', __( 'cURL Download Failed.' ) );
		}
		
		$filetype 	= wp_check_filetype( $file_path );		
		$file_array = array(
            'name' 		=> wp_basename( $file ),
            'type' 		=> $filetype['type'],
            'tmp_name' 	=> $file_path,
            'error' 	=> 0,
            'size' 		=> filesize( $file_path )
        );
						
		$this->log( $file_array );
		
        // Do the validation and storage stuff.
        $id	= media_handle_sideload( $file_array, $post_id, $desc );
 		
		@unlink( $file_path );
		
		$this->log( $id );
		
        // If error storing permanently, unlink.
        if ( is_wp_error( $id ) ) {            
            return $id;
        }
 
        // Store the original attachment source in meta.
        add_post_meta( $id, '_source_url', $file );		
		     
        return $id;  
	}
	
	public function append_nounce( $url = '' ) {
		if( ! $url ) {
			return $url;
		}
		
		return add_query_arg( 'nonce', wp_create_nonce( 'download' ), $url );
	}
	
	public function maybe_cron_delete_page() {
		if( ! isset( $_GET['_cron_delete_page'] ) ) {
			return;
		}
		
		$json	= file_get_contents( COMBINATION_PLUGIN_DIR . 'allcombinations.json' );
		
		$alldata	= json_decode( $json, true );
		
		$unique		= array();
		$duplicated	= array(); 
		
		if( $alldata ) {
			foreach( $alldata as $data ) {
				$combination= $data[0]; 
				$category	= $data[1];
				$collection	= $data[2];
				$model		= $data[3];
				$varient	= $data[4];
				$varient	= trim( $varient ) ? $varient : 'No Variant';
				$finish		= $data[5];
				$color		= $data[6];
				
				$key	= sanitize_title( $category ) . sanitize_title( $collection ) . sanitize_title( $model ) . sanitize_title( $varient ) . sanitize_title( $finish ) . sanitize_title( $color );
				
				if( ! isset( $unique[ $key ] ) ) {
					$unique[ $key ] 	= $data;		
				} else {
					$duplicated[] = $data;		
				}
			}
		}
		
		print_rr( count( $duplicated ) );
		print_rr( count( $unique ) );
		
		print_rr( $duplicated );
		print_rr( $unique );
		
		/*$this->_dlog( '_cron_delete_page: ' . rand( 11, 99 ) );
		
		global $wpdb;
 
		$attchments	= $wpdb->get_col( "SELECT ID FROM wp_hkbd3d3q8w_posts WHERE post_type = 'attachment' AND DATE(post_date) > DATE('2020-08-25') LIMIT 100" );
		
		if( $attchments ) {
			foreach( $attchments as $attchment_id ) {
				wp_delete_attachment( $attchment_id, true );	
				$this->_dlog( $attchment_id );
			}
		}
		
		$wpdb->close();*/
		die();	
	}
	
	public function log( $msg = '' ) {		
		$msg	= ( is_array( $msg ) || is_object( $msg ) ) ? print_r( $msg, 1 ) : $msg;		 	
		$ip		= function_exists( 'get_ip' ) ? get_ip() : $_SERVER['REMOTE_ADDR'];
		
		error_log( date('[Y-m-d H:i:s e IP: ' . $ip . '] ') . $msg . PHP_EOL, 3, __DIR__ . "/image.log" );
	}
	
	public function _dlog( $msg = "" ) {
		$msg	= ( is_array( $msg ) || is_object( $msg ) ) ? print_r( $msg, 1 ) : $msg;
		error_log( date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/delete.log" );
	}

	public function _ilog( $msg = "" ) {
		$msg	= ( is_array( $msg ) || is_object( $msg ) ) ? print_r( $msg, 1 ) : $msg;		 	
		error_log( date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/import.log" );
	}


	public function _log( $msg = "" ) {
		$msg	= ( is_array( $msg ) || is_object( $msg ) ) ? print_r( $msg, 1 ) : $msg;		 	
		error_log( date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/cron.log" );
	}

}

endif;

$custom_combination	= new Custom_Combination();