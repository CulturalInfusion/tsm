<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css'?>" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<div class="tsm-wrapper">
    <h2>
        List of students
    </h2>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=create') ?>" title="Add new student" class="btn btn-danger">Add new</a>
    <a href="<?php echo $this->add_parameters($this->base_url, 'view=import&form=csv') ?>" title="Upload your spreadsheet" class="btn btn-danger">Upload your spreadsheet</a>
    <table>
        <tr class="h4">
            <th class="col-sm-1">
                #
            </th>
            <th class="col-sm-3">
                Username
            </th>
            <th class="col-sm-2">
                Name
            </th>
            <th class="col-sm-4 email-col">
                Email
            </th>
            <th class="col-sm-2">
                Operations
            </th>
        </tr>
        <?php
        foreach ($students as $key => $student) {
            $student = get_userdata($student->ID);
            $course = $this->get_student_info($student->ID);
            ?>
            <tr class="h4">
                <td class="col-sm-1">
                    <?php
                    echo $key + 1; ?>
                </td>
                <td class="col-sm-3">
                    <?php
                    echo $student->user_login; ?>
                </td>
                <td class="col-sm-2">
                    <?php
                    echo $student->first_name . ' ' . $student->last_name; ?>
                </td>
                <td class="col-sm-4 email-col">
                    <?php
                    echo $student->user_email; ?>
                <td class="col-sm-2 text-center">
                    <a class="button btn btn-danger my-10" href="<?php echo $this->add_parameters($this->base_url, 'view=edit&student_ID=' . $student->ID) ?>">Edit</a>
                    <form id="delete_student_<?php $student->ID ?>" method="post">
                        <?php wp_nonce_field('delete_student'); ?>
                        <input type="hidden" name="task" value="destroy">
                        <input type="hidden" name="student_ID" value="<?php echo $student->ID ?>">
                        <button class="button btn btn-danger my-10" onclick="return confirmAction();">Delete</button>
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
