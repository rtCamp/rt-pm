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
						<?php bp_get_options_nav() ?>
						<li class="" id=""><input type="button"  data-reveal-id="add-new-leave-modal" value="Add New" />  </li>
						<li id=""><input type="button"  data-reveal-id="add-archives-modal" value="Archives" /> </li>
					</ul>
				</div>
				<!-- code for Projects -->
				<?php
					global $rt_pm_project, $rt_pm_bp_pm_project, $bp, $wpdb,  $wp_query;
					if (isset($_GET['rt_project_id']) || isset($_GET['post_type'])) 
						$rt_pm_bp_pm_project->custom_page_ui();
					else {
					$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
					
					$posts_per_page = get_option( 'posts_per_page' );
					
					$order = 'DESC';
					$attr = 'startdate';
					$meta_key = 'leave-start-date';
					if ( $attr == "startdate" ){
						$meta_key = 'leave-start-date';
					} else if( $attr == "enddate" ) {
						$meta_key = 'leave-end-date';
					}
			
					$offset = ( $paged - 1 ) * $posts_per_page;
					if ($offset <=0) {
						$offset = 0;
					}
					
					$args = array(
						'post_type' => $rt_pm_project->post_type,
						// 'meta_key'   => $meta_key,
						'orderby' => 'meta_value_num',
						'order'      => $order,
						'post_status' => 'any',
						'posts_per_page' => $posts_per_page,
						'offset' => $offset
					);
					// The Query
					$the_query = new WP_Query( $args );
					
					$max_num_pages =  $the_query->max_num_pages;
					
					?>
					<table cellspacing="0" class="projects-lists">
						<tbody>
							<tr class="lists-header">
								<th align="center" scope="row">
									<?php esc_html_e('Name', 'rt_pm');?>
								</th>
								<th align="center" scope="row">
									<?php esc_html_e('Type', 'rt_pm');?>
								</th>
								<th align="center" scope="row">
									<?php esc_html_e('Project Manager', 'rt_pm');?>
								</th>
								<th align="center" scope="row">
									<?php esc_html_e('Business Manager', 'rt_pm');?>
								</th>
								<th align="center" scope="row" data-sorting-type="ASC" data-attr-type="startdate">
									<?php esc_html_e('Start Date', 'rt_hrm');?>
									<span>
										<i class="fa fa-caret-down"></i>
									</span>
								</th>
								<th align="center" scope="row" data-sorting-type="ASC" data-attr-type="enddate">
									<?php esc_html_e('End Date', 'rt_hrm');?>
									<span>
										<i class="fa fa-caret-down"></i>
									</span>
								</th>
							</tr>
							<?php
							if ( $the_query->have_posts() ) {
								while ( $the_query->have_posts() ) { ?>
									<?php
									$the_query->the_post();
									$get_the_id =  get_the_ID();
									$get_user_meta = get_post_meta($get_the_id);
									$project_manager_id = get_post_meta( $get_the_id, 'project_manager', true );
									$business_manager_id = get_post_meta( $get_the_id, 'business_manager', true );
									$leave_duration_value = get_post_meta( $get_the_id, 'leave-duration', true );
									$leave_duration_type = get_term_by('slug', $leave_duration_value, 'rt-leave-type');
									
									$leave_start_date_value = get_post_meta( $get_the_id, 'leave-start-date', true );
									$leave_end_date_value = get_post_meta( $get_the_id, 'post_duedate', true );
									$leave_end_date_value = strtotime( $leave_end_date_value );
									$leave_end_date_value = date( 'd-m-Y', (int) $leave_end_date_value );
									
									$project_manager_info = get_user_by( 'id', $project_manager_id );
									if ( ! empty( $project_manager_info->user_nicename ) ){							
										$project_manager_nicename = $project_manager_info->user_nicename;
									}
									
									$business_manager_info = get_user_by( 'id', $business_manager_id );
									if ( ! empty( $business_manager_info->user_nicename ) ){							
										$business_manager_nicename = $business_manager_info->user_nicename;
									}
									
									//Returns Array of Term Names for "rt-leave-type"
									$rt_project_type_list = wp_get_post_terms($post->ID, 'rt_project-type', array("fields" => "names")); // tod0:need to call in correct way
									if ( bp_loggedin_user_id() == bp_displayed_user_id() ) {
									?>
									<tr class="lists-data">
										<td align="center" scope="row"><?php the_title();
										printf( __('<br /><span><a href="%s">Edit</a></span>&nbsp;&#124;'), esc_url( add_query_arg( array( 'rt_project_id'=> $get_the_id, 'action'=>'edit' ) ) ) ); ?>
										<a href="<?php the_permalink();?>">View</a>
										</td>
										<td align="center" scope="row">
											<?php if ( ! empty( $rt_project_type_list ) ) echo $rt_project_type_list[0]; ?>
										</td>
										<td align="center" scope="row"><?php echo $project_manager_nicename; ?></td>
										<td align="center" scope="row"><?php echo $business_manager_nicename; ?></td>
										<td align="center" scope="row"><?php echo the_date('d-m-Y');?></td>
										<td align="center" scope="row"><?php echo $leave_end_date_value;?></td>
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
					<?php if ( $max_num_pages > 1 ) { ?>
					<ul id="leave-pagination"><li id="prev"><a class="page-link">Previous</a></li><li id="next"><a class="page-link next">Next</a></li></ul>
					<?php }} ?>
			</div><!-- #item-body -->

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php locate_template( array( 'sidebar.php' ), true ) ?>

<?php get_footer() ?>