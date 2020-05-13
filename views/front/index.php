<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css'?>" rel="stylesheet" type="text/css" />
<div class="tsm-wrapper">
    <h2>
        List of students
    </h2>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=create') ?>" title="Add new student" class="button my-10">Add new</a>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=import&form=csv') ?>" title="Import from CSV" class="button my-10">Import from CSV</a>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=import&form=google-classroom') ?>" title="Import from Google Classroom" class="button my-10">Import from Google Classroom</a>
    <table>
        <tr>
            <th>
                ID
            </th>
            <th>
                Username
            </th>
            <th class="col-sm-0">
                Name
            </th>
            <th class="col-sm-0">
                User Email
            </th>
            <th>
                Operations
            </th>
        </tr>
        <?php
        foreach ($students as $student) {
            $student = get_userdata($student->ID);
            ?>
            <tr>
                <td>
                    <?php
                    echo $student->ID; ?>
                </td>
                <td>
                    <?php
                    echo $student->user_login; ?>
                </td>
                <td class="col-sm-0">
                    <?php
                    echo $student->first_name . ' ' . $student->last_name; ?>
                </td>
                <td class="col-sm-0">
                    <?php
                    echo $student->user_email; ?>
                </td>
                <td class="text-center">
                    <a class="button my-10" href="<?php echo $this->add_parameters($this->base_url, 'view=edit&student_ID=' . $student->ID) ?>">Edit</a>
                    <form id="delete_student_<?php $student->ID ?>" method="post">
                        <?php wp_nonce_field('delete_student'); ?>
                        <input type="hidden" name="task" value="destroy">
                        <input type="hidden" name="student_ID" value="<?php echo $student->ID ?>">
                        <button class="button" onclick="return confirmAction();">Delete</a>
                    </form>
                </td>
            </tr>
        <?php
        } ?>
    </table>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js'?>"></script>
