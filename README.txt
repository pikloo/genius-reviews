=== Genius Reviews ===
Contributors: ingeniusagency
Donate link: https://ingenius.agency
Tags: reviews, woocommerce, testimonials, import, csv, json-ld, schema
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Importez, affichez et optimisez vos avis clients WooCommerce avec JSON-LD SEO-ready.

== Description ==

**Genius Reviews** permet d’importer vos avis clients depuis un fichier CSV et d’afficher automatiquement les grilles ou carrousels d’avis sur vos fiches produits WooCommerce.

Les avis sont stockés en tant que custom post type `genius_review` et enrichis de données JSON-LD pour le référencement.

Fonctionnalités principales :

- 🧾 Import CSV automatique
- ⭐ Grille et carrousel d’avis (shortcodes et Elementor)
- 🧠 Tri par note, date, etc.
- 📈 Génération automatique de `aggregateRating` pour Google
- 🎨 Couleur de marque personnalisable
- 🗣️ Traductions : Français, Anglais, Espagnol, Italien, Allemand, Polonais

== Installation ==

1. Téléversez le dossier `genius-reviews` dans le répertoire `/wp-content/plugins/`.
2. Activez le plugin depuis le menu **Extensions** de WordPress.
3. Allez dans **Avis → Options & Import** pour configurer votre import et l’apparence.
4. Utilisez le shortcode `[genius_reviews_grid]` ou `[genius_reviews_slider]` dans vos pages produits.

== Shortcodes ==

= Grille d'avis =

`[genius_reviews_grid]`

Affiche les avis du produit courant sur une fiche produit WooCommerce.

`[genius_reviews_grid product_id="123" limit="6" sort="date_desc"]`

Affiche les avis d'un produit précis.

= Slider d'avis =

`[genius_reviews_slider limit="12" sort="rating_desc"]`

Affiche une sélection globale d'avis curés.

`[genius_reviews_slider limit="12" sort="rating_desc" mode="compact"]`

Affiche le slider en mode compact.

`[genius_reviews_slider scope="category" limit="12" sort="rating_desc"]`

Affiche les avis produits liés à la catégorie WooCommerce courante.

`[genius_reviews_slider scope="category" term_id="12" taxonomy="product_cat" limit="12" sort="rating_desc"]`

Affiche les avis produits d'une catégorie précise.

Le slider `scope="category"` calcule la moyenne et le nombre d'avis sur les produits de la catégorie. Si la catégorie contient 1 ou 2 avis, le slider complète l'affichage avec des avis produits pour atteindre 3 cartes, sans les intégrer aux statistiques catégorie. Si la catégorie ne contient aucun avis, ou si le shortcode est utilisé sur la page boutique sans catégorie courante, le slider affiche des avis produits globaux et utilise les statistiques des avis produits globaux.

= Badge d'avis =

`[genius_reviews_badge]`

Affiche le badge du produit courant.

`[genius_reviews_badge product_id="123"]`

Affiche le badge d'un produit précis.

`[genius_reviews_badge scope="category" mode="compact_rating"]`

Affiche le badge de la catégorie WooCommerce courante.

`[genius_reviews_badge scope="category" term_id="12" taxonomy="product_cat" mode="compact_rating"]`

Affiche le badge d'une catégorie précise.

= Intégration automatique sur la boutique et les catégories =

Pour afficher automatiquement le slider après la grille produits WooCommerce sur la boutique et les catégories, ajoutez ce hook dans le `functions.php` du thème enfant ou via un plugin de snippets :

    add_action('woocommerce_after_shop_loop', function () {
        $is_shop_page = function_exists('is_shop') && is_shop();
        $is_category_page = function_exists('is_product_category') && is_product_category();

        if (!$is_shop_page && !$is_category_page) {
            return;
        }

        $term = get_queried_object();
        if (!$term instanceof WP_Term) {
            echo do_shortcode('[genius_reviews_slider scope="category" limit="12" sort="rating_desc"]');
            return;
        }

        echo do_shortcode(sprintf(
            '[genius_reviews_slider scope="category" term_id="%d" taxonomy="%s" limit="12" sort="rating_desc"]',
            (int) $term->term_id,
            esc_attr($term->taxonomy)
        ));
    }, 20);

Pour l'afficher avant la grille produits, utilisez le hook `woocommerce_before_shop_loop`.

== Frequently Asked Questions ==

= Où apparaissent les avis ? =
Les avis sont visibles dans la section “Avis de nos clients” en bas de chaque fiche produit, ou via les shortcodes.

= Puis-je personnaliser les couleurs ? =
Oui, une couleur principale est configurable dans la page d’options du plugin.

= Est-ce compatible avec Elementor ? =
Oui, un widget “Genius Reviews” est inclus.

== Screenshots ==

1. Interface d’import CSV
2. Grille d’avis sur fiche produit
3. Carrousel d’avis
4. Réglages d’apparence

== Changelog ==
= 1.2.2.6 =
* Correction de l'affichage du slider `scope="category"` sur la page boutique quand aucune catégorie courante n'est disponible.
* Mise à jour du hook d'intégration WooCommerce pour couvrir la boutique et les catégories produits.

= 1.2.2.5 =
* Complétion des traductions admin dans toutes les langues disponibles.
* Correction du fallback du slider catégorie quand la catégorie ne contient aucun avis.
* Fallback du slider `scope="category"` vers les avis produits globaux sur la page boutique.

= 1.2.2.4 =
* Ajout du `scope="category"` sur le shortcode slider pour afficher les avis produits de la catégorie WooCommerce courante ou d'une catégorie précise.
* Calcul des statistiques du slider catégorie sur les avis produits de la catégorie, avec appoint d'avis produits uniquement pour compléter l'affichage.
* Fallback du slider catégorie vers les avis produits si la catégorie ne contient aucun avis.

= 1.2.2.3 =
* Ajout des couleurs personnalisables pour les étoiles par note, l’état inactif et le symbole interne.
* Centralisation du SVG des étoiles pour tous les shortcodes.
* Réorganisation de la page Options & Import.

= 1.2.2.1 =
* Ajout scope category pour le shortcode badge.
* Ajout scope category pour le shortcode slider, avec appoint d'avis produits si moins de 3 avis catégorie sont disponibles.

= 1.2.2 =
* Ajout d'un cache JSON-LD pour les pages catégories WooCommerce et attributs produits (`pa_*`) avec recalcul par cron.
* Génération d'un schéma `Product` agrégé pour les catégories et attributs avec `AggregateOffer` et `aggregateRating` calculé depuis les avis Genius Reviews.
* Calcul des notes catégories/attributs via moyenne pondérée des metas `_gr_avg_rating` et `_gr_review_count`.
* Injection du schéma catégories/attributs uniquement si au moins 3 avis sont disponibles.
* Fusion des notes Genius Reviews dans le schéma `Product` RankMath pour éviter les doublons sur les fiches produits.

= 1.2.1.13 =
* Correction style badge et fix génération données enrichies compatible rank math

= 1.2.1.12 =
* Suppression "Lato" & remplacement transient par user meta temporaire pour l'import admin

= 1.2.1.11 =
* Fix taille svg compatible FF

= 1.2.1.10 =
* Ajout du mode compact sur le shortcode slider via `mode="compact"` (colonne, gap réduit, 1 slide par vue) + centrage du bloc note en compact
* Admin `genius_review` : nouvelles colonnes `Product ID`, `Title`, `Note`, `Validation` (curated), colonnes triables
* Admin `genius_review` : `Product ID` cliquable vers l’édition de l’avis + filtres par note et par validation
* Page `gr-options` : ajout de l’exemple shortcode compact dans la liste des shortcodes
* Affichage auto des avis sur fiche produit déplacé pour sortir du layout en colonnes et se placer juste après le contenu principal
* Badge aligné sur les autres blocs : calcul basé uniquement sur les avis `curated = ok`

= 1.2.1.9 =
* Fix remove_spacing all reviews new version
* Shortcode summary

= 1.2.1.7 =
* Fix badge

= 1.2.1.6 =
* Parametre sans espace pour shortcode reviews grid et affichage badge use_count_global quand option active

= 1.2.1.5 =
* Option affiché tous les avis du sites sur FP si le produit n'a pas d'avis

= 1.2.1.4 =
* Mise à jour système de purge

= 1.2.1.3 =
* Suppression du CSS inutilisé à l'upload

= 1.2.1.2 =
* Fix flush WP rocket


= 1.2.1.1 =
* Scope Prefix post css pour éviter les conflits

= 1.2.1 =
* Remplacement swiper par splide , suppression des style inline pour fichier css public

= 1.2.0.5 =
* Appel Swiper CDN

= 1.2.0.4 =
* Ajout slug feedback PL et NL

= 1.2.0.3 =
* Correction nom de fichier de trad suédois 

= 1.2.0.2 =
* Patch synchronisation nouveaux avis 

= 1.2.0.1 =
* Correction url feedback pour reviews grid

= 1.2.0 =
* Gestion de plusieurs image + Allègement du plugin + exclusions de répertoires

= 1.1.9 =
* Champs reviews éditable

= 1.1.8.5 =
* Fix swiper pagination css + build

= 1.1.8.4 =
* Fix swiper pagination css

= 1.1.8.3 =
* Fix spanish trad

= 1.1.8.2 =
* CSS ajustements

= 1.1.8 =
* lien questionnaire fb internationaux
* Fix traduction
* Couleur hex dans la page d'options

= 1.1.6 =
* Fix force allow csv upload

= 1.1.4 =
* Fix traduction php-format

= 1.1.2 =
* Ajout du titre affiché

= 1.1.0 =
* Output json avis généraux
* MAJ traductions

= 1.0.3 =
* Grille avis généraux

= 1.0.2 =
* Affichage du badge
* Gestion multisite

= 1.0.1 =
* Traductions

= 1.0.0 =
* Première version publique
* Import CSV
* Grille et carrousel d’avis
* JSON-LD automatique

== Upgrade Notice ==

= 1.0.0 =
Version initiale stable.
