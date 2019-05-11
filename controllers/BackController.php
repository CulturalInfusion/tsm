<?php

class BackController extends Helper
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
                case 'update':
                    if (isset($_POST['teacher_ID']) &&
                    isset($_POST['max_allowance']) &&
                    is_numeric($_POST['max_allowance'])
                    ) {
                        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'update_teacher')) {
                            exit;
                        }
                        $teacher = get_userdata((int)$_POST['teacher_ID']);
                        if (!is_null($teacher)) {
                            // Check if user is teacher
                            if (in_array($teacher->roles[0], self::get_teacher_roles())) {
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
                                            'max_allowance' => (int)$_POST['max_allowance']
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
                                            'max_allowance' => (int)$_POST['max_allowance']
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
                global $wpdb;
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
                require_once(__DIR__ . '/../views/back/index.php');
                break;
        }
    }
}
