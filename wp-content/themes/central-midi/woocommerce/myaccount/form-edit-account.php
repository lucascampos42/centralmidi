<?php
/**
 * Modern Edit Account Form - Central MIDI
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.0.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_edit_account_form');
?>

<form class="woocommerce-EditAccountForm edit-account cm-modern-account-form" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>

    <?php do_action('woocommerce_edit_account_form_start'); ?>

    <!-- Card: Informações Pessoais -->
    <div class="cm-account-card">
        <div class="cm-account-card-header">
            <div class="cm-account-card-icon">
                <i class="ri-user-settings-line"></i>
            </div>
            <div>
                <h3 class="cm-account-card-title"><?php esc_html_e('Informações Pessoais', 'central-midi'); ?></h3>
                <p class="cm-account-card-desc"><?php esc_html_e('Seus dados cadastrais e endereço de e-mail de entrega.', 'central-midi'); ?></p>
            </div>
        </div>

        <div class="cm-form-grid-2">
            <p class="cm-form-group">
                <label for="account_first_name">
                    <?php esc_html_e('Nome', 'woocommerce'); ?> <span class="required" aria-hidden="true">*</span>
                </label>
                <input type="text" class="woocommerce-Input input-text cm-input" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr($user->first_name); ?>" required />
            </p>

            <p class="cm-form-group">
                <label for="account_last_name">
                    <?php esc_html_e('Sobrenome', 'woocommerce'); ?> <span class="required" aria-hidden="true">*</span>
                </label>
                <input type="text" class="woocommerce-Input input-text cm-input" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr($user->last_name); ?>" required />
            </p>
        </div>

        <p class="cm-form-group">
            <label for="account_display_name">
                <?php esc_html_e('Nome de Exibição', 'woocommerce'); ?> <span class="required" aria-hidden="true">*</span>
            </label>
            <input type="text" class="woocommerce-Input input-text cm-input" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?php echo esc_attr($user->display_name); ?>" required />
            <span id="account_display_name_description" class="cm-field-hint">
                <i class="ri-information-line"></i> <?php esc_html_e('Este é o nome visível no seu painel e comunicações da loja.', 'central-midi'); ?>
            </span>
        </p>

        <p class="cm-form-group">
            <label for="account_email">
                <?php esc_html_e('Endereço de E-mail', 'woocommerce'); ?> <span class="required" aria-hidden="true">*</span>
            </label>
            <input type="email" class="woocommerce-Input input-text cm-input" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr($user->user_email); ?>" required />
            <span class="cm-field-hint">
                <i class="ri-mail-check-line"></i> <?php esc_html_e('Os arquivos MIDI e confirmações de pedidos são enviados para este e-mail.', 'central-midi'); ?>
            </span>
        </p>
    </div>

    <?php do_action('woocommerce_edit_account_form_fields'); ?>

    <!-- Card: Alterar Senha -->
    <div class="cm-account-card cm-account-card--security">
        <div class="cm-account-card-header">
            <div class="cm-account-card-icon cm-account-card-icon--security">
                <i class="ri-lock-password-line"></i>
            </div>
            <div>
                <h3 class="cm-account-card-title"><?php esc_html_e('Segurança & Alteração de Senha', 'central-midi'); ?></h3>
                <p class="cm-account-card-desc"><?php esc_html_e('Deixe os campos abaixo em branco caso não queira alterar sua senha atual.', 'central-midi'); ?></p>
            </div>
        </div>

        <p class="cm-form-group">
            <label for="password_current"><?php esc_html_e('Senha Atual', 'woocommerce'); ?></label>
            <input type="password" class="woocommerce-Input input-text cm-input" name="password_current" id="password_current" autocomplete="current-password" placeholder="Digite sua senha atual" />
        </p>

        <div class="cm-form-grid-2">
            <p class="cm-form-group">
                <label for="password_1"><?php esc_html_e('Nova Senha', 'woocommerce'); ?></label>
                <input type="password" class="woocommerce-Input input-text cm-input" name="password_1" id="password_1" autocomplete="new-password" placeholder="Mínimo de 8 caracteres" />
            </p>

            <p class="cm-form-group">
                <label for="password_2"><?php esc_html_e('Confirmar Nova Senha', 'woocommerce'); ?></label>
                <input type="password" class="woocommerce-Input input-text cm-input" name="password_2" id="password_2" autocomplete="new-password" placeholder="Repita a nova senha" />
            </p>
        </div>
    </div>

    <?php do_action('woocommerce_edit_account_form'); ?>

    <div class="cm-account-form-actions">
        <?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
        <button type="submit" class="woocommerce-Button button cm-btn cm-btn-primary cm-save-account-btn" name="save_account_details" value="<?php esc_attr_e('Salvar alterações', 'woocommerce'); ?>">
            <i class="ri-save-3-line"></i> <?php esc_html_e('Salvar Alterações', 'central-midi'); ?>
        </button>
        <input type="hidden" name="action" value="save_account_details" />
    </div>

    <?php do_action('woocommerce_edit_account_form_end'); ?>
</form>

<?php do_action('woocommerce_after_edit_account_form'); ?>
