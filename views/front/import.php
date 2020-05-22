<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div class="tsm-wrapper">
    <?php
    $form = isset($_GET['form']) ? $_GET['form'] : 'csv';
    switch ($form) {
        case 'google-classroom':
    ?>
            <h2>
                Import student from Google Classroom account
            </h2>
            <form action="" method="post">
                <div class="tsm-field-wrap">
                    <?php
                    if (!isset($_SESSION['tsm_google_classroom_token'])) {
                    ?>
                        <a href="<?= $googleAuthUrl ?>" class="button">Authorize by Google</a>
                    <?php
                    } else {
                        echo "<select name='course_id'>";
                        foreach ($courses as $course) {
                            echo "<option value='" . $course->getId() . "'>" . $course->getName() . "</option>";
                        }
                        echo "</select>";
                    }
                    ?>
                </div>
                <div class="tsm-field-wrap tsm-submit-wrap">
                    <?php wp_nonce_field('import'); ?>
                    <input type="hidden" name="task" value="import">
                    <input type="hidden" name="form" value="google-classroom">
                    <input name="submit" type="submit" value="Import" class="button">
                    <a href="<?php echo $this->add_parameters($this->base_url, 'view=index') ?>" title="Back" class="button">Back</a>
                </div>
            </form>
        <?php
            break;
        default:
        ?>
            <h2>
                Import student from a CSV file
            </h2>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="tsm-field-wrap">
                    <label class="tsm-label">CSV Sample file</label>
                    <a href="<?= plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/csv/students.csv' ?>" class="button">Download</a>
                </div>
                <div class="tsm-field-wrap">
                    <label class="tsm-label" for="file">CSV file</label>
                    <input name="csv" type="file" id="file" class="tsm-field" required>
                </div>
                <div class="tsm-field-wrap tsm-submit-wrap">
                    <?php wp_nonce_field('import'); ?>
                    <input type="hidden" name="task" value="import">
                    <input type="hidden" name="form" value="csv">
                    <input name="submit" type="submit" value="Import" class="button">
                    <a href="<?php echo $this->add_parameters($this->base_url, 'view=index') ?>" title="Back" class="button">Back</a>
                </div>
            </form>
    <?php
            break;
    }
    ?>
</div>
<script type="text/javascript" src="<?= plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js' ?>"></script>