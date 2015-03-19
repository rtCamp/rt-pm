<?php
	global $rt_pm_project, $rt_pm_bp_pm, $rt_pm_bp_pm_project, $bp, $wpdb,  $wp_query,$rt_person, $wp_roles;;
	
	if (isset($_GET['rt_project_id']) || isset($_GET['post_type']) && ($_GET['action'] != 'archives')){
		$rt_pm_bp_pm_project->custom_page_ui();
	} else {
		$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		$paged = $page = max( 1, get_query_var('paged') );
		
		$posts_per_page = 20;
		
		$order = 'DESC';
		$attr = 'startdate';
		
		$meta_key = 'post_duedate';

		

		$offset = ( $paged - 1 ) * $posts_per_page;
		if ($offset <=0) {
			$offset = 0;
		}
		$post_status = array( 'new', 'active', 'paused','complete', 'closed' );

		$archive_text = __('Archive');
		
		
		$args = array(
			'post_type' => $rt_pm_project->post_type,
			'post_status' => $post_status,
			'posts_per_page' => $posts_per_page,
			'offset' => $offset
		);



		if( isset( $_GET['orderby'] ) ) {

			$order_by = $_GET['orderby'];

			$args['orderby'] = $order_by;
			$args['order'] =  $_GET['order'];
			if( $order_by == 'meta_value' ){

				$args['meta_key'] = $_GET['meta_key'];
			}

		}


        if ( bp_is_current_action('projects') ) {
            $args['post_status'] = $post_status;
            $archive = 'archive';
        }elseif( bp_is_current_action('archives') ) {
            $archive_text = __('Unarchive');
            $archive = 'unarchive';
            $args['post_status'] = 'trash';
        }
		
		/*echo "<pre>";
		print_r($args);
		echo "</pre>";*/
		
		$columns = array(
            array(
                    'column_label' => __( 'Name', RT_PM_TEXT_DOMAIN ) ,
                    'sortable' => true,
                    'orderby' => 'title',
                    'order' => 'asc'
            ),
			array(
                    'column_label' => __( 'Job Number', RT_PM_TEXT_DOMAIN ) ,
                    'sortable' => true,
                    'orderby' => 'meta_value',
					'meta_key' => 'rtpm_job_no',
                    'order' => 'asc'
            ),
            array(
                    'column_label' => __( 'Type', RT_PM_TEXT_DOMAIN ) ,
                    'sortable' => true,
                    'orderby' => 'rt_project-type',
                    'order' => 'asc'
            ),
            array(
                    'column_label' => __( 'PM', RT_PM_TEXT_DOMAIN ) ,
                    'sortable' => true,
                    'orderby' => 'project_manager',
                    'order' => 'asc'
                  
            ),
            array(
                    'column_label' => __( 'BDM', RT_PM_TEXT_DOMAIN ),
                    'sortable' => true,
                    'orderby' => 'business_manager',
                    'order' => 'asc'
            ),

    	);
		
		// The Query
		$the_query = new WP_Query( $args );
		$totalPage= $max_num_pages =  $the_query->max_num_pages;
        $editor_cap = rt_biz_get_access_role_cap( RT_PM_TEXT_DOMAIN, 'editor' );
		?>
        <div class="list-heading">
		    <div class="large-10 columns list-title">
				<?php if( bp_is_current_action('resources') ) { ?>
					<h4><?php _e( 'Resources', RT_PM_TEXT_DOMAIN ) ?></h4>
				<?php }else{ ?>
					<h4><?php _e( 'Projects', RT_PM_TEXT_DOMAIN ) ?></h4>
				<?php } ?>
		    </div>
		    <div class="large-2 columns">
		       
		    </div>
		</div>
		<?php if( !bp_is_current_action('resources') ) { ?>
		<table class="responsive">
			<thead>
				<tr>
                  <?php foreach ( $columns as $column ) {
                  ?>
                        <th>
                            <?php
                            if(  $column['sortable']  ) {

								$query_sting = array( 'orderby' => $column['orderby'] );

								if( isset( $column['meta_key'] ) )
									$query_sting['meta_key'] = $column['meta_key'];

                                    if ( isset( $_GET['orderby'] ) && $column['orderby']  == $_GET['orderby'] ) {
                                       
                                        $current_order = $_GET['order'];
                                       
                                        $order = 'asc' == $current_order ? 'desc' : 'asc';

										$query_sting['order'] = $order;

                                        printf( __('<a href="%s">%s <i class="fa fa-sort-%s"></i> </a>'), esc_url( add_query_arg(  $query_sting  ) ), $column['column_label'], $order );
                                        
                                    }else{

											$query_sting['order'] = 'desc';
                                          printf( __('<a href="%s">%s <i class="fa fa-sort"></i> </a>'), esc_url( add_query_arg( $query_sting ) ), $column['column_label'] );
                                    }
                                  
                            }else{
                                    echo $column['column_label'];
                            }

                            ?>
                        </th>
                <?php  } ?>
                </tr>
                </thead>
				<tbody>
				<?php
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) { ?>
						<?php
						$the_query->the_post();
						$get_the_id =  get_the_ID();
						$get_user_meta = get_post_meta($get_the_id);
						$project_manager_id = get_post_meta( $get_the_id, 'project_manager', true );
						$business_manager_id = get_post_meta( $get_the_id, 'business_manager', true );
						
						$project_end_date_value = get_post_meta( $get_the_id, 'post_duedate', true );
						if (! empty($project_end_date_value)) {
							$project_end_date_value = strtotime( $project_end_date_value );
							$project_end_date_value = date( 'd-m-Y', (int) $project_end_date_value );
						}
						
						$project_manager_info = get_user_by( 'id', $project_manager_id );
						if ( ! empty( $project_manager_info->user_nicename ) ){							
							$project_manager_nicename = $project_manager_info->display_name;
						}
						
//						$business_manager_info = get_user_by( 'id', $business_manager_id );
//						if ( ! empty( $business_manager_info->user_nicename ) ){
//							$business_manager_nicename = $business_manager_info->display_name;
//						}
//
						//Returns Array of Term Names for "rt-leave-type"
						$rt_project_type_list = wp_get_post_terms( $get_the_id, 'rt_project-type', array("fields" => "names")); // tod0:need to call in correct way
						if ( bp_loggedin_user_id() == bp_displayed_user_id() ) {
						?>
						
						<tr>
							<td>
							<?php the_title();
                             if( current_user_can( $editor_cap )  || get_current_user_id() == intval( get_the_author_meta('ID') ) ) {
                            ?>
							<div class="row-actions">
								<?php
								if( bp_is_current_action('projects') )
									printf( __('<a href="%s">' . __( 'Edit | ', RT_PM_TEXT_DOMAIN ) . '</a>'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'post_type' =>'rt_project','tab' => 'rt_project-details' ,'action'=>'edit' ), $rt_pm_bp_pm->get_component_root_url().'details' ) ) );
								printf( __('<a class="hidden-for-small-only" href="%s">' . __( 'View', RT_PM_TEXT_DOMAIN ) . '</a>'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'post_type' =>'rt_project','tab' => 'rt_project-details' ,'action'=>'view' ), $rt_pm_bp_pm->get_component_root_url().'details' ) ) );
								printf( __('<span class="hidden-for-small-only"> | </span><a href="%s">' . __( $archive_text, RT_PM_TEXT_DOMAIN ).'</a>'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=> $archive ) ) ) );
								if( bp_is_current_action('projects') )
									//printf( __('<span class="hidden-for-small-only"> | </span><a class="hidden-for-small-only deletepostlink" href="%s">' . __( 'Delete', RT_PM_TEXT_DOMAIN ) . '</a>'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=>'trash' ) ) ) );
								?>
							</div>
                                 <?php } ?>
							</td>

							<td>
								<?php echo get_post_meta( $get_the_id, 'rtpm_job_no', true ); ?>
							</td>

							<td>
								<?php if ( ! empty( $rt_project_type_list ) ) echo $rt_project_type_list[0]; ?>
							</td>
							<td><?php if ( ! empty( $project_manager_info->user_nicename ) ) echo $project_manager_nicename; ?></td>
							<td><?php if ( ! empty(  $business_manager_id ) ) echo rt_get_user_displayname( $business_manager_id ); ?></td>
							<!--<td><?php echo get_the_date('d-m-Y');?></td>
							<td><?php if ( ! empty( $project_end_date_value ) ) echo $project_end_date_value;?></td> -->
						</tr>
						<?php
						} 
					}
				} else {
					?>
					<tr><td colspan="6" align="center" scope="row"><?php _e( 'No Project Listing', RT_PM_TEXT_DOMAIN ); ?></td></tr>
					<?php
				}
				wp_reset_postdata();
				?>
			</tbody>
		</table>
	<?php } else { 
		$page = max( 1, get_query_var('paged') );
        $args = array(
                'post_type' => $rt_person->post_type,
                'post_status' => 'any',
                'posts_per_page' => 20,
                'paged' => $page,
                      );
		$wp_query = new WP_Query();
        $wp_query->query( $args );
								
		?>
<div class="rt-main-resources-container">
	<div class="rt-left-container">
		<table>
			<thead>
				<tr>
					<td>
						<?php _e( 'Project Resources', RT_PM_TEXT_DOMAIN ); ?>
					</td>
				</tr>
			</thead>
			<tbody>
				<?php 
				while ( $wp_query->have_posts() ) : $wp_query->the_post(); 
                        $people = $wp_query->post;
				?>
			<tr>
				<td>
                    <?php if( !empty( $people->post_title ) ) {
                            echo $people->post_title;
                            }else{
                            $person_wp_user_id = rt_biz_get_wp_user_for_person( $people->ID );
                            if( !empty( $person_wp_user_id ) ){
                            echo rt_get_user_displayname( $person_wp_user_id );
                        }
                    } ?>
                </td>
			</tr>
			<?php endwhile; ?>
			</tbody>
		</table>
	</div>
	<div class="rt-right-container"></div>
</div>
		<?php } ?>
		<?php /*if ( $max_num_pages > 1 ) { ?>
		<ul id="projects-pagination"><li id="prev"><a class="page-link"> &laquo; Previous</a></li><li id="next"><a class="page-link next">Next &raquo;</a></li></ul>
		<?php } */
		pm_pagination($totalPage, $page);
	} 
	?>