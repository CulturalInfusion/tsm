<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css'?>" rel="stylesheet" type="text/css" />
<div class="tsm-wrapper">
    <h2>
        Add new student
    </h2>
    <form action="" method="post">
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="first-name">First Name</label>
            <input name="first_name" type="text" id="first-name" class="tsm-field" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="last-name">Last Name</label>
            <input name="last_name" type="text" id="last-name" class="tsm-field" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="username">Username</label>
            <input name="user_login" type="text" id="username" class="tsm-field" minlength="4" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="password">Password</label>
            <input name="user_pass" type="password" id="password" class="tsm-field" minlength="5" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="password-confirmation">Confirm password</label>
            <input type="password" id="password-confirmation" minlength="5" class="tsm-field" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="email">Email</label>
            <input name="user_email" type="email" id="email" class="tsm-field" required>
        </div>
        <div class="tsm-field-wrap tsm-submit-wrap">
            <?php wp_nonce_field('create_student'); ?>
            <input type="hidden" name="task" value="store">
            <input name="submit" type="submit" value="Save" class="button" onclick="return confirmPassword();">
            <a href="<?php echo $this->add_parameters($this->base_url, 'view=index') ?>" title="Back" class="button">Back</a>
        </div>
    </form>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js'?>"></script>
