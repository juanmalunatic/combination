<?php

define('COMBINATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COMBINATION_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once COMBINATION_PLUGIN_DIR . 'combination_item.php';

// Meta key: _combination_id

if (!class_exists('Combination_Manager')) :

    class Combination_Manager
    {
        public function __construct()
        {
        }

        public function register_hooks()
        {
            // Export CSV functionality: WordPress hasn't sent headers yet on admin_init.
            add_action('admin_init', [$this, 'export_combinations_all']);
        }

        public function import_combinations($csv_file = [])
        {
            wp_cache_flush();

            $handle = fopen($csv_file, 'r');
            $i = 0;

            while (($data = fgetcsv($handle)) !== FALSE) {

                $i++;

                if ($i == 1) continue; // Skip header row

                if (!$data) continue;  // Skip empty rows

                // Create the item based on the CSV
                $combination_item = new Combination_Item($data);

                // Look for the post associated with said item
                $posts = get_posts([
                    'post_status' => 'publish',
                    'post_type' => 'combination',
                    'meta_key' => '_combination_id',
                    'meta_value' => $combination_item->combination_id,
                ]);

                $posts_count = count($posts);

                if ($posts_count === 0) {
                    $this->upsert_combination("insert", $combination_item);
                } else {
                    $this->upsert_combination("update", $combination_item, $posts[0]);
                }
            }
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

        public function add_metabox_edit_post()
        {
            // add_meta_box(
            //     'combination-post-metabox',
            //     'Combination Information',
            //     [$this, 'add_metabox_edit_post_content'],
            //     ['post', 'combination'],
            //     'advanced',
            //     'default',
            //     null
            // );
        }

        public function add_metabox_edit_post_content()
        {
            $post_id = get_the_ID();
            echo "Hello {$post_id} :)";
        }

        public function submenu_add_pages_cm()
        {
            // Import Combinations
            add_submenu_page(
                'edit.php?post_type=combination',
                'Import Combinations',
                'Import Combinations',
                'administrator',
                'import_combinations',
                [$this, 'page_import_combinations']
            );

            // Export Combinations
            add_submenu_page(
                'edit.php?post_type=combination',
                'Export Combinations',
                'Export Combinations',
                'administrator',
                'export_combinations',
                [$this, 'page_export_combinations']
            );

            // Update combination IDs
            add_submenu_page(
                'edit.php?post_type=combination',
                'Update Combination IDs',
                'Update CIDs',
                'administrator',
                'import_combination_ids',
                [$this, 'page_update_combination_ids']
            );

            // TODO Submenu: Update existing values / categories
            // add_submenu_page(
            //     'edit.php?post_type=combination',
            //     'Modify Terms',
            //     'Modify Terms',
            //     'administrator',
            //     'update_terms',
            //     [$this, 'page_update_terms']
            // );

        }

        public function page_import_combinations()
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

        public function page_update_combination_ids()
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

                <h1>Update Combination IDs</h1>

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

        public function page_export_combinations()
        {
            ?>
            <div class="wrap">

                <h1>Export Combinations</h1>

                <form method="POST" enctype="multipart/form-data" action="">

                    <input type="hidden" name="action" value="export_combinations_all"/>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_combinations_file">Generate CSV</label>
                            </th>
                            <td>
                                <input type="submit"
                                       id="submit"
                                       class="button button-primary"
                                       value="Export All">
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            <?php
        }

        // TODO Page: Update existing values / categories
        public function page_update_terms()
        {
            ?>
            Hello :)
            <?php
        }

        public function export_combinations_all()
        {
            global $pagenow; // edit.php
            $post_type = $_GET['post_type'];
            $page      = $_GET['page'];

            // This hook runs on every admin page so we want to exit ASAP.
            if ($pagenow != "edit.php" || $post_type != "combination" || $page != "export_combinations") {
                return;
            }

            // Here we check the actual action
            $action = $_POST['action'];
            if ($action != "export_combinations_all") {
                return;
            }

            // Temporarily increase PHP's processing capacity
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            ini_set('post_max_size', '200M');
            ini_set('upload_max_filesize', '200M');
            ini_set('max_allowed_packet', '200M');
            defined('WP_MEMORY_LIMIT') || define('WP_MEMORY_LIMIT', '2048M');

            // Query the data that will be exported

            // Stream the data as a CSV to the browser
            // Based off: https://code.iamkate.com/php/creating-downloadable-csv-files/
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=data.csv');
            $output = fopen('php://output', 'w');

            fputcsv($output, array('Column 1', 'Column 2', 'Column 3'));
            $sample_rows = [
                ["Hello", "this", "is"],
                ["Hi", "I", "am"],
                ["Bonjour", "Je", "Suis"],
            ];
            foreach ($sample_rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            die(); // Avoid outputting the rest of Wordpress
        }

        public function upsert_combination(
            string $action,
            Combination_Item $combination_item,
            WP_Post $post_existing = null
        )
        {
            // Create the post title
            $post_title = $combination_item->combination_id . ' - ' .
                $combination_item->category . ' - ' .
                $combination_item->collection . ' - ' .
                $combination_item->model . ' - ' .
                $combination_item->variant . ' - ' .
                $combination_item->finish . ' - ' .
                $combination_item->color;

            // Handle post status and post type
            $post_args = [
                'post_title' => $post_title,
                'post_status' => 'publish',
                'post_type' => 'combination',
            ];

            if ($action === 'insert') {
                // If inserting, create a post type with the required arguments
                $combination_post_id = wp_insert_post($post_args);
                if (is_wp_error($combination_post_id)) {
                    $this->_log_create($combination_post_id->get_error_message());
                    return;
                }
            } else {
                // If updating, ensure the arguments are right (aka pass $post_args)
                $combination_post_id = $post_existing->ID;
                $post_args['ID'] = $post_existing->ID;
                $updated_post = wp_update_post($post_args);
                if (is_wp_error($updated_post)) {
                    $this->_log_create($updated_post->get_error_message());
                }
            }

            // Update _combination_id for both cases: updating and inserting
            update_post_meta($combination_post_id, '_combination_id', $combination_item->combination_id);

            // Add images to the post
            $this->post_images_handle($combination_post_id, $combination_item);

            // Check whether the exact term ("White") exists under the taxonomy ("Color")
            // If it doesn't, create it.
            $category_term_id = false;
            $collection_term_id = false;
            $model_term_id = false;
            $variant_term_id = false;
            $finish_term_id = false;
            $color_term_id = false;

            // Cascade structure: $field_term_id may return false if not created; we don't want orphaned terms
            $category_term_id = $this->create_term_if_needed($combination_item->category, null);
            if ($category_term_id) {
                $collection_term_id = $this->create_term_if_needed($combination_item->collection, $category_term_id);
                if ($collection_term_id) {
                    $model_term_id = $this->create_term_if_needed($combination_item->model, $collection_term_id);
                    if ($model_term_id) {
                        $variant_term_id = $this->create_term_if_needed($combination_item->variant, $model_term_id);
                        if ($variant_term_id) {
                            $finish_term_id = $this->create_term_if_needed($combination_item->finish, $variant_term_id);
                            if ($finish_term_id) {
                                $color_term_id = $this->create_term_if_needed($combination_item->color, $finish_term_id);
                            }
                        }
                    }
                }
            }

            // Create the terms that will be appended to the post
            $term_id_array = [
                intval($category_term_id),
                intval($collection_term_id),
                intval($model_term_id),
                intval($variant_term_id),
                intval($finish_term_id),
                intval($color_term_id),
            ];
            // Remove all of the 0/false values (false gets cast to 0 via intval)
            $term_id_array = array_diff($term_id_array, [0]);

            // Set all terms at once and discard previous.
            wp_set_object_terms($combination_post_id, $term_id_array, 'combination_category', false);

            // Log
            if ($action === 'insert') {
                $this->_log_create("Created combination {$combination_item->combination_id} in post {$combination_post_id}.");
            } else if ($action === 'update') {
                $this->_log_create("Updated combination {$combination_item->combination_id} in post {$combination_post_id}.");
            }
        }

        public function create_term_if_needed($field_value, $field_parent_term_id)
        {

            $field_term = term_exists($field_value, 'combination_category', $field_parent_term_id);
            $field_term_id = $field_term['term_id'];

            $can_create_field_term = !$field_term_id && !empty($field_value);

            if ($field_term_id) {

                // If the term already exists, we return its ID
                return $field_term_id;

            } else if (empty($field_value)) {

                // If the incoming value is empty, we return false
                return false;

            } else if ($can_create_field_term) {

                // If the term_id doesn't exist (id 40 for "White"), and there's an incoming value ("White") we create said term
                // Note that the term is tied to the parent:
                //   "Metallic Finish" - "Silver" is different than
                //   "Gloss Finish"    - "Silver" (both are Silver but w/ different parent id)

                $term_args = [];
                if ($field_parent_term_id !== null) {
                    $term_args['parent'] = intval($field_parent_term_id);
                }

                $new_field_term = wp_insert_term($field_value, 'combination_category', $term_args);
                if (is_wp_error($new_field_term)) {
                    $this->_log_general($new_field_term->get_error_message());
                } else {
                    $new_field_term_id = $new_field_term['term_id'];
                    return $new_field_term_id;
                }
            }
        }

        public function post_images_handle($combination_post_id, Combination_Item $combination_item)
        {
            if (trim($combination_item->image_png)) {
                // First Find old image
                $png_image_id = $this->find_image_by_name($combination_item->image_png);
                if ($png_image_id) {
                    if (function_exists('update_field')) {
                        update_field('combination_png_image', $png_image_id, $combination_post_id);
                    }
                } else {
                    $png_image_id = $this->find_image_by_source_url($combination_item->image_png);
                    if ($png_image_id) {
                        if (function_exists('update_field')) {
                            update_field('combination_png_image', $png_image_id, $combination_post_id);
                        }
                    } else {
                        $png_image_id = media_sideload_image($combination_item->image_png, false, NULL, 'id');

                        if (!is_wp_error($png_image_id)) {
                            if (function_exists('update_field')) {
                                update_field('combination_png_image', $png_image_id, $combination_post_id);
                            }
                        } else {
                            $this->_log_general('new combination PNG media_sideload_image error');
                            $this->_log_general($png_image_id->get_error_messages());
                        }
                    }
                }
            } // else {} // else for empty image

            // FOR JPG	
            if (trim($combination_item->image_jpg)) {
                // First Find old image
                $jpg_image_id = $this->find_image_by_name($combination_item->image_jpg);
                if ($jpg_image_id) {
                    if (function_exists('update_field')) {
                        update_field('combination_jpg_image', $jpg_image_id, $combination_post_id);
                    }
                } else {
                    $jpg_image_id = $this->find_image_by_source_url($combination_item->image_jpg);
                    if ($jpg_image_id) {
                        if (function_exists('update_field')) {
                            update_field('combination_jpg_image', $jpg_image_id, $combination_post_id);
                        }
                    } else {
                        $jpg_image_id = media_sideload_image($combination_item->image_jpg, false, NULL, 'id');
                        if (!is_wp_error($jpg_image_id)) {
                            if (function_exists('update_field')) {
                                update_field('combination_jpg_image', $jpg_image_id, $combination_post_id);
                            }
                        } else {
                            $this->_log_general('new combination JPG media_sideload_image error');
                            $this->_log_general($jpg_image_id->get_error_messages());
                        }
                    }
                }
            } // else {} // Else for empty image
        }

        public function find_image_by_name($file = '')
        {
            if (!$file) {
                return false;
            }

            $filename = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $fileext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (!$filename || !$fileext) {
                return false;
            }

            global $wpdb;

            $attachment_ids = $wpdb->get_results("SELECT ID, guid FROM " . $wpdb->posts . " WHERE LOWER(post_title) = '" . $filename . "' AND post_type = 'attachment'");

            if ($attachment_ids) {
                foreach ($attachment_ids as $attachment) {
                    $attachment_ext = strtolower(pathinfo($attachment->guid, PATHINFO_EXTENSION));

                    if ($attachment_ext != $fileext) {
                        continue;
                    }

                    $attachment_path = get_attached_file($attachment->ID);

                    if (file_exists($attachment_path)) {
                        return $attachment->ID;
                    }
                }
            }

            return false;
        }

        public function find_image_by_source_url($source_url = '')
        {
            global $wpdb;

            if (!$source_url) {
                return false;
            }

            $attachment_id = $wpdb->get_var("SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_key = '_source_url' AND meta_value = '" . $source_url . "'");

            if ($attachment_id) {
                $attachment_path = get_attached_file($attachment_id);
                if (file_exists($attachment_path)) {
                    return $attachment_id;
                }
            }

            return false;
        }

        public function _log_ci($msg = "")
        {
            $msg = (is_array($msg) || is_object($msg)) ? print_r($msg, 1) : $msg;
            error_log(date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/ci.log");
        }

        public function _log_create($msg = "")
        {
            $msg = (is_array($msg) || is_object($msg)) ? print_r($msg, 1) : $msg;
            error_log(date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/create.log");
        }

        public function _log_general($msg = "")
        {
            $msg = (is_array($msg) || is_object($msg)) ? print_r($msg, 1) : $msg;
            error_log(date('[Y-m-d H:i:s e] ') . $msg . PHP_EOL, 3, __DIR__ . "/general.log");
        }
    }
endif;