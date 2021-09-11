<?php

class Helper
{
    protected $teacher;
    protected $base_url;
    protected $admin_base_url_of_plugin;
    public $table;
    public $feature_table;
    public $report_table;
    public $student_info_table;
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
        $this->student_info_table = $wpdb->prefix . "tsm_student_info";
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
     * Add new student
     */
    public function add_student($teacher_ID, $first_name, $last_name, $username, $password, $email, $showNotification = [], $randomEmail = false, $row = '')
    {
        global $wpdb;
        $query = "SELECT COUNT(*) AS `count` FROM $this->table WHERE `teacher_ID` = %d";
        $result = $wpdb->get_results($wpdb->prepare($query, $teacher_ID));
        $count = $result[0]->count;
        
        if ($randomEmail && empty($email)) {
            $email = md5(microtime(true)) . '@example.com';
        }

        if ($count < $this->get_maximum_signup_allowance($teacher_ID)) {
            $errorFlag = false;
            if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
                if ($showNotification) {
                    $this->add_notification('error', $row . 'Required form field is missing', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }
            if (4 > strlen($username)) {
                if ($showNotification) {
                    $this->add_notification('error', $row . 'Username too short. At least 4 characters is required', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }
            if (username_exists($username)) {
                if ($showNotification) {
                    $this->add_notification('error', $row . 'Sorry, that username already exists!', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }
            if (!validate_username($username)) {
                if ($showNotification) {
                    $this->add_notification('error', $row . 'Sorry, the username you entered is not valid', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }
            if (5 > strlen($password)) {
                if ($showNotification) {
                    $this->add_notification('error', $row . 'Password length must be greater than 5', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }
            if (!is_email($email)) {
                if ($showNotification) {
                    $this->add_notification('error', $row . 'Email is not valid', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }
            if (email_exists($email)) {
                if ($showNotification && in_array('duplicate_email', $showNotification)) {
                    $this->add_notification('error', $row . 'Email Already in use', $this->tsm_front_notification_key);
                }
                $errorFlag = true;
            }

            if (!$errorFlag) {
                // New student
                $userdata = array(
                    'user_login'    =>   sanitize_user($username),
                    'user_pass'     =>   esc_attr($password),
                    'user_email'    =>   sanitize_email($email),
                    'first_name'    =>   sanitize_text_field($first_name),
                    'last_name'     =>   sanitize_text_field($last_name)
                );
                $user_ID = wp_insert_user($userdata);
                if (!is_wp_error($user_ID)) {

                    // Update his/her role
                    wp_update_user(array('ID' => $user_ID, 'role' => 'subscriber'));

                    // Add the membership - Get from teacher
                    $teacherLevel = pmpro_getMembershipLevelForUser($teacher_ID);

                    $wpdb->insert(
                        $this->user_membership_level_table,
                        array(
                            'user_id' => $user_ID,
                            'membership_id' => $teacherLevel->id,
                            'cycle_period' => '',
                            'status' => 'active',
                            'startdate' => date('Y-m-d H:i:s')
                        ),
                        array(
                            '%d',
                            '%d',
                            '%s',
                            '%s',
                            '%s'
                        )
                    );

                    // Add relation to the teacher
                    $wpdb->insert(
                        $this->table,
                        array(
                            'created_at' => date('Y-m-d H:i:s'),
                            'teacher_ID' => $teacher_ID,
                            'student_ID' => $user_ID
                        ),
                        array(
                            '%s',
                            '%d',
                            '%d'
                        )
                    );
                    if ($showNotification) {
                        $this->add_notification('success', $row . 'New student has been added successfully. Id: ' . $user_ID, $this->tsm_front_notification_key);
                    }
                    return $user_ID;
                }
                return -1;
            }
            return -2;
        }
        if ($showNotification) {
            $this->add_notification('error', $row . 'Sorry, you\'ve reached your limit. Upgrade your plan to add new members.', $this->tsm_front_notification_key);
        }
        return -3;
    }

    /**
     * Student Info.
     * 
     * @param  int  $ID
     * @param  array  $course
     */
    public function update_student_info($ID, $course)
    {
        global $wpdb;
        $query = "SELECT COUNT(*) AS `count` FROM $this->student_info_table WHERE `student_ID` = %d";
        $result = $wpdb->get_results($wpdb->prepare($query, $ID));
        $count = $result[0]->count;
        if ($count > 0) {
            $wpdb->update(
                $this->student_info_table,
                array(
                    'course' => json_encode($course)
                ),
                array(
                    'student_ID' => $ID
                ),
                array(
                    '%s'
                ),
                array(
                    '%d'
                )
            );
        } else {
            $wpdb->insert(
                $this->student_info_table,
                array(
                    'student_ID' => $ID,
                    'course' => json_encode($course)
                ),
                array(
                    '%d',
                    '%s'
                )
            );
        }
    }

    /**
     * Student Info.
     * 
     * @param  int  $ID
     * @param  array  $course
     */
    public function get_student_info($ID, $get_course_name = true, $full_json = false)
    {
        global $wpdb;
        $query = "SELECT * FROM $this->student_info_table WHERE `student_ID` = %d";
        $result = $wpdb->get_results($wpdb->prepare($query, $ID));   

        if ($get_course_name) {
            if (isset($result[0])) {
                $course = json_decode($result[0]->course);
                $name = $course->name;
                $id = $course->id;
            } else {
                $name = 'Default';
                $id = 0;
            }
            if ($full_json) {
                return json_encode([
                    'id' => $id,
                    'name' => $name
                ]);
            } else {
                return $name;
            }
        }

        return (isset($result[0])) ? $result[0] : false;
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

    /**
     * Returns an authorized API client.
     * 
     * @param  string  $authCode
     * 
     * @return mixed the authorized client object
     */
    function get_google_client($authCode = null)
    {
        require_once(__DIR__ . '/../vendor/autoload.php');
        $client = new Google_Client();
        $client->setApplicationName('TSM Google Classroom API');
        $client->setScopes([
            Google_Service_Classroom::CLASSROOM_COURSES_READONLY,
            'https://www.googleapis.com/auth/classroom.profile.emails',
            'https://www.googleapis.com/auth/classroom.profile.photos',
            'https://www.googleapis.com/auth/classroom.rosters.readonly'
        ]);
        $credentials = stripslashes(get_option('tsm_google_classroom_api_credentials'));
        $credentials = json_decode($credentials, true);
        $client->setAuthConfig($credentials);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from session, if it exists.
        // The session variable stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        if (isset($_SESSION['tsm_google_classroom_token'])) {
            $accessToken = json_decode($_SESSION['tsm_google_classroom_token'], true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else if (is_null($authCode)) {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                return $authUrl;
            } else {
                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    // throw new Exception(join(', ', $accessToken));
                    return false;
                }
            }
            $_SESSION['tsm_google_classroom_token'] = json_encode($client->getAccessToken());
        }
        return $client;
    }
}
