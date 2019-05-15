<?php
/**
 * Get some info of a specific user.
 */

if (isset($_GET['id'])) {
    define('WP_USE_THEMES', false);
    require_once(__DIR__ . '/../../../../wp-load.php');
    require_once(__DIR__ . '/../helpers/Helper.php');

    $is_teacher = false;
    $teacher_ID = false;
    $id = (int)$_GET['id'];
    $helper = new Helper();
    $user = get_userdata($id);
    if ($user) {
        $roles = $user->roles;
        if (count($roles) > 0 && in_array($roles[0], $helper::get_teacher_roles())) {
            $is_teacher = true;
            $teacher_id = $id;
        } if (count($roles) > 0 && in_array($roles[0], $helper::get_student_roles())) {
            global $wpdb;
            $query = "SELECT `teacher_ID` FROM $helper->table WHERE `student_ID` = %d";
            $results = $wpdb->get_results($wpdb->prepare($query, $id));
            if (count($results) > 0) {
                $teacher_ID = $results[0]->teacher_ID;
            }
        }

        echo json_encode(array(
            'is_teacher' => $is_teacher,
            'teacher_id' => $teacher_ID
        ), true);
    }
}
