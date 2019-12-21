<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div>
    <h3>Report</h3>
    <form action="" method="post">
        <table class="widefat fixed" cellspacing="0">
            <?php
            if (count($reports) > 0) {
            ?>
                <thead>
                    <tr>
                        <th class="manage-column" scope="col">Shortcode</th>
                        <th class="manage-column" scope="col">Query</th>
                        <th></th>
                    </tr>
                </thead>
            <?php
            }
            ?>
            <tbody>
                <?php
                foreach ($reports as $report) {
                ?>
                    <tr>
                        <td>[tsm-report report=<?php echo $report->ID ?>]</td>
                        <td><?php echo $report->query ?></td>
                        <td>
                            <form action="" method="post">
                                <?php wp_nonce_field('remove_report'); ?>
                                <input type="hidden" name="report_ID" value="<?php echo $report->ID ?>">
                                <input type="hidden" name="task" value="remove_report">
                                <button class="button button-danger" onclick="return confirmAction();">X</button>
                            </form>
                        </td>
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <td colspan="2">
                        <textarea name="query" cols="60" rows="10" placeholder="Enter the query (please have security in mind)"></textarea>
                    </td>
                    <td>
                        <?php wp_nonce_field('save_report'); ?>
                        <input type="hidden" name="task" value="save_report">
                        <button class="button button-primary" onclick="return confirmAction();">Save Report</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>

    <?php
    if (!is_null($columns) && !is_null($records)) {
    ?>
        <div>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <?php
                        foreach ($columns as $column) {
                            echo "<th>" . $column . "</th>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($records as $record) {
                    ?>
                        <tr>
                            <?php
                            foreach ($columns as $column) {
                                echo "<td>" . $record[$column] . "</td>";
                            }
                            ?>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php
    }
    ?>
</div>
<script type="text/javascript" src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/script.js' ?>"></script>