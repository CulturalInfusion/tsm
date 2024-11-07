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
 * Version:           1.20.0
 * Author:            Mohsen Sadeghzade
 * Author URI:        https://techiefor.fun/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/CulturalInfusion/tsm
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
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

    $report_table = $wpdb->prefix . "tsm_reports";
    $sql = "CREATE TABLE IF NOT EXISTS $report_table (
		`ID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `created_at` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
		`query` TEXT NOT NULL,
        `filters` TEXT NOT NULL,
		PRIMARY KEY  (`ID`)
    ) $charset_collate;";
    dbDelta($sql);

    $student_info_table = $wpdb->prefix . "tsm_student_info";
    $sql = "CREATE TABLE IF NOT EXISTS $student_info_table (
		`student_ID` BIGINT(20) UNSIGNED NOT NULL,
        `course` JSON,
		PRIMARY KEY  (`student_ID`)
    ) $charset_collate;";
    dbDelta($sql);

    add_option('db_version', '2.0');

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
    if (count($roles) > 0 && count(array_intersect($teacher_roles, $roles)) > 0) {
        update_max_allowance($user);
    }
}

// Hook after plan is changed
add_action('pmpro_after_change_membership_level', 'update_students_plans', 10, 2);
function update_students_plans($level_id, $user_id)
{
    global $wpdb;
    $helper = new Helper;
    $teacher_roles = $helper::get_teacher_roles();
    // Check if user is teacher
    $user = get_userdata($user_id);
    $roles = $user->roles;
    if (count($roles) > 0 && count(array_intersect($teacher_roles, $roles)) > 0) {
        update_max_allowance($user);

        // Update students levels
        $students = $helper->get_students($user_id);
        foreach ($students as $student) {
            pmpro_changeMembershipLevel($level_id, $student->ID);
        }
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
        if (count(array_intersect(Helper::get_teacher_roles(), $teacher->roles)) > 0) {
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

add_action('admin_init', 'export_list_to_csv');
/**
 * Export list to a CSV file.
 */
function export_list_to_csv()
{
    if (
        isset($_GET['page']) &&
        $_GET['page'] == 'teacher-students-management' &&
        isset($_GET['task']) &&
        $_GET['task'] == 'export'
    ) {
        header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        header('Last-Modified: {' . gmdate('D, d M Y H:i:s') . '} GMT');

        // force download  
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');

        // disposition / encoding on response body
        header('Content-Disposition: attachment;filename=data_export_' . date('Y-m-d_H-i-s') . '.csv');
        header('Content-Transfer-Encoding: binary');
        $fp = fopen('php://output', 'w');

        $table_header = array('Id', '', 'Username', 'Name', 'Email', 'Roles', 'Teacher', 'School / Org');
        fputcsv($fp, $table_header);
        fputcsv($fp, array());

        $helper = new Helper();

        $args = array(
            'role__in'    => $helper::get_teacher_roles(),
            'orderby' => 'ID',
            'order'   => 'ASC'
        );
        $teachers = get_users($args);
        foreach ($teachers as $teacher) {
            $orgName = get_user_meta($teacher->ID, 'orgname', true);
            fputcsv($fp, array(
                $teacher->ID,
                '',
                $teacher->user_login,
                $teacher->first_name . ' ' . $teacher->last_name,
                $teacher->user_email,
                implode(', ', $teacher->roles),
                '-',
                $orgName
            ));

            $studentsOfTeacher = $helper->get_students($teacher->ID);
            if (!is_null($studentsOfTeacher)) {
                foreach ($studentsOfTeacher as $student) {
                    $student = get_userdata($student->ID);
                    fputcsv($fp, array(
                        '',
                        $student->ID,
                        $student->user_login,
                        $student->first_name . ' ' . $student->last_name,
                        $student->user_email,
                        implode(', ', $student->roles),
                        $teacher->user_login,
                        $orgName
                    ));
                }
            }
        }

        fclose($fp);
        die();
    }
}


/**
 * Google auth callback
 */
add_action('init', 'tsm_request_init');
function tsm_request_init()
{
    if (
        isset($_GET['page']) &&
        $_GET['page'] == 'tsm-front'
    ) {
        require_once(__DIR__ . '/controllers/FrontController.php');
        $frontController = new FrontController();
        if (
            isset($_GET['task']) &&
            $_GET['task'] == 'set-google-auth-code' &&
            isset($_GET['code'])
        ) {
            // Google Classroom Callback
            $frontController->process_request($_GET['code']);
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
    if (count($teacher->roles) > 0 && count(array_intersect($teacher_roles, $teacher->roles)) > 0) {
        require_once(__DIR__ . '/controllers/FrontController.php');
        $frontController = new FrontController();
        $frontController->process_request();
        $frontController->initialize_page();
    }
}
add_shortcode('tsm', 'wrapper');

/**
 * Report shortcode.
 */
function report_shortcode($atts, $content = null)
{
    if (isset($atts['report'])) {
        global $wpdb;
        $helper = new Helper();
        $report_ID = (int) $atts['report'];
        $report = $wpdb->get_results($wpdb->prepare("SELECT `query`, `filters` FROM " . $helper->report_table . " WHERE `ID` = %d", $report_ID));
        if (!is_null($report) && count($report) > 0) {
            $field_pre_key = 'tsm_report_field_';
            $original_query = $query = stripslashes($report[0]->query);
            $filters = explode(',', $report[0]->filters);
            if (
                isset($_GET['tsm_report_action']) &&
                isset($_GET['tsm_report_id']) &&
                $_GET['tsm_report_id'] == $report_ID
            ) {
                $i = 0;
                $query_condition = '';
                foreach (array_keys($_GET) as $getIndex) {
                    if (strpos($getIndex, $field_pre_key) !== false && !empty($_GET[$getIndex])) {
                        if ($i > 0) {
                            $query_condition .= ' AND ';
                        }
                        $query_condition .= 'WHERE s.' . esc_sql(substr($getIndex, strlen($field_pre_key))) . '="' . esc_sql($_GET[$getIndex]) . '"';
                        $i++;
                    }
                }
                if ($i > 0) {
                    $query = 'SELECT s.* FROM (' . $query . ') AS s ' . $query_condition;
                }
            }
            $records = $wpdb->get_results($query, ARRAY_A);
            $columns = array();
            if (is_array($records) && count($records) > 0) {
                $columns = array_keys($records[0]);
            }

            if (
                isset($_GET['tsm_report_action']) &&
                $_GET['tsm_report_action'] == 'export' &&
                isset($_GET['tsm_report_id']) &&
                $_GET['tsm_report_id'] == $report_ID
            ) {
                header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
                header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
                header('Last-Modified: {' . gmdate('D, d M Y H:i:s') . '} GMT');

                // force download  
                header('Content-Type: application/force-download');
                header('Content-Type: application/octet-stream');
                header('Content-Type: application/download');

                // disposition / encoding on response body
                header('Content-Disposition: attachment;filename=data_export_' . date('Y-m-d_H-i-s') . '.csv');
                header('Content-Transfer-Encoding: binary');
                ob_end_clean();
                $fp = fopen('php://output', 'w');

                $table_header = $columns;
                fputcsv($fp, $table_header);
                fputcsv($fp, array());

                foreach ($records as $record) {
                    $array = array();
                    foreach ($columns as $column) {
                        array_push($array, $record[$column]);
                    }
                    fputcsv($fp, $array);
                }

                fclose($fp);
                die();
            }

            // prepare select box for each filter
            $filtersInHtml = '';
            foreach ($filters as $key => $filter) {
                $filter_values = $wpdb->get_results("SELECT DISTINCT s." . $filter . " FROM (" . $original_query . ") AS s", ARRAY_A);
                if (count($filter_values) > 0) {
                    $filtersInHtml .= "<label>" . $filter . "</label><select name='" . $field_pre_key . $filter . "' style='margin-right: 10px;'>
                    <option value=''>...</option>
                    ";
                    foreach ($filter_values as $value) {
                        if (empty($value[$filter])) {
                            continue;
                        }
                        $filtersInHtml .= "<option value='" . $value[$filter] . "'>" . $value[$filter] . "</option>";
                    }
                    $filtersInHtml .= "</select>";
                }
            }

            $return = "
            <div>
                <h3>Report</h3>";
            $return .= "<div>
                <form action='' method='GET'>";
            if (!empty($filtersInHtml)) {
                $return .= $filtersInHtml .
                    "<br><button class='button button-primary' type='submit' name='tsm_report_action' value='do_filter'>Filter</button>";
            }
            $return .= "
            <input type='hidden' name='tsm_report_id' value='" . $report_ID . "'>
            <button class='button button-primary' type='submit' name='tsm_report_action' value='export'>
                " . (!empty($filtersInHtml) ? 'Filter and ' : '') . "Export
                </button>
                </form>
            </div>";
            if (!is_null($columns) && !is_null($records)) {
                $return .= "
                    <div>
                        <table class='widefat fixed' cellspacing='0'>
                            <thead>
                                <tr>";
                foreach ($columns as $column) {
                    $return .= "<th>" . $column . "</th>";
                }
                $return .= "
                                </tr>
                            </thead>
                            <tbody>";
                foreach ($records as $record) {
                    $return .= "<tr>";
                    foreach ($columns as $column) {
                        $return .= "<td>" . $record[$column] . "</td>";
                    }
                    $return .= "</tr>";
                }
                $return .= "
                            </tbody>
                        </table>
                    </div>";
            }
            $return .= "</div>";
            return $return;
        }
    }
}
add_shortcode('tsm-report', 'report_shortcode');


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
    add_submenu_page(
        $menu_slug,
        'List',
        'List',
        'manage_options',
        $menu_slug,
        'teacher_students_management_page'
    );
    add_submenu_page(
        $menu_slug,
        'Utility',
        'Utility',
        'manage_options',
        'teacher-students-management-utility',
        'teacher_students_management_utility_page'
    );
    add_submenu_page(
        $menu_slug,
        'Report',
        'Report',
        'manage_options',
        'teacher-students-management-report',
        'teacher_students_management_report_page'
    );
    add_submenu_page(
        $menu_slug,
        'Settings',
        'Settings',
        'manage_options',
        'teacher-students-management-settings',
        'teacher_students_management_settings_page'
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

/**
 * Utility page for the plugin.
 */
function teacher_students_management_utility_page()
{
    global $wp, $wpdb;
    require_once(__DIR__ . '/controllers/BackController.php');
    $backController = new BackController();
    $backController->view = 'utility';
    $backController->process_request();
    $backController->initialize_page();
}

/**
 * Report page for the plugin.
 */
function teacher_students_management_report_page()
{
    global $wp, $wpdb;
    require_once(__DIR__ . '/controllers/BackController.php');
    $backController = new BackController();
    $backController->view = 'report';
    $backController->process_request();
    $backController->initialize_page();
}

/**
 * Settings page for the plugin.
 */
function teacher_students_management_settings_page()
{
    global $wp, $wpdb;
    require_once(__DIR__ . '/controllers/BackController.php');
    $backController = new BackController();
    $backController->view = 'settings';
    $backController->process_request();
    $backController->initialize_page();
}
