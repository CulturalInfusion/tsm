<?php

class BackController extends Helper
{
    public $view;

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
        if (isset($_REQUEST['task'])) {
            $requested_task = $_REQUEST['task'];
            switch ($requested_task) {
                case 'update':
                    if (
                        isset($_POST['teacher_ID']) &&
                        isset($_POST['max_allowance']) &&
                        is_numeric($_POST['max_allowance'])
                    ) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'update_teacher')) {
                            exit;
                        }
                        $teacher = get_userdata((int) $_POST['teacher_ID']);
                        if (!is_null($teacher)) {
                            // Check if user is teacher
                            if (count(array_intersect(self::get_teacher_roles(), $teacher->roles)) > 0) {
                                $query = "SELECT COUNT(*) AS `count` FROM $this->feature_table WHERE `teacher_ID` = %d";
                                $result = $wpdb->get_results($wpdb->prepare($query, $teacher->ID));
                                $count = $result[0]->count;
                                if ($count > 0) {
                                    $query = "SELECT `ID` FROM $this->feature_table WHERE `teacher_ID` = %d";
                                    $result = $wpdb->get_results($wpdb->prepare($query, $teacher->ID));
                                    $ID = $result[0]->ID;

                                    $wpdb->update(
                                        $this->feature_table,
                                        array(
                                            'created_at' => 'now()',
                                            'teacher_ID' => $teacher->ID,
                                            'max_allowance' => (int) $_POST['max_allowance']
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
                                        $this->feature_table,
                                        array(
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'teacher_ID' => $teacher->ID,
                                            'max_allowance' => (int) $_POST['max_allowance']
                                        ),
                                        array(
                                            '%s',
                                            '%d',
                                            '%d'
                                        )
                                    );
                                }
                                $this->add_notification('success', 'Max allowance updated successfully!', $this->tsm_back_notification_key);
                                $this->redirect($this->admin_base_url_of_plugin);
                            }
                        }
                        $this->add_notification('error', 'There were some problems while updating!', $this->tsm_back_notification_key);
                        $this->redirect($this->admin_base_url_of_plugin);
                    }
                    break;
                case 'move_students':
                    if (
                        isset($_POST['teacher_ID']) &&
                        isset($_POST['users']) &&
                        is_array($_POST['users'])
                    ) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'move_students')) {
                            exit;
                        }
                        $students = $_POST['users'];
                        $teacher = get_userdata((int) $_POST['teacher_ID']);
                        if (!is_null($teacher)) {
                            // Check if user is teacher
                            if (count(array_intersect(self::get_teacher_roles(), $teacher->roles)) > 0) {
                                $query = "SELECT COUNT(*) AS `count` FROM $this->table WHERE `teacher_ID` = %d";
                                $result = $wpdb->get_results($wpdb->prepare($query, $teacher->ID));
                                $count = $result[0]->count;
                                $maximum_signup_allowance = $this->get_maximum_signup_allowance($teacher->ID);
                                if (
                                    $count < $maximum_signup_allowance
                                    && count($students) <= ($maximum_signup_allowance - $count)
                                ) {
                                    $teacherLevel = pmpro_getMembershipLevelForUser($teacher->ID);
                                    foreach ($students as $student_ID) {
                                        $student = get_userdata((int) $student_ID);
                                        $student->remove_role('contributor');
                                        $student->add_role('subscriber');
                                        pmpro_changeMembershipLevel($teacherLevel->id, $student->ID);

                                        // add to table
                                        // delete from table if existed
                                        $query = "DELETE FROM $this->table WHERE `student_ID` = %d";
                                        $wpdb->query($wpdb->prepare($query, $student->ID));

                                        // insert into it
                                        $wpdb->insert(
                                            $this->table,
                                            array(
                                                'created_at' => date('Y-m-d H:i:s'),
                                                'teacher_ID' => $teacher->ID,
                                                'student_ID' => $student->ID
                                            ),
                                            array(
                                                '%s',
                                                '%d',
                                                '%d'
                                            )
                                        );
                                    }
                                    $this->add_notification(
                                        'success',
                                        'Students have been moved successfully!',
                                        $this->tsm_back_notification_key
                                    );
                                    $this->redirect($this->admin_utility_url_of_plugin);
                                } else {
                                    $this->add_notification('error', 'Sorry, you\'ve reached your limit. Upgrade your plan to add new members.', $this->tsm_back_notification_key);
                                    $this->redirect($this->admin_utility_url_of_plugin);
                                }
                            }
                        }
                        $this->add_notification('error', 'There were some problems while moving students!', $this->tsm_back_notification_key);
                        $this->redirect($this->admin_utility_url_of_plugin);
                    }
                    break;
                case 'save_report':
                    if (isset($_POST['query'])) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'save_report')) {
                            exit;
                        }
                        // insert into it
                        $wpdb->insert(
                            $this->report_table,
                            array(
                                'created_at' => date('Y-m-d H:i:s'),
                                'query' => $_POST['query'],
                            ),
                            array(
                                '%s',
                                '%s'
                            )
                        );
                        $this->add_notification(
                            'success',
                            'Query has been saved successfully!',
                            $this->tsm_back_notification_key
                        );
                        $this->redirect($this->admin_report_url_of_plugin);
                    }
                    break;
                case 'edit_report':
                case 'remove_report':
                    if (isset($_POST['report_ID'])) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'edit_remove_report')) {
                            exit;
                        }
                    }
                    $ID = intval($_POST['report_ID']);
                    switch ($requested_task) {
                        case 'edit_report':
                            if (isset($_POST['query'])) {
                                $filters = '';
                                if (
                                    isset($_POST['filters']) &&
                                    is_array($_POST['filters'])
                                ) {
                                    $filters = implode(',', $_POST['filters']);
                                }
                                $wpdb->update(
                                    $this->report_table,
                                    array(
                                        'query' => $_POST['query'],
                                        'filters' => $filters
                                    ),
                                    array(
                                        'ID' => $ID
                                    ),
                                    array(
                                        '%s',
                                        '%s'
                                    )
                                );
                                $this->add_notification(
                                    'success',
                                    'Query has been updated successfully!',
                                    $this->tsm_back_notification_key
                                );
                                $this->redirect($this->admin_report_url_of_plugin);
                            }
                            break;

                        case 'remove_report':
                            // delete from table if existed
                            $query = "DELETE FROM $this->report_table WHERE `ID` = %d";
                            $wpdb->query($wpdb->prepare($query, $ID));

                            $this->add_notification(
                                'success',
                                'Query has been removed successfully!',
                                $this->tsm_back_notification_key
                            );
                            $this->redirect($this->admin_report_url_of_plugin);
                            break;
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
            case 'utility':
            case 'report':
                $args = array(
                    'role__in'    => self::get_teacher_roles(),
                    'orderby' => 'ID',
                    'order'   => 'ASC'
                );
                $teachers = get_users($args);
                if (isset($_SESSION[$this->tsm_back_notification_key])) {
                    $this->print_messages($_SESSION[$this->tsm_back_notification_key]['status'], $_SESSION[$this->tsm_back_notification_key]['messages']);
                    unset($_SESSION[$this->tsm_back_notification_key]);
                }

                if ($this->view == 'utility') {
                    $users = get_users(array('orderby' => 'ID'));
                }

                if ($this->view == 'report') {
                    $reports = $wpdb->get_results('SELECT * FROM ' . $this->report_table);
                }
                require_once(__DIR__ . '/../views/back/' . $this->view . '.php');
                break;
        }
    }
}
