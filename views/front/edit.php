<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css'?>" rel="stylesheet" type="text/css" />
<div class="tsm-wrapper">
    <h2>
        Edit student <?php echo $student->display_name ?>
    </h2>
    <form action="" method="post">
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="first-name">First Name</label>
            <input name="first_name" type="text" id="first-name" class="tsm-field" value="<?php echo $student->first_name ?>" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="last-name">Last Name</label>
            <input name="last_name" type="text" id="last-name" class="tsm-field" value="<?php echo $student->last_name ?>" required>
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="course">Course</label>
            <input type="text" id="course" readonly class="tsm-field" value="<?php echo $course ?>">
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label">Username</label>
            <input type="text" readonly disabled value="<?php echo $student->user_login ?>" class="tsm-field">
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="password">Password</label>
            <input name="user_pass" type="password" id="password" minlength="5" class="tsm-field" placeholder="Enter if you want to change">
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="password-confirmation">Confirm password</label>
            <input type="password" id="password-confirmation" minlength="5" class="tsm-field" required placeholder="Enter if you typed password">
        </div>
        <div class="tsm-field-wrap">
            <label class="tsm-label" for="email">Email</label>
            <input name="user_email" type="email" id="email" class="tsm-field" value="<?php echo $student->user_email ?>" required>
        </div>
        <div class="tsm-field-wrap tsm-submit-wrap">
            <?php wp_nonce_field('update_student'); ?>
            <input type="hidden" name="task" value="update">
            <input name="submit" type="submit" value="Save" class="button" onclick="return confirmPassword();">
            <a href="<?php echo $this->add_parameters($this->base_url, 'view=index') ?>" title="Back" class="button">Back</a>
        </div>
    </form>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js'?>"></script>
