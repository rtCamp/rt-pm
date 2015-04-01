<?php

/********************************************************************************
 * Screen Functions
 *
 * Screen functions are the controllers of BuddyPress. They will execute when their
 * specific URL is caught. They will first save or manipulate data using business
 * functions, then pass on the user to a template file.
 * @author paresh
 */
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


class Rtbp_Pm_Screens {

    /**
     * Placeholder method
     */
    public function __construct() {

        $this->setup();
    }


    /**
     * Setup actions and filters
     */
    public function setup() {

        add_action( 'bp_screens', array( $this, 'bp_pm_screen' ) );
    }

    /**
     * Return a singleton instance of the class.
     *
     * @return Rtpm_Screens
     */
    public static function factory() {
        static $instance = false;
        if ( ! $instance ) {
            $instance = new self();
            $instance->setup();
        }
        return $instance;
    }

    /**
     * Load 'project' activity page
     */
    public function bp_pm_screen() {

        if( bp_is_current_component( BP_PM_SLUG ) )
            bp_core_load_template( 'members/single/home'  );
    }


    public function bp_pm_projects() {
        add_action('bp_template_content', array( $this, 'load_projects_template') );
    }

    public function load_projects_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-projects.php';
    }

    public function bp_pm_projects_new() {
        add_action('bp_template_content', array( $this, 'load_projects_new_template') );
    }

    public function load_projects_new_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-projects-new.php';
    }

    public function bp_pm_details() {
        add_action('bp_template_content', array( $this, 'load_projects_details_template') );
    }

    public function load_projects_details_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-add.php';
    }

    public function bp_pm_attachments() {
        add_action('bp_template_content', array( $this, 'load_projects_attachments_template') );
    }

    public function load_projects_attachments_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-attachments.php';
    }

    public function bp_pm_time_entries() {
        add_action('bp_template_content', array( $this, 'load_projects_time_entries_template') );
    }

    public function load_projects_time_entries_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-time-entries.php';
    }

    public function bp_pm_tasks() {
        add_action('bp_template_content',array( $this, 'load_projects_tasks_template') );
    }

    public function load_projects_tasks_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-tasks.php';
    }

    public function bp_pm_notifications() {
        add_action('bp_template_content', array( $this, 'load_projects_notifications_template') );
    }

    public function load_projects_notifications_template() {

        include  RT_PM_BP_PM_PATH.'/templates/pm-notifications.php';
    }

    public function bp_pm_gantt(){

        add_action('bp_template_content', array( $this, 'load_project_gantt_admin_template' ) );
    }

    public function load_project_gantt_admin_template(){

        include  RT_PM_BP_PM_PATH.'/templates/pm-gantt-admin.php';
    }

    public function rtpm_project_overview_screen(){
        wp_enqueue_script( 'rtvoxxi-context-script', get_stylesheet_directory_uri().'/assets/js/contextMenu.min.js', array('jquery'), BUDDYBOSS_CHILD_THEME_VERS );
        wp_enqueue_style( 'rtvoxxi-context-style', get_stylesheet_directory_uri().'/assets/css/contextMenu.css');

        wp_enqueue_script('rtpm-handleba-script', RT_PM_URL . 'app/assets/javascripts/handlebars.js', "", true);


        add_action('bp_template_content', array( $this, 'rtpm_project_overview_template' ) );

        global $rt_bp_reports;

        $rt_bp_reports =  Rt_Bp_Reports_Loader::factory();
    }

    public function rtpm_project_overview_template(){
        include  RT_PM_BP_PM_PATH.'/templates/pm-project-overview.php';
    }

}

global $rtbp_pm_screen;
$rtbp_pm_screen = Rtbp_Pm_Screens::factory();

