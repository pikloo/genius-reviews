<?php
if (!defined('ABSPATH')) exit;

class Genius_Reviews_Ajax
{

    //--------IMPORT CSV-------//
    public static function ajax_upload_csv()
    {
        error_log('file :' . print_r($_FILES, true));

        check_ajax_referer('gr_import_nonce', 'nonce');
        if (! current_user_can('manage_woocommerce')) wp_send_json_error('perm');
        if (empty($_FILES['csv']['tmp_name'])) wp_send_json_error(['msg' => 'nofile']);

        $uploaded = wp_handle_upload($_FILES['csv'], ['test_form' => false]);
        if (isset($uploaded['error'])) wp_send_json_error(['msg' => $uploaded['error']]);

        $state = [
            'file'        => $uploaded['file'],
            'offset'      => 0,
            'delimiter'   => null,
            'header'      => null,
            'created'     => 0,
            'updated'     => 0,
            'skipped'     => 0,
            'rows'        => 0,
            'per_product' => [],
        ];
        set_transient(self::state_key(), $state, HOUR_IN_SECONDS);

        wp_send_json_success(['total' => self::count_lines($uploaded['file'])]);
    }

    public static function ajax_process_chunk()
    {
        check_ajax_referer('gr_import_nonce', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('perm');
        }

        $state = get_transient(self::state_key());
        if (!$state || empty($state['file'])) {
            wp_send_json_error(['msg' => 'state-missing']);
        }

        $chunk = isset($_POST['chunk']) ? max(10, (int)$_POST['chunk']) : 150;
        $fh = fopen($state['file'], 'r');
        if (!$fh) wp_send_json_error(['msg' => 'open-failed']);

        // Initialisation du CSV si première passe
        if (empty($state['delimiter'])) {
            $probe = fgets($fh);
            $state['delimiter'] = (substr_count($probe, ';') > substr_count($probe, ',')) ? ';' : ',';
            rewind($fh);
            $state['header'] = array_map('trim', fgetcsv($fh, 0, $state['delimiter']));
            $state['offset'] = ftell($fh);
            $state['rows']   = 0;
            $state['created'] = $state['updated'] = $state['skipped'] = 0;
            $state['per_product'] = [];
        } else {
            fseek($fh, $state['offset']);
        }

        $required = [
            'title',
            'body',
            'rating',
            'review_date',
            'source',
            'curated',
            'reviewer_name',
            'reviewer_email',
            'product_id',
            'product_handle',
            'reply',
            'reply_date',
            'picture_urls',
            'ip_address',
            'location'
        ];
        $idx = array_flip($state['header']);

        $processed = 0;
        while ($processed < $chunk && ($row = fgetcsv($fh, 0, $state['delimiter'])) !== false) {
            $state['rows']++;
            $processed++;
            $state['offset'] = ftell($fh);

            if (count($row) < count($state['header'])) {
                $state['skipped']++;
                continue;
            }

            $data = [];
            foreach ($required as $col) {
                $data[$col] = isset($idx[$col]) ? trim((string)$row[$idx[$col]]) : '';
            }

            $post_title = !empty($data['title'])
                ? wp_strip_all_tags($data['title'])
                : mb_substr(wp_strip_all_tags($data['body']), 0, 50);

            $uid = self::generate_uid($data);

            $existing = get_posts([
                'post_type'      => 'genius_review',
                'meta_key'       => '_gr_uid',
                'meta_value'     => $uid,
                'fields'         => 'ids',
                'posts_per_page' => 1,
                'no_found_rows'  => true
            ]);

            $postarr = [
                'post_type'   => 'genius_review',
                'post_status' => 'publish',
                'post_title'  => $post_title,
                'post_content' => wp_kses_post($data['body']),
                'post_parent' => (int)$data['product_id'],
            ];

            if ($existing) {
                $postarr['ID'] = (int)$existing[0];
                wp_update_post($postarr);
                $rid = (int)$existing[0];
                $state['updated']++;
            } else {
                $rid = wp_insert_post($postarr);
                add_post_meta($rid, '_gr_uid', $uid, true);
                $state['created']++;
            }

            update_post_meta($rid, '_gr_rating', (float)$data['rating']);
            update_post_meta($rid, '_gr_review_date', sanitize_text_field($data['review_date']));
            update_post_meta($rid, '_gr_source', sanitize_text_field($data['source']));
            update_post_meta($rid, '_gr_curated', ($data['curated'] == '1' || strtolower($data['curated']) === 'true'));
            update_post_meta($rid, '_gr_reviewer_name', sanitize_text_field($data['reviewer_name']));
            update_post_meta($rid, '_gr_reviewer_hash', hash('sha256', strtolower($data['reviewer_email'])));
            update_post_meta($rid, '_gr_product_id', (int)$data['product_id']);
            update_post_meta($rid, '_gr_product_handle', sanitize_text_field($data['product_handle']));
            update_post_meta($rid, '_gr_reply', wp_kses_post($data['reply']));
            update_post_meta($rid, '_gr_reply_date', sanitize_text_field($data['reply_date']));
            update_post_meta($rid, '_gr_ip', sanitize_text_field($data['ip_address']));
            update_post_meta($rid, '_gr_location', sanitize_text_field($data['location']));

            // Image
            if (!empty($data['picture_urls'])) {
                $urls = preg_split('/[\s,]+/', $data['picture_urls']);
                $url = $urls ? trim($urls[0]) : '';
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $tmp = download_url($url, 15);
                    if (!is_wp_error($tmp)) {
                        $file_array = ['name' => wp_basename(parse_url($url, PHP_URL_PATH)), 'tmp_name' => $tmp];
                        $att_id = media_handle_sideload($file_array, $rid);
                        if (!is_wp_error($att_id) && !has_post_thumbnail($rid)) {
                            set_post_thumbnail($rid, $att_id);
                        }
                    }
                }
            }

            $pid = (int)$data['product_id'];
            if (!isset($state['per_product'][$pid])) {
                $state['per_product'][$pid] = [
                    'name' => $data['product_handle'],
                    'added' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'avg' => 0,
                    'count' => 0
                ];
            }
            if ($existing) $state['per_product'][$pid]['updated']++;
            else $state['per_product'][$pid]['added']++;
        }

        fclose($fh);

        $total    = max(0, self::count_lines($state['file']) - 1);
        $percent  = $total ? min(100, round(($state['rows'] / $total) * 100)) : 100;
        $complete = ($state['rows'] >= $total);

        if ($complete) {
            $percent = 100;
            foreach (array_keys($state['per_product']) as $pid) {
                self::recalc_product($pid);
                $state['per_product'][$pid]['avg']   = (float)get_post_meta($pid, '_gr_avg_rating', true);
                $state['per_product'][$pid]['count'] = (int)get_post_meta($pid, '_gr_review_count', true);
            }
            set_transient(self::state_key(), $state, MINUTE_IN_SECONDS);
        } else {
            set_transient(self::state_key(), $state, HOUR_IN_SECONDS);
        }

        $progress_step = $total > 0 ? round(100 / $total, 2) : 0;

        wp_send_json_success([
            'percent'       => $percent,
            'progress_step' => $progress_step,
            'complete'      => $complete,
            'created'       => (int)$state['created'],
            'updated'       => (int)$state['updated'],
            'skipped'       => (int)$state['skipped'],
            'perProduct'    => $state['per_product'],
        ]);
    }


    public static function recalc_product($product_id)
    {
        $q = new WP_Query([
            'post_type' => 'genius_review',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_key' => '_gr_product_id',
            'meta_value' => (int)$product_id
        ]);
        $total = 0;
        $count = 0;
        foreach ($q->posts as $rid) {
            $r = (float)get_post_meta($rid, '_gr_rating', true);
            if ($r > 0) {
                $total += $r;
                $count++;
            }
        }
        $avg = $count ? round($total / $count, 2) : 0;
        update_post_meta($product_id, '_gr_avg_rating', $avg);
        update_post_meta($product_id, '_gr_review_count', $count);
    }

    private static function generate_uid($data)
    {
        $clean = function ($v) {
            // minuscule + trim + espaces normalisés
            return trim(preg_replace('/\s+/', ' ', strtolower((string)$v)));
        };

        $date = !empty($data['review_date']) ? strtotime($data['review_date']) : '';

        return md5(
            $clean($data['reviewer_email']) . '|' .
                (int)$data['product_id'] . '|' .
                $date . '|' .
                mb_substr($clean($data['body']), 0, 50)
        );
    }

    private static function state_key()
    {
        return 'gr_state_' . get_current_user_id();
    }

    private static function count_lines($file)
    {
        $c = 0;
        $f = fopen($file, 'r');
        if (!$f) return 0;
        while (($line = fgets($f)) !== false) {
            if (trim($line) !== '') {
                $c++;
            }
        }
        fclose($f);

        // On enlève l'entête
        return max(0, $c - 1);
    }


    //--------REVIEWS------//
    public static function gr_load_reviews()
    {
        check_ajax_referer('gr_public_nonce', 'nonce');

        $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        $limit      = isset($_POST['limit']) ? (int) $_POST['limit'] : 6;
        $page       = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $offset     = ($page - 1) * $limit;
        $sort       = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date_desc';

        $sort_args = Genius_Reviews_Query_Helper::map_sort($sort);

        $args = [
            'post_type'      => 'genius_review',
            'posts_per_page' => $limit,
            'offset'         => $offset,
        ];

        $args = array_merge($args, $sort_args);

        if ($product_id) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = [];
            }
            $args['meta_query'][] = [
                'key'   => '_gr_product_id',
                'value' => $product_id,
            ];
        }

        $q = new WP_Query($args);

        $html = '';
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                $html .= Genius_Reviews_Render::review_card(get_the_ID(), 'grid');
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'html'     => $html,
            'count'    => $q->post_count,
            'offset'   => $offset + $q->post_count,
            'has_more' => ($offset + $q->post_count) < (int) $q->found_posts,
        ]);
    }
}
