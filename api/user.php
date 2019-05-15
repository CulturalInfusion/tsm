<?php
/**
 * Get some info of a specific user.
 */

if (isset($_GET['username'])) {
    define('WP_USE_THEMES', false);
    require_once(__DIR__ . '/../../../../wp-load.php');
    require_once(__DIR__ . '/../helpers/Helper.php');

    $is_teacher = false;
    $teacher_ID = false;
    $teacher_username = false;
    $username = sanitize_user($_GET['username']);
    $helper = new Helper();
    $user = get_user_by('login', $username);
    if ($user) {
        $roles = $user->roles;
        if (count($roles) > 0 && in_array($roles[0], $helper::get_teacher_roles())) {
            $is_teacher = true;
            $teacher_ID = $user->ID;
            $teacher_username = $user->user_login;
        } if (count($roles) > 0 && in_array($roles[0], $helper::get_student_roles())) {
            global $wpdb;
            $query = "SELECT `teacher_ID` FROM $helper->table WHERE `student_ID` = %d";
            $results = $wpdb->get_results($wpdb->prepare($query, $user->ID));
            if (count($results) > 0) {
                $teacher_ID = $results[0]->teacher_ID;
                $teacher = get_userdata($teacher_ID);
                $teacher_username = $teacher->user_login;
            }
        }

        echo json_encode(array(
            'is_teacher' => $is_teacher,
            'teacher_id' => $teacher_ID,
            'teacher_username' => $teacher_username
        ), true);
    }
}
