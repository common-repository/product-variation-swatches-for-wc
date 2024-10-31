<?php
defined('ABSPATH') or die('Keep Silent');

/**
 * Example:
 *  new EVDPL_Customize_Heading( $wp_customize, 'section', esc_html__( 'Heading Options', 'text-domain' ) );
 */
if (!class_exists('EVDPL_Customize_Heading_Control')):

    class EVDPL_Customize_Heading_Control extends WP_Customize_Control {

        public $type = 'evdpl-heading';

        public function __construct($manager, $id, $args = array()) {
            parent::__construct($manager, $id, $args);
        }

        public function enqueue() {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_style('evdpl-customize-heading-control', woocommerce_variation_swatches()->assets_uri("/css/evdpl-customize-heading-control$suffix.css"));
        }

        protected function render_content() {
            ?>
            <?php if (!empty($this->label)) : ?>
                <h4 class="evdpl-customize-heading-control-title"><?php echo esc_html($this->label); ?></h4>
            <?php
            endif;
        }

    }
endif;

if (!class_exists('EVDPL_Customize_Heading')):

    class EVDPL_Customize_Heading {

        public function __construct($wp_customize, $section, $title, $priority = NULL) {

            static $customize_heading_control_id = 1;
            $this->add_settings($wp_customize, $customize_heading_control_id);
            $this->add_controls($wp_customize, $title, $section, $priority, $customize_heading_control_id);
            $customize_heading_control_id++;
        }

        private function add_settings($wp_customize, $id) {

            $wp_customize->add_setting(sprintf('evdpl-customize-heading-control-%d', esc_attr($id)), array(
                'sanitize_callback' => 'sanitize_key'
            ));
        }

        private function add_controls($wp_customize, $title, $section, $priority, $id) {

            $wp_customize->add_control(new EVDPL_Customize_Heading_Control($wp_customize, sprintf('evdpl-customize-heading-control-%d', esc_attr($id)), array(
                        'label' => esc_attr($title),
                        'section' => esc_attr($section),
                        'priority' => esc_attr($priority),
                    )));
        }
    }
endif;