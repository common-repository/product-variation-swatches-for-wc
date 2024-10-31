<?php
	defined( 'ABSPATH' ) or die( 'Keep Silent' );
?>
<style type="text/css">
    .variable-item:not(.radio-variable-item) {
        width  : <?php echo esc_attr($width) ?>px;
        height : <?php echo esc_attr($height) ?>px;
    }

    .evdpl-style-squared .button-variable-item {
        min-width : <?php echo esc_attr($width) ?>px;
    }

    .button-variable-item span {
        font-size : <?php echo esc_attr($font_size) ?>px;
    }
</style>
