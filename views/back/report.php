<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div>
    <h3>Report</h3>
    <table class="widefat fixed" cellspacing="0">
        <?php
        if (count($reports) > 0) {
        ?>
            <thead>
                <tr>
                    <th class="manage-column" scope="col" width="20%">Shortcode</th>
                    <th class="manage-column" scope="col" width="30%">Query</th>
                    <th class="manage-column" scope="col" width="30%">Filters</th>
                    <th width="20%"></th>
                </tr>
            </thead>
        <?php
        }
        ?>
        <tbody>
            <?php
            foreach ($reports as $report) {
                $filters = explode(',', $report->filters);
            ?>
                <tr>
                    <td colspan="4">
                        <form action="" method="post">
                            <table width="100%">
                                <tr>
                                    <td width="20%">[tsm-report report=<?php echo $report->ID ?>]</td>
                                    <td width="30%"><textarea name="query"><?php echo stripslashes($report->query) ?></textarea></td>
                                    <td width="30%">
                                        <select name="filters[]" class="searchable-selector" multiple>
                                            <?php
                                            foreach (Helper::get_columns_by_query($report->query) as $column) {
                                                echo "<option value='" . $column . "' " . (in_array($column, $filters) ? 'selected' : '') . ">" . $column . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td width="10%">
                                        <button class="button button-primary" name="task" value="edit_report" onclick="return confirmAction();">Save</
                                    </td>
                                    <td width="10%">
                                        <?php wp_nonce_field('edit_remove_report'); ?>
                                        <input type="hidden" name="report_ID" value="<?php echo $report->ID ?>">
                                        <button class="button button-secondary" name="task" value="remove_report" onclick="return confirmAction();">X</button>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <form action="" method="post">
        <table>
            <tbody>
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

<!-- Searchable selectors -->
<link rel="stylesheet" href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/chosen.min.css' ?>">
<script src="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/js/chosen.jquery.min.js' ?>"></script>
<script type="text/javascript">
    jQuery('.searchable-selector').chosen({
        width: "95%"
    });
</script>