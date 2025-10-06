<?php
if (!defined('ABSPATH')) exit;

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
                    'orderby'  => 'meta_value',
                    'order'    => 'ASC',
                ];

            case 'rating_desc':
                return [
                    'meta_query' => [
                        'relation' => 'AND',
                        'rating_clause' => [
                            'key'   => '_gr_rating',
                            'type'  => 'NUMERIC',
                        ],
                        'date_clause' => [
                            'key'   => '_gr_review_date',
                            'type'  => 'DATE',
                        ],
                    ],
                    'orderby' => [
                        'rating_clause' => 'DESC',
                        'date_clause'   => 'DESC',
                    ],
                ];

            case 'rating_asc':
                return [
                    'meta_query' => [
                        'relation' => 'AND',
                        'rating_clause' => [
                            'key'   => '_gr_rating',
                            'type'  => 'NUMERIC',
                        ],
                        'date_clause' => [
                            'key'   => '_gr_review_date',
                            'type'  => 'DATE',
                        ],
                    ],
                    'orderby' => [
                        'rating_clause' => 'ASC',
                        'date_clause'   => 'DESC',
                    ],
                ];

            default: // date_desc
                return [
                    'meta_key' => '_gr_review_date',
                    'orderby'  => 'meta_value',
                    'order'    => 'ASC',
                ];
        }
    }
}
