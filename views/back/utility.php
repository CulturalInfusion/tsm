<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div>
    <h3>Move students</h3>
    <form action="" method="post">
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th class="manage-column" scope="col">Teacher</th>
                    <th class="manage-column" scope="col">Users</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="teacher_ID">
                            <?php
                            foreach ($teachers as $teacher) {
                                echo "<option value='" . $teacher->ID . "'>
                                " . $teacher->ID . ' - ' . $teacher->user_login . "
                                </option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <select name="users[]" multiple style="height: 400px;">
                            <?php
                            foreach ($users as $user) {
                                echo "<option value='" . $user->ID . "'>
                                    " . $user->ID . ' - ' . $user->user_login . "
                                </option>";
                            }
                            ?>
                        </select>
                    </td>
                    <td>
                        <?php wp_nonce_field('move_students'); ?>
                        <input type="hidden" name="task" value="move_students">
                        <button class="button button-primary" onclick="return confirmAction();">Move</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js' ?>"></script>