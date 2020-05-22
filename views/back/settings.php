<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div>
    <h3>Settings</h3>
    <form action="" method="post">
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th class="manage-column" scope="col">Key</th>
                    <th class="manage-column" scope="col">Value</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Google Classroom API Credentials
                    </td>
                    <td>
                        <textarea name="tsm_google_classroom_api_credentials"><?php echo $googleClassroomApiCredentials ?></textarea>
                    </td>
                    <td>
                        <?php wp_nonce_field('save_settings'); ?>
                        <input type="hidden" name="task" value="save_settings">
                        <button class="button button-primary" onclick="return confirmAction();">Save</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js' ?>"></script>