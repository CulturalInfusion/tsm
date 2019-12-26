<?php

class Helper
{
    protected $teacher;
    protected $base_url;
    protected $admin_base_url_of_plugin;
    public $table;
    public $feature_table;
    public $report_table;
    protected $user_table;
    protected $user_membership_level_table;
    protected $tsm_front_notification_key;
    protected $tsm_back_notification_key;

    /**
     * Construct the object and initalize the properties;
     */
    public function __construct()
    {
        global $wp;
        global $wpdb;
        $this->teacher = wp_get_current_user();
        $this->base_url = home_url($wp->request);
        $this->admin_base_url_of_plugin = admin_url('admin.php?page=teacher-students-management');
        $this->admin_utility_url_of_plugin = admin_url('admin.php?page=teacher-students-management-utility');
        $this->admin_report_url_of_plugin = admin_url('admin.php?page=teacher-students-management-report');
        $this->table = $wpdb->prefix . "teacher_students";
        $this->feature_table = $wpdb->prefix . "teacher_features";
        $this->report_table = $wpdb->prefix . "tsm_reports";
        $this->user_table = $wpdb->prefix . "users";
        $this->user_membership_level_table = $wpdb->prefix . "pmpro_memberships_users";
        $this->tsm_front_notification_key = 'tsm_front_notification';
        $this->tsm_back_notification_key = 'tsm_back_notification';
    }

    /**
     * Get teacher roles.
     *
     * @return array
     */
    public static function get_teacher_roles()
    {
        return array(
            'contributor'
        );
    }

    /**
     * Get student roles.
     *
     * @return array
     */
    public static function get_student_roles()
    {
        return array(
            'subscriber'
        );
    }

    /**
     * Get user level info based on membership plugin.
     *
     * @param  string  $key
     * @return mixed
     */
    public static function get_user_level_info($key)
    {
        global $wpdb;
        $level = [];
        $membership_levels_table = $wpdb->prefix . "pmpro_membership_levels";
        $query = "SELECT `id`, `description` FROM $membership_levels_table WHERE `name` = %s";
        $results = $wpdb->get_results($wpdb->prepare($query, $key));
        if (count($results) > 0) {
            $level['id'] = $results[0]->id;
            // Data pattern in the description field of each level: <!--max_allowance=80-->
            preg_match('<!--(max_allowance=(.*))-->', $results[0]->description, $match);
            if (isset($match[2]) && is_numeric($match[2])) {
                $level['max_allowance'] = $match[2];
                return $level;
            }
        }
        return null;
    }

    /**
     * Get table name by predefined key.
     *
     * @param  string  $key
     * @return mixed
     */
    public static function get_table_name($key)
    {
        global $wpdb;
        $tables = array(
            'feature_table' => $wpdb->prefix . 'teacher_features',
            'user_membership_level' => $wpdb->prefix . 'pmpro_memberships_users'
        );
        return (isset($tables[$key]) ? $tables[$key] : null);
    }

    /**
     * Get students of specific teacher
     *
     * @param  int  $teacher_ID
     * @return array $students
     */
    public function get_students($teacher_ID)
    {
        global $wpdb;
        $query = "SELECT `ID` FROM $this->user_table WHERE `ID` IN (SELECT `student_ID` FROM $this->table WHERE `teacher_ID` = %d)";
        $students = $wpdb->get_results($wpdb->prepare($query, $teacher_ID));
        return $students;
    }

    /**
     * Print the message.
     *
     * @param  string  $status
     * @param  array  $messages
     */
    protected function print_messages($status, $messages)
    {
        if ($status == 'success' || $status == 'error') {
            echo '<div class="tsm-notice notice notice-' . $status . ' is-dismissible">';
            echo '<strong>' . ucfirst($status) . '</strong>: ';
            foreach ($messages as $message) {
                echo esc_html($message) . '<br/>';
            }
            echo '</div>';
        }
    }

    /**
     * Redirect inside the HTML, after header is set.
     *
     * @param  string  $url
     */
    protected function redirect($url)
    {
        $string = '<script type="text/javascript">';
        $string .= 'window.location = "' . $url . '"';
        $string .= '</script>';
        echo $string;
        die;
    }

    /**
     * Add notification to session.
     *
     * @param  string  $status
     * @param  string  $message
     * @param  string  $notification_key
     */
    protected function add_notification($status, $message, $notification_key)
    {
        $_SESSION[$notification_key]['status'] = $status;

        if (
            !isset($_SESSION[$notification_key]['messages']) ||
            !is_array($_SESSION[$notification_key]['messages'])
        ) {
            $_SESSION[$notification_key]['messages'] = array();
        }
        array_push($_SESSION[$notification_key]['messages'], $message);
    }

    /**
     * Add query to url.
     *
     * @param  string  $url
     * @param  string  $newQuery
     * @return string
     */
    public function add_parameters($url, $newQuery)
    {
        return (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . $newQuery;
    }

    /**
     * Get maximum allowance for signups
     *
     * @param  int  $teacherId
     * @return int $max_allowance
     */
    protected function get_maximum_signup_allowance($teacherId)
    {
        global $wpdb;
        $max_allowance = 0;
        $query = "SELECT `max_allowance` FROM $this->feature_table WHERE `teacher_ID` = %d";
        $results = $wpdb->get_results($wpdb->prepare($query, $teacherId));
        if (count($results) > 0) {
            $max_allowance = $results[0]->max_allowance;
        }
        return $max_allowance;
    }

    /**
     * Get columns by query
     *
     * @param  string  $query
     * @return array $columns
     */
    protected static function get_columns_by_query($query)
    {
        global $wpdb;
        $records = $wpdb->get_results(stripslashes($query), ARRAY_A);
        $columns = array();
        if (is_array($records) && count($records) > 0) {
            $columns = array_keys($records[0]);
        }
        return $columns;
    }
}
