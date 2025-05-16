<?php
add_action('admin_menu', function () {
    add_menu_page(
        'Parent Block Importer',
        'Blocks Importer',
        'manage_options',
        'blink-block-importer',
        'render_blink_block_importer_page',
        'dashicons-block-default',
        '2',
    );
});

function render_blink_block_importer_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $current_user = wp_get_current_user();
    $current_user_email = $current_user->user_email;
    if (!str_contains($current_user_email, "@live")) {
        echo "<div class='notice notice-error notice-alt'><h2>Helaas je hebt geen toegang tot deze pagina!</h2></div>";
        exit();
    }

    $parent_blocks_path = get_template_directory() . '/parts/blocks/';
    $child_blocks_path  = get_stylesheet_directory() . '/parts/blocks/';
    $available_blocks  = [];

    if (is_dir($parent_blocks_path)) {
        $dirs = scandir($parent_blocks_path);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($parent_blocks_path . $dir)) {
                $available_blocks[] = $dir;
            }
        }
    }
    empty($available_blocks) ? $var_disabled = "disabled" : $var_disabled = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_blocks'])) {
        $selected = array_map('sanitize_text_field', $_POST['selected_blocks']);
        $copied = [];

        foreach ($selected as $block) {
            $src = $parent_blocks_path . $block;
            $dest = $child_blocks_path . $block;

            if (!file_exists($dest)) {
                blink_recursive_copy($src, $dest);
                $copied[] = $block;
            }
        }

        if (!empty($copied)) : ?>
            <div class="notice notice-success notice-alt is-dismissible">
                <p>De volgende Block(s) zijn gekopieerd: <b><?= (implode(', ', $copied)); ?></b></p>
            </div>
        <?php endif;
    }
    ?>

    <div class="wrap">
        <h1 style="font-weight: bold; font-size: 2rem;">Parent Thema Blocks Importeren</h1>

        <form method="post">
            <?php wp_nonce_field('blink_block_import'); ?>

            <p style="font-weight: bold; font-size: 1rem">Selecteer welke blocks je wil kopiëren naar het child theme:</p>
            <p style="font-weight: bold; font-size: 0.8rem; color: #8c8d93; margin-top: -10px">(Doorgekruiste Blocks zijn (al) gekopieerd)</p>

            <p>
                <input type="text" id="block-search" class="regular-text" placeholder="Zoek een block..." <?= $var_disabled?> style="width: 300px;">
            </p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox" <?= $var_disabled?> onclick="toggleAllCheckboxes(this)">
                    </td>
                    <th class="manage-column">Block Naam</th>
                </tr>
                </thead>
            </table>
            <div style="max-height: 300px; overflow: auto; border: 1px solid #ccd0d4">
                <table class="wp-list-table widefat fixed striped">
                    <tbody id="the-list">
                    <?php if (empty($available_blocks)) : ?>
                        <td colspan="2" style="color: #979797; font-weight: bold; font-style: italic">Er zijn momenteel geen blocks in de parent theme</td>
                    <?php else : ?>
                        <?php foreach ($available_blocks as $block) :
                            $exists_in_child = is_dir($child_blocks_path . $block);
                            $disabled = $exists_in_child ? 'disabled' : '';
                            $table_row_style = $exists_in_child ? 'style="color: #979797; text-decoration: line-through;"' : '';
                            ?>
                            <tr <?= $table_row_style; ?>>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="selected_blocks[]" value="<?= ($block); ?>" <?= $disabled; ?>>
                                </th>
                                <td class="column-primary"><?= ($block); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr id="no-results" style="display: none;">
                        <td colspan="2" style="color: #979797; font-weight: bold; font-style: italic">Geen blocks gevonden, probeer een ander naam!</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="button button-primary" style="margin-top: 15px">Kopieer geselecteerde blokken</button>
            <script>
                document.getElementById('block-search').addEventListener('input', function () {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#the-list tr');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        if (row.id === 'no-results') return;

                        const text = row.textContent.toLowerCase();
                        const match = text.includes(searchTerm);
                        row.style.display = match ? '' : 'none';
                        if (match) visibleCount++;
                    });

                    const noResultsRow = document.getElementById('no-results');
                    noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
                });
                function toggleAllCheckboxes(master) {
                    const checkboxes = document.querySelectorAll(
                        '#the-list input[type="checkbox"]:not(:disabled)'
                    );
                    checkboxes.forEach(cb => cb.checked = master.checked);
                }
            </script>
        </form>
    </div>

    <?php
}

function blink_recursive_copy($src, $dest) {
    $dir = opendir($src);
    @mkdir($dest, 0755, true);
    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') continue;

        $src_block_path = $src . '/' . $file;
        $dest_child_path = $dest . '/' . $file;

        if (is_dir($src_block_path)) {
            blink_recursive_copy($src_block_path, $dest_child_path);
        } else {
            copy($src_block_path, $dest_child_path);
        }
    }
    closedir($dir);
}