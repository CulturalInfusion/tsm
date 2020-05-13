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
    public function process_request($google_auth_callback_code = null)
    {
        global $wp;
        global $wpdb;

        if (isset($_POST['task']) || !is_null($google_auth_callback_code)) {
            $task = isset($_POST['task']) ? $_POST['task'] : 'import';
            switch ($task) {
                case 'import':
                    $form = !is_null($google_auth_callback_code) ? 'google-classroom' : $_POST['form'];
                    if ($form) {
                        if (isset($_POST['form']) && !wp_verify_nonce($_REQUEST['_wpnonce'], 'import')) {
                            exit;
                        }
                        switch ($form) {
                            case 'csv':
                                if (isset($_FILES['csv'])) {
                                    $csvFile = fopen($_FILES['csv']['tmp_name'], 'r');
                                    $row = 0;
                                    $successful = 0;
                                    $failed = 0;
                                    while (($data = fgetcsv($csvFile, 1000, ',')) !== FALSE) {
                                        $row++;
                                        if ($row == 1) {
                                            continue;
                                        }
                                        $student_ID = $this->add_student($this->teacher->ID, $data[0], $data[1], $data[2], $data[3], $data[4], false);
                                        if ($student_ID > 0) {
                                            $successful++;
                                        } else if ($student_ID < 0) {
                                            $failed++;
                                        }
                                    }
                                    $this->add_notification('success', 'Import process is done. Successful: ' . $successful . ', ' . 'Failed: ' . $failed, $this->tsm_front_notification_key);
                                    $this->redirect($this->base_url);
                                }
                                break;
                            case 'google-classroom':
                                if (!is_null($google_auth_callback_code)) {
                                    // After callback

                                    // Get the API client and construct the service object.
                                    try {
                                        require_once(__DIR__ . '/../vendor/autoload.php');
                                        $client = $this->get_google_client($google_auth_callback_code);
                                        $service = new Google_Service_Classroom($client);
                                        $results = $service->courses->listCourses(['pageSize' => 0]);
                                        if (count($results->getCourses()) != 0) {
                                            $row = 0;
                                            $successful = 0;
                                            $failed = 0;
                                            foreach ($results->getCourses() as $course) {
                                                $students = $service->courses_students->listCoursesStudents($course->getId(), ['pageSize' => 0])->getStudents();
                                                foreach ($students as $student) {
                                                    $profile = $student->getProfile();
                                                    $email = $profile->emailAddress;
                                                    $first_name = $profile->name->givenName;
                                                    $last_name = $profile->name->familyName;
                                                    $username = $email;
                                                    $password = $email;

                                                    $student_ID = $this->add_student($this->teacher->ID, $first_name, $last_name, $username, $password, $email, false);
                                                    if ($student_ID > 0) {
                                                        $successful++;
                                                    } else if ($student_ID < 0) {
                                                        $failed++;
                                                    }
                                                }
                                            }
                                            $this->add_notification('success', 'Import process is done. Successful: ' . $successful . ', ' . 'Failed: ' . $failed, $this->tsm_front_notification_key);
                                        }
                                        $currentUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                                        $this->redirect(strtok($currentUrl, '?'));
                                    } catch (Exception $e) {
                                        $e->getMessage();
                                    }
                                }
                                break;
                        }
                    }
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
                        $first_name = $_POST['first_name'];
                        $last_name = $_POST['last_name'];
                        $username = $_POST['user_login'];
                        $password = $_POST['user_pass'];
                        $email = $_POST['user_email'];
                        $student_ID = $this->add_student($this->teacher->ID, $first_name, $last_name, $username, $password, $email);
                        $this->redirect($this->base_url);
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
            case 'import':
                if (isset($_GET['form']) && $_GET['form'] == 'google-classroom') {
                    $googleClient = $this->get_google_client();
                    $googleAuthUrl = '';
                    if (is_string($googleClient)) {
                        $googleAuthUrl = $googleClient;
                    }
                }
                require_once(__DIR__ . '/../views/front/import.php');
                break;
            case 'import-google-classroom':
                require_once(__DIR__ . '/../views/front/import-google-classroom.php');
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
