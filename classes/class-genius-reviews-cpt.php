<?php
if (! defined('ABSPATH')) exit;

class Genius_Reviews_CPT
{

    private static function get_meta_fields()
    {
        return [
            '_gr_display_title' => [
                'label'    => 'Titre affiché',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_rating' => [
                'label'    => 'Note',
                'input'    => 'number',
                'attributes' => [
                    'min'  => '0',
                    'step' => '0.1',
                ],
                'sanitize' => 'float',
            ],
            '_gr_review_date' => [
                'label'    => 'Date',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_source' => [
                'label'    => 'Source',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_curated' => [
                'label'    => 'Curated ?',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_reviewer_name' => [
                'label'    => 'Auteur',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_product_id' => [
                'label'    => 'Produit ID',
                'input'    => 'number',
                'attributes' => [
                    'min' => '0',
                ],
                'sanitize' => 'int',
            ],
            '_gr_product_handle' => [
                'label'    => 'Handle',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_reply' => [
                'label'    => 'Réponse',
                'input'    => 'textarea',
                'sanitize' => 'textarea',
            ],
            '_gr_reply_date' => [
                'label'    => 'Date réponse',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_ip' => [
                'label'    => 'IP',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
            '_gr_location' => [
                'label'    => 'Localisation',
                'input'    => 'text',
                'sanitize' => 'text',
            ],
        ];
    }

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
        $fields = self::get_meta_fields();
        $can_edit = current_user_can('manage_options');

        if ($can_edit) {
            wp_nonce_field('gr_meta_save', 'gr_meta_nonce');
        }

        echo '<table class="form-table">';
        foreach ($fields as $key => $label) {
            $field = is_array($label) ? $label : ['label' => $label, 'input' => 'text', 'sanitize' => 'text'];
            $val = get_post_meta($post->ID, $key, true);
            echo '<tr><th><label>' . esc_html($field['label']) . '</label></th><td>';
            if ($can_edit) {
                $attributes = ['class' => 'widefat'];
                if (!empty($field['attributes']) && is_array($field['attributes'])) {
                    $attributes = array_merge($attributes, $field['attributes']);
                }
                if ($field['input'] === 'textarea') {
                    echo '<textarea name="gr_meta[' . esc_attr($key) . ']" class="widefat" rows="3">' . esc_textarea($val) . '</textarea>';
                } else {
                    $type = in_array($field['input'], ['text', 'number'], true) ? $field['input'] : 'text';
                    $attrs = '';
                    foreach ($attributes as $attr_key => $attr_value) {
                        $attrs .= ' ' . esc_attr($attr_key) . '="' . esc_attr($attr_value) . '"';
                    }
                    echo '<input type="' . esc_attr($type) . '" name="gr_meta[' . esc_attr($key) . ']" value="' . esc_attr($val) . '"' . $attrs . ' />';
                }
            } else {
                if ($field['input'] === 'textarea') {
                    echo '<textarea readonly class="widefat" rows="3">' . esc_textarea($val) . '</textarea>';
                } else {
                    echo '<input type="text" readonly class="widefat" value="' . esc_attr($val) . '"/>';
                }
            }
            echo '</td></tr>';
        }
        echo '</table>';
    }

    public static function save_metabox($post_id, $post, $update)
    {
        if (! isset($_POST['gr_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gr_meta_nonce'])), 'gr_meta_save')) {
            return;
        }

        if (! current_user_can('manage_options')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (! isset($_POST['gr_meta']) || ! is_array($_POST['gr_meta'])) {
            return;
        }

        $fields = self::get_meta_fields();
        $input = wp_unslash($_POST['gr_meta']);

        foreach ($fields as $meta_key => $field) {
            if (! array_key_exists($meta_key, $input)) {
                continue;
            }

            $sanitize_type = isset($field['sanitize']) ? $field['sanitize'] : 'text';
            $raw_value = $input[$meta_key];

            if (is_array($raw_value)) {
                continue;
            }

            $value = self::sanitize_meta_value($raw_value, $sanitize_type);

            update_post_meta($post_id, $meta_key, $value);
        }
    }

	private static function sanitize_meta_value($value, $type)
	{
		switch ($type) {
			case 'int':
				return (string) absint($value);
            case 'float':
                if ($value === '' || $value === null) {
                    return '';
                }
                return (string) floatval($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'text':
			default:
				return sanitize_text_field($value);
		}
	}

	public static function sync_product_on_save($post_id, $post, $update)
	{
		if (! $post instanceof WP_Post || $post->post_type !== 'genius_review') {
			return;
		}

		if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return;
		}

		self::recalc_related_product($post_id);
	}

	public static function sync_product_on_status_change($post_id)
	{
		if (get_post_type($post_id) !== 'genius_review') {
			return;
		}

		self::recalc_related_product($post_id);
	}

	private static function recalc_related_product($review_id)
	{
		$product_id = (int) get_post_meta($review_id, '_gr_product_id', true);
		if ($product_id <= 0) {
			return;
		}

		if (is_callable(['Genius_Reviews_Ajax', 'recalc_product'])) {
			Genius_Reviews_Ajax::recalc_product($product_id);
		}
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
