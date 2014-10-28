<?php
/**
 * Don't load this file directly!
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rt_PM_Bp_PM_Project
 *
 * @author kishore
 */
if( !class_exists( 'Rt_PM_Bp_PM_Project' ) ) {
	class Rt_PM_Bp_PM_Project extends Rt_PM_Project {

		var $post_type = 'rt_project';
		// used in mail subject title - to detect whether it's a PM mail or not. So no translation
		var $name = 'PM';
		var $labels = array();
		var $statuses = array();
		var $custom_menu_order = array();

		static $dashboard_slug = 'rtpm-dashboard';

		var $organization_email_key = 'account_email';
		var $contact_email_key = 'contact_email';

		function __construct() {
            parent::__construct();
        }

		function init_project() {
			$menu_position = 34;
			$this->get_custom_labels();
			$this->get_custom_statuses();
            $this->get_custom_menu_order();
			$this->register_custom_post( $menu_position );
			$this->register_custom_statuses();

            global $rt_pm_project_type, $bp;
            $rt_pm_project_type->project_type( rtpm_post_type_name( $this->labels['name'] ) );

			$this->hooks();
		}

		function hooks() {
			// add_action( 'admin_menu', array( $this, 'register_custom_pages' ), 1 );
            // add_filter( 'custom_menu_order', array($this, 'custom_pages_order') );
            // add_action( 'p2p_init', array( $this, 'create_connection' ) );
            // add_action( 'save_post', 'update_project_meta' );

			// add_action( 'wp_ajax_rtpm_add_attachement', array( $this, 'attachment_added_ajax' ) );
            // add_action( 'wp_ajax_rtpm_remove_attachment', array( $this, 'attachment_remove_ajax') );

			// CRM Lead to PM Project - Link Hook
			//add_action( 'rt_crm_after_lead_information', array( $this, 'crm_to_pm_link' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'convert_lead_to_project' )  );
			
			// add_action('wp_ajax__ajax_fetch_custom_list', array($this, '_ajax_fetch_custom_list_callback'));
			// add_action( 'wp_ajax_nopriv__ajax_fetch_custom_list', array( $this, '_ajax_fetch_custom_list_callback' ) );
        }

		//function _ajax_fetch_custom_list_callback() {
 
		   // $rtpm_bp_pm_time_entry_list = new Rt_PM_BP_PM_Time_Entry_List_View();
		    //$rtpm_bp_pm_time_entry_list->ajax_response();
		//}
		

		function convert_lead_to_project() {
			if ( ! isset( $_REQUEST['rt_pm_convert_to_project'] ) ) {
				return;
			}
                        
			$lead_id = $_REQUEST['rt_pm_convert_to_project'];
			$lead = get_post( $lead_id );       
        
			$project = array();
			$project['post_title'] = $lead->post_title;
			$project['post_type'] = $this->post_type;
			$project['post_status'] = 'new';
			$project['post_content'] = $lead->post_content;
			$project['post_date'] = current_time( 'mysql' );
			$project['post_date_gmt'] = gmdate('Y-m-d H:i:s');
                        $project_id = wp_insert_post( $project );
                        
                        $attachments = get_posts( array(
                            'post_parent' => $lead_id,
                            'post_type' => 'attachment',
                            'fields' => 'ids',
                            'posts_per_page' => -1,
                        ));
                        
                        $lead_term = rt_biz_get_post_for_organization_connection( $lead->ID, $lead->post_type );
                         
                        $project_organization=array();
                        foreach ($lead_term as $tterm) {
                            array_push($project_organization,$tterm->p2p_to);
                        }
                        
                        $lead_term = rt_biz_get_post_for_person_connection( $lead->ID, $lead->post_type );
								
                        $project_client=array();
                        foreach ($lead_term as $tterm) {
                              array_push($project_client,$tterm->p2p_to);                                  
                        }
                        
                         $data = array(		
                        'project_organization' => $project_organization,
                        'project_client' => $project_client
                        );
                   
                
                        foreach ( $data as $key=>$value ) {
                             update_post_meta( $project_id, $key, $value );
                        }
                        
                        foreach ( $attachments as $attachment ) {
                     
                            $filepath = get_attached_file( $attachment );
                            $post_attachment_hashes = get_post_meta( $project_id, '_rt_wp_pm_attachment_hash' );
                                
                            if ( ! empty( $post_attachment_hashes ) && in_array( md5_file( $filepath ), $post_attachment_hashes ) ) {
                                continue;
                            }
                            
                            $file = get_post($attachment);
                            if( !empty( $file->post_parent ) ) {
                                 $args = array(
                                'post_mime_type' => $file->post_mime_type,
                                'guid' => $file->guid,
                                'post_title' => $file->post_title,
                                'post_content' => $file->post_content,
                                'post_parent' => $project_id,
                                'post_author' => get_current_user_id(),
                            );
                           
                            wp_insert_attachment( $args, $file->guid, $project_id );
                           
                            add_post_meta( $project_id, '_rt_wp_pm_attachment_hash', md5_file( $filepath ) );
                          }
			
                        }

			do_action( 'rt_pm_convert_lead_to_project', $lead_id, $project_id );

			wp_safe_redirect( add_query_arg( array( 'post_type' => $this->post_type, 'page' => 'rtpm-add-'.$this->post_type, $this->post_type.'_id' => $project_id ), admin_url( 'edit.php' ) ) );
			die();
		}

		function crm_to_pm_link( $lead, $user_edit ) {
			if ( ! isset( $lead->ID ) ) {
				return;
			}

			if ( ! $user_edit ) {
				return;
			} ?>
			<div class="row collapse">
				<div class="large-4 mobile-large-1 columns">
					<span class="prefix" title="<?php echo Rt_PM_Settings::$settings['menu_label']; ?>"><label><?php echo Rt_PM_Settings::$settings['menu_label']; ?></label></span>
				</div>
				<div class="large-8 mobile-large-3 columns">
					<div class="rtcrm_attr_border"><a class="rtcrm_public_link" target="_blank" href="<?php echo add_query_arg( array( 'rt_pm_convert_to_project' => $lead->ID ) ); ?>"><?php _e( 'Convert to Project' ); ?></a></div>
				</div>
			</div>
		<?php }

		function register_custom_post( $menu_position ) {
            $logo_url = Rt_PM_Settings::$settings['logo_url'];

			if ( empty( $logo_url ) ) {
                $logo_url = RT_PM_URL.'app/assets/img/pm-16X16.png';
			}

			$args = array(
				'labels' => $this->labels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true, // Show the UI in admin panel
				'menu_icon' => $logo_url,
				'menu_position' => $menu_position,
				'supports' => array('title', 'editor', 'comments', 'custom-fields'),
				'capability_type' => $this->post_type,
			);
			register_post_type( $this->post_type, $args );
		}

		function register_custom_statuses() {
			foreach ($this->statuses as $status) {

				register_post_status($status['slug'], array(
					'label' => $status['slug']
					, 'public' => true
					, '_builtin' => false
					, 'label_count' => _n_noop("{$status['name']} <span class='count'>(%s)</span>", "{$status['name']} <span class='count'>(%s)</span>"),
				));
			}
		}

		function get_custom_labels() {
			$menu_label = Rt_PM_Settings::$settings['menu_label'];
			$this->labels = array(
				'name' => __( 'Project' ),
				'singular_name' => __( 'Project' ),
				'menu_name' => $menu_label,
				'all_items' => __( 'Projects' ),
				'add_new' => __( 'Add Project' ),
				'add_new_item' => __( 'Add Project' ),
				'new_item' => __( 'Add Project' ),
				'edit_item' => __( 'Edit Project' ),
				'view_item' => __( 'View Project' ),
				'search_items' => __( 'Search Projects' ),
			);
			return $this->labels;
		}

		function get_custom_statuses() {
			$this->statuses = array(
				array(
					'slug' => 'new',
					'name' => __( 'New' ),
					'description' => __( 'New Project is created' ),
				),
				array(
					'slug' => 'active',
					'name' => __( 'Active' ),
					'description' => __( 'Project is activated' ),
				),
				array(
					'slug' => 'paused',
					'name' => __( 'Paused' ),
					'description' => __( 'Project is under Paused' ),
				),
				array(
					'slug' => 'complete',
					'name' => __( 'Complete' ),
					'description' => __( 'Project is Completed' ),
				),
				array(
					'slug' => 'closed',
					'name' => __( 'Closed' ),
					'description' => __( 'Project is closed' ),
				),
			);
			return $this->statuses;
		}

        function get_custom_menu_order(){
            $this->custom_menu_order = array(
				self::$dashboard_slug,
                'rtpm-all-'.$this->post_type,
                'rtpm-add-'.$this->post_type,
                'edit-tags.php?taxonomy='.Rt_PM_Project_Type::$project_type_tax.'&amp;post_type='.$this->post_type,
                'edit-tags.php?taxonomy='.Rt_PM_Time_Entry_Type::$time_entry_type_tax,
				Rt_PM_User_Reports::$user_reports_page_slug,
                'rtpm-settings',
            );
        }

        /**
         * Custom list page for Projects
         */
		function projects_list_view() {
            $args = array(
                'post_type' => $this->post_type,
                'labels' => $this->labels,
            );
            rtpm_get_template( 'admin/projects-list-view.php', $args );
		}

		function dashboard_ui() {
			$args = array();
            rtpm_get_template( 'admin/dashboard.php', $args );
		}

        /**
         * Custom page ui for Add Project
         */
		function custom_page_ui() {
            $args = array(
                'post_type' => $this->post_type,
                'labels' => $this->labels,
            );
            rtpm_get_template('project.php', $args,'',RT_PM_BP_PM_PATH . 'templates/');
		}

        /**
         * Register post relation between project with task
         */
        function create_connection() {
            global $rt_pm_task;
            p2p_register_connection_type( array(
                'name' => $this->post_type.'_to_'.$rt_pm_task->post_type,
                'from' => $this->post_type,
                'to' => $rt_pm_task->post_type,
            ) );
        }

		public function get_project_task_tab($labels,$user_edit){
            global $rt_pm_project, $rt_pm_bp_pm, $rt_pm_task, $rt_pm_time_entries_model;

            if( ! isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != $rt_pm_project->post_type ) {
                wp_die("Opsss!! You are in restricted area");
            }

            $post_type=$_REQUEST['post_type'];
            $task_post_type=$rt_pm_task->post_type;
            $task_labels=$rt_pm_task->labels;

            //Trash action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'trash' && isset( $_REQUEST[$task_post_type.'_id'] ) ) {
                wp_delete_post( $_REQUEST[$task_post_type.'_id'] );
				$rt_pm_time_entries_model->delete_timeentry( array( 'task_id' => $_REQUEST[$task_post_type.'_id'] ) );
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . '?post_type='.$post_type.'&page=rtpm-add-'.$post_type.'&'.$post_type.'_id='.$_REQUEST[$post_type.'_id'].'&tab='.$post_type.'-task' . '"; </script> ';
				die();
            }

            //restore action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'restore' && isset( $_REQUEST[$task_post_type.'_id'] ) ) {
                wp_untrash_post( $_REQUEST[$task_post_type.'_id'] );
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . '?post_type='.$post_type.'&page=rtpm-add-'.$post_type.'&'.$post_type.'_id='.$_REQUEST[$post_type.'_id'].'&tab='.$post_type.'-task' . '"; </script> ';
				die();
            }

            //Delete action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'delete' && isset( $_REQUEST[$task_post_type.'_id'] ) ) {
                wp_delete_post( $_REQUEST[$task_post_type.'_id'] );
                $rt_pm_project->remove_connect_post_to_entity($task_post_type,$_REQUEST[$task_post_type.'_id']);
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . '?post_type='.$post_type.'&page=rtpm-add-'.$post_type.'&'.$post_type.'_id='.$_REQUEST[$post_type.'_id'].'&tab='.$post_type.'-task' . '"; </script> ';
                die();
            }

            //Check Post object is init or not
            if ( isset( $_POST['post'] ) ) {
                $action_complete= false;
                $newTask = $_POST['post'];
                $creationdate = $newTask['post_date'];
                if ( isset( $creationdate ) && $creationdate != '' ) {
                    try {
                        $dr = date_create_from_format( 'M d, Y H:i A', $creationdate );
                        $UTC = new DateTimeZone('UTC');
                        $dr->setTimezone($UTC);
                        $timeStamp = $dr->getTimestamp();
                        $newTask['post_date'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
                        $newTask['post_date_gmt'] = gmdate('Y-m-d H:i:s', (intval($timeStamp)));
                    } catch ( Exception $e ) {
                        $newTask['post_date'] = current_time( 'mysql' );
                        $newTask['post_date_gmt'] = gmdate('Y-m-d H:i:s');
                    }
                } else {
                    $newTask['post_date'] = current_time( 'mysql' );
                    $newTask['post_date_gmt'] = gmdate('Y-m-d H:i:s');
                }

                $duedate = $newTask['post_duedate'];
                if ( isset( $duedate ) && $duedate != '' ) {
                    try {
                        $dr = date_create_from_format( 'M d, Y H:i A', $duedate );
                        $UTC = new DateTimeZone('UTC');
                        $dr->setTimezone($UTC);
                        $timeStamp = $dr->getTimestamp();
                        $newTask['post_duedate'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
                    } catch ( Exception $e ) {
                        $newTask['post_duedate'] = current_time( 'mysql' );
                    }
                }

                // Post Data to be saved.
                $post = array(
                    'post_content' => $newTask['post_content'],
                    'post_status' => $newTask['post_status'],
                    'post_title' => $newTask['post_title'],
                    'post_date' => $newTask['post_date'],
                    'post_date_gmt' => $newTask['post_date_gmt'],
                    'post_type' => $task_post_type
                );

                $updateFlag = false;
                //check post request is for Update or insert
                if ( isset($newTask['post_id'] ) ) {
                    $updateFlag = true;
                    $post = array_merge( $post, array( 'ID' => $newTask['post_id'] ) );
                    $data = array(
						'post_assignee' => $newTask['post_assignee'],
                        'post_project_id' => $newTask['post_project_id'],
                        'post_duedate' => $newTask['post_duedate'],
                        'date_update' => current_time( 'mysql' ),
                        'date_update_gmt' => gmdate('Y-m-d H:i:s'),
                        'user_updated_by' => get_current_user_id(),
                    );
                    $post_id = @wp_update_post( $post );
                    $rt_pm_project->connect_post_to_entity($task_post_type,$newTask['post_project_id'],$post_id);
                    foreach ( $data as $key=>$value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }else{
                    $data = array(
						'post_assignee' => $newTask['post_assignee'],
                        'post_project_id' => $newTask['post_project_id'],
                        'post_duedate' => $newTask['post_duedate'],
                        'date_update' => current_time( 'mysql' ),
                        'date_update_gmt' => gmdate('Y-m-d H:i:s'),
                        'user_updated_by' => get_current_user_id(),
                        'user_created_by' => get_current_user_id(),
                    );
                    $post_id = @wp_insert_post($post);
                    $rt_pm_project->connect_post_to_entity($task_post_type,$newTask['post_project_id'],$post_id);
                    foreach ( $data as $key=>$value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                    $_REQUEST["new"]=true;
                    $newTask['post_id']= $post_id;
                }

                // Attachments
                $old_attachments = get_posts( array(
                    'post_parent' => $newTask['post_id'],
                    'post_type' => 'attachment',
                    'fields' => 'ids',
                    'posts_per_page' => -1,
                ));
                $new_attachments = array();
                if ( isset( $_POST['attachment'] ) ) {
                    $new_attachments = $_POST['attachment'];
                    foreach ( $new_attachments as $attachment ) {
                        if( !in_array( $attachment, $old_attachments ) ) {
                            $file = get_post($attachment);
                            $filepath = get_attached_file( $attachment );
                            $post_attachment_hashes = get_post_meta( $newTask['post_id'], '_rt_wp_pm_attachment_hash' );
                            if ( ! empty( $post_attachment_hashes ) && in_array( md5_file( $filepath ), $post_attachment_hashes ) ) {
                                continue;
                            }
                            if( !empty( $file->post_parent ) ) {
                                $args = array(
                                    'post_mime_type' => $file->post_mime_type,
                                    'guid' => $file->guid,
                                    'post_title' => $file->post_title,
                                    'post_content' => $file->post_content,
                                    'post_parent' => $newTask['post_id'],
                                    'post_author' => get_current_user_id(),
                                    'post_status' => 'inherit'
                                );
                                $new_attachments_id = wp_insert_attachment( $args, $file->guid, $newTask['post_id'] );
                                /*$new_attach_data=wp_generate_attachment_metadata($new_attachments_id,$filepath);
                                wp_update_attachment_metadata( $new_attachments_id, $new_attach_data );*/
                                add_post_meta( $newTask['post_id'], '_rt_wp_pm_attachment_hash', md5_file( $filepath ) );
                            } else {
                                wp_update_post( array( 'ID' => $attachment, 'post_parent' => $newTask['post_id'] ) );
                                $filepath = get_attached_file( $attachment );
                                add_post_meta( $newTask['post_id'], '_rt_wp_pm_attachment_hash', md5_file( $filepath ) );
                            }
                        }
                    }

                    foreach ( $old_attachments as $attachment ) {
                        if( !in_array( $attachment, $new_attachments ) ) {
                            wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
                            $filepath = get_attached_file( $attachment );
                            delete_post_meta($newTask['post_id'], '_rt_wp_pm_attachment_hash', md5_file( $filepath ) );
                        }
                    }
                } else {
                    foreach ( $old_attachments as $attachment ) {
                        wp_update_post( array( 'ID' => $attachment, 'post_parent' => '0' ) );
                        $filepath = get_attached_file( $attachment );
                        delete_post_meta($newTask['post_id'], '_rt_wp_pm_attachment_hash', md5_file( $filepath ) );
                    }
                    delete_post_meta($newTask['post_id'], '_rt_wp_pm_attachment_hash' );
                }

				echo '<script>window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . "?post_type={$post_type}&{$post_type}_id={$_REQUEST[ "{$post_type}_id" ]}&tab={$post_type}-task"  . '";</script> ';
				$_REQUEST["{$task_post_type}_id"] = null;
                $action_complete= true;
				die();
            }

            //Check for wp error
            if ( isset( $post_id ) && is_wp_error( $post_id ) ) {
                wp_die( 'Error while creating new '. ucfirst( $rt_pm_project->labels['name'] ) );
            }

			$form_ulr = $rt_pm_bp_pm->get_component_root_url().bp_current_action() . "?post_type={$post_type}&{$post_type}_id={$_REQUEST["{$post_type}_id"]}&tab={$post_type}-task";
            ///alert Notification
            if ( isset( $action_complete ) && $action_complete){
                if (isset($_REQUEST["new"])) {
                    ?>
                    <div class="alert-box success">
                        <?php _e('New '.  ucfirst($task_labels['name']).' Inserted Sucessfully.'); ?>
                        <a href="#" class="close">&times;</a>
                    </div>
                <?php
                }
                if(isset($updateFlag) && $updateFlag){ ?>
                    <div class="alert-box success">
                        <?php _e(ucfirst($task_labels['name'])." Updated Sucessfully."); ?>
                        <a href="#" class="close">&times;</a>
                    </div>
                <?php }
            }
            if (isset($_REQUEST["{$task_post_type}_id"])) {
                $form_ulr .= "&{$task_post_type}_id=" . $_REQUEST["{$task_post_type}_id"];
                $post = get_post($_REQUEST["{$task_post_type}_id"]);
                if (!$post) {
                    ?>
                    <div class="alert-box alert">
                        Invalid Post ID
                        <a href="" class="close">&times;</a>
                    </div>
                    <?php
                    $post = false;
                }
                if ( $post->post_type != $task_post_type ) {
                    ?>
                    <div class="alert-box alert">
                        Invalid Post Type
                        <a href="" class="close">&times;</a>
                    </div>
                    <?php
                    $post = false;
                }

                $create = new DateTime($post->post_date);
                $modify = new DateTime($post->post_modified);
                $createdate = $create->format("M d, Y h:i A");
                $modifydate = $modify->format("M d, Y h:i A");

            }else{
                $post=null;
            }

            // get project meta
            if (isset($post->ID)) {
                $due = new DateTime(get_post_meta($post->ID, 'post_duedate', true));
                $due_date = $due->format("M d, Y h:i A");
				$post_assignee = get_post_meta($post->ID, 'post_assignee', true);
			} else {
				$post_assignee = '';
			}

            //assign to
            $results_member = Rt_PM_Utils::get_pm_rtcamp_user();
            ?>

            <?php if (isset($post->ID)){?>
                <script>
                    jQuery(document).ready(function($) {
                        setTimeout(function() {
                            $("#div-add-task").reveal({
                                opened: function(){
                                }
                            });
                        },10);
                    });
                </script>
            <?php } ?>

            <div id="wp-custom-list-table">
			<?php
				if( $user_edit ) {
					if (isset($_REQUEST["{$task_post_type}_id"])) {
						$btntitle = 'Update Task';
					}else{
						$btntitle = 'Add Task';
					}
				?>
				<div class="row list-heading">
					<div class="large-9 columns list-title">
						<h4><?php _e( 'Tasks', RT_PM_TEXT_DOMAIN ) ?></h4>
					</div>
					<div class="large-3 columns">
						<button class="right mybutton add-task" type="button" ><?php _e( $btntitle ); ?></button>
					</div>

				</div>
				<?php
				}
				$rtpm_task_list= new Rt_PM_BP_PM_Task_List_View( $user_edit );
				$rtpm_task_list->prepare_items();
				$rtpm_task_list->display();
			?>
            </div>

			<!--reveal-modal-add-task -->
            <div id="div-add-task" class="reveal-modal">
        		<fieldset>
	                <form method="post" id="form-add-post" data-posttype="<?php echo $task_post_type; ?>" action="<?php echo $form_ulr; ?>">
	                    <input type="hidden" name="post[post_project_id]" id='project_id' value="<?php echo $_REQUEST["{$post_type}_id"]; ?>" />
	                    <?php if (isset($post->ID) && $user_edit ) { ?>
	                        <input type="hidden" name="post[post_id]" id='task_id' value="<?php echo $post->ID; ?>" />
	                    <?php } ?>
	                    <legend><h4> <?php echo (isset($post->ID)) ? $post->post_title : _e( 'Add New Task', RT_PM_TEXT_DOMAIN ) ; ?></h4></legend>
	                    <div class="row collapse postbox">
	                        <div class="large-6 columns">
	                        	<label><?php _e(ucfirst($task_labels['name'])." Name"); ?><small class="required"> * Required</small></label>
	                            <?php if( $user_edit ) { ?>
	                                <input name="post[post_title]" id="new_<?php echo $task_post_type ?>_title" type="text" placeholder="<?php _e(ucfirst($task_labels['name'])." Name"); ?>" value="<?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?>" />
	                            <?php } else { ?>
	                                <span><?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?></span><br /><br />
	                            <?php } ?>
	                            
	                            <?php
	                            if( $user_edit ) {
	                            	?>
	                            	<textarea name="post[post_content]" rows="5" type="text" placeholder="<?php _e("Message"); ?>" ><?php echo ( isset($post->ID ) ) ? trim($post->post_content) : ""; ?></textarea>
	                                <?php
	                                //wp_editor( ( isset( $post->ID ) ) ? $post->post_content : "", "post_content", array( 'textarea_name' => 'post[post_content]', 'media_buttons' => false, 'tinymce' => false, 'quicktags' => false, 'textarea_rows' => 5 ) );
	                            } else {
	                                echo ucfirst($labels['name']).' Content : <br /><br /><span>'.(( isset($post->ID) ) ? trim($post->post_content) : '').'</span><br /><br />';
	                            }
	                            ?>
	                            <fieldset>
	                            <div class="row collapse">
	                            	<div class="large-6 columns">
		                                <span class="status-hidden" title="Status"><label>Status</label></span>
		                            </div>
	                            	<div class="large-6 columns push-1">
		                                <span class="create-date-hidden" title="Create Date"><label>Create Date</label></span>
		                            </div><hr />
	                        	</div>
	                        	
	                        	<div class="row collapse">
	                        		<div class="large-6 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
	                        			<span class="hidden" title="Status"><label>Status</label></span>
		                                <?php
		                                if (isset($post->ID))
		                                    $pstatus = $post->post_status;
		                                else
		                                    $pstatus = "";
		                                $post_status = $rt_pm_task->get_custom_statuses();
		                                $custom_status_flag = true;
		                                ?>
		                                <?php if( $user_edit ) { ?>
		                                    <select id="rtpm_post_status" class="right" name="post[post_status]">
		                                        <?php foreach ($post_status as $status) {
		                                            if ($status['slug'] == $pstatus) {
		                                                $selected = 'selected="selected"';
		                                                $custom_status_flag = false;
		                                            } else {
		                                                $selected = '';
		                                            }
		                                            printf('<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name']);
		                                        } ?>
		                                        <?php if ( $custom_status_flag && isset( $post->ID ) ) { echo '<option selected="selected" value="'.$pstatus.'">'.$pstatus.'</option>'; } ?>
		                                    </select>
		                                <?php } else {
		                                    foreach ( $post_status as $status ) {
		                                        if($status['slug'] == $pstatus) {
		                                            echo '<span class="rtpm_view_mode">'.$status['name'].'</span>';
		                                            break;
		                                        }
		                                    }
		                                } ?>
		                            </div>
		                            <div class="large-1 mobile-large-1 columns">&nbsp;&nbsp;&nbsp;&nbsp;</div>
		                            <div class="large-5 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
		                            	<span class="hidden" title="Create Date"><label>Create Date</label></span>
		                                <?php if( $user_edit ) { ?>
		                                    <input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
		                                           value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>"
		                                           title="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" id="create_<?php echo $task_post_type ?>_date">
		                                    <input name="post[post_date]" type="hidden" value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" />
		                                <?php } else { ?>
		                                    <span class="rtpm_view_mode moment-from-now"><?php echo $createdate ?></span>
		                                <?php } ?>
		                            </div>
	                    		</div>
	                    		</fieldset>
	                    		<fieldset>
	                    		<div class="row collapse">
	                            	<div class="large-6 columns">		                                
		                                <span class="assigned-to-hidden" title="Assigned To"><label for="post[post_assignee]">Assigned To</label></span>
		                            </div>
	                            	<div class="large-6 columns push-1">
		                                <span class="due-date-hidden" title="Due Date"><label>Due Date<small class="required"> * Required</small></label></span>
		                            </div><hr />
	                        	</div>
	                        	<div class="row collapse">
	                        		<div class="large-6 columns">
	                        			<span class="hidden" title="Assigned To"><label for="post[post_assignee]">Assigned To</label></span>
		                                <?php if( $user_edit ) { ?>
		                                    <select name="post[post_assignee]" >
												<option value=""><?php _e( 'Select Assignee' ); ?></option>
		                                        <?php
		                                        if (!empty($results_member)) {
		                                            foreach ($results_member as $author) {
		                                                if ($author->ID == $post_assignee) {
		                                                    $selected = " selected";
		                                                } else {
		                                                    $selected = " ";
		                                                }
		                                                echo '<option value="' . $author->ID . '"' . $selected . '>' . $author->display_name . '</option>';
		                                            }
		                                        }
		                                        ?>
		                                    </select>
		                                <?php } ?>
		                            </div>
		                            <div class="large-1 mobile-large-1 columns">&nbsp;&nbsp;&nbsp;&nbsp;</div>
		                            <div class="large-5 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
		                                <span class="hidden" title="Due Date"><label>Due Date<small class="required"> * Required</small></label></span>
		                                <?php if( $user_edit ) { ?>
		                                    <input class="datetimepicker moment-from-now" type="text" placeholder="Select Due Date"
		                                           value="<?php echo ( isset($due_date) ) ? $due_date : ''; ?>"
		                                           title="<?php echo ( isset($due_date) ) ? $due_date : ''; ?>" id="due_<?php echo $task_post_type ?>_date">
		                                    <input name="post[post_duedate]" type="hidden" value="<?php echo ( isset($due_date) ) ? $due_date : ''; ?>" />
		                                <?php } else { ?>
		                                    <span class="rtpm_view_mode moment-from-now"><?php echo $duedate ?></span>
		                                <?php } ?>
		                            </div>
	                        	</div>
	                        	</fieldset>
	                        </div>
	                        <div class="large-6 columns">
	                        	<?php $attachments = array();
		                        if ( isset( $post->ID ) ) {
		                            $attachments = get_posts( array(
		                                'posts_per_page' => -1,
		                                'post_parent' => $post->ID,
		                                'post_type' => 'attachment',
		                            ));
		                        }
		                        ?>
	                            <div class="inside">
	                                <div class="row collapse" id="attachment-container">
	                                    <?php if( $user_edit ) { ?>
	                                        <a href="#" class="button push-1" id="add_pm_attachment"><?php _e('Add Docs'); ?></a>
	                                    <?php } ?>
	                                    <div class="scroll-height">
	                                    	<table>
	                                    	 <?php if ( ! empty($attachments)){?>
												  <tr>
												    <th scope="column">Type</th>
												    <th scope="column">Name</th>
												    <th scope="column">Size</th>
												    <th scope="column"></th>
												  </tr>
		                                        <?php foreach ($attachments as $attachment) { ?>
		                                            <?php $extn_array = explode('.', $attachment->guid); $extn = $extn_array[count($extn_array) - 1]; ?>
		                                            <tr class="large-12 mobile-large-3 attachment-item" data-attachment-id="<?php echo $attachment->ID; ?>">
												    	<td scope="column"><img height="20px" width="20px" src="<?php echo RT_PM_URL . "app/assets/file-type/" . $extn . ".png"; ?>" /></td>
												    	<td scope="column">
												    		<a target="_blank" href="<?php echo wp_get_attachment_url($attachment->ID); ?>">
		                                                    	<?php echo '<span>'.$attachment->post_title .".".$extn.'</span>'; ?>
		                                                	</a>
		                                                </td>
												    	<td scope="column">
												    	<?php
												    		$attached_file = get_attached_file( $attachment->ID );
																if ( file_exists( $attached_file ) ) {
																	$bytes = filesize( $attached_file );
																	$response['filesizeInBytes'] = $bytes;
																	echo '<span>'. $response['filesizeHumanReadable'] = size_format( $bytes ) .'</span>';
																}
															?>
														</td>
														<td scope="column">
															<?php if( $user_edit ) { ?>
			                                                    <a href="#" class="rtpm_delete_attachment right">x</a>
			                                                <?php } ?>
			                                                <input type="hidden" name="attachment[]" value="<?php echo $attachment->ID; ?>" />
														</td>
												  	</tr>
		                                        <?php } ?>
	                                        <?php } ?>
	                                        </table>
	                                    </div>
	                                </div>
	                            </div>
	                        	
	                        </div>
	                        <div class="row collapse right">
	                        	<button class="mybutton " type="submit" id="save-task">Save task</button>
	                        </div>
	                    </div>
	            		
	                </form>
                </fieldset>
            <a class="close-reveal-modal">×</a>
            </div>

        <?php }
		
        public function get_project_timeentry_tab($labels,$user_edit){
            global $rt_pm_project, $rt_pm_bp_pm, $rt_pm_task,$rt_pm_time_entries,$rt_pm_time_entries_model;

            if( ! isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != $rt_pm_project->post_type ) {
                wp_die("Opsss!! You are in restricted area");
            }

            $post_type=$_REQUEST['post_type'];
            $task_post_type=$rt_pm_task->post_type;
            $timeentry_labels = $rt_pm_time_entries->labels;
            $timeentry_post_type = Rt_PM_Time_Entries::$post_type;

            //Trash action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'delete' && isset( $_REQUEST[$timeentry_post_type.'_id'] ) ) {
                $rt_pm_time_entries_model->delete_timeentry( array( 'id' => $_REQUEST[$timeentry_post_type.'_id'] ) );
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action(). '?post_type='.$post_type.'&page=rtpm-add-'.$post_type.'&'.$post_type.'_id='.$_REQUEST[$post_type.'_id'].'&tab='.$post_type.'-timeentry' . '"; </script> ';
                die();
            }

            //Check Post object is init or not
            if ( isset( $_POST['post'] ) ) {
                $action_complete = false;
                $newTimeEntry = $_POST['post'];
                $creationdate = $newTimeEntry['post_date'];
                if ( isset( $creationdate ) && $creationdate != '' ) {
                    try {
                        $dr = date_create_from_format( 'M d, Y H:i A', $creationdate );
                        $UTC = new DateTimeZone('UTC');
                        $dr->setTimezone($UTC);
                        $timeStamp = $dr->getTimestamp();
                        $newTimeEntry['post_date'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
                    } catch ( Exception $e ) {
                        $newTimeEntry['post_date'] = current_time( 'mysql' );
                    }
                } else {
                    $newTimeEntry['post_date'] = current_time( 'mysql' );
                }

                // Post Data to be saved.
                $post = array(
                    'project_id' => $newTimeEntry['post_project_id'],
                    'task_id' => $newTimeEntry['post_task_id'],
                    'type' => $newTimeEntry['post_timeentry_type'],
                    'message' => $newTimeEntry['post_title'],
                    'time_duration' => $newTimeEntry['post_duration'],
                    'timestamp' => $newTimeEntry['post_date'],
                    'author' => get_current_user_id(),
                );
                $updateFlag = false;
                //check post request is for Update or insert
                if ( isset($newTimeEntry['post_id'] ) ) {
                    $updateFlag = true;
                    $where = array( 'id' => $newTimeEntry['post_id'] );
                    $post_id = $rt_pm_time_entries_model->update_timeentry($post,$where);
                }else{
                    $post_id = $rt_pm_time_entries_model->add_timeentry($post);
                    $_REQUEST["new"]=true;
                }
				
				// Used for notification -- Regeistered in RT_PM_Notification
				do_action( 'rt_pm_time_entry_saved', $newTimeEntry, $author = get_current_user_id(), $this );

				echo '<script>window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . "?post_type={$post_type}&{$post_type}_id={$_REQUEST[ "{$post_type}_id" ]}&tab={$post_type}-timeentry" . '";</script> ';
                $_REQUEST["{$timeentry_post_type}_id"] = null;
                $action_complete= true;
				die();
            }

            if ( isset( $action_complete ) && $action_complete ){
                if (isset($_REQUEST["new"])) {
                    ?>
                    <div class="alert-box success">
                        <?php _e('New '.  ucfirst($timeentry_labels['name']).' Inserted Sucessfully.'); ?>
                        <a href="#" class="close">&times;</a>
                    </div>
                <?php
                }
                if(isset($updateFlag) && $updateFlag){ ?>
                    <div class="alert-box success">
                        <?php _e(ucfirst($timeentry_labels['name'])." Updated Sucessfully."); ?>
                        <a href="#" class="close">&times;</a>
                    </div>
                <?php }
            }

            $form_ulr = $rt_pm_bp_pm->get_component_root_url().bp_current_action() . "?post_type={$post_type}&{$post_type}_id={$_REQUEST["{$post_type}_id"]}&tab={$post_type}-timeentry";//&{$task_post_type}_id={$_REQUEST["{$task_post_type}_id"]}
            ///alert Notification
            if (isset($_REQUEST["{$timeentry_post_type}_id"])) {
                $form_ulr .= "&{$timeentry_post_type}_id=" . $_REQUEST["{$timeentry_post_type}_id"];
                $post = $rt_pm_time_entries_model->get_timeentry($_REQUEST["{$timeentry_post_type}_id"]);
                if (!$post) {
                    ?>
                    <div class="alert-box alert">
                        Invalid Post ID
                        <a href="" class="close">&times;</a>
                    </div>
                    <?php
                    $post = false;
                }

                $create = new DateTime($post->timestamp);
                $createdate = $create->format("M d, Y h:i A");
            }

            // get project meta
            if (isset($post->id)) {
                $_REQUEST["{$post_type}_id"]=$post->project_id;
                $task_id=$post->task_id;
            } else {
                if ( isset ( $_REQUEST["{$task_post_type}_id"] ) ) {
                    $task_id= $_REQUEST["{$task_post_type}_id"];
                }
            }
            ?>

            <?php if (isset($post->id) || ( isset( $_REQUEST["action"] ) && $_REQUEST["action"]=="timeentry")){?>
                <script>
                    jQuery(document).ready(function($) {
                        setTimeout(function() {
                            jQuery(".add-time-entry").trigger('click');
                        },10);
                    });
                </script>
            <?php } ?>

            <div id="wp-custom-list-table">
			<?php
				if( $user_edit ) {
					if (isset($_REQUEST["{$timeentry_post_type}_id"])) {
						$btntitle = 'Update Time Entry';
					}else{
						$btntitle = 'Add Time Entry';
					}
					?>
					<div class="row list-heading">
						<div class="large-8 columns list-title">
							<h4><?php _e( 'Time Entries', RT_PM_TEXT_DOMAIN ) ?></h4>
						</div>
						<div class="large-4 columns">
							<button class="right mybutton add-time-entry" type="button" ><?php _e($btntitle, RT_PM_TEXT_DOMAIN ); ?></button>
						</div>

					</div>
					<?php
				}

				$project_current_budget_cost = 0;
				$project_current_time_cost = 0;
				$time_entries = $rt_pm_time_entries_model->get_by_project_id( $_REQUEST["{$post_type}_id"] );
				if ( $time_entries['total'] && ! empty( $time_entries['result'] ) ) {
					foreach ( $time_entries['result'] as $time_entry ) {
						$type = $time_entry['type'];
						$term = get_term_by( 'slug', $type, Rt_PM_Time_Entry_Type::$time_entry_type_tax );
						$project_current_budget_cost += floatval( $time_entry['time_duration'] ) * Rt_PM_Time_Entry_Type::get_charge_rate_meta_field( $term->term_id );
						$project_current_time_cost += $time_entry['time_duration'];
					}
				}
			?>

				<div id="rtpm_project_cost_report" class="row collapse">
					<div class="large-3 columns">
						<h5><?php _e( 'Total Project Cost:'); ?>&nbsp;<?php echo '$ '.$project_current_budget_cost; ?></h5>
					</div>
					<div class="large-3 columns">
						<h5><?php _e( 'Project Budget:'); ?>&nbsp;<?php echo '$ '.floatval( get_post_meta( $_REQUEST["{$post_type}_id"], '_rtpm_project_budget', true ) ); ?></h5>
					</div>
					<div class="large-3 columns">
						<h5><?php _e( 'Total Time spent:'); ?>&nbsp;<?php echo $project_current_time_cost.__(' hours'); ?></h5>
					</div>
					<div class="large-3 columns">
						<h5><?php _e( 'Project Estimated Time:'); ?>&nbsp;<?php echo floatval( get_post_meta( $_REQUEST["{$post_type}_id"], 'project_estimated_time', true ) ).__(' hours'); ?></h5>
					</div>
				</div>

				<?php
				$rtpm_bp_pm_time_entry_list = new Rt_PM_BP_PM_Time_Entry_List_View();
				$rtpm_bp_pm_time_entry_list->prepare_items();
				$rtpm_bp_pm_time_entry_list->display();
				?>
			</div>

            <!--reveal-modal-add-task -->
            <div id="div-add-time-entry" class="reveal-modal">
                <fieldset>
                    
                    <form method="post" id="form-add-post" data-posttype="<?php echo $timeentry_post_type; ?>" action="<?php echo $form_ulr; ?>">
                        <input type="hidden" name="post[post_project_id]" id='project_id' value="<?php echo $_REQUEST["{$post_type}_id"]; ?>" />
                        <?php if (isset($post->id) && $user_edit ) { ?>
                            <input type="hidden" name="post[post_id]" id='task_id' value="<?php echo $post->id; ?>" />
                        <?php } ?>
                        <legend><h4>Time Entry</h4></legend><hr />
                        <div class="row collapse">
                            <div class="large-2 mobile-large-2 columns">
                                <label for="Task">Task</label>
                            </div>
							<div class="large-10 mobile-large-6 columns">
							<?php
                            $rtpm_task_list= new Rt_PM_Task_List_View( $user_edit );
                            $rtpm_task_list->prepare_items();
                            $rtpm_task_list->get_drop_down($task_id);
                            ?>
							</div>
                        </div>
						<div class="row collapse rtpm-post-author-wrapper">
                            <div class="large-2 mobile-large-2 columns">
                                <label for="post[post_timeentry_type]">Type</label>
                            </div>
                            <div class="large-10 mobile-large-6 columns">
								<?php $terms = get_terms( Rt_PM_Time_Entry_Type::$time_entry_type_tax, array( 'hide_empty' => false, 'order' => 'asc' ) ); ?>
                                <?php if( $user_edit ) { ?>
                                    <select name="post[post_timeentry_type]" >
									<?php foreach ( $terms as $term ) { ?>
										<option <?php echo isset($post) && $post->type == $term->slug ? 'selected="selected"' :''; ?> value="<?php echo $term->slug; ?>" ><?php echo $term->name; ?></option>
									<?php } ?>
                                    </select>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="row collapse">
                        	<div class="large-2 mobile-large-2 columns">
                                <label for="post[post_duration]">Time</label>
                            </div>
                            <div class="large-3 mobile-large-3 columns">
                                <?php if( $user_edit ) { ?>
								<input type="number" name="post[post_duration]" step="0.25" min="0" value="<?php echo ( isset( $post ) ) ? $post->time_duration : ''; ?>" />
                                <?php } ?>
                            </div>
                            <div class="large-1 mobile-large-1 columns">&nbsp;</div>
                            <div class="large-2 mobile-large-4 columns">
                                <label>Date Created</label>
                            </div>
                            <div class="large-4 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                <?php if( $user_edit ) { ?>
                                    <input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
                                           value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>"
                                           title="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" id="create_<?php echo $timeentry_post_type ?>_date">
                                    <input name="post[post_date]" type="hidden" value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" />
                                <?php } else { ?>
                                    <span class="rtpm_view_mode moment-from-now"><?php echo $createdate ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="row collapse postbox">
                            <div class="large-12 columns">
                            	<label>Message<small class="required"> * Required</small></label>
                                <?php if( $user_edit ) { ?>
                                    <textarea name="post[post_title]" id="new_<?php echo $timeentry_post_type ?>_title" type="text" placeholder="<?php _e("Message"); ?>" ><?php echo ( isset($post->id) ) ? $post->message : ""; ?> </textarea>
                                <?php } else { ?>
                                    <span><?php echo ( isset($post->id) ) ? trim($post->message) : ""; ?></span><br /><br />
                                <?php } ?>
                            </div>
                        </div>
                        <?php 
						if (isset($_REQUEST["{$timeentry_post_type}_id"])) {
							$btntitle = __( 'Save Time Entry' );
						} else {
							$btntitle = __( 'Add Time Entry' );
						}
						?>
                        <button class="mybutton right" type="submit" id="save-task"><?php _e( $btntitle ); ?></button>
                    </form>
                </fieldset>
                <a class="close-reveal-modal">×</a>
            </div>

        <?php }

        public function get_project_description_tab($labels,$user_edit){
            global $rt_pm_project,$rt_pm_bp_pm, $rt_pm_project_type, $rt_pm_task, $rt_pm_time_entries_model, $bp;

            if( ! isset( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != $rt_pm_project->post_type ) {
                wp_die("Opsss!! You are in restricted area");
            }

            $post_type=$_REQUEST['post_type'];

            //Trash action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'trash' && isset( $_REQUEST[$post_type.'_id'] ) ) {
                wp_delete_post( $_REQUEST[$post_type.'_id'] );
				$args = array(
					'post_type' =>  $rt_pm_task->post_type,
					'post_status' => 'any',
					'meta_query' => array(
						'key' => Rt_PM_Task_List_View::$project_id_key,
						'value' => array( $_REQUEST[$post_type.'_id'] ),
					),
				);
				$tasks = get_posts( $args );
				foreach ( $tasks as $t ) {
					wp_delete_post( $t );
				}
				$rt_pm_time_entries_model->delete_timeentry( array( 'project_id' => $_REQUEST[$post_type.'_id'] ) );
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . '"; </script> ';
                die();
            }
			
			//Archive action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'archive' && isset( $_REQUEST[$post_type.'_id'] ) ) {
                wp_trash_post( $_REQUEST[$post_type.'_id'] );
				$args = array(
					'post_type' =>  $rt_pm_task->post_type,
					'post_status' => 'any',
					'meta_query' => array(
						'key' => Rt_PM_Task_List_View::$project_id_key,
						'value' => array( $_REQUEST[$post_type.'_id'] ),
					),
				);
				$tasks = get_posts( $args );
				foreach ( $tasks as $t ) {
					// wp_trash_post( $t );
				}
				// $rt_pm_time_entries_model->delete_timeentry( array( 'project_id' => $_REQUEST[$post_type.'_id'] ) );
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . '"; </script> ';
                die();
            }

			//UnArchive action
            if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'unarchive' && isset( $_REQUEST[$post_type.'_id'] ) ) {
				$unarchive_post = array(
					'ID'           => $_REQUEST[$post_type.'_id'],
					'post_status' => 'active'
				);
                wp_update_post( $unarchive_post );
				$args = array(
					'post_type' =>  $rt_pm_task->post_type,
					'post_status' => 'any',
					'meta_query' => array(
						'key' => Rt_PM_Task_List_View::$project_id_key,
						'value' => array( $_REQUEST[$post_type.'_id'] ),
					),
				);
				$tasks = get_posts( $args );
				foreach ( $tasks as $t ) {
					//wp_delete_post( $t );
				}
				// $rt_pm_time_entries_model->delete_timeentry( array( 'project_id' => $_REQUEST[$post_type.'_id'] ) );
				echo '<script> window.location="' . $rt_pm_bp_pm->get_component_root_url().bp_current_action() . '"; </script> ';
                die();
            }

            //Check Post object is init or not
            if ( isset( $_POST['post'] ) ) {
                $newProject = $_POST['post'];
                $creationdate = $newProject['post_date'];
                if ( isset( $creationdate ) && $creationdate != '' ) {
                    try {
                        $dr = date_create_from_format( 'M d, Y H:i A', $creationdate );
                        $UTC = new DateTimeZone('UTC');
                        $dr->setTimezone($UTC);
                        $timeStamp = $dr->getTimestamp();
                        $newProject['post_date'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
                        $newProject['post_date_gmt'] = gmdate('Y-m-d H:i:s', (intval($timeStamp)));
                    } catch ( Exception $e ) {
                        $newProject['post_date'] = current_time( 'mysql' );
                        $newProject['post_date_gmt'] = gmdate('Y-m-d H:i:s');
                    }
                } else {
                    $newProject['post_date'] = current_time( 'mysql' );
                    $newProject['post_date_gmt'] = gmdate('Y-m-d H:i:s');
                }
				
				// Change format for post_duedate
				$postduedate = $newProject['post_duedate'];
				if ( isset( $postduedate ) && $postduedate != '' ) {
					$dr = date_create_from_format( 'M d, Y H:i A', $postduedate );
					$UTC = new DateTimeZone('UTC');
					$dr->setTimezone($UTC);
					$timeStamp = $dr->getTimestamp();
					$newProject['post_duedate'] = gmdate('Y-m-d H:i:s', (intval($timeStamp) + ( get_option('gmt_offset') * 3600 )));
				}

                // Post Data to be saved.
                $post = array(
                    'post_content' => $newProject['post_content'],
                    'post_status' => $newProject['post_status'],
                    'post_title' => $newProject['post_title'],
                    'post_date' => $newProject['post_date'],
                    'post_date_gmt' => $newProject['post_date_gmt'],
                    'post_type' => $post_type
                );

                $updateFlag = false;
                //check post request is for Update or insert
                if ( isset($newProject['post_id'] ) ) {
                    $updateFlag = true;
                    $post = array_merge( $post, array( 'ID' => $newProject['post_id'] ) );
                    $data = array(
						'project_manager' => $newProject['project_manager'],
                        'post_completiondate' => $newProject['post_completiondate'],
                        'post_duedate' => $newProject['post_duedate'],
						'project_estimated_time' => $newProject['project_estimated_time'],
                        'project_client' => $newProject['project_client'],
                        'project_organization' => $newProject['project_organization'],
                        'project_member' => isset($newProject['project_member'])? $newProject['project_member'] : '',
						'business_manager' => $newProject['business_manager'],
						'_rtpm_status_detail' => $newProject['status_detail'],
						'_rtpm_project_budget' => $newProject['project_budget'],
                        'date_update' => current_time( 'mysql' ),
                        'date_update_gmt' => gmdate('Y-m-d H:i:s'),
                        'user_updated_by' => get_current_user_id(),
                    );
                    $post_id = @wp_update_post( $post );
                    $rt_pm_project_type->save_project_type($post_id,$newProject);
                    $data = apply_filters( 'rt_pm_project_detail_meta', $data);
                    foreach ( $data as $key=>$value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }else{
                    $data = array(
						'project_manager' => $newProject['project_manager'],
                        'post_completiondate' => $newProject['post_completiondate'],
                        'post_duedate' => $newProject['post_duedate'],
                        'project_estimated_time' => $newProject['project_estimated_time'],
                        'project_client' => $newProject['project_client'],
                        'project_organization' => $newProject['project_organization'],
                        'project_member' => $newProject['project_member'],
						'business_manager' => $newProject['business_manager'],
						'_rtpm_status_detail' => $newProject['status_detail'],
						'_rtpm_project_budget' => $newProject['project_budget'],
                        'date_update' => current_time( 'mysql' ),
                        'date_update_gmt' => gmdate('Y-m-d H:i:s'),
                        'user_updated_by' => get_current_user_id(),
                        'user_created_by' => get_current_user_id(),
                    );
                    $post_id = @wp_insert_post($post);
                    $rt_pm_project_type->save_project_type($post_id,$newProject);
                    $data = apply_filters( 'rt_pm_project_detail_meta', $data);
                    foreach ( $data as $key=>$value ) {
                        update_post_meta( $post_id, $key, $value );
                    }
                }
                $_REQUEST[$post_type."_id"] = $post_id;
				$bp_bp_nav_link = $rt_pm_bp_pm->get_component_root_url().bp_current_action();
				$bp_bp_nav_link .= "?post_type={$post_type}&page=rtpm-add-{$post_type}&{$post_type}_id={$_REQUEST["{$post_type}_id"]}&tab={$post_type}-details";
				echo '<script> window.location="' . $bp_bp_nav_link .'"; </script> ';
				die();
            }

            //Check for wp error
            if ( isset($post_id) && is_wp_error( $post_id ) ) {
                wp_die( 'Error while creating new '. ucfirst( $rt_pm_project->labels['name'] ) );
            }

			$form_ulr = add_query_arg( array( 'post_type' => $post_type ), $rt_pm_bp_pm->get_component_root_url().bp_current_action() );

            ///alert Notification
            if (isset($_REQUEST["{$post_type}_id"])) {
                $form_ulr .= "&{$post_type}_id=" . $_REQUEST["{$post_type}_id"];
                if (isset($_REQUEST["new"])) {
                    ?>
                    <div class="alert-box success">
                        <?php _e('New '.  ucfirst($labels['name']).' Inserted Sucessfully.'); ?>
                        <a href="#" class="close">&times;</a>
                    </div>
                <?php
                }
                if(isset($updateFlag) && $updateFlag){ ?>
                    <div class="alert-box success">
                        <?php _e(ucfirst($labels['name'])." Updated Sucessfully."); ?>
                        <a href="#" class="close">&times;</a>
                    </div>
                <?php }
                $post = get_post($_REQUEST["{$post_type}_id"]);
                if (!$post) {
                    ?>
                    <div class="alert-box alert">
                        Invalid Post ID
                        <a href="" class="close">&times;</a>
                    </div>
                    <?php
                    $post = false;
                }
                if ( $post->post_type != $rt_pm_project->post_type ) {
                    ?>
                    <div class="alert-box alert">
                        Invalid Post Type
                        <a href="" class="close">&times;</a>
                    </div>
                    <?php
                    $post = false;
                }

                $create = new DateTime($post->post_date);

                $modify = new DateTime($post->post_modified);
                $createdate = $create->format("M d, Y h:i A");
                $modifydate = $modify->format("M d, Y h:i A");

            }

            // get project meta
            if (isset($post->ID)) {
                $post_author = $post->post_author;
				$project_manager = get_post_meta( $post->ID, "project_manager", true );
                $project_member = get_post_meta($post->ID, "project_member", true);
                $project_client = get_post_meta($post->ID, "project_client", true);
				$project_organization = get_post_meta($post->ID, "project_organization", true);
                $completiondate= get_post_meta($post->ID, 'post_completiondate', true);
                $duedate= get_post_meta($post->ID, 'post_duedate', true);
				if ( ! empty( $duedate ) ){
					$duedate = new DateTime($duedate); // date formating hack
					$duedate = $duedate->format("M d, Y h:i A"); // date formating hack
				}
				$business_manager = get_post_meta( $post->ID, 'business_manager', true );
            } else {
                $post_author = get_current_user_id();
            }

            //project manager & project members
            $results_member = Rt_PM_Utils::get_pm_rtcamp_user();
            $arrProjectMember = array();
            $subProjectMemberHTML = "";
            if( !empty( $results_member ) ) {
                foreach ( $results_member as $author ) {
                    if (isset($project_member) && $project_member && !empty($project_member) && in_array($author->ID, $project_member)) {
                        $subProjectMemberHTML .= "<li id='project-member-auth-" . $author->ID
                            . "' class='contact-list pull-1'>" . get_avatar($author->user_email, 24) . '<a target="_blank" class="heading" title="'.$author->display_name.'" href="'.get_edit_user_link($author->ID).'">'.$author->display_name.'</a>'
                            . "<a class='right' href='#removeProjectMember'><i class='foundicon-remove'></i></a>
                                        <input type='hidden' name='post[project_member][]' value='" . $author->ID . "' /></li>";
                    }
                    $arrProjectMember[] = array("id" => $author->ID, "label" => $author->display_name, "imghtml" => get_avatar($author->user_email, 24), 'user_edit_link'=>  get_edit_user_link($author->ID));
                }
            }

            //Project client
            $results_client = Rt_PM_Utils::get_pm_client_user();
            $arrProjectClient = array();
            $subProjectClientHTML = "";
            if( !empty( $results_client ) ) {
                foreach ( $results_client as $client ) {
					$email = rt_biz_get_entity_meta( $client->ID, $this->contact_email_key, true );
                    if (isset($project_client) && $project_client && !empty($project_client) && in_array($client->ID, $project_client)) {
                        $subProjectClientHTML .= "<li id='project-client-auth-" . $client->ID
                            . "' class='contact-list pull-1'>" . get_avatar($email, 24) . '<a target="_blank" class="heading" title="'.$client->post_title.'" href="'.get_edit_user_link($client->ID).'">'.$client->post_title.'</a>'
                            . "<a class='right' href='#removeProjectClient'><i class='foundicon-remove'></i></a>
                                        <input type='hidden' name='post[project_client][]' value='" . $client->ID . "' /></li>";
                    }
					$connection = rt_biz_get_organization_to_person_connection( $client->ID );
					$org = array();
					foreach ( $connection as $c ) {
						$org[] = $c->ID;
					}
                    $arrProjectClient[] = array("id" => $client->ID, "label" => $client->post_title, "imghtml" => get_avatar($email, 24), 'user_edit_link'=>  get_edit_user_link($client->ID), 'organization' => $org);
                }
            }

			//Project organization
            $results_organization = Rt_PM_Utils::get_pm_organizations();
            $arrProjectOrganizations = array();
            $subProjectOrganizationsHTML = "";
            if( !empty( $results_organization ) ) {
                foreach ( $results_organization as $organization ) {
					$email = rt_biz_get_entity_meta( $organization->ID, $this->organization_email_key, true );
                    if (isset($project_organization) && $project_organization && !empty($project_organization) && in_array($organization->ID, $project_organization)) {
                        $subProjectOrganizationsHTML .= "<li id='project-org-auth-" . $organization->ID
                            . "' class='contact-list pull-1'>" . get_avatar($email, 24) . '<a target="_blank" class="heading" title="'.$organization->post_title.'" href="'.get_edit_user_link($organization->ID).'">'.$organization->post_title.'</a>'
                            . "<a class='right' href='#removeProjectOrganization'><i class='foundicon-remove'></i></a>
                                        <input type='hidden' name='post[project_organization][]' value='" . $organization->ID . "' /></li>";
                    }
                    $arrProjectOrganizations[] = array("id" => $organization->ID, "label" => $organization->post_title, "imghtml" => get_avatar($email, 24), 'user_edit_link'=>  get_edit_user_link($organization->ID));
                }
            }

            ?>
            <div id="add-new-post" class="row">
                <?php if( $user_edit ) { ?>
                <form method="post" id="form-add-post" data-posttype="<?php echo $post_type ?>" action="<?php echo $form_ulr; ?>">
                <?php } ?>
                    <?php if (isset($post->ID) && $user_edit ) { ?>
                        <input type="hidden" name="post[post_id]" id='project_id' value="<?php echo $post->ID; ?>" />
                    <?php } ?>
                    <div class="row">
                    	<div class="large-4 columns">
                    		<?php _e('Details', RT_PM_TEXT_DOMAIN);?>
                    	</div>
						<?php
						if (isset($post->ID)) {
							$save_button = __( 'Update' );
						} else {
							$save_button = __( 'Add Project' );
						}
						?>
                        
                            
						<?php if( $user_edit ) { ?>
						<div class="large-8 columns right push-3">
							<button class="mybutton" type="submit" ><?php _e($save_button); ?></button>&nbsp;&nbsp;&nbsp;
							<?php 
							if(isset($post->ID)) { 
								$get_post_status = get_post_status( $post->ID );
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
							<button id="top-<?php echo $button_archive_id; ?>" class="mybutton" data-href="<?php echo add_query_arg( array( 'action' => $archive_action, 'rt_project_id' => $post->ID ), $redirect ); ?>" class=""><?php _e($archive_button); ?></button>&nbsp;&nbsp;&nbsp;
							<button id="top-button-trash" class="mybutton" data-href="<?php echo add_query_arg( array( 'action' => 'trash', 'rt_project_id' => $post->ID ), $redirect ); ?>" class=""><?php _e( 'Delete' ); ?></button>&nbsp;&nbsp;&nbsp;
							<?php } ?>
						</div>
						<?php } ?>
                        
                    </div>
                    <div class="row">
                        <div class="large-12 small-12 columns ui-sortable meta-box-sortables">
                            <div class="row collapse postbox">
                                <div class="large-12 columns">
                                    <?php if( $user_edit ) { ?>
                                        <input name="post[post_title]" id="new_<?php echo $post_type ?>_title" type="text" placeholder="<?php _e(ucfirst($labels['name'])." Name"); ?>" value="<?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?>" />
                                    <?php } else { ?>
                                        <span><?php echo ( isset($post->ID) ) ? $post->post_title : ""; ?></span><br /><br />
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="row collapse rtpm-lead-content-wrapper">
                                <div class="large-12 columns">
                                	<fieldset>
                                    <?php
                                    if( $user_edit ) {
                                        wp_editor( ( isset( $post->ID ) ) ? $post->post_content : "", "post_content", array( 'textarea_name' => 'post[post_content]', 'media_buttons' => false, 'tinymce' => false, 'quicktags' => false ) );
                                    } else {
                                        echo ucfirst($labels['name']).' Content : <br /><br /><span>'.(( isset($post->ID) ) ? $post->post_content : '').'</span><br /><br />';
                                    }
                                    ?>
                                    </fieldset>
                                </div>
                            </div>
                        </div>
					</div>
					<div class="row">
						<div class="large-6 columns ui-sortable meta-box-sortables">
                            <div id="rtpm-assignee" class="row collapse rtpm-post-author-wrapper">
                                <div class="large-12 mobile-large-12 columns">
                                    <label for="post[project_manager]">Project Manager</label>
                                </div>
                                <div class="large-12 mobile-large-12 columns">
                                    <?php if( $user_edit ) { ?>
                                        <select name="post[project_manager]" >
											<option value=""><?php _e( 'Select PM' ); ?></option>
                                            <?php
                                            if (!empty($results_member)) {
                                                foreach ($results_member as $author) {
                                                    if ($author->ID == $project_manager) {
                                                        $selected = " selected";
                                                    } else {
                                                        $selected = " ";
                                                    }
                                                    echo '<option value="' . $author->ID . '"' . $selected . '>' . $author->display_name . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    <?php 
										} else {
											if (!empty($results_member)) {
                                                foreach ($results_member as $author) {
                                                    if ($author->ID == $project_manager) {
                                                        $selected = " selected";
														echo '<div class="small-8 large-8 columns rtpm_attr_border">' .
														'<span class="rtpm_view_mode">' . $author->display_name . '</span>' .
														'</div>';
                                                    }
                                                    
                                                                                        
                                                }
                                            }
										} 
									?>
                                </div>
                            </div>
                            <div id="rtpm-bm" class="row collapse rtpm-post-author-wrapper">
                                <div class="large-12 mobile-large-12 columns">
                                    <label for="post[business_manager]"><?php _e('Business Manager'); ?></label>
                                </div>
                                <div class="large-12 mobile-large-12 columns">
                                    <?php if( $user_edit ) { ?>
                                        <select name="post[business_manager]" >
											<option value=""><?php _e( 'Select BM' ); ?></option>
                                            <?php
                                            if (!empty($results_member)) {
                                                foreach ($results_member as $bm) {
                                                    if ($bm->ID == $business_manager) {
                                                        $selected = " selected";
                                                    } else {
                                                        $selected = " ";
                                                    }
                                                    echo '<option value="' . $bm->ID . '"' . $selected . '>' . $bm->display_name . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    <?php 
										} else {
											if (!empty($results_member)) {
                                                foreach ($results_member as $bm) {
                                                    if ($bm->ID == $business_manager) {
                                                        $selected = " selected";
														echo '<div class="small-8 large-8 columns rtpm_attr_border">' .
														'<span class="rtpm_view_mode">' . $bm->display_name . '</span>' .
														'</div>';
                                                    }
                                                }
                                            }
										} 
									?>
                                </div>
                            </div>
							<div class="row collapse postbox">
								<div class="large-12 mobile-large-12 columns">
									<label for="post[team_members]"><?php _e( 'Team Members' ); ?></label>
								</div>
								<div class="large-12 mobile-large-12 columns">
									<script>
										var arr_project_member_user =<?php echo json_encode($arrProjectMember); ?>;
									</script>
									<?php if ( $user_edit ) { ?>
									<input style="margin-bottom:10px" type="text" placeholder="Type User Name to select" id="project_member_user_ac" />
									<?php } ?>
									<ul id="divProjectMemberList" class="large-block-grid-1 small-block-grid-1">
										<?php echo $subProjectMemberHTML; ?>
									</ul>
								</div>
							</div>
							<div class="row collapse postbox">
								<div class="large-12 mobile-large-12 columns">
									<label for="post[organization]"><?php _e( 'Organization' ); ?></label>
								</div>
								<div class="large-12 mobile-large-12 columns">
									<script>
                                        var arr_project_organization =<?php echo json_encode($arrProjectOrganizations); ?>;
                                    </script>
								<?php if ( $user_edit ) { ?>
									<input type="text" placeholder="Type Name to select" id="project_org_search_account" />
								<?php } ?>
									<ul id="divAccountsList" class="block-grid large-1-up">
										<?php echo $subProjectOrganizationsHTML; ?>
									</ul>
								</div>
							</div>
                            <div class="row collapse postbox">
								<div class="large-12 mobile-large-12 columns">
									<label for="post[organization_contacts]"><?php _e( 'Organization Contacts' ); ?></label>
								</div>
                                <div class="large-12 mobile-large-12 columns">
                                    <script>
                                        var arr_project_client_user =<?php echo json_encode($arrProjectClient); ?>;
                                    </script>
                                    <?php if ( $user_edit ) { ?>
                                        <input style="margin-bottom:10px" type="text" placeholder="Type User Name to select" id="project_client_user_ac" />
                                    <?php } ?>
                                    <ul id="divProjectClientList" class="large-block-grid-1 small-block-grid-1">
                                        <?php echo $subProjectClientHTML; ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="row collapse postbox hide">
                                <div class="handlediv" title="Click to toggle"><br></div>
                                <h6 class="hndle"><span><i class="foundicon-smiley"></i> Participants</span></h6>
                                <div class="inside">
                                    <ul class="rtpm-participant-list large-block-grid-1 small-block-grid-1">
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="large-6 small-12 columns ui-sortable meta-box-sortables">
                            <div class="row collapse postbox">
                                <div class="inside">
                                    <div class="row collapse">
                                        <div class="small-4 large-4 columns">
                                            <label for="post[post_status]">Status</label>
                                        </div>
                                        <div class="small-8 large-8 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                            <?php
                                            if (isset($post->ID))
                                                $pstatus = $post->post_status;
                                            else
                                                $pstatus = "";
                                            $post_status = $rt_pm_project->get_custom_statuses();
                                            $custom_status_flag = true;
                                            ?>
                                            <?php if( $user_edit ) { ?>
                                                <select id="rtpm_post_status" class="right" name="post[post_status]">
                                                    <?php foreach ($post_status as $status) {
                                                        if ($status['slug'] == $pstatus) {
                                                            $selected = 'selected="selected"';
                                                            $custom_status_flag = false;
                                                        } else {
                                                            $selected = '';
                                                        }
                                                        printf('<option value="%s" %s >%s</option>', $status['slug'], $selected, $status['name']);
                                                    } ?>
                                                    <?php if ( $custom_status_flag && isset( $post->ID ) ) { echo '<option selected="selected" value="'.$pstatus.'">'.$pstatus.'</option>'; } ?>
                                                </select>
                                            <?php } else {
                                                $status_html='';
                                                foreach ( $post_status as $status ) {
                                                    if($status['slug'] == $pstatus) {
                                                        $status_html = '<span class="rtpm_view_mode">'.$status['name'].'</span>';
                                                        break;
                                                    }
                                                }
                                                if ( !isset( $status_html ) || empty( $status_html ) && ( isset( $pstatus ) && !empty( $pstatus ) ) ){
                                                    $status_html = '<span class="rtpm_view_mode">'.$pstatus.'</span>';
                                                }
                                                echo $status_html;
                                            } ?>
                                        </div>
                                    </div>
                                    <div id="rtpm_status_detail" class="row collapse">
                                        <div class="large-4 small-4 columns">
                                            <label><?php _e('Status Detail'); ?></label>
                                        </div>
                                        <div class="large-8 small-8 columns">
											<?php if( $user_edit ) { ?>
											<textarea name="post[status_detail]"><?php echo ( isset($post->ID) ) ? get_post_meta( $post->ID, '_rtpm_status_detail', true ) : ''; ?></textarea>
											<?php } else { ?>
												<span><?php echo ( isset($post->ID) ) ? get_post_meta( $post->ID, '_rtpm_status_detail', true ) : ''; ?></span>
											<?php } ?>
										</div>
                                    </div>
                                    <div id="rtpm_project_type_wrapper" class="row collapse <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                        <div class="large-4 small-4 columns">
                                            <label><?php _e('Project Type'); ?></label>
                                        </div>
                                        <div class="large-8 small-8 columns">
                                            <?php $rt_pm_project_type->get_project_types_dropdown( ( isset( $post->ID ) ) ? $post->ID : '', $user_edit ); ?>
                                        </div>
                                    </div>
                                    <div class="row collapse">
                                        <div class="large-4 small-4 columns">
                                            <label>Create Date</label>
                                        </div>
                                        <div class="large-8 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                            <?php if( $user_edit ) { ?>
                                                <input class="datetimepicker moment-from-now" type="text" placeholder="Select Create Date"
                                                       value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>"
                                                       title="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>">
                                                <input name="post[post_date]" type="hidden" value="<?php echo ( isset($createdate) ) ? $createdate : ''; ?>" />
                                            <?php } else { ?>
                                                <span class="rtpm_view_mode moment-from-now"><?php echo $createdate ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php if (isset($post->ID)) { ?>
                                        <div class="row collapse">
                                            <div class="large-4 mobile-large-1 columns">
                                                <label>Modify Date</label>
                                            </div>
                                            <div class="large-8 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                                <?php if( $user_edit ) { ?>
                                                    <input class="moment-from-now"  type="text" placeholder="Modified on Date"  value="<?php echo $modifydate; ?>"
                                                           title="<?php echo $modifydate; ?>" readonly="readonly">
                                                <?php } else { ?>
                                                    <span class="rtpm_view_mode moment-from-now"><?php echo $modifydate; ?></span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="row collapse">
                                        <div class="large-4 small-4 columns">
                                            <label>Completion Date</label>
                                        </div>
                                        <div class="large-8 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                            <?php if( $user_edit ) { ?>
                                                <input class="datetimepicker moment-from-now" type="text" placeholder="Select Completion Date"
                                                       value="<?php echo ( isset($completiondate) ) ? $completiondate : ''; ?>"
                                                       title="<?php echo ( isset($completiondate) ) ? $completiondate : ''; ?>">
                                                <input name="post[post_completiondate]" type="hidden" value="<?php echo ( isset($completiondate) ) ? $completiondate : ''; ?>" />
                                            <?php } else { ?>
                                                <span class="rtpm_view_mode moment-from-now"><?php echo $completiondate ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="row collapse">
                                        <div class="large-4 small-4 columns">
                                            <label>Due Date</label>
                                        </div>
                                        <div class="large-8 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                            <?php if( $user_edit ) { ?>
                                                <input class="datetimepicker moment-from-now" type="text" placeholder="Select Due Date"
                                                       value="<?php echo ( isset($duedate) ) ? $duedate : ''; ?>"
                                                       title="<?php echo ( isset($duedate) ) ? $duedate : ''; ?>">
                                                <input name="post[post_duedate]" type="hidden" value="<?php echo ( isset($duedate) ) ? $duedate : ''; ?>" />
                                            <?php } else { ?>
                                                <span class="rtpm_view_mode moment-from-now"><?php echo $duedate ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="row collapse">
                                        <div class="large-4 small-4 columns">
                                            <label>Estimated Time</label>
                                        </div>
                                        <div class="large-8 mobile-large-2 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                            <?php if( $user_edit ) { ?>
                                                <input name="post[project_estimated_time]" type="text" value="<?php echo ( isset($post->ID) ) ? get_post_meta( $post->ID, 'project_estimated_time', true ) : ''; ?>" />
                                            <?php } else { ?>
                                                <span class="rtpm_view_mode moment-from-now"><?php echo ( isset($post->ID) ) ? get_post_meta( $post->ID, 'project_estimated_time', true ) : ''; ?></span>
                                            <?php } ?>
                                        </div>
                                    </div>
									<div class="row collapse">
										<div class="large-4 mobile-large-1 columns">
											<label for="project_budget">Budget</label>
										</div>
										<div class="large-7 mobile-large-2 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
											<?php if( $user_edit ) { ?>
											<input type="text" name="post[project_budget]" id="project_budget" value="<?php echo ( isset( $post->ID ) ) ? get_post_meta( $post->ID, '_rtpm_project_budget', true ) : ''; ?>" />
											<?php } else { ?>
											<span class="rtpm_view_mode"><?php echo ( isset( $post->ID ) ) ? get_post_meta( $post->ID, '_rtpm_project_budget', true ) : ''; ?></span>
											<?php } ?>
										</div>
										<?php //if( $user_edit ) { ?>
										<div class="large-1 mobile-large-1 columns">
											<span class="">$</span>
										</div>
										<?php // } ?>
									</div>
                                </div>
                            </div>
                            <?php 
							if ( isset( $post->ID ) ) { do_action( 'rt_pm_other_details', $user_edit, $post ); }
                            ?>
                        </div>
                        
                    </div>
                    <div class="row">
						<?php
						if (isset($post->ID)) {
							$save_button = __( 'Update' );
						} else {
							$save_button = __( 'Add Project' );
						}
						?>
                        
                            
						<?php if( $user_edit ) { ?>
						<div class="large-8 columns right push-3">
							<button class="mybutton" type="submit" ><?php _e($save_button); ?></button>&nbsp;&nbsp;&nbsp;
							<?php 
							if(isset($post->ID)) { 
								$get_post_status = get_post_status( $post->ID );
								if ( isset( $get_post_status ) && $get_post_status == 'trash' ){
									$archive_action = 'unarchive';
									$archive_button = __( 'Unarchive' );
									$button_archive_id = 'button-unarchive';
								} else {
									$archive_action = 'archive';
									$archive_button = __( 'Archive' );
									$button_archive_id = 'button-archive';
								}
							?>
							<button id="<?php echo $button_archive_id; ?>" class="mybutton" data-href="<?php echo add_query_arg( array( 'action' => $archive_action, 'rt_project_id' => $post->ID ), $redirect ); ?>" class=""><?php _e($archive_button); ?></button>&nbsp;&nbsp;&nbsp;
							<button id="button-trash" class="mybutton" data-href="<?php echo add_query_arg( array( 'action' => 'trash', 'rt_project_id' => $post->ID ), $redirect ); ?>" class=""><?php _e( 'Delete' ); ?></button>&nbsp;&nbsp;&nbsp;
							<?php } ?>
						</div>
						<?php } ?>
                        
                    </div>
                <?php if( $user_edit ) { ?>
                </form>
                <?php } ?>
            </div> <?php
        }

        function get_project_file_tab($labels,$user_edit){
        	global $rt_pm_bp_pm;
            $post_type=$_REQUEST['post_type'];
            $projectid = $_REQUEST["{$post_type}_id"];

            $attachments = array();
            $arg= array(
                'posts_per_page' => -1,
                'post_parent' => $projectid,
                'post_type' => 'attachment',
            );
            if ( isset($_REQUEST['attachment_tag']) && $_REQUEST['attachment_tag']!= -1 ){
                $arg=array_merge($arg, array('tax_query' => array(
                    array(
                        'taxonomy' => 'attachment_tag',
                        'field' => 'term_id',
                        'terms' => $_REQUEST['attachment_tag'])
                    ))
                );
            }
            
			$form_ulr = $rt_pm_bp_pm->get_component_root_url().bp_current_action();
			$form_ulr .= "?post_type={$post_type}&page=rtpm-add-{$post_type}&{$post_type}_id={$_REQUEST["{$post_type}_id"]}&tab={$post_type}-files";
           ?>
			<div id="wp-custom-list-table">
				<div id="attachment-error" class="row"></div>
				<!--<div class="row">
				<?php if( $user_edit ) { ?>
					<button class="right mybutton add-external-link" type="button" ><?php _e("Add External link"); ?></button>
					<button class="right mybutton add-project-file" data-projectid="<?php echo $projectid; ?>" id="add_project_attachment" type="button" ><?php _e('Add File'); ?></button>
				<?php } ?>
				</div>-->

				<div id="attachment-search-row" class="row collapse postbox">
				   
					   <form id ="attachment-search" method="post" action="<?php echo $form_ulr; ?>">
					   		<div class="row list-heading">
					   			<div class="large-4 columns">
                    				<?php _e('Attachments'); ?>                  	
                				</div>
                				<div class="large-8 columns">
                    				<button class="right mybutton" type="submit" ><?php _e('Search'); ?></button>
                    				<?php
		                               if ( isset( $_REQUEST['attachment_tag'] ) ) {                                         
		                                    wp_dropdown_categories('taxonomy=attachment_tag&hide_empty=0&orderby=name&name=attachment_tag&show_option_none=Select Media tag&selected='.$_REQUEST['attachment_tag']);
		                               }
								   ?>   	
                				</div>
                    		</div>
					   </form></h6>
					   <?php pm_add_documents_section( $projectid ); ?>
				    <!--<div class="inside">
					   <div class="row collapse" id="attachment-container">

						   <div class="scroll-height">
							   <?php if ( $attachments ){ ?>
								   <?php foreach ($attachments as $attachment) { ?>
									   <?php $extn_array = explode('.', $attachment->guid); $extn = $extn_array[count($extn_array) - 1];
										   if ( get_post_meta( $attachment->ID, '_wp_attached_external_file', true) == 1){
											   $extn ='unknown';
										   }
									   ?>
									   <div class="large-12 mobile-large-3 columns attachment-item" data-attachment-id="<?php echo $attachment->ID; ?>">
										   <a target="_blank" href="<?php echo wp_get_attachment_url($attachment->ID); ?>">
											   <img height="20px" width="20px" src="<?php echo RT_PM_URL . "app/assets/file-type/" . $extn . ".png"; ?>" />
											   <?php echo $attachment->post_title; ?>
										   </a>
										   <?php $taxonomies=get_attachment_taxonomies($attachment);
										   $taxonomies=get_the_terms($attachment,$taxonomies[0]);
										   $term_html = '';
										   if ( isset( $taxonomies ) && !empty( $taxonomies ) ){?>
											   <div style="display:inline-flex;margin-left: 20px;" class="attachment-meta">[&nbsp;
												   <?php foreach( $taxonomies as $taxonomy){
													   if ( !empty($term_html) ){
														   $term_html.=',&nbsp;';
													   }
													   $term_html .= '<a href="'.$rt_pm_bp_pm->get_component_root_url().bp_current_action() .'?post_type='.$post_type.'&'.$post_type.'_id='.$_REQUEST["{$post_type}_id"].'&tab='.$post_type.'-files&attachment_tag='.$taxonomy->term_id.'"'.'" title="'. $taxonomy->name .'" >'.$taxonomy->name.'</a>';
												   }
												   echo $term_html;?>&nbsp;]
												   
											   </div>
										   <?php } ?>
										   <?php if( $user_edit ) { ?>
											   <a href="#" data-attachmentid="<?php echo $attachment->ID; ?>"  class="rtpm_delete_project_attachment right">x</a>
										   <?php } ?>
										   <input type="hidden" name="attachment[]" value="<?php echo $attachment->ID; ?>" />
										  <?php /*var_dump( get_post_meta($attachment, '_flagExternalLink') ) */?>
									   </div>
								   <?php } ?>
							   <?php }else{ ?>
								   <?php if (  isset($_POST['attachment_tag']) && $_POST['attachment_tag']!= -1 ){ ?>
									   <div class="large-12 mobile-large-3 columns no-attachment-item">
										   <?php $term = get_term( $_POST['attachment_tag'], 'attachment_tag' ); ?>
										   Not Found Attachment<?php echo isset( $term )? ' of ' . $term->name . ' Term!' :'!' ?>
									   </div>
								   <?php }else{ ?>
									   <div class="large-12 mobile-large-3 columns no-attachment-item">
										   <?php delete_post_meta($projectid, '_rt_wp_pm_attachment_hash'); ?>
										   Attachment Not found!
									   </div>
								   <?php } ?>
							   <?php } ?>
						   </div>
					   </div>
				   </div>-->
			   </div>
		   </div>

            <!--reveal-modal-add-external file -->
            <div id="div-add-external-link" class="reveal-modal large">
                <fieldset>
                    <legend><h4>Add External Link</h4></legend>
                    <form method="post" id="form-external-link" data-posttype="projec-attachment" action="<?php echo $form_ulr; ?>">
                        <input type="hidden" name="post[post_project_id]" id='project_id' value="<?php echo $_REQUEST["{$post_type}_id"]; ?>" />
                        <div class="row collapse">
                            <div class="large-3 small-4 columns">
                                <label>Title</label>
                            </div>
                            <div class="large-9 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                <input placeholder="Title" name="post[post_title]" type="text" value="" />
                            </div>
                        </div>
                        <div class="row collapse">
                            <div class="large-3 small-4 columns">
                                <label>External Link</label>
                            </div>
                            <div class="large-9 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                <input placeholder="External Link" name="post[post_link]" type="text" value="" />
                            </div>
                        </div>
                        <div class="row collapse">
                            <div class="large-3 small-4 columns">
                                <label>Categories</label>
                            </div>
                            <div class="large-9 mobile-large-1 columns <?php echo ( ! $user_edit ) ? 'rtpm_attr_border' : ''; ?>">
                                <?php $media_terms= get_categories("taxonomy=attachment_tag&hide_empty=0&orderby=name");?>
                                <ul class="media-term-list">
                                    <?php foreach ( $media_terms as $term ){?>
                                    <li><label><input type="checkbox" name="post[term][]" value="<?php echo $term->term_id; ?>"><span><?php echo $term->name; ?></span></label></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                        <div class="row collapse">

                        </div>
                        <button class="mybutton right" type="submit" id="save-external-link">Save</button>
                    </form>
                </fieldset>
                <a class="close-reveal-modal">×</a>
            </div>
        <?php
        }

		function get_project_notification_tab( $labels, $user_edit ) {
			global $rt_biz_notification_rules_model, $rt_pm_notification, $rt_pm_bp_pm;
			$project_id = $_REQUEST["{$this->post_type}_id"];
			$users = Rt_PM_Utils::get_pm_rtcamp_user();
			$operators = $rt_pm_notification->get_operators();
			$post_type = $_REQUEST['post_type'];

			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'delete' ) {
				if ( isset( $_REQUEST['rule_id'] ) ) {
					$rule = $rt_biz_notification_rules_model->get( array( 'id' => $_REQUEST['rule_id'] ) );
					$rule = $rule[0];
					if ( $rule->rule_type == 'periodic' ) {
						wp_clear_scheduled_hook( 'rt_pm_timely_notification_'.$rule->id, array( $rule ) );
					}
					$rt_biz_notification_rules_model->delete( array( 'id' => $_REQUEST['rule_id'] ) );
				}
			}

			if ( isset( $_POST['rtpm_add_notification_rule'] ) ) {

				$error = array();
				$rule_type = '';
				$schedule = ( ! empty( $_POST['rtpm_nr_schedule'] ) ) ? $_POST['rtpm_nr_schedule'] : NULL;
				$period = ( ! empty( $_POST['rtpm_nr_period'] ) ) ? $_POST['rtpm_nr_period'] : NULL;
				$period_type = ( ! empty( $_POST['rtpm_nr_period_type'] ) ) ? $_POST['rtpm_nr_period_type'] : NULL;
				$context = '';
				$operator = '';
				$value = $_POST['rtpm_nr_value'];
				$value_type = '';
				$user = '';

				if ( empty( $_POST['rtpm_nr_context'] ) ) {
					$error[] = '<div class="error"><p>'.__('Context is required').'</p></div>';
				} else {
					$context = $_POST['rtpm_nr_context'];
				}
				if ( empty( $_POST['rtpm_nr_operator'] ) ) {
					$error[] = '<div class="error"><p>'.__('Operator is required').'</p></div>';
				} else {
					$operator = $_POST['rtpm_nr_operator'];
				}
				if ( empty( $_POST['rtpm_nr_value_type'] ) ) {
					$error[] = '<div class="error"><p>'.__('Value Type is required').'</p></div>';
				} else {
					$value_type = $_POST['rtpm_nr_value_type'];
				}
				if ( empty( $_POST['rtpm_nr_user'] ) ) {
					$error[] = '<div class="error"><p>'.__('User is required').'</p></div>';
				} else {
					$user = $_POST['rtpm_nr_user'];
				}
				if ( empty( $_POST['rtpm_nr_type'] ) || ! in_array( $_POST['rtpm_nr_type'], array( 'triggered', 'periodic' ) ) ) {
					$error[] = '<div class="error"><p>'.__('User is required').'</p></div>';
				} else {
					$rule_type = $_POST['rtpm_nr_type'];
				}

				if ( $rule_type == 'periodic' ) {
					if ( empty( $period_type ) ) {
						$error[] = '<div class="error"><p>'.__('Period Type is required').'</p></div>';
					}
					if ( empty( $schedule ) ) {
						$error[] = '<div class="error"><p>'.__('Schedule is required').'</p></div>';
					}
				}

				if ( empty( $error ) ) {
					$data = array(
						'rule_type' => $rule_type,
						'schedule' => $schedule,
						'period' => $period,
						'period_type' => $period_type,
						'module' => RT_PM_TEXT_DOMAIN,
						'entity' => $this->post_type,
						'entity_id' => $project_id,
						'context' => $context,
						'operator' => $operator,
						'value' => $value,
						'value_type' => $value_type,
						'subject' => $operators[$operator]['subject'],
						'message' => $operators[$operator]['message'],
						'user' => $user,
					);
					$rt_biz_notification_rules_model->insert( $data );
				} else {
					$_SESSION['rtpm_errors'] = $error;
				}
				$bp_bp_nav_link = $rt_pm_bp_pm->get_component_root_url().bp_current_action();
				$bp_bp_nav_link .= "?post_type={$post_type}&{$post_type}_id={$_REQUEST[ "{$post_type}_id" ]}&tab={$post_type}-notification";
				echo '<script>window.location="' . $bp_bp_nav_link . '";</script> ';
				die();
			}
			?>
			<div class="wrap rtpm-notification-rules">
				<?php
					$error = $_SESSION['rtpm_errors'];
					unset( $_SESSION['rtpm_errors'] );
					if( ! empty( $error ) ) {
						foreach ( $error as $e ) {
							echo $e;
						}
					}
				?>
				<div class="row list-heading">
			        <div class="large-10 columns list-title">
			            <h4><?php _e( 'Notifications', RT_PM_TEXT_DOMAIN ) ?></h4>
			        </div>
			        <div class="large-2 columns">
			          
			        </div>
			    </div>
				<div class="row list-heading notification-top">
			        <div class="large-10 columns list-title">
			            <h4><?php _e( 'Triggered Notifications', RT_PM_TEXT_DOMAIN ) ?></h4>
			        </div>
			        <div class="large-2 columns">
			          
			        </div>
			    </div>
				<?php if ( $user_edit ) { ?>
				<div class="add-notification-rule">
					<form method="post">
						<input type="hidden" name="rtpm_add_notification_rule" value="1" />
						<input type="hidden" name="rtpm_nr_type" value="triggered" />
						<div class="row">
							<div class="large-3 columns">
								<select name="rtpm_nr_context" required="required">
									<option value=""><?php _e( 'Contexts' ) ?></option>
									<?php foreach ( $rt_pm_notification->get_contexts() as $key => $label ) { ?>
									<option value="<?php echo $key; ?>"><?php echo $label; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-2 columns">
								<select name="rtpm_nr_operator" required="required">
									<option value=""><?php _e( 'Operators' ); ?></option>
									<?php foreach ( $operators as $key => $operator ) { ?>
									<option value="<?php echo $key; ?>"><?php echo $key; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-1 columns">
								<input type="text" name="rtpm_nr_value" />
							</div>
							<div class="large-2 columns">
								<select name="rtpm_nr_value_type" required="required">
									<option value=""><?php _e( 'Value Type' ); ?></option>
									<option value="absolute"><?php _e('Absolute'); ?></option>
									<option value="percentage"><?php _e('Percentage'); ?></option>
								</select>
							</div>
							<div class="large-3 columns">
								<select name="rtpm_nr_user" required="required">
									<option value=""><?php _e( 'Select User to Notify' ); ?></option>
									<option value="{{project_manager}}"><?php _e( '{{project_manager}}' ); ?></option>
									<option value="{{business_manager}}"><?php _e( '{{business_manager}}' ); ?></option>
									<?php foreach ( $users as $u ) { ?>
									<option value="<?php echo $u->ID; ?>"><?php echo $u->display_name; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-1 columns">
								<input type="submit" class="button-primary" value="<?php _e( 'Add' ); ?>">
							</div>
						</div>
					</form>
				</div>
				<?php } ?>
				<table class="" cellspacing="0">
					<thead>
						<tr>
							<th scope='col' id='rtpm_schedule' class='manage-column column-rtpm_schedule'  style=""><span>Schedule</span></th>
							<th scope='col' id='rtpm_context' class='manage-column column-rtpm_context'  style=""><span>Context</span></th>
							<th scope='col' id='rtpm_operator' class='manage-column column-rtpm_operator'  style=""><span>Operator</span></th>
							<th scope='col' id='rtpm_value' class='manage-column column-rtpm_value'  style=""><span>Value</span></th>
							<th scope='col' id='rtpm_value_type' class='manage-column column-rtpm_value_type' style=""><span>Value Type</span></th>
							<th scope='col' id='rtpm_period' class='manage-column column-rtpm_period'  style=""><span>Period</span></th>
							<th scope='col' id='rtpm_period_type' class='manage-column column-rtpm_period_type' style=""><span>Period Type</span></th>
							<th scope='col' id='rtpm_user' class='manage-column column-rtpm_user'  style=""><span>User to Notify</span></th>
							<th scope='col' id='rtpm_delete_rule' class='manage-column column-rtpm_delete_rule'  style=""><span>&nbsp;</span></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope='col' id='rtpm_schedule' class='manage-column column-rtpm_schedule'  style=""><span>Schedule</span></th>
							<th scope='col' id='rtpm_context' class='manage-column column-rtpm_context'  style=""><span>Context</span></th>
							<th scope='col' id='rtpm_operator' class='manage-column column-rtpm_operator'  style=""><span>Operator</span></th>
							<th scope='col' id='rtpm_value' class='manage-column column-rtpm_value'  style=""><span>Value</span></th>
							<th scope='col' id='rtpm_value_type' class='manage-column column-rtpm_value_type' style=""><span>Value Type</span></th>
							<th scope='col' id='rtpm_period' class='manage-column column-rtpm_period'  style=""><span>Period</span></th>
							<th scope='col' id='rtpm_period_type' class='manage-column column-rtpm_period_type' style=""><span>Period Type</span></th>
							<th scope='col' id='rtpm_user' class='manage-column column-rtpm_user'  style=""><span>User to Notify</span></th>
							<th scope='col' id='rtpm_rule_actions' class='manage-column column-rtpm_rule_actions'  style=""><span>&nbsp;</span></th>
						</tr>
					</tfoot>

					<tbody>
						<?php
						$rules = $rt_biz_notification_rules_model->get( array( 'entity_id' => $project_id, 'rule_type' => 'triggered' ) );
						?>
						<?php if ( ! empty( $rules ) ) { ?>
							<?php foreach ( $rules as $r ) { ?>
						<tr>
							<td class="rtpm_schedule column-rtpm_schedule">
							<?php
								$schedules = wp_get_schedules();
								echo ( ! empty( $schedules[$r->schedule]['display'] ) ) ? $schedules[$r->schedule]['display'] : __( 'NA' );
							?>
							</td>
							<td class='rtpm_context column-rtpm_context'>
							<?php
								$context = $rt_pm_notification->get_context_label( $r->context, $r->rule_type );
								echo $context;
							?>
							</td>
							<td class='rtpm_operator column-rtpm_operator'><?php echo $r->operator; ?></td>
							<td class='rtpm_value column-rtpm_value'>
							<?php
								echo ( ! empty( $r->value ) ) ? $r->value : __( 'Nill' );
							?>
							</td>
							<td class='rtpm_value_type column-rtpm_value_type'>
							<?php
								switch( $r->value_type ) {
									case 'percentage' :
										_e( 'Percentage' );
										break;
									case 'absolute' :
										_e( 'Absolute' );
										break;
								}
							?>
							</td>
							<td class="rtpm_period column-rtpm_period">
							<?php
								if ( $r->rule_type == 'triggered' ) {
									_e('NA');
								} else {
									echo ( ! empty( $r->period ) ) ? $r->period : __( 'Nill' );
								}
							?>
							</td>
							<td class="rtpm_period_type column-rtpm_period_type">
							<?php
								if ( $r->rule_type == 'triggered' ) {
									_e('NA');
								} else {
									echo ucfirst( $r->period_type );
								}
							?>
							</td>
							<td class='rtpm_user column-rtpm_user'>
							<?php
								switch( $r->user ) {
									case '{{project_manager}}':
										$project_manager = get_user_by( 'id', get_post_meta( $project_id, 'project_manager', true ) );
										echo $project_manager->display_name;
										break;
									case '{{business_manager}}':
										$business_manager = get_user_by( 'id', get_post_meta( $project_id, 'business_manager', true ) );
										echo $business_manager->display_name;
										break;
									default:
										$user = get_user_by( 'id', $r->user );
										echo $user->display_name;
										break;
								}
							?>
							</td>
							<td class='rtpm_rule_actions column-rtpm_rule_actions'>
								<a class="rtpm-delete" href="<?php echo add_query_arg( array( 'action' => 'delete', 'rule_id' => $r->id ) ); ?>"><?php _e( 'Delete' ) ?></a>
							</td>
						</tr>
							<?php } ?>
						<?php } else { ?>
						<tr><td colspan="6"><?php echo _e( 'No Rules Found !' ); ?></td></tr>
						<?php } ?>
					</tbody>
				</table>
				<br />
				<hr />
				<br />
				<div class="row list-heading notification-bottom">
			        <div class="large-10 columns list-title">
			            <h4><?php _e( 'Periodic Notifications', RT_PM_TEXT_DOMAIN ) ?></h4>
			        </div>
			        <div class="large-2 columns">
			          
			        </div>
			    </div>
				<?php if ( $user_edit ) { ?>
				<div class="add-notification-rule notification-bottom">
					<form method="post">
						<input type="hidden" name="rtpm_add_notification_rule" value="1" />
						<input type="hidden" name="rtpm_nr_type" value="periodic" />
						<div class="row">
							<div class="large-2 columns">
								<select name="rtpm_nr_schedule" required="required">
									<option value=""><?php _e( 'Schedule' ); ?></option>
									<?php foreach ( wp_get_schedules() as $key => $schedule ) { ?>
									<option value="<?php echo $key; ?>"><?php echo $schedule['display']; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-2 columns">
								<select name="rtpm_nr_context" required="required">
									<option value=""><?php _e( 'Contexts' ) ?></option>
									<?php foreach ( $rt_pm_notification->get_contexts( 'periodic' ) as $key => $label ) { ?>
									<option value="<?php echo $key; ?>"><?php echo $label; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-1 columns">
								<select name="rtpm_nr_operator" required="required">
									<option value=""><?php _e( 'Operators' ); ?></option>
									<?php foreach ( $operators as $key => $operator ) { ?>
									<option value="<?php echo $key; ?>"><?php echo $key; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-1 columns">
								<input type="text" name="rtpm_nr_value" placeholder="<?php _e( 'Value' ); ?>" />
							</div>
							<div class="large-1 columns">
								<select name="rtpm_nr_value_type" required="required">
									<option value=""><?php _e( 'Value Type' ); ?></option>
									<option value="absolute"><?php _e('Absolute'); ?></option>
									<option value="percentage"><?php _e('Percentage'); ?></option>
								</select>
							</div>
							<div class="large-1 columns">
								<input type="text" name="rtpm_nr_period" placeholder="<?php _e( 'Period' ); ?>" />
							</div>
							<div class="large-1 columns">
								<select name="rtpm_nr_period_type" required="required">
									<option value=""><?php _e( 'Period Type' ); ?></option>
									<option value="before"><?php _e( 'Before' ); ?></option>
									<option value="after"><?php _e( 'After' ); ?></option>
								</select>
							</div>
							<div class="large-2 columns">
								<select name="rtpm_nr_user" required="required">
									<option value=""><?php _e( 'Select User to Notify' ); ?></option>
									<option value="{{project_manager}}"><?php _e( '{{project_manager}}' ); ?></option>
									<option value="{{business_manager}}"><?php _e( '{{business_manager}}' ); ?></option>
									<?php foreach ( $users as $u ) { ?>
									<option value="<?php echo $u->ID; ?>"><?php echo $u->display_name; ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="large-1 columns">
								<input type="submit" class="button-primary" value="<?php _e( 'Add' ); ?>">
							</div>
						</div>
					</form>
				</div>
				<?php } ?>
				<table class="" cellspacing="0">
					<thead>
						<tr>
							<th scope='col' id='rtpm_schedule' class='manage-column column-rtpm_schedule'  style=""><span>Schedule</span></th>
							<th scope='col' id='rtpm_context' class='manage-column column-rtpm_context'  style=""><span>Context</span></th>
							<th scope='col' id='rtpm_operator' class='manage-column column-rtpm_operator'  style=""><span>Operator</span></th>
							<th scope='col' id='rtpm_value' class='manage-column column-rtpm_value'  style=""><span>Value</span></th>
							<th scope='col' id='rtpm_value_type' class='manage-column column-rtpm_value_type' style=""><span>Value Type</span></th>
							<th scope='col' id='rtpm_period' class='manage-column column-rtpm_period'  style=""><span>Period</span></th>
							<th scope='col' id='rtpm_period_type' class='manage-column column-rtpm_period_type' style=""><span>Period Type</span></th>
							<th scope='col' id='rtpm_user' class='manage-column column-rtpm_user'  style=""><span>User to Notify</span></th>
							<th scope='col' id='rtpm_delete_rule' class='manage-column column-rtpm_delete_rule'  style=""><span>&nbsp;</span></th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope='col' id='rtpm_schedule' class='manage-column column-rtpm_schedule'  style=""><span>Schedule</span></th>
							<th scope='col' id='rtpm_context' class='manage-column column-rtpm_context'  style=""><span>Context</span></th>
							<th scope='col' id='rtpm_operator' class='manage-column column-rtpm_operator'  style=""><span>Operator</span></th>
							<th scope='col' id='rtpm_value' class='manage-column column-rtpm_value'  style=""><span>Value</span></th>
							<th scope='col' id='rtpm_value_type' class='manage-column column-rtpm_value_type' style=""><span>Value Type</span></th>
							<th scope='col' id='rtpm_period' class='manage-column column-rtpm_period'  style=""><span>Period</span></th>
							<th scope='col' id='rtpm_period_type' class='manage-column column-rtpm_period_type' style=""><span>Period Type</span></th>
							<th scope='col' id='rtpm_user' class='manage-column column-rtpm_user'  style=""><span>User to Notify</span></th>
							<th scope='col' id='rtpm_rule_actions' class='manage-column column-rtpm_rule_actions'  style=""><span>&nbsp;</span></th>
						</tr>
					</tfoot>

					<tbody>
						<?php
						$rules = $rt_biz_notification_rules_model->get( array( 'entity_id' => $project_id, 'rule_type' => 'periodic' ) );
						?>
						<?php if ( ! empty( $rules ) ) { ?>
							<?php foreach ( $rules as $r ) { ?>
						<tr>
							<td class="rtpm_schedule column-rtpm_schedule">
							<?php
								$schedules = wp_get_schedules();
								echo ( ! empty( $schedules[$r->schedule]['display'] ) ) ? $schedules[$r->schedule]['display'] : __( 'NA' );
							?>
							</td>
							<td class='rtpm_context column-rtpm_context'>
							<?php
								$context = $rt_pm_notification->get_context_label( $r->context, $r->rule_type );
								echo $context;
							?>
							</td>
							<td class='rtpm_operator column-rtpm_operator'><?php echo $r->operator; ?></td>
							<td class='rtpm_value column-rtpm_value'>
							<?php
								echo ( ! empty( $r->value ) ) ? $r->value : __( 'Nill' );
							?>
							</td>
							<td class='rtpm_value_type column-rtpm_value_type'>
							<?php
								switch( $r->value_type ) {
									case 'percentage' :
										_e( 'Percentage' );
										break;
									case 'absolute' :
										_e( 'Absolute' );
										break;
								}
							?>
							</td>
							<td class="rtpm_period column-rtpm_period">
							<?php
								if ( $r->rule_type == 'triggered' ) {
									_e('NA');
								} else {
									echo ( ! empty( $r->period ) ) ? $r->period : __( 'Nill' );
								}
							?>
							</td>
							<td class="rtpm_period_type column-rtpm_period_type">
							<?php
								if ( $r->rule_type == 'triggered' ) {
									_e('NA');
								} else {
									echo ucfirst( $r->period_type );
								}
							?>
							</td>
							<td class='rtpm_user column-rtpm_user'>
							<?php
								switch( $r->user ) {
									case '{{project_manager}}':
										$project_manager = get_user_by( 'id', get_post_meta( $project_id, 'project_manager', true ) );
										echo $project_manager->display_name;
										break;
									case '{{business_manager}}':
										$business_manager = get_user_by( 'id', get_post_meta( $project_id, 'business_manager', true ) );
										echo $business_manager->display_name;
										break;
									default:
										$user = get_user_by( 'id', $r->user );
										echo $user->display_name;
										break;
								}
							?>
							</td>
							<td class='rtpm_rule_actions column-rtpm_rule_actions'>
								<a class="rtpm-delete" href="<?php echo add_query_arg( array( 'action' => 'delete', 'rule_id' => $r->id ) ); ?>"><?php _e( 'Delete' ) ?></a>
							</td>
						</tr>
							<?php } ?>
						<?php } else { ?>
						<tr><td colspan="6"><?php echo _e( 'No Rules Found !' ); ?></td></tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		<?php }
                
	}
}