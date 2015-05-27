<?php

/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Description of BP HRM functions.
 *
 * @author kishore
 */


function pm_pagination( $totalPage, $page ){
    global $rt_pm_bp_pm;
   
    if( $totalPage > 1 ){
                                            
        $base = $rt_pm_bp_pm->get_component_root_url().bp_current_action().'/%_%';
        $formate = 'page/%#%';
        if( isset( $_GET['orderby'] ) ) {

                $arr_params = array( 'orderby' => $_GET['orderby'], 'order' => $_GET['order'] );
                $base = add_query_arg( $arr_params, $rt_pm_bp_pm->get_component_root_url().bp_current_action() ) .'%_%' ; 
                $formate = '&paged=%#%';
        }

        $customPagHTML     =  '<div class="projects-lists pagination" role="menubar" aria-label="Pagination"><span class="current">Page '.$page.' of '.$totalPage.'</span>'.paginate_links( array(
        'base' => $base,
        'format' => $formate,
        'total' => $totalPage,
        'current' => $page
        )).'</div>';
        echo $customPagHTML;
    }
}

function render_project_summary_buttons( $post_id ){
	global $rt_pm_bp_pm;
	if (isset($post_id)) {
				$save_button = __( 'Update' );
			} else {
				$save_button = __( 'Add Project' );
			}
	?>
	<button class="mybutton" type="submit" ><?php _e($save_button); ?></button>
				<?php 
				if(isset($post_id)) { 
					$get_post_status = get_post_status( $post_id );
					if ( isset( $get_post_status ) && $get_post_status == 'trash' ){
						$archive_action = 'unarchive';
						$archive_button = __( 'Unarchive' );
						$button_archive_id = 'button-unarchive';
						$redirect = $rt_pm_bp_pm->get_component_root_url(). 'archives';
					} else {
						$archive_action = 'archive';
						$archive_button = __( 'Archive' );
						$button_archive_id = 'button-archive';
						$redirect = $rt_pm_bp_pm->get_component_root_url();
					}
					
				?>
				<button id="top-<?php echo $button_archive_id; ?>" class="mybutton" data-href="<?php echo add_query_arg( array( 'action' => $archive_action, 'rt_project_id' => $post_id ), $redirect ); ?>" class=""><?php _e($archive_button); ?></button>
				<button id="top-button-trash" class="mybutton" data-href="<?php echo add_query_arg( array( 'action' => 'trash', 'rt_project_id' => $post_id ), $redirect ); ?>" class=""><?php _e( 'Delete' ); ?></button>
				<?php  }
}

/**
 *  returns next dates array
 * @param string $date
 * @return array
 */

function rt_get_next_dates( $date ){
		$date_object = date_create( $date );
		$start = date_timestamp_get( $date_object );
		$dates=array();
		
		// if mobile show only 3 columns
		
		if( wp_is_mobile() ){
			$table_cols = 3;	
		}else{
			$table_cols = 9;
		}
		for($i = 0; $i<=$table_cols; $i++)
		{
			array_push($dates,date('Y-m-d', strtotime("+$i day", $start)));
		}
		return $dates;
}

add_action( 'wp_ajax_rtpm_validate_estimated_date', 'rtpm_validate_estimated_date_ajax' );
add_action( 'wp_ajax_nopriv_rtpm_validate_estimated_date', 'rtpm_validate_estimated_date_ajax' );

/**
 *  Function to check if estimated hours are less than max working hours
 */

function rtpm_validate_estimated_date_ajax(){
	
	$start_date = $_POST['start_date'];
	$end_date = $_POST['end_date'];
	$est_time = $_POST['est_time'];
	$project_id = $_POST['project_id'];
	$max_working_hours = get_post_meta($project_id,'working_hours',true);
	$first_date = date_create_from_format( 'M d, Y H:i A', $start_date );
	$second_date = date_create_from_format( 'M d, Y H:i A', $end_date );
	$diff = $second_date->diff($first_date);
	$diff_hours = $diff->h;
	
	// hours between start date and end date
	
	$diff_hours = $diff_hours + ($diff->days*(int)$max_working_hours);

	if( (int)$diff_hours < (int)$est_time ){
		echo json_encode( array( 'fetched' => false,'diff'=>$diff_hours ) );
	}else{
		echo json_encode( array( 'fetched' => true ) );
	}
	die;
}

function pm_get_attachment_data(){
  
    $meta = get_post( $_POST['attachment_id'] );
   
    echo json_encode( $meta );
    
    die(0);
}

add_action( 'wp_ajax_rtpmattachment_metadata', 'pm_get_attachment_data' );

function pm_save_attachment_data(){
  
  
    $args = array(
        'ID' => $_POST['ID'],
        'post_title' => $_POST['post_title'],
        'post_excerpt' => $_POST['post_excerpt'],
        'post_content' => $_POST['post_content'],
    );
   $post_id = wp_update_post( $args );
   echo $post_id;
   die();
  
}

add_action( 'wp_ajax_rtpmattachment_save_data', 'pm_save_attachment_data' );


function pm_add_new_documents(){

    
    $parent_post_id = $_POST['post_id'];
    $filename = $_POST['filename'];
    //var_dump($filenames);
    //foreach ( $filenames as $filename ) {
        
    
    // $filename should be the path to a file in the upload directory.
   

    // The ID of the post this attachment is for.
    //$parent_post_id = 37;

    // Check the type of tile. We'll use this as the 'post_mime_type'.
    $filetype = wp_check_filetype( basename( $filename ), null );

    // Get the path to the upload directory.
    $wp_upload_dir = wp_upload_dir();

    // Prepare an array of post data for the attachment.
    $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
    );

    // Insert the attachment.
    $attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );

   $data = array(
           'attachment_id'=>$attach_id
    );
    echo json_encode( $data );
    
    die();
}
add_action( 'wp_ajax_rtpm_add_new_documents', 'pm_add_new_documents'  );


function pm_remove_document(){
    
    $attachment_id = $_POST[ 'attachment_id' ];
    wp_delete_attachment( $attachment_id );
}
add_action( 'wp_ajax_rtpm_remove_document', 'pm_remove_document'  );

/**
 * Get project edit url
 * @param $project_id
 * @return string
 */
function rtpm_bp_get_project_details_url( $project_id ) {
    global $rt_pm_bp_pm, $rt_pm_project;

    $project_edit_link = add_query_arg( array( 'rt_project_id' => $project_id, 'action' => 'edit', 'post_type' => $rt_pm_project->post_type, 'tab' => "{$rt_pm_project->post_type}-details" ), $rt_pm_bp_pm->get_component_root_url().'/details' );
    return $project_edit_link;
}

