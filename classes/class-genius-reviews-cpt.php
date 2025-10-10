<?php
if (! defined('ABSPATH')) exit;

class Genius_Reviews_CPT
{

    public static function register()
    {
        $labels = [
            'name'               => __('Avis', 'genius-reviews'),
            'singular_name'      => __('Avis', 'genius-reviews'),
            'add_new'            => __('Ajouter un avis', 'genius-reviews'),
            'add_new_item'       => __('Ajouter un nouvel avis', 'genius-reviews'),
            'edit_item'          => __('Éditer l’avis', 'genius-reviews'),
            'new_item'           => __('Nouvel avis', 'genius-reviews'),
            'view_item'          => __('Voir l’avis', 'genius-reviews'),
            'search_items'       => __('Rechercher des avis', 'genius-reviews'),
            'not_found'          => __('Aucun avis', 'genius-reviews'),
            'not_found_in_trash' => __('Aucun avis dans la corbeille', 'genius-reviews'),
            'menu_name'          => __('Genius Reviews', 'genius-reviews'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => false,
            'exclude_from_search' => true,
            'supports'           => ['title', 'editor', 'thumbnail'],
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'menu_icon'          => 'dashicons-format-quote'
        ];

        register_post_type('genius_review', $args);

        // Meta clés normalisées
        register_post_meta('genius_review', '_gr_display_title',        ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_rating',        ['type' => 'number', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_review_date',   ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_source',        ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_curated',       ['type' => 'boolean', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_reviewer_name', ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_reviewer_hash', ['type' => 'string', 'single' => true, 'show_in_rest' => false]); // email hash (GDPR)
        register_post_meta('genius_review', '_gr_product_id',    ['type' => 'integer', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_product_handle', ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_reply',         ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_reply_date',    ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_ip',            ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
        register_post_meta('genius_review', '_gr_location',      ['type' => 'string', 'single' => true, 'show_in_rest' => false]);
    }


    public static function register_metaboxes()
    {
        add_meta_box(
            'gr_review_details',
            __('Détails de l’avis', 'genius-reviews'),
            [__CLASS__, 'render_metabox'],
            'genius_review',
            'normal',
            'default'
        );
    }

    public static function render_metabox($post)
    {
        $fields = [
            '_gr_display_title' => 'Titre affiché',
            '_gr_rating'        => 'Note',
            '_gr_review_date'   => 'Date',
            '_gr_source'        => 'Source',
            '_gr_curated'       => 'Curated ?',
            '_gr_reviewer_name' => 'Auteur',
            '_gr_product_id'    => 'Produit ID',
            '_gr_product_handle' => 'Handle',
            '_gr_reply'         => 'Réponse',
            '_gr_reply_date'    => 'Date réponse',
            '_gr_ip'            => 'IP',
            '_gr_location'      => 'Localisation',
        ];

        echo '<table class="form-table">';
        foreach ($fields as $key => $label) {
            $val = get_post_meta($post->ID, $key, true);
            echo '<tr><th><label>' . esc_html($label) . '</label></th><td>';
            echo '<input type="text" readonly class="widefat" value="' . esc_attr($val) . '"/>';
            echo '</td></tr>';
        }
        echo '</table>';
    }


    // /**
    //  * Recalcul automatique quand on enregistre une review
    //  */
    // public function recalc_on_save($post_id, $post, $update)
    // {
    //     if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

    //     $product_id = (int) get_post_meta($post_id, '_gr_product_id', true);
    //     if ($product_id) {
    //         if (method_exists('Genius_Reviews_Admin_Page', 'recalc_product')) {
    //             Genius_Reviews_Admin_Page::recalc_product($product_id);
    //         }
    //     }
    // }
}
