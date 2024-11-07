<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div class="tsm-wrapper">
    <?php
    $form = isset($_GET['form']) ? $_GET['form'] : 'csv';
    switch ($form) {
        case 'google-classroom-':
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
                Upload your students list via a spreadsheet (CSV file)
            </h2>
            <h4>
                <p><strong>→ To upload your student list, please follow these steps: </strong></p>
            </h4>
            <ol>
                <li><strong>Download the CSV Template: </strong>
                Begin by downloading the provided CSV template, which can be opened with Microsoft Excel or similar applications.</li>
                <li><strong>Enter Student Information: </strong>
                Fill in each student’s name, surname, and email address in the designated fields. Avoid special characters (e.g., write "Sam OBrien" instead of "Sam O'Birian").</li>
                <li><strong>Set Passwords:</strong> 
                Passwords must be at least 8 characters long and include uppercase, lowercase, and a special character. Students will have the option to change their passwords later.</li>
                <li><strong>Save and Upload:</strong> Once completed, save your file as a CSV and click "Upload List" to submit it here. The upload may take a couple of minutes. If any errors occur, you’ll have the option to edit and re-upload the affected items.</li>
                <li><strong>Need Assistance?</strong> If you prefer, you can contact our team directly. One of our friendly team members will help you upload the list on your behalf.</li>
            </ol>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="tsm-field-wrap">
                    <label class="tsm-label">Sample file ( CSV format )</label>
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