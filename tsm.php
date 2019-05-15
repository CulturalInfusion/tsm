<?php
/**
 * A plugin for manage students membership by teacher.
 *
 * @since             1.0.0
 * @package           TSM
 *
 * @wordpress-plugin
 * Plugin Name:       Teacher's Students Management
 * Description:       Teacher's students management.
 * Version:           1.2.1
 * Author:            Mohsen Sadeghzade
 * Author URI:        https://techiefor.fun/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/CulturalInfusion/tsm
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Enable session for message handling
if (!session_id()) {
    session_start();
}
require_once(__DIR__ . '/helpers/Helper.php');

/**
 * Initialize plugin on activate.
 */
function tsm_install()
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    $teacher_students_table = $wpdb->prefix . "teacher_students";

    $sql = "CREATE TABLE IF NOT EXISTS $teacher_students_table (
		`ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		`created_at` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
		`teacher_ID` BIGINT(20) UNSIGNED NOT NULL,
		`student_ID` BIGINT(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (`ID`),
        FOREIGN KEY (`teacher_ID`) REFERENCES `" . $wpdb->prefix . "users`(`ID`) ON UPDATE CASCADE ON DELETE RESTRICT,
        FOREIGN KEY (`student_ID`) REFERENCES `" . $wpdb->prefix . "users`(`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
	) $charset_collate;";
    dbDelta($sql);

    $feature_table = $wpdb->prefix . "teacher_features";

    $sql = "CREATE TABLE IF NOT EXISTS $feature_table (
		`ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		`created_at` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
		`teacher_ID` BIGINT(20) UNSIGNED NOT NULL,
		`max_allowance` INT(11) UNSIGNED NOT NULL,
		PRIMARY KEY  (`ID`),
        UNIQUE KEY (`teacher_ID`),
        FOREIGN KEY (`teacher_ID`) REFERENCES `" . $wpdb->prefix . "users`(`ID`) ON UPDATE CASCADE ON DELETE RESTRICT
	) $charset_collate;";
    dbDelta($sql);

    add_option('db_version', '1.0');

    // Update max allowance for each teacher
    $args = array(
        'role__in'    => Helper::get_teacher_roles(),
        'orderby' => 'ID',
        'order'   => 'ASC'
    );
    $teachers = get_users($args);
    foreach ($teachers as $teacher) {
        update_max_allowance($teacher);
    }
}
register_activation_hook(__FILE__, 'tsm_install');

// Hook after registration
add_action('pmpro_after_checkout', 'initalize_teacher_features');
function initalize_teacher_features($user_id)
{
    global $wpdb;
    $teacher_roles = Helper::get_teacher_roles();
    // Check if user is teacher
    $user = get_userdata($user_id);
    $roles = $user->roles;
    if (count($roles) > 0 && in_array($roles[0], $teacher_roles)) {
        update_max_allowance($user);
    }
}

// Hook after update the teacher profile (from admin panel)
add_action('edit_user_profile_update', 'update_teacher_features');

function update_teacher_features($user_id)
{
    global $wpdb;
    if (current_user_can('edit_user', $user_id)) {
        $teacher = get_userdata($user_id);
        // Check if user is teacher
        if (in_array($teacher->roles[0], Helper::get_teacher_roles())) {
            update_max_allowance($teacher);
        }
    }
}

/**
 * Update max allowance of the teacher on different events.
 *
 * @param  object  $teacher
 */
function update_max_allowance($teacher)
{
    global $wpdb;
    $query = "SELECT `name` FROM " . $wpdb->prefix . "pmpro_membership_levels WHERE `id` = (SELECT `membership_id` FROM " . $wpdb->prefix . "pmpro_memberships_users WHERE `user_id` = %d ORDER BY `id` DESC LIMIT 1)";
    $result = $wpdb->get_results($wpdb->prepare($query, $teacher->ID));
    if (!is_null($result) && count($result) > 0) {
        $name = $result[0]->name;
        $user_level_info = Helper::get_user_level_info($name);
        if (!is_null($user_level_info)) {
            $max_allowance = $user_level_info['max_allowance'];

            $query = "SELECT COUNT(*) AS `count` FROM " . Helper::get_table_name('feature_table') . " WHERE `teacher_ID` = %d";
            $result = $wpdb->get_results($wpdb->prepare($query, $teacher->ID));
            $count = $result[0]->count;
            if ($count > 0) {
                $query = "SELECT `ID` FROM " . Helper::get_table_name('feature_table') . " WHERE `teacher_ID` = %d";
                $result = $wpdb->get_results($wpdb->prepare($query, $teacher->ID));
                $ID = $result[0]->ID;

                $wpdb->update(
                    Helper::get_table_name('feature_table'),
                    array(
                        'created_at' => 'now()',
                        'teacher_ID' => $teacher->ID,
                        'max_allowance' => $max_allowance
                    ),
                    array(
                        'ID' => $ID
                    ),
                    array(
                        '%d',
                        '%d'
                    )
                );
            } else {
                $wpdb->insert(
                    Helper::get_table_name('feature_table'),
                    array(
                        'created_at' => date('Y-m-d H:i:s'),
                        'teacher_ID' => $teacher->ID,
                        'max_allowance' => $max_allowance
                    ),
                    array(
                        '%s',
                        '%d',
                        '%d'
                    )
                );
            }
        }
    }
}

/**
 * Wrapper for the plugin to be called inside the application.
 */
function wrapper()
{
    $teacher = wp_get_current_user();
    $teacher_roles = Helper::get_teacher_roles();
    // Check if user is teacher
    if (count($teacher->roles) > 0 && in_array($teacher->roles[0], $teacher_roles)) {
        require_once(__DIR__ . '/controllers/FrontController.php');
        $frontController = new FrontController();
        $frontController->process_request();
        $frontController->initialize_page();
    }
}
add_shortcode('tsm', 'wrapper');

add_action('admin_menu', 'teacher_students_management_menu');
/**
 * Admin menu for the plugin.
 */
function teacher_students_management_menu()
{
    $page_title = 'Teacher\'s students management';
    $menu_title = $page_title;
    $capability = 'manage_options';
    $menu_slug  = 'teacher-students-management';
    $function   = 'teacher_students_management_page';
    $icon_url   = 'dashicons-admin-users';

    add_menu_page(
        $page_title,
        $menu_title,
        $capability,
        $menu_slug,
        $function,
        $icon_url
    );
}

/**
 * Base admin page for the plugin.
 */
function teacher_students_management_page()
{
    global $wp, $wpdb;
    require_once(__DIR__ . '/controllers/BackController.php');
    $backController = new BackController();
    $backController->process_request();
    $backController->initialize_page();
}
