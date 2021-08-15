<?php

/**
 * Get some info of a specific user.
 */

if (isset($_GET['username'])) {
    define('WP_USE_THEMES', false);
    require_once(__DIR__ . '/../../../../wp-load.php');
    require_once(__DIR__ . '/../helpers/Helper.php');

    $is_teacher = false;
    $teacher_ID = '';
    $teacher_username = '';
    $username = sanitize_user($_GET['username']);
    $helper = new Helper();
    $user = get_user_by('login', $username);
    if ($user) {
        $roles = $user->roles;
        if (count($roles) > 0 && count(array_intersect($helper::get_teacher_roles(), $roles)) > 0) {
            $is_teacher = true;
            $teacher_ID = $user->ID;
            $teacher_username = $user->user_login;
        } else {
            global $wpdb;
            $query = "SELECT `teacher_ID` FROM $helper->table WHERE `student_ID` = %d";
            $results = $wpdb->get_results($wpdb->prepare($query, $user->ID));
            if (count($results) > 0) {
                $teacher_ID = $results[0]->teacher_ID;
                $teacher = get_userdata($teacher_ID);
                $teacher_username = $teacher->user_login;
            }
        }

        $output = [
            'is_teacher' => $is_teacher,
            'teacher_id' => $teacher_ID,
            'teacher_username' => $teacher_username
        ];

        if ($is_teacher) {
            $students = $helper->get_students($teacher_ID);
            foreach ($students as $key => $student) {
                $students[$key] = get_student_api_output($helper, $student->ID);
            }
            $output['students'] = $students;
        } else if (count($roles) > 0 && count(array_intersect($helper::get_student_roles(), $roles)) > 0) {
            $output['info'] = get_student_api_output($helper, $user->ID);
        }
        
        header('Content-Type: application/json');
        echo json_encode($output, true);
    }
}

function get_student_api_output($helper, $student_ID)
{
    $student = get_userdata($student_ID);
    $student_info = $helper->get_student_info($student_ID);
    $output = new stdClass;
    $output->ID = $student_ID;
    if ($student_info) {
        $output->course = $student_info->course;
    }
    $output->username = $student->user_login;
    $output->name = $student->user_nicename;
    return $output;
}
