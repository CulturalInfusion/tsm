<?php

class FrontController extends Helper
{
    protected $view;

    /**
     * Construct the object and initalize the properties;
     */
    public function __construct()
    {
        global $wp;
        $this->view = isset($_GET['view']) ? $_GET['view'] : 'index';

        parent::__construct();
    }

    /**
     * Process available request.
     */
    public function process_request()
    {
        global $wp;
        global $wpdb;
        if (isset($_POST['task'])) {
            switch ($_POST['task']) {
                case 'store':
                    if (
                        isset($_POST['first_name']) &&
                        isset($_POST['last_name']) &&
                        isset($_POST['user_login']) &&
                        isset($_POST['user_pass']) &&
                        isset($_POST['user_email'])
                    ) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'create_student')) {
                            exit;
                        }
                        $query = "SELECT COUNT(*) AS `count` FROM $this->table WHERE `teacher_ID` = %d";
                        $result = $wpdb->get_results($wpdb->prepare($query, $this->teacher->ID));
                        $count = $result[0]->count;
                        if ($count < $this->get_maximum_signup_allowance($this->teacher->ID)) {
                            $first_name = $_POST['first_name'];
                            $last_name = $_POST['last_name'];
                            $username = $_POST['user_login'];
                            $password = $_POST['user_pass'];
                            $email = $_POST['user_email'];

                            $errorFlag = false;
                            if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
                                $this->add_notification('error', 'Required form field is missing', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (4 > strlen($username)) {
                                $this->add_notification('error', 'Username too short. At least 4 characters is required', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (username_exists($username)) {
                                $this->add_notification('error', 'Sorry, that username already exists!', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (!validate_username($username)) {
                                $this->add_notification('error', 'Sorry, the username you entered is not valid', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (5 > strlen($password)) {
                                $this->add_notification('error', 'Password length must be greater than 5', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (!is_email($email)) {
                                $this->add_notification('error', 'Email is not valid', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (email_exists($email)) {
                                $this->add_notification('error', 'Email Already in use', $this->tsm_front_notification_key);
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
                                    $teacherLevel = pmpro_getMembershipLevelForUser($this->teacher->ID);

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
                                            'teacher_ID' => $this->teacher->ID,
                                            'student_ID' => $user_ID
                                        ),
                                        array(
                                            '%s',
                                            '%d',
                                            '%d'
                                        )
                                    );
                                    $this->add_notification('success', 'New student has been added successfully. Id: ' . $user_ID, $this->tsm_front_notification_key);
                                }
                            }
                            $this->redirect($this->base_url);
                        } else {
                            $this->add_notification('error', 'Sorry, you\'ve reached your limit. Upgrade your plan to add new members.', $this->tsm_front_notification_key);
                            $this->redirect($this->base_url);
                        }
                    }
                    break;
                case 'update':
                    if (
                        isset($_GET['student_ID']) &&
                        is_numeric($_GET['student_ID']) &&
                        isset($_POST['first_name']) &&
                        isset($_POST['last_name']) &&
                        isset($_POST['user_pass']) &&
                        isset($_POST['user_email'])
                    ) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'update_student')) {
                            exit;
                        }
                        $student_ID = (int) $_GET['student_ID'];
                        // Check if student is registerd by the teacher
                        $query = "SELECT COUNT(*) AS `count` FROM $this->table WHERE `teacher_ID` = %d AND `student_ID` = %d";
                        $result = $wpdb->get_results($wpdb->prepare($query, $this->teacher->ID, $student_ID));
                        $count = $result[0]->count;
                        if ($count == 1) {
                            $student = get_userdata($student_ID);

                            $first_name = $_POST['first_name'];
                            $last_name = $_POST['last_name'];
                            $password = $_POST['user_pass'];
                            $email = $_POST['user_email'];

                            $errorFlag = false;
                            if (empty($first_name) || empty($last_name) || empty($email)) {
                                $this->add_notification('error', 'Required form field is missing', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (!empty($password) && 5 > strlen($password)) {
                                $this->add_notification('error', 'Password length must be greater than 5', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (!is_email($email)) {
                                $this->add_notification('error', 'Email is not valid', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }
                            if (email_exists($email) !== false && email_exists($email) != $student->ID) {
                                $this->add_notification('error', 'Email Already in use', $this->tsm_front_notification_key);
                                $errorFlag = true;
                            }

                            if (!$errorFlag) {
                                $userdata = array(
                                    'ID'            =>   (int) $student->ID,
                                    'user_login'    =>   sanitize_user($student->user_login),
                                    'user_email'    =>   sanitize_email($email),
                                    'first_name'    =>   sanitize_text_field($first_name),
                                    'last_name'     =>   sanitize_text_field($last_name)
                                );
                                if (!empty($password)) {
                                    $userdata['user_pass'] = wp_hash_password(esc_attr($password));
                                }
                                $user_ID = wp_insert_user($userdata);
                                if (!is_wp_error($user_ID)) {
                                    $this->add_notification('success', 'Student has been updated successfully. Id: ' . $user_ID, $this->tsm_front_notification_key);
                                } else {
                                    $this->add_notification('error', 'Somethings happened while updating the user.', $this->tsm_front_notification_key);
                                }
                            }
                            $this->redirect($this->base_url);
                        }
                    }
                    break;
                case 'destroy':
                    if (
                        isset($_POST['student_ID'])
                        && is_numeric($_POST['student_ID'])
                    ) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_student')) {
                            exit;
                        }
                        $student_ID = (int) $_POST['student_ID'];
                        // Check if student is registerd by the teacher
                        $query = "SELECT COUNT(*) AS `count` FROM $this->table WHERE `teacher_ID` = %d AND `student_ID` = %d";
                        $result = $wpdb->get_results($wpdb->prepare($query, $this->teacher->ID, $student_ID));
                        $count = $result[0]->count;
                        if ($count == 1) {
                            $query = "DELETE FROM $this->table WHERE `teacher_ID` = %d AND `student_ID` = %d";
                            $wpdb->query($wpdb->prepare($query, $this->teacher->ID, $student_ID));

                            require_once(ABSPATH . 'wp-admin/includes/user.php');
                            wp_delete_user($student_ID);
                            $this->add_notification('success', 'Student has been delete successfully. Id: ' . $student_ID, $this->tsm_front_notification_key);
                            $this->redirect($this->base_url);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Intialize appropriate view.
     */
    public function initialize_page()
    {
        global $wp;
        global $wpdb;
        switch ($this->view) {
            case 'index':
                $students = $this->get_students($this->teacher->ID);
                if (isset($_SESSION[$this->tsm_front_notification_key])) {
                    $this->print_messages($_SESSION[$this->tsm_front_notification_key]['status'], $_SESSION[$this->tsm_front_notification_key]['messages']);
                    unset($_SESSION[$this->tsm_front_notification_key]);
                }
                require_once(__DIR__ . '/../views/front/index.php');
                break;
            case 'create':
                require_once(__DIR__ . '/../views/front/create.php');
                break;
            case 'edit':
                if (
                    isset($_GET['student_ID'])
                    && is_numeric($_GET['student_ID'])
                ) {
                    $student_ID = (int) $_GET['student_ID'];
                    // Check if student is registerd by the teacher
                    $query = "SELECT COUNT(*) AS `count` FROM $this->table WHERE `teacher_ID` = %d AND `student_ID` = %d";
                    $result = $wpdb->get_results($wpdb->prepare($query, $this->teacher->ID, $student_ID));
                    $count = $result[0]->count;
                    if ($count == 1) {
                        $student = get_userdata($student_ID);
                        require_once(__DIR__ . '/../views/front/edit.php');
                    }
                }
                break;
        }
    }
}
