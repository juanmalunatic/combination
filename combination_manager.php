<?php

define('COMBINATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COMBINATION_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once COMBINATION_PLUGIN_DIR . 'combination_item.php';

if (!class_exists('Combination_Manager')) :

    class Combination_Manager
    {
        public function __construct()
        {
        }

        public function _log_ci($msg = "")
        {
            $msg = (is_array($msg) || is_object($msg)) ? print_r($msg, 1) : $msg;
            error_log(date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/ci.log");
        }

        public function import_combinations($csv_file = []) {

        }

        public function update_combination_ids($csv_file = [])
        {

            wp_cache_flush();

            $handle = fopen($csv_file, 'r');
            $i = 0;

            $failed_cids = [];
            while (($data = fgetcsv($handle)) !== FALSE) {

                $i++;

                // Skip header row
                if ($i == 1) {
                    continue;
                }

                // Skip empty rows
                if (!$data) {
                    continue;
                }

                $combination_item = new Combination_Item($data);

                $combination_search = get_posts([
                    'post_status' => 'publish',
                    'post_type' => 'combination',
                    'tax_query' => [
                        'relation' => 'AND',
                        [
                            'taxonomy' => 'combination_category',
                            'field' => 'term_id',
                            'terms' => intval($combination_item->collection_term_id),
                        ],
                        [
                            'taxonomy' => 'combination_category',
                            'field' => 'term_id',
                            'terms' => intval($combination_item->model_term_id),
                        ],
                        [
                            'taxonomy' => 'combination_category',
                            'field' => 'term_id',
                            'terms' => intval($combination_item->variant_term_id),
                        ],
                        [
                            'taxonomy' => 'combination_category',
                            'field' => 'term_id',
                            'terms' => intval($combination_item->finish_term_id),
                        ],
                        [
                            'taxonomy' => 'combination_category',
                            'field' => 'term_id',
                            'terms' => intval($combination_item->color_term_id),
                        ],
                    ],
                ]);

                // Result logging section
                $this->_log_ci("==================================");
                $this->_log_ci("ITEM: \n\r");
                $this->_log_ci($combination_item);
                $this->_log_ci("\n\r");

                $combination_id = $combination_item->combination_id;

                // If nothing is found, we skip the row
                $this->_log_ci("OUTCOME: \n\r");
                $posts_found = count($combination_search);
                if ($posts_found !== 0) {
                    // If there are exact duplicates in the CSV they will be assigned the same ID ($posts_found > 1)
                    // If two posts have the same exact content, one will be left behind.
                    $post = $combination_search[0];
                    update_post_meta($post->ID, '_combination_id', $combination_id);
                    $this->_log_ci("Successfully added _combination_id {$combination_id} to post {$post->ID}.");
                } else {
                    $this->_log_ci("Failed to find a post with these values. CID: {$combination_id}");
                    $failed_cids[] = $combination_id;
                }
            } // end while looping through rows

            $this->_log_ci("===========/ PROCESS END ===========");

            if (!empty($failed_cids)) {
                $this->_log_ci("The following CIDs weren't found in the system: ");
                $this->_log_ci($failed_cids);
            }
        }

        public function submenu_add_pages_cm()
        {
            // Update combination IDs
            add_submenu_page(
                'edit.php?post_type=combination',
                'Import Combinations',
                'Import Combinations',
                'administrator',
                'import_combinations',
                [$this, 'page_import_combinations']
            );

            // Update combination IDs
            add_submenu_page(
                'edit.php?post_type=combination',
                'Update Combination IDs',
                'Update CIDs',
                'administrator',
                'import_combination_ids',
                [$this, 'page_import_combination_ids']
            );

            // Update any existing value / category
            add_submenu_page(
                'edit.php?post_type=combination',
                'Modify Terms',
                'Modify Terms',
                'administrator',
                'update_terms',
                [$this, 'page_update_terms']
            );

        }

        function page_import_combinations()
        {

            $is_imported = false;
            $is_import_error = false;

            if (isset($_POST['import_combinations']) && $_POST['import_combinations'] != '') {

                if ($_FILES['import_combinations_file'] && $_FILES['import_combinations_file']['error'] == 0) {

                    $collection_import_file = $_FILES['import_combinations_file']['tmp_name'];

                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    ini_set('post_max_size', '200M');
                    ini_set('upload_max_filesize', '200M');
                    ini_set('max_allowed_packet', '200M');
                    defined('WP_MEMORY_LIMIT') || define('WP_MEMORY_LIMIT', '2048M');

                    $cm = new Combination_Manager();
                    $cm->import_combinations($collection_import_file);
                    $is_imported = true;
                } else {
                    $is_import_error = false;
                }
            }
            ?>
            <div class="wrap">

                <?php if ($is_imported) { ?>
                    <div class="updated settings-error notice is-dismissible"
                         style="margin: 0 0 20px; max-width: 845px;">
                        <p><strong>CSV Imported successfully.</strong></p>
                        <button class="notice-dismiss" type="button">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                <?php } ?>

                <?php if ($is_import_error) { ?>
                    <div class="updated settings-error notice is-dismissible"
                         style="margin: 0 0 20px; max-width: 845px;">
                        <p><strong>CSV Imported not imported please check csv file and format.</strong></p>
                        <button class="notice-dismiss" type="button">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                <?php } ?>

                <h1>Import Combinations</h1>

                <form method="post" enctype="multipart/form-data">
                    <h1></h1>
                    <input type="hidden" name="import_combinations" value="1"/>
                    <table class="form-table">

                        <tr>
                            <th scope="row"><label for="import_combinations_file">Upload CSV</label></th>
                            <td><input type="file" name="import_combinations_file" id="import_combinations_file"
                                       accept=".csv" required/></td>
                        </tr>

                    </table>

                    <p class="submit"><input type="submit" name="import_combinations" id="submit"
                                             class="button button-primary" value="Save Changes"></p>
                </form>
            </div>
            <?php
        }

        function page_update_terms()
        {
            ?>
            Hello :)
            <?php
        }

        function page_import_combination_ids()
        {

            $is_imported = false;
            $is_import_error = false;

            if (isset($_POST['import_combinations']) && $_POST['import_combinations'] != '') {

                if ($_FILES['import_combinations_file'] && $_FILES['import_combinations_file']['error'] == 0) {

                    $collection_import_file = $_FILES['import_combinations_file']['tmp_name'];

                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    ini_set('post_max_size', '200M');
                    ini_set('upload_max_filesize', '200M');
                    ini_set('max_allowed_packet', '200M');
                    defined('WP_MEMORY_LIMIT') || define('WP_MEMORY_LIMIT', '2048M');

                    $cm = new Combination_Manager();
                    $cm->update_combination_ids($collection_import_file);
                    $is_imported = true;
                } else {
                    $is_import_error = false;
                }
            }
            ?>
            <div class="wrap">

                <?php if ($is_imported) { ?>
                    <div class="updated settings-error notice is-dismissible"
                         style="margin: 0 0 20px; max-width: 845px;">
                        <p><strong>CSV Imported successfully.</strong></p>
                        <button class="notice-dismiss" type="button">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                <?php } ?>

                <?php if ($is_import_error) { ?>
                    <div class="updated settings-error notice is-dismissible"
                         style="margin: 0 0 20px; max-width: 845px;">
                        <p><strong>CSV Imported not imported please check csv file and format.</strong></p>
                        <button class="notice-dismiss" type="button">
                            <span class="screen-reader-text">Dismiss this notice.</span>
                        </button>
                    </div>
                <?php } ?>

                <h1>Combination IDs Import</h1>

                <form method="post" enctype="multipart/form-data">
                    <h1></h1>
                    <input type="hidden" name="import_combinations" value="1"/>
                    <table class="form-table">

                        <tr>
                            <th scope="row"><label for="import_combinations_file">Upload CSV</label></th>
                            <td><input type="file" name="import_combinations_file" id="import_combinations_file"
                                       accept=".csv" required/></td>
                        </tr>

                    </table>

                    <p class="submit"><input type="submit" name="import_combinations" id="submit"
                                             class="button button-primary" value="Save Changes"></p>
                </form>
            </div>
            <?php
        }

    }
endif;