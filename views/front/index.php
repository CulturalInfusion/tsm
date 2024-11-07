<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css'?>" rel="stylesheet" type="text/css" />
<div class="tsm-wrapper">
    <h2>
        List of students
    </h2>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=create') ?>" title="Add new student" class="button my-10">Add new</a>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=import&form=csv') ?>" title="Upload your spreadsheet" class="button my-10">Import from CSV</a>
    <!-- 
    <a href="<?php //echo $this->add_parameters($this->base_url, 'view=import&form=google-classroom') ?>" title="Import from Google Classroom" class="button my-10">Import from Google Classroom</a> 
     -->
    <table>
        <tr>
            <th>
                #
            </th>
            <th>
                Username
            </th>
            <th class="col-sm-0">
                Name
            </th>
            <th class="col-sm-0 email-col">
                Email
            </th>
           <!-- <th class="col-sm-0 email-col">
                Course
            </th> -->
            <th>
                Operations
            </th>
        </tr>
        <?php
        foreach ($students as $key => $student) {
            $student = get_userdata($student->ID);
            $course = $this->get_student_info($student->ID);
            ?>
            <tr>
                <td>
                    <?php
                    echo $key + 1; ?>
                </td>
                <td>
                    <?php
                    echo $student->user_login; ?>
                </td>
                <td class="col-sm-0">
                    <?php
                    echo $student->first_name . ' ' . $student->last_name; ?>
                </td>
                <td class="col-sm-0 email-col">
                    <?php
                    echo $student->user_email; ?>
                <-- </td>
                <td class="col-sm-0 email-col">
                    <?php
                    echo $course;
                    ?>
                </td> -->
                <td class="text-center">
                    <a class="button my-10" href="<?php echo $this->add_parameters($this->base_url, 'view=edit&student_ID=' . $student->ID) ?>">Edit</a>
                    <form id="delete_student_<?php $student->ID ?>" method="post">
                        <?php wp_nonce_field('delete_student'); ?>
                        <input type="hidden" name="task" value="destroy">
                        <input type="hidden" name="student_ID" value="<?php echo $student->ID ?>">
                        <button class="button" onclick="return confirmAction();">Delete</button>
                    </form>
                </td>
            </tr>
            <?php
        } ?>
    </table>
</div>
<?php
if (!$notification_flag) {
?>
    <script type="text/javascript">
        var no_hash=true;
    </script>
<?php
}
?>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js'?>"></script>
