<?php
	defined( 'ABSPATH' ) or die( 'Keep Quit' );
?>

<script type="text/template" id="tmpl-evince-deactive-feedback-dialog-<?php echo esc_attr( $slug ) ?>">
    <div class="evince-backbone-modal evince-deactive-feedback-dialog">
        <div class="evince-backbone-modal-content">
            <section class="evince-backbone-modal-main" role="main">
                <header class="evince-backbone-modal-header">
                    <h1><?php esc_html_e( 'QUICK FEEDBACK', 'woocommerce-variation-swatches' ); ?></h1>
                    <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-variation-swatches' ); ?></span>
                    </button>
                </header>
                <article>
                    <div class="evince-feedback-dialog-form-body">

                        <h2><?php esc_html_e( 'May we have a little info about why you are deactivating?', 'woocommerce-variation-swatches' ); ?></h2>

                        <form class="feedback-dialog-form" method="post" onsubmit="return false">
                            <input type="hidden" name="action" value="evince_deactivate_feedback"/>
                            <input type="hidden" name="plugin" value="<?php echo esc_attr( $slug ) ?>"/>
                            <input type="hidden" name="version" value="<?php echo esc_attr( $version ) ?>"/>
                            <div class="feedback-dialog-form-body">
								<?php foreach ( $deactivate_reasons as $reason_key => $reason ) : ?>
                                    <div class="feedback-dialog-input-wrapper">
                                        <input id="feedback-<?php echo esc_attr( $reason_key ); ?><?php echo esc_attr( $slug ) ?>" class="feedback-dialog-input" type="radio" name="reason_type" value="<?php echo esc_attr( $reason_key ); ?>"/>
                                        <label for="feedback-<?php echo esc_attr( $reason_key ); ?><?php echo esc_attr( $slug ) ?>" class="feedback-dialog-label"><?php echo $reason[ 'title' ]; ?></label>
										<?php if ( ! empty( $reason[ 'input_placeholder' ] ) ) : ?>
                                            <input value="<?php echo( isset( $reason[ 'input_value' ] ) ? $reason[ 'input_value' ] : '' ) ?>" class="feedback-text" style="display: none" disabled type="text" name="reason_text" placeholder="<?php echo esc_attr( $reason[ 'input_placeholder' ] ); ?>"/>
										<?php endif; ?>
										<?php if ( ! empty( $reason[ 'alert' ] ) ) : ?>
                                            <div class="feedback-text feedback-alert"><?php echo $reason[ 'alert' ]; ?></div>
										<?php endif; ?>
                                    </div>
								<?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                </article>
                <footer>
                    <div class="inner">
                        <div class="evince-action-button-group">
                            <button id="send-ajax" class="button button-primary feedback-dialog-form-button-send" data-defaultvalue="<?php esc_html_e( 'Send feedback &amp; Deactivate', 'woocommerce-variation-swatches' ) ?>" data-deactivating="<?php esc_html_e( 'Deactivating...', 'woocommerce-variation-swatches' ) ?>"><?php esc_html_e( 'Send feedback &amp; Deactivate', 'woocommerce-variation-swatches' ) ?></button>
                            <span class="spinner"></span>
                        </div>

                        <a class="feedback-dialog-form-button-skip" href="{{ data.deactivate_link }}"><?php esc_html_e( 'Skip &amp; Deactivate', 'woocommerce-variation-swatches' ) ?></a>
                        <div class="clear"></div>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="evince-backbone-modal-backdrop modal-close"></div>
</script>