<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css'?>" rel="stylesheet" type="text/css" />
<div>
    <table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th id="id" class="manage-column column-id" scope="col">Id</th>
                <th class="manage-column" scope="col">Username</th>
                <th class="manage-column" scope="col">Name</th>
                <th class="manage-column" scope="col">Email</th>
                <th class="manage-column" scope="col">Role</th>
                <th class="manage-column" scope="col">Teacher</th>
                <th class="manage-column" scope="col">Max Allowance</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            foreach ($teachers as $teacher) {
                ?>
                <tr class="<?php echo ($i % 2 == 0) ? 'alternate' : ''; ?>">
                    <td class="column-id">
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $teacher->ID) ?>"><?php echo $teacher->ID; ?></a>
                    </td>
                    <td>
                        <?php
                        echo $teacher->user_login; ?>
                    </td>
                    <td>
                        <?php
                        echo $teacher->first_name . ' ' . $teacher->last_name; ?>
                    </td>
                    <td>
                        <?php
                        echo $teacher->user_email; ?>
                    </td>
                    <td>
                        <?php
                        $roles = $teacher->roles;
                        echo implode(', ', $roles);
                        ?>
                    </td>
                    <td>
                        -
                    </td>
                    <td>
                        <form action="" method="post">
                            <?php wp_nonce_field('update_teacher'); ?>
                            <input type="number" min="0" name="max_allowance" value="<?php echo $this->get_maximum_signup_allowance($teacher->ID) ?>" class="regular-text max-allowance">
                            <input type="hidden" name="teacher_ID" value="<?php echo $teacher->ID ?>">
                            <input type="hidden" name="task" value="update">
                            <button class="button button-primary" onclick="return confirmAction();">Save</a>
                        </form>
                    </td>
                </tr>
                <?php
                $i++;
                $studentsOfTeacher = $this->get_students($teacher->ID);
                if (!is_null($studentsOfTeacher)) {
                    foreach($studentsOfTeacher as $student) {
                        $student = get_userdata($student->ID);
                    ?>
                        <tr class="<?php echo ($i % 2 == 0) ? 'alternate' : ''; ?>">
                            <td class="column-id">
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $student->ID); ?>"><?php echo $student->ID; ?></a>
                            </td>
                            <td>
                                <?php
                                echo esc_html($student->user_login); 
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $student->first_name . ' ' . $student->last_name; ?>
                            </td>
                            <td>
                                <?php
                                echo $student->user_email; ?>
                            </td>
                            <td>
                                <?php
                                $roles = $student->roles;
                                echo implode(', ', $roles);
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $teacher->ID); ?>"><?php echo $teacher->user_login; ?></a>
                            </td>
                            <td>
                                -
                            </td>
                        </tr>
                    <?php
                        $i++;
                    }
                }
                ?>
            <?php
            } ?>
        </tbody>
    </table>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js'?>"></script>
