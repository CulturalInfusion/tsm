<link href="<?php echo plugin_dir_url(__DIR__ . '/../../tsm.php') . 'assets/css/style.css' ?>" rel="stylesheet" type="text/css" />
<div>
    <h3>Report</h3>
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