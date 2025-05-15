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

    $parentBlocksPath = get_template_directory() . '/parts/blocks/';
    $childBlocksPath  = get_stylesheet_directory() . '/parts/blocks/';
    $availableBlocks  = [];

    if (is_dir($parentBlocksPath)) {
        $dirs = scandir($parentBlocksPath);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($parentBlocksPath . $dir)) {
                $availableBlocks[] = $dir;
            }
        }
    }

    empty($availableBlocks) ? $varDisabled = "disabled" : $varDisabled = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_blocks'])) {
        $selected = array_map('sanitize_text_field', $_POST['selected_blocks']);
        $copied = [];

        foreach ($selected as $block) {
            $src = $parentBlocksPath . $block;
            $dest = $childBlocksPath . $block;

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
                <input type="text" id="block-search" class="regular-text" placeholder="Zoek een block..." <?= $varDisabled?> style="width: 300px;">
            </p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox" <?= $varDisabled?> onclick="toggleAllCheckboxes(this)">
                    </td>
                    <th class="manage-column">Block Naam</th>
                </tr>
                </thead>
            </table>
            <div style="max-height: 300px; overflow: auto; border: 1px solid #ccd0d4">
                <table class="wp-list-table widefat fixed striped">
                    <tbody id="the-list">
                    <?php if (empty($availableBlocks)) : ?>
                        <td colspan="2" style="color: #979797; font-weight: bold; font-style: italic">Er zijn momenteel geen blocks in de parent theme</td>
                    <?php else : ?>
                        <?php foreach ($availableBlocks as $block):
                            $existsInChild = is_dir($childBlocksPath . $block);
                            $disabled = $existsInChild ? 'disabled' : '';
                            $rowClass = $existsInChild ? 'style="color: #999; text-decoration: line-through;"' : '';
                            ?>
                            <tr <?= $rowClass; ?>>
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

        $srcPath = $src . '/' . $file;
        $destPath = $dest . '/' . $file;

        if (is_dir($srcPath)) {
            blink_recursive_copy($srcPath, $destPath);
        } else {
            copy($srcPath, $destPath);
        }
    }
    closedir($dir);
}


