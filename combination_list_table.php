<?php

if( ! class_exists('WP_List_Table') ){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Combination_List_Page extends WP_List_Table {

    var $_data = array();

	function __construct(){

        global $status, $page;
		
        //Set parent defaults

        parent::__construct( array(
            'singular'  => 'Donation',     	//singular name of the listed records
            'plural'    => 'Donations',    	//plural name of the listed records
            'ajax'      => true       		//does this table support ajax?
        ) );
    }

	function column_default( $item, $column_name ){
        switch( $column_name ){
            case 'name':
                return $item[ $column_name ];
			case 'donation_amount':
                return "$ " . $item[ $column_name ];				
			default:
                return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';	//print_r( $item, true ) //Show the whole array for troubleshooting purposes
        }
    }

	function column_name( $item ){
        //Build row actions
        $actions = array(
            //'edit'      => sprintf( '<a href="post.php?post=%s&action=edit">Edit</a>', $item['ID'] ),
            //'delete'    => sprintf( '<a href="?page=%s&action=%s&donation[]=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['ID'] ),
        );

        //Return the title contents
        return sprintf( '%1$s %3$s',	// <span style="color:silver">(id:%2$s)</span>
            /*$1%s*/ $item['name'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions( $actions )
        );
    }

    function column_cb( $item ){

        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }

    function get_columns(){

        $columns = array(
            //'cb'        		=> '<input type="checkbox" />', //Render a checkbox instead of text
            'name'				=> 'First Name',	
			'last_name'			=> 'Last Name',	
			'donation_amount'	=> 'Payment',	
			//'monthly_donation'	=> 'Is Monthly?',	
			'txn_id'			=> 'Txn ID',	
			'email_address'		=> 'Email',	
			'phone_no'			=> 'Phone',	
			'street_address'	=> 'Street',	
			'suburb'			=> 'Suburb',	
			'state'				=> 'State',	
			'postcode'			=> 'postcode',	
			'country'			=> 'Country',				
			'keep_me_posted'	=> 'Keep Me Posted',	
			'created_at'		=> 'Date/Time',	
        );

        return $columns;
    }

    function get_sortable_columns() {
		
        $sortable_columns = array(
            //'name'	=> array( 'name', true ),     //true means it's already sorted
            //'url'  	=> array('url', false)
        );

        return $sortable_columns;
    }

    function get_bulk_actions() {

        $actions = array(
        	//'delete'    => 'Delete'
        );
		
        return $actions;
    }

    function process_bulk_action() {

        //Detect when a bulk action is being triggered...

        if( 'delete' === $this->current_action() ) {

			global $wpdb;

			$items	= $_GET['pdonation'];

			if( is_array( $items ) && $items ) {
				foreach( $items as $single_item ) {
					
					//$wpdb->delete( $wpdb->prefix . "donation_form_user", array( 'id' => $single_item ) );
				}

				global $is_donation_deleted;
				$is_donation_deleted	= true;	
			}		

			//wp_die('Items deleted (or they would be if we had items to delete)!');
        }        
    }

    function prepare_items() {

        global $wpdb; //This is used only if making any database queries

		// Get Per Page

        $user_ID 	= get_current_user_id();

		$screen 	= get_current_screen();

		$option 	= $screen->get_option( 'per_page', 'option' );

		$per_page 	= get_user_meta( $user_ID, $option, true );

		if ( empty ( $per_page ) || $per_page < 1 ) {
    		$per_page	= $screen->get_option( 'per_page', 'default' );
		}		

		// Get Columns
        $columns 	= $this->get_columns();

        $hidden 	= array();

        $sortable 	= $this->get_sortable_columns();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $this->process_bulk_action();        

		// For search
		$search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;

        //$data = $this->_data;

        $data	= array();

		$all_donations	= $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "donation_user WHERE txn_id != '' ORDER BY id DESC" );

		if( $all_donations ) {
			foreach( $all_donations as $single_donation ) {
				$data[]	= array( 
								"ID"				=> $single_donation->id,
								'name'				=> $single_donation->first_name,
								'last_name'			=> $single_donation->last_name,
								'donation_amount'	=> $single_donation->donation_amount,
								//'monthly_donation'	=> $single_donation->monthly_donation ? 'Yes' : 'No',
								'txn_id'			=> $single_donation->monthly_donation ? $single_donation->recurring_payment_id : $single_donation->txn_id,
								'email_address'		=> $single_donation->email_address,
								'phone_no'			=> $single_donation->phone_no,
								'street_address'	=> $single_donation->street_address,
								'suburb'			=> $single_donation->suburb,
								'state'				=> $single_donation->state,
								'postcode'			=> $single_donation->postcode,
								'country'			=> $single_donation->country,
								
								'keep_me_posted'	=> $single_donation->keep_me_posted ? 'Yes' : 'No', 
								'created_at'		=> $single_donation->created_at,	
								);

			}
		}
		
		function usort_reorder( $a, $b ){

            $orderby 	= ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'name'; 	//If no sort, default to title

            $order 		= ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; 		//If no order, default to asc

            $result 	= strcmp( $a[ $orderby ], $b[ $orderby ] ); 		//Determine sort order

            return ( $order === 'asc' ) ? $result : -$result; 		//Send final sort direction to usort
        }

        usort($data, 'usort_reorder');

        $current_page 	= $this->get_pagenum();

        $total_items 	= count( $data );
		
        $data 			= array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

        $this->items = $data;

		$this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil( $total_items / $per_page )   //WE have to calculate the total number of pages
        ) );
    }
}