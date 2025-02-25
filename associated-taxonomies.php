<?php

 /**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             1.0.0
  * @package           Associated_Taxonomies
  *
  * @wordpress-plugin
  *
  * Plugin Name: Associated Taxonomies
  * Description: Adds a field to associate terms within the same taxonomy.
  * Plugin URI:  https://github.com/robertdevore/associated-taxonomies/
  * Version:     1.0.1
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: associated-taxonomies
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/associated-taxonomies/
  */
 
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define the plugin version.
define( 'ASSOCIATED_TAXONOMIES_VERSION', '1.0.1' );
define( 'ASSOCIATED_TAXONOMIES_PATH', __FILE__ );

// Include the Plugin Update Checker.
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/associated-taxonomies/',
    __FILE__,
    'associated-taxonomies'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

require 'classes/Associated_Taxonomies.php';

// Initialize the plugin class.
new Associated_Taxonomies();

/**
 * Load plugin text domain for translations
 * 
 * @since  1.0.1
 * @return void
 */
function associated_taxonomies_load_textdomain() {
    load_plugin_textdomain( 
        'associated-taxonomies',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'associated_taxonomies_load_textdomain' );

/**
 * Shortcode to display associated terms for any taxonomy.
 *
 * Usage: [associated_taxonomy_terms id="123" taxonomy="category"]
 *
 * @param array $atts Shortcode attributes.
 * 
 * @since  1.0.0
 * @return string HTML output for the associated terms.
 */
function associated_taxonomy_terms_shortcode( $atts ) {
    // Extract attributes with defaults.
    $atts = shortcode_atts( [
        'id'       => 0,
        'taxonomy' => '',
    ], $atts );

    $term_id  = intval( $atts['id'] );
    $taxonomy = sanitize_text_field( $atts['taxonomy'] );

    // Validate the input.
    if ( ! $term_id || ! taxonomy_exists( $taxonomy ) || ! term_exists( $term_id, $taxonomy ) ) {
        return '<p>' . esc_html__( 'Invalid term ID or taxonomy.', 'associated-taxonomies' ) . '</p>';
    }

    // Get the parent term.
    $parent_term = get_term( $term_id, $taxonomy );

    // Get associated terms from term metadata.
    $associated_terms = get_term_meta( $term_id, 'associated_terms', true );
    $associated_terms = is_array( $associated_terms ) ? $associated_terms : [];

    // Start building the output.
    ob_start();
    ?>
    <div class="associated-terms-wrapper">
        <!-- Display parent term -->
        <div class="parent-term">
            <h2><?php echo esc_html( $parent_term->name ); ?></h2>
            <p class="description"><?php echo esc_html( $parent_term->description ); ?></p>
        </div>

        <!-- Display associated terms -->
        <?php if ( ! empty( $associated_terms ) ) : ?>
            <ul class="associated-terms-list">
                <?php foreach ( $associated_terms as $associated_term_id ) : ?>
                    <?php
                    $associated_term = get_term( $associated_term_id, $taxonomy );
                    if ( $associated_term ) :
                    ?>
                        <li>
                            <a href="<?php echo esc_url( get_term_link( $associated_term_id, $taxonomy ) ); ?>">
                                <?php echo esc_html( $associated_term->name ); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?php esc_html_e( 'No associated terms found.', 'associated-taxonomies' ); ?></p>
        <?php endif; ?>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode( 'related_terms', 'associated_taxonomy_terms_shortcode' );

/**
 * Shortcode to display posts with a parent term and at least one child term for any taxonomy.
 *
 * Usage: [parent_child_posts parent="12" child="34,56" taxonomy="category"]
 *
 * @param array $atts Shortcode attributes.
 * 
 * @since  1.0.0
 * @return string HTML output for the list of posts.
 */
function parent_child_posts_shortcode( $atts ) {
    // Extract attributes with default values.
    $atts = shortcode_atts( [
        'parent'   => '',
        'child'    => '',
        'taxonomy' => '',
    ], $atts );

    $parent_id = intval( $atts['parent'] );
    $child_ids = array_map( 'intval', explode( ',', $atts['child'] ) );
    $taxonomy  = sanitize_text_field( $atts['taxonomy'] );

    // Validate the input.
    if ( ! $parent_id || empty( $child_ids ) || ! taxonomy_exists( $taxonomy ) ) {
        return '<p>' . esc_html__( 'Invalid parent or child terms provided, or invalid taxonomy.', 'associated-taxonomies' ) . '</p>';
    }

    // Build the query arguments.
    $query_args = [
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy'         => $taxonomy,
                'field'            => 'term_id',
                'terms'            => [ $parent_id ],
                'include_children' => false,
            ],
            [
                'taxonomy'         => $taxonomy,
                'field'            => 'term_id',
                'terms'            => $child_ids,
                'operator'         => 'IN',
            ],
        ],
    ];

    $query = new WP_Query( $query_args );

    // Check if any posts are found.
    if ( ! $query->have_posts() ) {
        return '<p>' . esc_html__( 'No posts found for the specified terms.', 'associated-taxonomies' ) . '</p>';
    }

    // Build the output.
    ob_start();
    echo '<ul class="parent-child-posts-list">';
    while ( $query->have_posts() ) {
        $query->the_post();
        ?>
        <li>
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </li>
        <?php
    }
    echo '</ul>';

    // Reset the post data.
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'posts_by_related_terms', 'parent_child_posts_shortcode' );
