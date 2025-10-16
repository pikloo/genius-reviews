<?php
if (!defined('ABSPATH'))
    exit;

class Genius_Reviews_Render
{

    /**
     * Injecte les couleurs de marque (principale et hover) dans le <head>.
     * Récupère la couleur personnalisée depuis les options
     *
     * @return void
     */
    public static function inject_brand_color()
    {
        $color = get_option('gr_color_brand_custom', '#58AF59');
        if (!$color)
            return;

        // Génère automatiquement la couleur de hover
        $hover = self::adjust_brightness($color, -20);

        echo '<style>
        :root {
            --color-brand-custom: ' . esc_html($color) . ';
            --color-brand-custom-hover: ' . esc_html($hover) . ';
        }
    </style>';
    }

    /**
     * Génère le bloc principal d’avis clients sous forme de grille.
     *
     * Récupère les avis liés à un produit spécifique,
     * calcule les moyennes et compte les notes, puis affiche le template complet.
     *
     * @param array $args {
     *     @type int    $product_id ID du produit (facultatif).
     *     @type int    $limit      Nombre maximum d’avis à afficher.
     *     @type string $sort       Type de tri (date_desc, rating_asc, etc.).
     * }
     * @return string HTML complet de la grille d’avis.
     */
    public static function grid($args = [])
    {
        $defaults = [
            'product_id' => 0,
            'limit' => 6,
            'sort' => 'date_desc',
        ];

        $args = wp_parse_args($args, $defaults);

        if (isset($_GET['gr-sort'])) {
            $args['sort'] = sanitize_text_field($_GET['gr-sort']);
        }

        $sort_args = Genius_Reviews_Query_Helper::map_sort($args['sort']);

        $q_args = [
            'post_type' => 'genius_review',
            'posts_per_page' => $args['limit'],
            'meta_query' => [
                [
                    'key' => '_gr_curated',
                    'value' => 'ok',
                    'compare' => '=',
                ],
            ],
        ];

        if ($args['product_id']) {
            $q_args['meta_query'][] = [
                'key' => '_gr_product_id',
                'value' => $args['product_id'],
            ];
        }

        // Fusionne meta_query éventuelle issue du tri
        if (!empty($sort_args['meta_query'])) {
            $q_args['meta_query'][] = $sort_args['meta_query'];
            unset($sort_args['meta_query']);
        }

        $q_args = array_merge($q_args, $sort_args);

        $q = new WP_Query($q_args);

        if (!$q->have_posts()) {
            return self::render_no_reviews();
        }

        // Stats produit basées uniquement sur curated "ok"
        $avg = (float) get_post_meta($args['product_id'], '_gr_avg_rating', true);
        $count = (int) get_post_meta($args['product_id'], '_gr_review_count', true);

        // Détails par note
        $counts_by_rating = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $meta_query_stats = [
            [
                'key' => '_gr_curated',
                'value' => 'ok',
                'compare' => '=',
            ],
        ];

        if ($args['product_id']) {
            $meta_query_stats[] = [
                'key' => '_gr_product_id',
                'value' => $args['product_id'],
            ];
        }

        $q_stats = new WP_Query([
            'post_type' => 'genius_review',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => $meta_query_stats,
        ]);

        if ($q_stats->have_posts()) {
            foreach ($q_stats->posts as $review_id) {
                $rating = (int) get_post_meta($review_id, '_gr_rating', true);
                if ($rating >= 1 && $rating <= 5) {
                    $counts_by_rating[$rating]++;
                }
            }
        }

        return self::render_grid($q, $avg, $count, $args, $counts_by_rating);
    }


    /**
     * Génère un carrousel d’avis clients.
     *
     * Utilisé notamment sur la page d’accueil ou les pages vitrines.
     * Affiche les avis sous forme de slides.
     *
     * @param array $args {
     *     @type int    $limit Nombre d’avis à afficher.
     *     @type string $sort  Type de tri.
     * }
     * @return string|null HTML du carrousel ou null si aucun avis trouvé.
     */
    public static function slider($args = [])
    {
        $defaults = [
            'limit' => 12,
            'sort' => 'rating_desc',
        ];
        $args = wp_parse_args($args, $defaults);

        $sort_args = Genius_Reviews_Query_Helper::map_sort($args['sort']);

        $q_args = [
            'post_type' => 'genius_review',
            'posts_per_page' => $args['limit'],
            'meta_query' => [
                [
                    'key' => '_gr_curated',
                    'value' => 'ok',
                    'compare' => '=',
                ],
            ],
        ];

        if (!empty($sort_args['meta_query'])) {
            $q_args['meta_query'] = array_merge($q_args['meta_query'], (array) $sort_args['meta_query']);
            unset($sort_args['meta_query']);
        }

        $q_args = array_merge($q_args, $sort_args);
        $q = new WP_Query($q_args);

        $stats = Genius_Reviews_Query_Helper::get_global_stats();
        $avg = $stats['avg'];
        $count = $stats['count'];

        if ($count < 1) {
            return;
        }

        $html = self::render_carousel($q, $avg, $count);

        // Inject JSON-LD si pas sur une page produit
        if (!is_product()) {
            $json = Genius_Reviews_Output_Json_Ld::output_slider_jsonld($q->posts);
            if ($json) {
                add_action('wp_footer', function () use ($json) {
                    echo '<script type=\"application/ld+json\">' . $json . '</script>';
                }, 5);
            }
        }

        return $html;
    }


    /**
     * Affiche un petit badge de note pour un produit donné.
     *
     * Montre la moyenne d’étoiles et le nombre total d’avis.
     *
     * @param array $args {
     *     @type int $product_id ID du produit concerné.
     * }
     * @return string HTML du badge, vide si aucune donnée.
     */
    public static function badge($args = [])
    {
        $defaults = [
            'product_id' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        if (!$args['product_id'])
            return '';

        $avg = get_post_meta($args['product_id'], '_gr_avg_rating', true);
        $count = get_post_meta($args['product_id'], '_gr_review_count', true);

        if (empty($avg) || empty($count))
            return '';

        return self::render_badge($count, $avg);
    }

    /**
     * Affiche une grille complète avec onglets :
     * - Avis Produits (product_id > 0)
     * - Avis Boutique (product_id = 0)
     *
     * @param array $args
     * @return string
     */
    public static function grid_all_reviews($args = [])
    {
        $defaults = [
            'limit' => 12,
            'sort' => 'date_desc',
        ];
        $args = wp_parse_args($args, $defaults);

        if (isset($_GET['gr-sort'])) {
            $args['sort'] = sanitize_text_field($_GET['gr-sort']);
        }

        $sort_args = Genius_Reviews_Query_Helper::map_sort($args['sort']);

        $base_meta = [
            [
                'key' => '_gr_curated',
                'value' => 'ok',
                'compare' => '=',
            ],
        ];

        $meta_products = array_merge($base_meta, [
            [
                'key' => '_gr_product_id',
                'value' => 0,
                'compare' => '>',
            ]
        ]);

        $meta_shop = array_merge($base_meta, [
            [
                'key' => '_gr_product_id',
                'value' => 0,
                'compare' => '=',
            ]
        ]);

        if (!empty($sort_args['meta_query'])) {
            $meta_products = array_merge($meta_products, (array) $sort_args['meta_query']);
            $meta_shop = array_merge($meta_shop, (array) $sort_args['meta_query']);
            unset($sort_args['meta_query']);
        }

        $q_products = new WP_Query(array_merge([
            'post_type' => 'genius_review',
            'posts_per_page' => $args['limit'],
            'meta_query' => $meta_products,
        ], $sort_args));

        $q_shop = new WP_Query(array_merge([
            'post_type' => 'genius_review',
            'posts_per_page' => $args['limit'],
            'meta_query' => $meta_shop,
        ], $sort_args));

        $stats = Genius_Reviews_Query_Helper::get_global_stats();
        $avg = $stats['avg'];
        $count = $stats['count'];
        $counts_by_rating = $stats['counts_by_rating'];

        $args['mode'] = 'all';

        $all_reviews = array_merge($q_products->posts, $q_shop->posts);
        $json = Genius_Reviews_Output_Json_Ld::output_slider_jsonld($all_reviews);

        if ($json) {
            add_action('wp_footer', function () use ($json) {
                echo '<script type="application/ld+json">' . $json . '</script>';
            }, 5);
        }

        return self::render_grid(
            [
                'q_products' => $q_products,
                'q_shop' => $q_shop,
            ],
            $avg,
            $count,
            $args,
            $counts_by_rating
        );
    }




    /**
     * Construit une carte d’avis individuelle.
     *
     * Utilisé dans la grille et le carrousel.
     * Contient les étoiles, le titre, l’extrait, le nom et la date.
     *
     * @param int    $post_id ID du post (avis).
     * @param string $mode    Mode d’affichage ("grid" ou "slider").
     * @return string HTML complet de la carte.
     */
    public static function review_card($post_id, $mode = '')
    {
        $rating = (int) get_post_meta($post_id, '_gr_rating', true);
        $reviewer = get_post_meta($post_id, '_gr_reviewer_name', true);
        $date = get_post_meta($post_id, '_gr_review_date', true);
        $title = get_post_meta($post_id, '_gr_display_title', true);
        $content = get_the_content($post_id);
        $product_id = (int) get_post_meta($post_id, '_gr_product_id', true);

        ob_start(); ?>
        <div class="break-inside-avoid flex flex-col gap-2 h-full w-full">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex gap-0.5">
                    <?php echo self::render_stars($rating, 'w-4.5 h-4.5'); ?>
                </div>
                <div class="text-xs text-gray-medium">
                    <?php
                    if ($date) {
                        $timestamp = strtotime($date);
                        if ($timestamp) {
                            echo sprintf(
                                __('Il y a %s', 'genius-reviews'),
                                human_time_diff($timestamp, current_time('timestamp'))
                            );
                        } else {
                            echo esc_html($date);
                        }
                    }
                    ?>
                </div>
            </div>
            <?php if ($mode === 'all' && $product_id > 0): ?>
                <?php $product = wc_get_product($product_id); ?>
                <?php if ($product): ?>
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>"
                        class="text-brand-custom hover:text-brand-custom-hover hover:underline">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <p class="!font-semibold text-base leading-[26px]"><?php echo esc_html($title); ?></p>

            <?php if (has_post_thumbnail($post_id) && $mode === 'grid'): ?>
                <div class="w-full aspect-[360/198.33] max-h-[198.33px] flex-shrink-0 self-stretch">
                    <?php echo get_the_post_thumbnail(
                        $post_id,
                        'medium',
                        ['class' => 'review-img']
                    ); ?>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'slider'): ?>
                <?php
                $limit = 120;
                $truncated = mb_strlen($content) > $limit;
                $excerpt_display = $truncated ? mb_substr($content, 0, $limit) . '…' : $content;
                ?>
                <p class="text-[14px] gr-excerpt line-clamp-none flex flex-col gap-1 items-start">
                    <?php echo esc_html($excerpt_display); ?>
                    <?php if ($truncated): ?>
                        <span class="hidden gr-full-text"><?php echo esc_html($content); ?></span>
                        <button type="button" class="gr-read-more text-brand-custom text-sm ml-1 hover:underline">
                            <?php _e('Voir plus', 'genius-reviews'); ?>
                        </button>
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p class="text-[14px]"><?php echo esc_html($content); ?></p>
            <?php endif; ?>

            <p class="text-gray-medium text-sm leading-[22px]">
                <?php echo esc_html($reviewer ?: __('Client', 'genius-reviews')); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }


    /**
     * Rendu HTML complet de la grille des avis.
     *
     * Génère les statistiques, les barres de répartition, le tri,
     * le bouton “voir plus” et la liste des avis.
     *
     * @param $query            Objet WP_Query des avis.
     * @param float    $avg              Note moyenne.
     * @param int      $count            Nombre total d’avis.
     * @param array    $args             Paramètres initiaux de la grille.
     * @param array    $counts_by_rating Détails par note.
     * @return string HTML du bloc.
     */
    private static function render_grid($query, $avg, $count, $args, $counts_by_rating)
    {
        $is_all_mode = isset($args['mode']) && $args['mode'] === 'all' && is_array($query);

        ob_start();
        $colors = self::rating_colors();
        $counts_by_rating = isset($counts_by_rating) ? $counts_by_rating : [];
        $total = max(1, array_sum($counts_by_rating));
        ?>

        <div class="gr-bloc max-w-[1260px] p-4 md:p-12.5 mx-auto space-y-8.5">

            <!-- Header avec stats -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex flex-col gap-2">
                        <span class="text-lg font-bold"><?php esc_html_e('Avis de nos clients', 'genius-reviews'); ?></span>
                        <div class="flex gap-2 items-center">
                            <div class="flex gap-0.5">
                                <?php
                                $color_class = self::avg_color_class($avg);
                                for ($i = 1; $i <= 5; $i++) {
                                    $class = $i <= round($avg) ? $color_class : 'text-gray-light';
                                    echo '<svg xmlns="http://www.w3.org/2000/svg" class="w-5.5 h-5.5 ' . $class . '" viewBox="0 0 25 25" fill="none">
                    <path d="M25 0H0V25H25V0Z" fill="currentColor"/>
                    <path d="M12.1792 4L14.1858 10.1756H20.6792L15.4259 13.9923L13.8026 15.1718L12.1792 16.3512L6.92591 20.168L8.93249 13.9923L3.6792 10.1756H10.1726L12.1792 4Z" fill="white"/>
                    <path d="M18.0229 20.4999L15.9604 14.3124L12.8667 16.7187L18.0229 20.4999Z" fill="white"/>
                </svg>';
                                }
                                ?>
                            </div>
                            <span class="text-base font-medium"><?php echo number_format($avg, 1); ?></span>
                        </div>
                    </div>

                    <p class="text-base">
                        <?php
                        $label = sprintf(
                            __('Basé sur %1$s%3$s avis%2$s', 'genius-reviews'),
                            '<span class="font-bold">',
                            '</span>',
                            number_format_i18n((int) $count)
                        );
                        echo wp_kses_post($label);
                        ?>
                    </p>

                </div>


                <div class="space-y-0.5">
                    <div class="space-y-0.5">
                        <?php for ($i = 5; $i >= 1; $i--):
                            $percent = ($counts_by_rating[$i] / $total) * 100;
                            $text_class = $colors[$i]['text'];
                            $bg_class = $colors[$i]['bg'];
                            ?>
                            <div class="flex items-center gap-2.5">
                                <div class="flex gap-0.5">
                                    <?php for ($j = 1; $j <= 5; $j++): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 16 15"
                                            class="<?php echo $j <= $i ? $text_class : 'text-gray-light'; ?>">
                                            <path d="M15.5 0H0.5V15H15.5V0Z" fill="currentColor" />
                                            <path
                                                d="M7.80752 2.40002L9.01147 6.10539H12.9075L9.75555 8.39543L8.78153 9.1031L7.80752 9.81076L4.65555 12.1008L5.85949 8.39543L2.70752 6.10539H6.60357L7.80752 2.40002Z"
                                                fill="white" />
                                            <path d="M11.3137 12.2999L10.0762 8.58746L8.21997 10.0312L11.3137 12.2999Z" fill="white" />
                                        </svg>
                                    <?php endfor; ?>
                                </div>

                                <div class="flex-1 h-3 w-[126px] bg-gray-light rounded-full overflow-hidden">
                                    <div class="h-full rounded-full <?php echo $bg_class; ?>"
                                        style="width: <?php echo $percent; ?>%">
                                    </div>
                                </div>

                                <div class="w-6 text-right text-gray-medium text-sm">
                                    <?php echo $counts_by_rating[$i]; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php
                $lang = substr(get_locale(), 0, 2);

                $gr_slugs = [
                    'fr' => 'questionnaire-feedback',
                    'nl' => 'feedbackvragenlijst',
                    'de' => 'fragebogen-feedback',
                    'it' => 'questionario-di-feedback',
                    'es' => 'cuestionario-de-opinion',
                ];

                $target_slug = isset($gr_slugs[$lang]) ? $gr_slugs[$lang] : $gr_slugs['fr'];
                $target_url = home_url('/' . $target_slug . '/');
                ?>

                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url($target_url); ?>" class="gr-btn bg-brand-custom hover:bg-brand-custom-hover">
                        <?php _e('Écrire un avis', 'genius-reviews'); ?>
                    </a>
                <?php else: ?>
                    <?php
                    $login_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(get_permalink());
                    ?>
                    <p class="text-sm text-gray-500 mt-4">
                        <?php
                        echo wp_kses_post(
                            sprintf(
                                __("Vous devez être <a href=\"%s\" class=\"text-brand-custom hover:underline font-medium\">connecté(e)</a> pour écrire un avis.", "genius-reviews"),
                                esc_url($login_url)
                            )
                        );

                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Tri -->
            <form method="get">
                <div class="flex justify-end relative">
                    <select id="gr-sort" name="gr-sort" onchange="this.form.submit()"
                        class="!appearance-none !bg-none !rounded-lg !border-none !h-auto !pl-10 !p-3.5 !shadow-lg !text-lg !font-bold !w-full">
                        <option value="date_desc" <?php selected($args['sort'], 'date_desc'); ?>>
                            <?php _e('Plus récents', 'genius-reviews'); ?>
                        </option>
                        <option value="date_asc" <?php selected($args['sort'], 'date_asc'); ?>>
                            <?php _e('Plus anciens', 'genius-reviews'); ?>
                        </option>
                        <option value="rating_desc" <?php selected($args['sort'], 'rating_desc'); ?>>
                            <?php _e('Meilleures notes', 'genius-reviews'); ?>
                        </option>
                        <option value="rating_asc" <?php selected($args['sort'], 'rating_asc'); ?>>
                            <?php _e('Moins bonnes notes', 'genius-reviews'); ?>
                        </option>
                    </select>

                    <span class=" pointer-events-none absolute left-3 top-1/2 translate-y-[-70%]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="12" viewBox="0 0 17 12" fill="none">
                            <path
                                d="M0 1C0 0.801088 0.0790175 0.610322 0.21967 0.46967C0.360322 0.329018 0.551088 0.25 0.75 0.25H15.75C15.9489 0.25 16.1397 0.329018 16.2803 0.46967C16.421 0.610322 16.5 0.801088 16.5 1C16.5 1.19891 16.421 1.38968 16.2803 1.53033C16.1397 1.67098 15.9489 1.75 15.75 1.75H0.75C0.551088 1.75 0.360322 1.67098 0.21967 1.53033C0.0790175 1.38968 0 1.19891 0 1ZM2.5 6C2.5 5.80109 2.57902 5.61032 2.71967 5.46967C2.86032 5.32902 3.05109 5.25 3.25 5.25H13.25C13.4489 5.25 13.6397 5.32902 13.7803 5.46967C13.921 5.61032 14 5.80109 14 6C14 6.19891 13.921 6.38968 13.7803 6.53033C13.6397 6.67098 13.4489 6.75 13.25 6.75H3.25C3.05109 6.75 2.86032 6.67098 2.71967 6.53033C2.57902 6.38968 2.5 6.19891 2.5 6ZM5.5 11C5.5 10.8011 5.57902 10.6103 5.71967 10.4697C5.86032 10.329 6.05109 10.25 6.25 10.25H10.25C10.4489 10.25 10.6397 10.329 10.7803 10.4697C10.921 10.6103 11 10.8011 11 11C11 11.1989 10.921 11.3897 10.7803 11.5303C10.6397 11.671 10.4489 11.75 10.25 11.75H6.25C6.05109 11.75 5.86032 11.671 5.71967 11.5303C5.57902 11.3897 5.5 11.1989 5.5 11Z"
                                fill="black" />
                        </svg>
                    </span>

                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 10 10" fill="none">
                            <g clip-path="url(#clip0_5_3880)">
                                <path
                                    d="M5.00009 7.85059C5.17925 7.85059 5.35845 7.78218 5.49508 7.64567L9.79486 3.34579C10.0684 3.07226 10.0684 2.6288 9.79486 2.35538C9.52145 2.08197 9.07807 2.08197 8.80452 2.35538L5.00009 6.1601L1.19551 2.35565C0.922099 2.08224 0.478653 2.08224 0.205264 2.35565C-0.0683911 2.62906 -0.0683911 3.0724 0.205264 3.34592L4.50497 7.64583C4.64168 7.78231 4.82087 7.85059 5.00009 7.85059Z"
                                    fill="black" />
                            </g>
                            <defs>
                                <clipPath id="clip0_5_3880">
                                    <rect width="10" height="10" fill="white"
                                        transform="matrix(-4.37114e-08 -1 -1 4.37114e-08 10 10)" />
                                </clipPath>
                            </defs>
                        </svg>
                    </span>
                </div>
            </form>

            <!-- Contenu -->
            <?php if ($is_all_mode): ?>
                <?php
                $q_products = $query['q_products'];
                $q_shop = $query['q_shop'];
                $total_products = $q_products->found_posts;
                $total_shop = $q_shop->found_posts;
                ?>
                <div class="flex gap-4 border-b border-gray-200 mb-6">
                    <button class="gr-tab !bg-transparent text-brand-custom hover:text-brand-custom-hover gr-tab-active" data-tab="products">
                        <?php
                        echo wp_kses_post(
                            sprintf(
                                __('Avis sur Produits (%s)', 'genius-reviews'),
                                number_format_i18n((int) $q_products->found_posts)
                            )
                        );
                        ?>
                    </button>

                    <button class="gr-tab !bg-transparent text-brand-custom hover:text-brand-custom-hover" data-tab="shop">
                        <?php
                        echo wp_kses_post(
                            sprintf(
                                __('Avis sur Boutique (%s)', 'genius-reviews'),
                                number_format_i18n((int) $q_shop->found_posts)
                            )
                        );
                        ?>
                    </button>

                </div>

                <div id="gr-tab-products" class="gr-tab-content">
                    <?php self::render_grid_inner($q_products, $args); ?>
                </div>

                <div id="gr-tab-shop" class="gr-tab-content hidden">
                    <?php self::render_grid_inner($q_shop, $args); ?>
                </div>

                <?php if ($total_products > ($args['limit'] ?? 12) || $total_shop > ($args['limit'] ?? 12)): ?>
                    <div class="flex justify-center mt-6">
                        <button id="gr-load-more" class="gr-btn bg-brand-custom hover:bg-brand-custom-hover"
                            data-limit="<?php echo esc_attr($args['limit']); ?>"
                            data-total-products="<?php echo esc_attr($total_products); ?>"
                            data-total-shop="<?php echo esc_attr($total_shop); ?>" data-mode="all_reviews">
                            <?php _e('Voir plus d’avis', 'genius-reviews'); ?>
                        </button>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div id="gr-tab-products" class="gr-tab-content">
                    <?php self::render_grid_inner($query, $args); ?>
                </div>

                <?php if ($count > ($args['limit'] ?? 12)): ?>
                    <div class="flex justify-center mt-6">
                        <button id="gr-load-more" class="gr-btn bg-brand-custom hover:bg-brand-custom-hover"
                            data-limit="<?php echo esc_attr($args['limit']); ?>" data-total-products="<?php echo esc_attr($count); ?>"
                            data-total-shop="0">
                            <?php _e('Voir plus d’avis', 'genius-reviews'); ?>
                        </button>
                    </div>
                <?php endif; ?>

            <?php endif; ?>


        </div>

        <?php
        return ob_get_clean();
    }



    /**
     * Rendu HTML du carrousel d’avis.
     *
     * Construit la structure compatible Swiper.js avec les flèches et slides.
     *
     * @param WP_Query $query Objet WP_Query des avis.
     * @param float    $avg   Moyenne des notes.
     * @param int      $count Nombre total d’avis.
     * @return string HTML du carrousel complet.
     */
    private static function render_carousel(WP_Query $query, $avg, $count)
    {
        ob_start();

        if (!$query->have_posts()) {
            echo '<div class="gr-bloc text-center p-6">';
            echo '<p class="text-gray-500">' . __('Aucun avis disponible.', 'genius-reviews') . '</p>';
            echo '</div>';
            return ob_get_clean();
        }
        ?>

        <div class="gr-bloc max-w-[1260px] flex flex-col md:flex-row gap-12.5 md:items-start">

            <!-- stats globales -->
            <div class="flex flex-col items-center justify-center text-center pt-6">
                <span class="text-xl font-bold"><?php echo esc_html(self::rating_label($avg)); ?></span>

                <div class="flex gap-0.5 mt-2 mb-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 <?php echo $i <= round($avg) ? 'text-brand' : 'text-gray-light'; ?>" viewBox="0 0 25 25"
                            fill="none">
                            <path d="M25 0H0V25H25V0Z" fill="currentColor" />
                            <path
                                d="M12.1792 4L14.1858 10.1756H20.6792L15.4259 13.9923L13.8026 15.1718L12.1792 16.3512L6.92591 20.168L8.93249 13.9923L3.6792 10.1756H10.1726L12.1792 4Z"
                                fill="white" />
                            <path d="M18.0229 20.4999L15.9604 14.3124L12.8667 16.7187L18.0229 20.4999Z" fill="white" />
                        </svg>
                    <?php endfor; ?>
                </div>

                <span class="text-base whitespace-nowrap">
                    <?php
                    $label = sprintf(
                        __('Basé sur %1$s%3$s avis%2$s', 'genius-reviews'),
                        '<span class="font-bold">',
                        '</span>',
                        number_format_i18n((int) $count)
                    );
                    echo wp_kses_post($label);
                    ?>
                </span>
            </div>

            <div class="swiper gr-swiper md:!px-12">
                <div class="swiper-wrapper max-w-[360px]  md:max-w-full">
                    <?php while ($query->have_posts()):
                        $query->the_post(); ?>
                        <div class="swiper-slide px-10 md:px-0">
                            <?php echo self::review_card(get_the_ID(), 'slider'); ?>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata(); ?>
                </div>

                <div
                    class="swiper-button-prev !left-0 after:!hidden bg-white border border-gray-300 rounded-full p-2 hover:border-brand-custom group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="6" height="11" viewBox="0 0 6 11" fill="none"
                        class="stroke-gray-400 -translate-x-[1px] group-hover:stroke-brand-custom">
                        <path d="M5.21641 9.45946L1.16235 5.4054L5.21641 1.35135" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <div
                    class="swiper-button-next !right-0 after:!hidden bg-white border border-gray-300 rounded-full p-2 hover:border-brand-custom group">
                    <svg xmlns="http://www.w3.org/2000/svg" width="6" height="11" viewBox="0 0 6 11" fill="none"
                        class="rotate-180 translate-x-[1px] stroke-gray-400 group-hover:stroke-brand-custom">
                        <path d="M5.21641 9.45946L1.16235 5.4054L5.21641 1.35135" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>

            </div>
        </div>


        <?php
        return ob_get_clean();
    }

    /**
     * Rendu du badge de note (étoiles + nombre d’avis).
     *
     * Utilisé sur les pages produits et collections.
     *
     * @param int   $count Nombre total d’avis.
     * @param float $avg   Note moyenne du produit.
     * @return string HTML du badge.
     */
    private static function render_badge($count, $avg)
    {
        ob_start();
        ?>
        <div class="gr-badge cursor-pointer flex items-center gap-2 my-2">
            <div class="flex gap-0.5">
                <?php echo self::render_stars(round($avg), "w-4 h-4"); ?>
            </div>
            <span class="text-xs">
                <?php
                $label = sprintf(
                    _n('%s avis', '%s avis', (int) $count, 'genius-reviews'),
                    number_format_i18n((int) $count)
                );
                echo esc_html($label);
                ?>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }


    // Génère juste la grille (cartes d’avis)
    private static function render_grid_inner(WP_Query $query, $args)
    {
        ?>
        <div class="gr-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10 space-y-10"
            data-product-id="<?php echo esc_attr($args['product_id'] ?? 0); ?>">
            <?php
            while ($query->have_posts()) {
                $query->the_post();
                echo self::review_card(get_the_ID(), $args['mode'] ?: 'grid');
            }
            wp_reset_postdata();
            ?>
        </div>
        <?php
    }




    /**
     * Affiche un message lorsqu’aucun avis n’est disponible.
     *
     * @return string HTML du message “Aucun avis”.
     */
    private static function render_no_reviews()
    {
        ob_start(); ?>
        <div class="gr-bloc max-w-[1260px] p-4 md:p-12.5 mx-auto space-y-8.5 flex flex-col items-center gap-3 py-6 text-center">
            <span class="font-bold !m-0 text-lg"><?php esc_html_e('Avis de nos clients', 'genius-reviews'); ?></span>

            <div class="flex flex-col !m-0">
                <div class="flex gap-0.5">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-light" viewBox="0 0 25 25" fill="none">
                            <path d="M25 0H0V25H25V0Z" fill="currentColor" />
                            <path
                                d="M12.1792 4L14.1858 10.1756H20.6792L15.4259 13.9923L13.8026 15.1718L12.1792 16.3512L6.92591 20.168L8.93249 13.9923L3.6792 10.1756H10.1726L12.1792 4Z"
                                fill="white" />
                            <path d="M18.0229 20.4999L15.9604 14.3124L12.8667 16.7187L18.0229 20.4999Z" fill="white" />
                        </svg>
                    <?php endfor; ?>
                </div>
            </div>

            <p class="text-sm"><?php esc_html_e('Aucun avis pour le moment', 'genius-reviews'); ?></p>

            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(home_url("/questionnaire-feedback")); ?>"
                    class="gr-btn bg-brand-custom hover:bg-brand-custom-hover">
                    <?php _e('Écrire un avis', 'genius-reviews'); ?>
                </a>
            <?php else: ?>
                <?php
                $login_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url(get_permalink());
                ?>
                <p class="text-sm text-gray-500 mt-4">
                    <?php
                    echo wp_kses_post(
                        sprintf(
                            __("Vous devez être <a href=\"%s\" class=\"text-brand-custom hover:underline font-medium\">connecté(e)</a> pour écrire un avis.", "genius-reviews"),
                            esc_url($login_url)
                        )
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }



    /**
     * Retourne les classes de couleur associées à chaque note (1 à 5).
     *
     * @return array Tableau associatif des classes CSS pour chaque rating.
     */
    private static function rating_colors()
    {
        return [
            5 => ['text' => 'text-rating-5', 'bg' => 'bg-rating-5'],
            4 => ['text' => 'text-rating-4', 'bg' => 'bg-rating-4'],
            3 => ['text' => 'text-rating-3', 'bg' => 'bg-rating-3'],
            2 => ['text' => 'text-rating-2', 'bg' => 'bg-rating-2'],
            1 => ['text' => 'text-rating-1', 'bg' => 'bg-rating-1'],
        ];
    }

    /**
     * Génère le code SVG des étoiles pour une note donnée.
     *
     * @param int    $rating Note entre 1 et 5.
     * @param string $size   Classes CSS de taille à appliquer.
     * @return string HTML contenant les 5 étoiles.
     */
    private static function render_stars($rating, $size = 'w-5 h-5')
    {
        $colors = self::rating_colors();
        ob_start();
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $rating ? $colors[$rating]['text'] : 'text-gray-light';
            echo '<svg xmlns="http://www.w3.org/2000/svg" class="' . $size . ' ' . $class . '" viewBox="0 0 25 25">
            <path d="M25 0H0V25H25V0Z" fill="currentColor"/>
            <path d="M12.1792 4L14.1858 10.1756H20.6792L15.4259 13.9923L13.8026 15.1718L12.1792 16.3512L6.92591 20.168L8.93249 13.9923L3.6792 10.1756H10.1726L12.1792 4Z" fill="white"/>
            <path d="M18.0229 20.4999L15.9604 14.3124L12.8667 16.7187L18.0229 20.4999Z" fill="white"/>
        </svg>';
        }
        return ob_get_clean();
    }

    /**
     * Retourne la classe de couleur correspondant à la moyenne des notes.
     *
     * Permet de colorer les étoiles en fonction de la moyenne générale.
     *
     * @param float $avg Note moyenne du produit.
     * @return string Classe CSS de couleur
     */
    private static function avg_color_class($avg)
    {
        $colors = self::rating_colors();
        $rounded = max(1, min(5, round($avg)));
        return $colors[$rounded]['text'];
    }

    /**
     * Ajuste la luminosité d’une couleur hexadécimale.
     *
     * Sert à générer automatiquement la couleur de survol
     * à partir de la couleur principale.
     *
     * @param string $hex   Code hexadécimal de la couleur.
     * @param int    $steps Niveau de luminosité à ajouter ou retirer.
     * @return string Nouvelle couleur hex.
     */
    private static function adjust_brightness($hex, $steps)
    {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) === 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
            . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }


    /**
     * Retourne une étiquette de texte basée sur la moyenne des notes.
     *
     *
     * @param float $avg Moyenne des avis.
     * @return string Libellé correspondant.
     */
    private static function rating_label($avg)
    {
        if ($avg >= 4.5)
            return __('Excellent', 'genius-reviews');
        if ($avg >= 4.0)
            return __('Très bien', 'genius-reviews');
        if ($avg >= 3.0)
            return __('Bien', 'genius-reviews');
        if ($avg >= 2.0)
            return __('Moyen', 'genius-reviews');
        return __('À éviter', 'genius-reviews');
    }


    /**
     * Tronque un texte à un nombre de caractères sans couper les mots.
     *
     * @param string $text     Le texte original
     * @param int    $limit    Nombre max de caractères
     * @param string $ellipsis Texte ajouté à la fin si tronqué
     * @return string
     */
    private static function truncate_text($text, $limit = 50, $ellipsis = '…')
    {
        $text = trim(wp_strip_all_tags($text));

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);

        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut) . $ellipsis;
    }
}
