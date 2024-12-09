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
  * Version:     1.0.0
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
define( 'ASSOCIATED_TAXONOMIES_VERSION', '1.0.0' );

// Include the Plugin Update Checker.
require 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/associated-taxonomies/',
    __FILE__,
    'associated-taxonomies'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

/**
 * Summary of Associated_Taxonomies
 */
class Associated_Taxonomies {

    public function __construct() {
        add_action( 'init', [ $this, 'register_hooks_for_taxonomies' ], 20 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
    }

    /**
     * Register hooks dynamically for all public taxonomies.
     * 
     * @since  1.0.0
     * @return void
     */
    public function register_hooks_for_taxonomies() {
        $taxonomies = apply_filters( 'associated_taxonomies_tax_list', get_taxonomies( [ 'public' => true ], 'names' ) );

        foreach ( $taxonomies as $taxonomy ) {
            add_action( "{$taxonomy}_add_form_fields", [ $this, 'add_select2_field' ] );
            add_action( "{$taxonomy}_edit_form_fields", [ $this, 'edit_select2_field' ] );
            add_action( "created_{$taxonomy}", [ $this, 'save_associated_terms' ] );
            add_action( "edited_{$taxonomy}", [ $this, 'save_associated_terms' ] );
        }
    }

    /**
     * Enqueue Select2 scripts and styles for admin taxonomy pages.
     * 
     * @since  1.0.0
     * @return void
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'term.php' === $hook || 'edit-tags.php' === $hook ) {
            wp_enqueue_script(
                'associated-taxonomies-select2-js',
                plugins_url( 'assets/js/select2.min.js', __FILE__ ),
                [ 'jquery' ],
                ASSOCIATED_TAXONOMIES_VERSION,
                true
            );

            wp_enqueue_style(
                'associated-taxonomies-select2-css',
                plugins_url( 'assets/css/select2.min.css', __FILE__ ),
                [],
                ASSOCIATED_TAXONOMIES_VERSION
            );

            wp_add_inline_script( 'associated-taxonomies-select2-js', "
                jQuery(document).ready(function($) {
                    $('.associated-taxonomies-select2').select2({
                        placeholder: 'Select associated terms...',
                        allowClear: true
                    });
                });
            " );
        }
    }

    /**
     * Enqueue frontend styles for shortcodes.
     * 
     * @since  1.0.0
     * @return void
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'associated-taxonomies-css',
            plugins_url( 'assets/css/associated-taxonomies.css', __FILE__ ),
            [],
            ASSOCIATED_TAXONOMIES_VERSION
        );
    }

    /**
     * Add Select2 field for associating terms on the "Add Term" screen.
     * 
     * @since  1.0.0
     * @return void
     */
    public function add_select2_field( $taxonomy ) {
        ?>
        <div class="form-field term-group">
            <label for="associated_terms"><?php esc_html_e( 'Associated Terms', 'associated-taxonomies' ); ?></label>
            <select name="associated_terms[]" id="associated_terms" class="associated-taxonomies-select2" multiple>
                <?php
                $terms = get_terms( [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ] );

                foreach ( $terms as $term ) {
                    echo '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e( 'Select terms to associate with this one.', 'associated-taxonomies' ); ?></p>
        </div>
        <?php
    }

    /**
     * Edit Select2 field for associating terms on the "Edit Term" screen.
     * 
     * @since  1.0.0
     * @return void
     */
    public function edit_select2_field( $term ) {
        $associated_terms = get_term_meta( $term->term_id, 'associated_terms', true );
        $associated_terms = is_array( $associated_terms ) ? $associated_terms : [];
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row"><label for="associated_terms"><?php esc_html_e( 'Associated Terms', 'associated-taxonomies' ); ?></label></th>
            <td>
                <select name="associated_terms[]" id="associated_terms" class="associated-taxonomies-select2" multiple>
                    <?php
                    $terms = get_terms( [
                        'taxonomy'   => $term->taxonomy,
                        'hide_empty' => false,
                    ] );

                    foreach ( $terms as $available_term ) {
                        if ( $available_term->term_id === $term->term_id ) {
                            continue;
                        }
                        echo '<option value="' . esc_attr( $available_term->term_id ) . '" ' . selected( in_array( $available_term->term_id, $associated_terms, true ), true, false ) . '>' . esc_html( $available_term->name ) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php esc_html_e( 'Select terms to associate with this one.', 'associated-taxonomies' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save associated terms to term metadata.
     *
     * @param int $term_id The term ID.
     * 
     * @since  1.0.0
     * @return void
     */
    public function save_associated_terms( $term_id ) {
        if ( isset( $_POST['associated_terms'] ) && is_array( $_POST['associated_terms'] ) ) {
            update_term_meta( $term_id, 'associated_terms', array_map( 'intval', $_POST['associated_terms'] ) );
        } else {
            delete_term_meta( $term_id, 'associated_terms' );
        }
    }
}

// Initialize the plugin.
new Associated_Taxonomies();

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
