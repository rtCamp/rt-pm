<?php get_header() ?>
	<div id="content">
		<div class="padder">

			<div id="item-header">
				<?php locate_template( array( 'members/single/member-header.php' ), true ) ?>
			</div>

			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="object-nav">
					<ul>
						<?php bp_get_displayed_user_nav() ?>
					</ul>
				</div>
			</div>

			<div id="item-body">

				<div class="item-list-tabs no-ajax" id="subnav">
					<ul>
						<?php 
						global $rt_pm_project, $rt_pm_bp_pm, $rt_pm_bp_pm_project, $bp, $wpdb,  $wp_query;
						bp_get_options_nav();
						?>
					</ul>
				</div>
				<!-- code for Projects -->
				<?php
					//global $rt_pm_project, $rt_pm_bp_pm_project, $bp, $wpdb,  $wp_query;
					
					if (isset($_GET['rt_project_id']) || isset($_GET['post_type']) && ($_GET['action'] != 'archives')){
						$rt_pm_bp_pm_project->custom_page_ui();
					} else {
						$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
						$paged = $page = max( 1, get_query_var('paged') );
						
						$posts_per_page = get_option( 'posts_per_page' );
						
						$order = 'DESC';
						$attr = 'startdate';
						
						$meta_key = 'post_duedate';
						$orderby = 'meta_value';
						
						if( isset( $_GET['orderby'] ) ) {
	                        $meta_key = $args['orderby'] = $_GET['orderby'];
	                        $order = $args['order'] =  $_GET['order'];
	                	}
						if( $meta_key == "rt_project-type" ) {
							$meta_key = 'post_duedate';
							$orderby = 'rt_project-type';
						}
						if( $meta_key == "title" ) {
							$meta_key = 'post_duedate';
							$orderby = 'title';
						}
						if( $meta_key == "date" ) {
							$meta_key = 'post_duedate';
							$orderby = 'date';
						}
						
				
						$offset = ( $paged - 1 ) * $posts_per_page;
						if ($offset <=0) {
							$offset = 0;
						}
						if ($_GET['action'] == 'archives'){
							$post_status = 'trash';
							$archive = 'unarchive';
							$archive_text = __('Unarchive');
							$projectslists = 'projects-archives-lists';
							$projects_pagination = 'projects-archives-pagination';
						} else {
							$post_status = array( 'new', 'active', 'paused','complete', 'closed' );
							$archive = 'archive';
							$archive_text = __('Archive');
							$projectslists = 'projects-lists';
							$projects_pagination = 'projects-pagination';
						}
						
						
						$args = array(
							'post_type' => $rt_pm_project->post_type,
							'meta_key'   => $meta_key,
							'orderby' => $orderby,
							'order'      => $order,
							'post_status' => $post_status,
							'posts_per_page' => $posts_per_page,
							'offset' => $offset
						);
						
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
		                            'column_label' => __( 'Type', RT_PM_TEXT_DOMAIN ) ,
		                            'sortable' => true,
		                            'orderby' => 'rt_project-type',
		                            'order' => 'asc'
		                    ),
		                    array(
		                            'column_label' => __( 'Project Manager', RT_PM_TEXT_DOMAIN ) ,
		                            'sortable' => true,
		                            'orderby' => 'project_manager',
		                            'order' => 'asc'
		                          
		                    ),
		                    array(
		                            'column_label' => __( 'Business Manager', RT_HRM_TEXT_DOMAIN ),
		                            'sortable' => true,
		                            'orderby' => 'business_manager',
		                            'order' => 'asc'
		                    ),
		                    array(
		                            'column_label' => __( 'Start Date', RT_PM_TEXT_DOMAIN ) ,
		                            'sortable' => true,
		                            'orderby' => 'date',
		                            'order' => 'asc'
		                    ),
		                    array(
		                            'column_label' => __( 'End Date', RT_HRM_TEXT_DOMAIN ) ,
		                            'sortable' => true,
		                            'orderby' => 'post_duedate',
		                            'order' => 'asc'
		                    ),
		
		            	);
					
						
						// The Query
						$the_query = new WP_Query( $args );
						
						$totalPage= $max_num_pages =  $the_query->max_num_pages;
						
						?>
						<table cellspacing="0" class="<?php echo $projectslists; ?>">
							<tbody>
								<tr class="lists-header">
			                      <?php foreach ( $columns as $column ) {
			                      ?>
			                            <td>
			                                <?php
			                                if(  $column['sortable']  ) {
			
			                                        if ( isset( $_GET['orderby'] ) && $column['orderby']  == $_GET['orderby'] ) {
			                                           
			                                            $current_order = $_GET['order'];
			                                           
			                                            $order = 'asc' == $current_order ? 'desc' : 'asc';
			                                            
			                                            printf( __('<a href="%s">%s <i class="fa fa-sort-%s"></i> </a>'), esc_url( add_query_arg( array( 'orderby' => $column['orderby'] ,'order' => $order ) ) ), $column['column_label'], $order );
			                                            
			                                        }else{
			                                              printf( __('<a href="%s">%s <i class="fa fa-sort"></i> </a>'), esc_url( add_query_arg( array( 'orderby' => $column['orderby'] ,'order' => 'desc' ) ) ), $column['column_label'] );
			                                        }
			                                      
			                                }else{
			                                        echo $column['column_label'];
			                                }
			
			                                ?>
			                            </td>
			                    <?php  } ?>
		                        </tr>
								<?php /*<tr class="lists-header">
									<th align="center" class="order title" scope="row" data-sorting-type="ASC" data-attr-type="title">
										<?php esc_html_e('Name', 'rt_pm');?>
										<span>
											<i class="fa fa-caret-down"></i>
										</span>
									</th>
									<th align="center" class="order projecttype" scope="row" data-sorting-type="ASC" data-attr-type="projecttype">
										<?php esc_html_e('Type', 'rt_pm');?>
										<span>
											<i class="fa fa-caret-down"></i>
										</span>
									</th>
									<th align="center" class="order projectmanager" scope="row" data-sorting-type="ASC" data-attr-type="projectmanager">
										<?php esc_html_e('Project Manager', 'rt_pm');?>
										<span>
											<i class="fa fa-caret-down"></i>
										</span>
									</th>
									<th align="center" class="order projectmanager" scope="row" data-sorting-type="ASC" data-attr-type="businessmanager">
										<?php esc_html_e('Business Manager', 'rt_pm');?>
										<span>
											<i class="fa fa-caret-down"></i>
										</span>
									</th>
									<th align="center" class="order startdate" scope="row" data-sorting-type="ASC" data-attr-type="startdate">
										<?php esc_html_e('Start Date', 'rt_hrm');?>
										<span>
											<i class="fa fa-caret-down"></i>
										</span>
									</th>
									<th align="center" class="order enddate" scope="row" data-sorting-type="ASC" data-attr-type="enddate">
										<?php esc_html_e('End Date', 'rt_hrm');?>
										<span>
											<i class="fa fa-caret-down"></i>
										</span>
									</th>
								</tr> */?>
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
										
										$business_manager_info = get_user_by( 'id', $business_manager_id );
										if ( ! empty( $business_manager_info->user_nicename ) ){							
											$business_manager_nicename = $business_manager_info->display_name;
										}
										
										//Returns Array of Term Names for "rt-leave-type"
										$rt_project_type_list = wp_get_post_terms($post->ID, 'rt_project-type', array("fields" => "names")); // tod0:need to call in correct way
										if ( bp_loggedin_user_id() == bp_displayed_user_id() ) {
										?>
										<tr class="lists-data">
											<td align="center" scope="row"><?php the_title();
											printf( __('<br /><span><a href="%s">Edit</a></span>&nbsp;&#124;'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=>'edit' ) ) ) );
											printf( __('<span><a href="%s">View</a></span>&nbsp;&#124;'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=>'view' ) ) ) );
											printf( __('<span><a href="%s">'.$archive_text.'</a></span>&nbsp;&#124;'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=> $archive ) ) ) ); 
											printf( __('<span><a class="deletepostlink" href="%s">Delete</a></span>'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=>'trash' ) ) ) );
											?>
											</td>
											<td align="center" scope="row">
												<?php if ( ! empty( $rt_project_type_list ) ) echo $rt_project_type_list[0]; ?>
											</td>
											<td align="center" scope="row"><?php echo $project_manager_nicename; ?></td>
											<td align="center" scope="row"><?php echo $business_manager_nicename; ?></td>
											<td align="center" scope="row"><?php echo get_the_date('d-m-Y');?></td>
											<td align="center" scope="row"><?php if ( ! empty( $project_end_date_value ) ) echo $project_end_date_value;?></td>
										</tr>
										<?php
										} 
									}
								} else {
									?>
									<tr class="lists-data"><td colspan="6" align="center" scope="row">No Project Listing</td></tr>
									<?php
								}
								wp_reset_postdata();
								?>
							</tbody>
						</table>
						<?php /*if ( $max_num_pages > 1 ) { ?>
						<ul id="projects-pagination"><li id="prev"><a class="page-link"> &laquo; Previous</a></li><li id="next"><a class="page-link next">Next &raquo;</a></li></ul>
						<?php } */
						pm_pagination($totalPage, $page);
					} 
					?>
			</div><!-- #item-body -->

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>