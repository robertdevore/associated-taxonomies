<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    wp_die();
}

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