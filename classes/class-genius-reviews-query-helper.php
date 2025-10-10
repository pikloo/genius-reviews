<?php
if (!defined('ABSPATH'))
    exit;

class Genius_Reviews_Query_Helper
{
    /**
     * Retourne les paramètres de tri pour WP_Query
     *
     * @param string $sort (date_desc, date_asc, rating_desc, rating_asc)
     * @return array
     */
    public static function map_sort($sort = 'date_desc')
    {
        switch ($sort) {
            case 'date_asc':
                return [
                    'meta_key' => '_gr_review_date',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                ];

            case 'rating_desc':
                return [
                    'meta_query' => [
                        'relation' => 'AND',
                        'rating_clause' => [
                            'key' => '_gr_rating',
                            'type' => 'NUMERIC',
                        ],
                        'date_clause' => [
                            'key' => '_gr_review_date',
                            'type' => 'DATE',
                        ],
                    ],
                    'orderby' => [
                        'rating_clause' => 'DESC',
                        'date_clause' => 'DESC',
                    ],
                ];

            case 'rating_asc':
                return [
                    'meta_query' => [
                        'relation' => 'AND',
                        'rating_clause' => [
                            'key' => '_gr_rating',
                            'type' => 'NUMERIC',
                        ],
                        'date_clause' => [
                            'key' => '_gr_review_date',
                            'type' => 'DATE',
                        ],
                    ],
                    'orderby' => [
                        'rating_clause' => 'ASC',
                        'date_clause' => 'DESC',
                    ],
                ];

            default:
                return [
                    'meta_key' => '_gr_review_date',
                    'orderby' => 'meta_value',
                    'order' => 'DESC',
                ];
        }
    }

    /**
     * Calcule les statistiques globales (moyenne et nombre d'avis)
     *
     * @return array ['avg' => float, 'count' => int]
     */
    public static function get_global_stats()
    {
        global $wpdb;

        $ratings = $wpdb->get_col("
        SELECT r.meta_value
        FROM {$wpdb->postmeta} AS r
        INNER JOIN {$wpdb->posts} AS p ON p.ID = r.post_id
        INNER JOIN {$wpdb->postmeta} AS c ON c.post_id = p.ID
        WHERE r.meta_key = '_gr_rating'
          AND c.meta_key = '_gr_curated'
          AND c.meta_value = 'ok'
          AND p.post_type = 'genius_review'
          AND p.post_status = 'publish'
    ");

        $ratings = array_map('intval', $ratings);
        $count = count($ratings);
        $avg = $count ? round(array_sum($ratings) / $count, 2) : 0;

        // Répartition par note
        $counts_by_rating = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($ratings as $r) {
            if ($r >= 1 && $r <= 5)
                $counts_by_rating[$r]++;
        }

        return [
                'avg' => $avg,
                'count' => $count,
                'counts_by_rating' => $counts_by_rating
            ];
    }


}
