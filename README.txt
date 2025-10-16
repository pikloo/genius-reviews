=== Genius Reviews ===
Contributors: ingeniusagency
Donate link: https://ingenius.agency
Tags: reviews, woocommerce, testimonials, import, csv, json-ld, schema
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
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

= 1.1.8 =
* CSS ajustements
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
