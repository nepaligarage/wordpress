<?php
/**
 * Renders the vehicle comparison table HTML.
 *
 * @package NepaliGarage
 */

defined( 'ABSPATH' ) || exit;

class NG_Comparison_Renderer {

    // Confidence-level → CSS modifier mapping.
    private const BADGE_MAP = [
        'official'   => 'official',
        'estimated'  => 'estimated',
        'unverified' => 'unverified',
    ];

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Render a full comparison table for the provided variants data.
     *
     * @param  array<int, array<string, mixed>> $variants_data  Raw Supabase rows.
     * @return string HTML string ready for output.
     */
    public function render( array $variants_data ): string {
        if ( count( $variants_data ) < 2 ) {
            return '<p class="ng-error">' . esc_html__( 'Not enough variants to compare.', 'nepaligarage' ) . '</p>';
        }

        $a = $variants_data[0];
        $b = $variants_data[1];

        $specs_a = $this->index_specs( $a['variant_specs'] ?? [] );
        $specs_b = $this->index_specs( $b['variant_specs'] ?? [] );

        $categories = $this->collect_categories( $specs_a, $specs_b );

        ob_start();
        include NG_PLUGIN_DIR . 'templates/comparison-table.php';
        return (string) ob_get_clean();
    }

    // -----------------------------------------------------------------------
    // Helpers used by the template
    // -----------------------------------------------------------------------

    /**
     * Build a lookup: spec_field.key → spec row.
     *
     * @param  array<int, array<string, mixed>> $specs
     * @return array<string, array<string, mixed>>
     */
    public function index_specs( array $specs ): array {
        $index = [];
        foreach ( $specs as $spec ) {
            $key = $spec['spec_field']['key'] ?? null;
            if ( $key ) {
                $index[ $key ] = $spec;
            }
        }
        return $index;
    }

    /**
     * Collect all spec categories present in either variant.
     *
     * @param  array<string, mixed> $specs_a
     * @param  array<string, mixed> $specs_b
     * @return array<string, array<string, mixed>>  category_key → [ label, fields[] ]
     */
    public function collect_categories( array $specs_a, array $specs_b ): array {
        $categories = [];
        $all_specs  = array_merge( array_values( $specs_a ), array_values( $specs_b ) );

        foreach ( $all_specs as $spec ) {
            $field    = $spec['spec_field'] ?? [];
            $cat_key  = $field['category'] ?? 'general';
            $cat_label = $field['category_label'] ?? ucfirst( $cat_key );
            $field_key = $field['key'] ?? null;

            if ( ! $field_key ) {
                continue;
            }

            if ( ! isset( $categories[ $cat_key ] ) ) {
                $categories[ $cat_key ] = [
                    'label'  => $cat_label,
                    'fields' => [],
                ];
            }

            // Avoid duplicate field keys per category.
            $already = array_column( $categories[ $cat_key ]['fields'], 'key' );
            if ( ! in_array( $field_key, $already, true ) ) {
                $categories[ $cat_key ]['fields'][] = [
                    'key'   => $field_key,
                    'label' => $field['label'] ?? $field_key,
                    'unit'  => $field['unit'] ?? '',
                ];
            }
        }

        return $categories;
    }

    /**
     * Render a single spec cell: value + confidence badge + source tooltip.
     *
     * @param  array<string, mixed> $spec_index  Result of index_specs().
     * @param  string               $field_key
     * @return string HTML
     */
    public function render_cell( array $spec_index, string $field_key ): string {
        if ( ! isset( $spec_index[ $field_key ] ) ) {
            return '<span class="ng-cell-empty">' . esc_html__( 'N/A', 'nepaligarage' ) . '</span>';
        }

        $spec       = $spec_index[ $field_key ];
        $value      = $spec['value'] ?? '';
        $unit       = $spec['spec_field']['unit'] ?? '';
        $confidence = $spec['confidence'] ?? 'unverified';
        $source     = $spec['source']['label'] ?? '';

        $badge_mod = self::BADGE_MAP[ $confidence ] ?? 'unverified';

        $title_attr = '';
        if ( $source ) {
            $title_attr = ' title="' . esc_attr( sprintf(
                /* translators: %s: data source label */
                __( 'Source: %s', 'nepaligarage' ),
                $source
            ) ) . '"';
        }

        $value_html = esc_html( $value );
        if ( $unit ) {
            $value_html .= ' <span class="ng-unit">' . esc_html( $unit ) . '</span>';
        }

        $badge_label = ucfirst( $badge_mod );

        return sprintf(
            '<span class="ng-cell-value"%s>%s</span> <span class="ng-badge ng-badge--%s">%s</span>',
            $title_attr,
            $value_html,
            esc_attr( $badge_mod ),
            esc_html( $badge_label )
        );
    }

    /**
     * Extract a named hero spec value from the indexed specs.
     *
     * @param  array<string, mixed> $spec_index
     * @param  string               $field_key
     * @return string
     */
    public function hero_value( array $spec_index, string $field_key ): string {
        $spec  = $spec_index[ $field_key ] ?? null;
        if ( ! $spec ) {
            return esc_html__( 'N/A', 'nepaligarage' );
        }

        $value = $spec['value'] ?? '';
        $unit  = $spec['spec_field']['unit'] ?? '';

        return esc_html( $value ) . ( $unit ? ' ' . esc_html( $unit ) : '' );
    }

    /**
     * Build a display name for a variant.
     *
     * @param  array<string, mixed> $variant
     * @return string
     */
    public function variant_name( array $variant ): string {
        $brand = $variant['model']['brand']['name'] ?? '';
        $model = $variant['model']['name'] ?? '';
        $trim  = $variant['name'] ?? '';

        return esc_html( trim( "$brand $model $trim" ) );
    }
}
