<?php
/**
 * FFL Importer Class
 *
 * Handles bulk import of FFL listings from ATF CSV/Excel files
 *
 * @package    Gun_Shop_Directory
 * @subpackage Gun_Shop_Directory/includes
 */

class GSD_Importer {

    /**
     * Import FFLs from CSV file
     *
     * @param string $file_path Path to CSV file
     * @param array $column_mapping Mapping of CSV columns to post fields
     * @param int $batch_size Number of records to process per batch
     * @param string $post_status Post status for imported listings (publish or pending)
     * @return array Results of import
     */
    public function import_from_csv($file_path, $column_mapping = array(), $batch_size = 50, $post_status = 'publish') {
        if (!file_exists($file_path)) {
            return array(
                'success' => false,
                'message' => __('File not found.', 'gun-shop-directory')
            );
        }

        // Default column mapping for standard ATF FFL format
        $default_mapping = array(
            'license_number' => 'License Number',
            'business_name' => 'Business Name',
            'street' => 'Premise Street',
            'city' => 'Premise City',
            'state' => 'Premise State',
            'zip' => 'Premise Zip Code',
            'license_type' => 'License Type',
            'expiration_date' => 'Expiration Date',
            'phone' => 'Business Phone',
            'mail_street' => 'Mail Street',
            'mail_city' => 'Mail City',
            'mail_state' => 'Mail State',
            'mail_zip' => 'Mail Zip Code',
        );

        $mapping = !empty($column_mapping) ? $column_mapping : $default_mapping;

        $results = array(
            'success' => true,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'messages' => array(),
        );

        // Open CSV file
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return array(
                'success' => false,
                'message' => __('Unable to open file.', 'gun-shop-directory')
            );
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (empty($headers)) {
            fclose($handle);
            return array(
                'success' => false,
                'message' => __('Invalid CSV file format.', 'gun-shop-directory')
            );
        }

        // Normalize headers (trim whitespace, handle BOM)
        $headers = array_map(function($header) {
            // Remove BOM if present
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            return trim($header);
        }, $headers);

        // Build column index mapping
        $column_indices = array();
        foreach ($mapping as $field => $header_name) {
            $index = array_search($header_name, $headers);
            if ($index !== false) {
                $column_indices[$field] = $index;
            }
        }

        // Process rows
        $row_number = 1;
        $batch_count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            $batch_count++;

            // Extract data based on column mapping
            $data = array();
            foreach ($column_indices as $field => $index) {
                $data[$field] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            // Skip if no business name
            if (empty($data['business_name'])) {
                $results['skipped']++;
                continue;
            }

            // Import the listing
            $import_result = $this->import_single_listing($data, $post_status);

            if ($import_result === 'imported') {
                $results['imported']++;
            } elseif ($import_result === 'updated') {
                $results['updated']++;
            } elseif ($import_result === 'skipped') {
                $results['skipped']++;
            } else {
                $results['errors']++;
                $results['messages'][] = sprintf(
                    __('Error on row %d: %s', 'gun-shop-directory'),
                    $row_number,
                    $import_result
                );
            }

            // Prevent timeout on large imports
            if ($batch_count >= $batch_size) {
                $batch_count = 0;
                // Allow WordPress to breathe
                usleep(100000); // 100ms
            }
        }

        fclose($handle);

        return $results;
    }

    /**
     * Import a single listing
     *
     * @param array $data Listing data
     * @param string $post_status Post status for imported listings
     * @return string Result status: 'imported', 'updated', 'skipped', or error message
     */
    private function import_single_listing($data, $post_status = 'publish') {
        // Check if listing already exists by FFL number
        $existing_id = $this->find_existing_listing($data['license_number']);

        // Determine business type from license type
        $business_type = $this->get_business_type_from_license($data['license_type']);

        // Skip if business type is null (e.g., Type 03 collectors)
        if ($business_type === null) {
            return 'skipped';
        }

        // Prepare post data
        $post_data = array(
            'post_title' => sanitize_text_field($data['business_name']),
            'post_type' => 'gsd_listing',
            'post_status' => $post_status,
            'post_content' => sprintf(
                __('Federal Firearms License holder. License Type: %s. License Number: %s.', 'gun-shop-directory'),
                $data['license_type'],
                $data['license_number']
            ),
        );

        if ($existing_id) {
            // Update existing listing
            $post_data['ID'] = $existing_id;
            $listing_id = wp_update_post($post_data);
            $status = 'updated';
        } else {
            // Create new listing
            $listing_id = wp_insert_post($post_data);
            $status = 'imported';
        }

        if (is_wp_error($listing_id)) {
            return $listing_id->get_error_message();
        }

        // Save meta data
        update_post_meta($listing_id, '_gsd_ffl_number', sanitize_text_field($data['license_number']));
        update_post_meta($listing_id, '_gsd_license_type', sanitize_text_field($data['license_type']));
        update_post_meta($listing_id, '_gsd_business_type', $business_type);
        update_post_meta($listing_id, '_gsd_address', sanitize_text_field($data['street']));
        update_post_meta($listing_id, '_gsd_city', sanitize_text_field($data['city']));
        update_post_meta($listing_id, '_gsd_state', sanitize_text_field($data['state']));
        update_post_meta($listing_id, '_gsd_zip', sanitize_text_field($data['zip']));
        update_post_meta($listing_id, '_gsd_country', 'United States');

        if (!empty($data['phone'])) {
            update_post_meta($listing_id, '_gsd_phone', sanitize_text_field($data['phone']));
        }

        if (!empty($data['expiration_date'])) {
            update_post_meta($listing_id, '_gsd_license_expiration', sanitize_text_field($data['expiration_date']));
        }

        // Save mailing address if different
        if (!empty($data['mail_street'])) {
            update_post_meta($listing_id, '_gsd_mail_address', sanitize_text_field($data['mail_street']));
            update_post_meta($listing_id, '_gsd_mail_city', sanitize_text_field($data['mail_city']));
            update_post_meta($listing_id, '_gsd_mail_state', sanitize_text_field($data['mail_state']));
            update_post_meta($listing_id, '_gsd_mail_zip', sanitize_text_field($data['mail_zip']));
        }

        // Mark as imported from ATF
        update_post_meta($listing_id, '_gsd_imported_from_atf', '1');
        update_post_meta($listing_id, '_gsd_import_date', current_time('mysql'));

        return $status;
    }

    /**
     * Find existing listing by FFL number
     *
     * @param string $ffl_number FFL license number
     * @return int|null Post ID if found, null otherwise
     */
    private function find_existing_listing($ffl_number) {
        if (empty($ffl_number)) {
            return null;
        }

        $args = array(
            'post_type' => 'gsd_listing',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_gsd_ffl_number',
                    'value' => $ffl_number,
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query($args);
        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Fix business types for already-imported FFL listings
     *
     * @return array Results of the fix operation
     */
    public function fix_imported_business_types() {
        $results = array(
            'success' => true,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'errors' => 0,
        );

        // Find all listings imported from ATF (including older imports)
        // Look for listings that have FFL license numbers
        $args = array(
            'post_type' => 'gsd_listing',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_gsd_ffl_number',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            $results['message'] = __('No imported listings found.', 'gun-shop-directory');
            return $results;
        }

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Get the license type
            $license_type = get_post_meta($post_id, '_gsd_license_type', true);

            if (empty($license_type)) {
                $results['skipped']++;
                continue;
            }

            // Determine correct business type
            $new_business_type = $this->get_business_type_from_license($license_type);

            // If type 03 collector, delete the listing
            if ($new_business_type === null) {
                wp_delete_post($post_id, true);
                $results['deleted']++;
            } else {
                // Update the business type
                $old_business_type = get_post_meta($post_id, '_gsd_business_type', true);

                if ($old_business_type !== $new_business_type) {
                    update_post_meta($post_id, '_gsd_business_type', $new_business_type);
                    $results['updated']++;
                } else {
                    $results['skipped']++;
                }
            }
        }

        wp_reset_postdata();

        return $results;
    }

    /**
     * Determine business type from FFL license type
     *
     * @param string $license_type ATF license type
     * @return string|null Business type, or null to skip import
     */
    private function get_business_type_from_license($license_type) {
        // Extract just the type number (e.g., "01" from "01 - Dealer")
        $type_num = substr(trim($license_type), 0, 2);

        switch ($type_num) {
            case '01': // Dealer in firearms
            case '02': // Pawnbroker
            case '09': // Dealer in destructive devices
                // Retail dealers - typically brick & mortar stores
                return 'brick_mortar';

            case '03': // Collector of curios & relics
                // Not a business, skip import
                return null;

            case '06': // Ammunition manufacturer
            case '07': // Manufacturer of firearms
            case '08': // Importer of firearms
            case '10': // Manufacturer of destructive devices
            case '11': // Importer of destructive devices
                // Manufacturers/importers - may have retail operations
                return 'brick_mortar';

            default:
                // Unknown type, default to brick & mortar
                return 'brick_mortar';
        }
    }

    /**
     * Get preview of CSV file
     *
     * @param string $file_path Path to CSV file
     * @param int $rows Number of rows to preview
     * @return array|false Array of rows or false on error
     */
    public function preview_csv($file_path, $rows = 5) {
        if (!file_exists($file_path)) {
            return false;
        }

        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return false;
        }

        $preview = array();
        $count = 0;

        while (($row = fgetcsv($handle)) !== false && $count < $rows) {
            $preview[] = $row;
            $count++;
        }

        fclose($handle);
        return $preview;
    }

    /**
     * Convert XLSX to CSV
     *
     * Requires PhpSpreadsheet or similar library
     * For now, user should manually convert in Excel/Google Sheets
     *
     * @param string $xlsx_path Path to XLSX file
     * @return string|false Path to converted CSV or false on error
     */
    public function convert_xlsx_to_csv($xlsx_path) {
        // TODO: Implement XLSX conversion if needed
        // For now, recommend manual conversion
        return false;
    }
}
