<?php
/**
 * Plugin Name: Socialura Demo Contact Form
 * Description: Local contact form replacement for WPForms on the VPS clone. Stores submissions locally; no SMTP/API required.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function socialura_contact_form_shortcode() {
    $sent = isset($_GET['contact_sent']) && $_GET['contact_sent'] === '1';
    $error = isset($_GET['contact_error']) && $_GET['contact_error'] === '1';
    ob_start();
    ?>
    <style>
      .socialura-contact-wrap{max-width:760px;margin:28px 0;padding:28px;background:#fff;border-radius:22px;box-shadow:0 14px 45px rgba(15,23,42,.08);border:1px solid rgba(15,23,42,.06)}
      .socialura-contact-title{font-size:26px;font-weight:800;margin:0 0 8px;color:#1e293b}.socialura-contact-subtitle{margin:0 0 22px;color:#64748b}.socialura-contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.socialura-contact-field{margin-bottom:16px}.socialura-contact-field label{display:block;font-weight:700;margin-bottom:7px;color:#334155}.socialura-contact-field input,.socialura-contact-field textarea{width:100%;border:1px solid #d8dee8!important;border-radius:12px!important;padding:13px 15px!important;background:#fff!important;color:#111827!important;box-shadow:none!important;height:auto!important}.socialura-contact-field textarea{min-height:150px;resize:vertical}.socialura-contact-button{border:0!important;border-radius:999px!important;padding:14px 26px!important;color:#fff!important;font-weight:800!important;cursor:pointer;background:linear-gradient(90deg,#ff6b35,#ee2a7b,#6228d7)!important;box-shadow:none!important}.socialura-contact-alert{padding:13px 15px;border-radius:12px;margin-bottom:18px;font-weight:700}.socialura-contact-alert.success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.socialura-contact-alert.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}@media(max-width:640px){.socialura-contact-grid{grid-template-columns:1fr}.socialura-contact-wrap{padding:22px 16px}}
    </style>
    <div class="socialura-contact-wrap">
      <h2 class="socialura-contact-title">Contactez-nous</h2>
      <p class="socialura-contact-subtitle">Envoyez-nous votre message. Sur cette démo, les messages sont enregistrés localement sur le serveur.</p>
      <?php if ( $sent ) : ?><div class="socialura-contact-alert success">Merci, votre message a bien été envoyé en mode démo.</div><?php endif; ?>
      <?php if ( $error ) : ?><div class="socialura-contact-alert error">Veuillez remplir les champs obligatoires.</div><?php endif; ?>
      <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="socialura_contact_submit">
        <?php wp_nonce_field('socialura_contact_submit', 'socialura_contact_nonce'); ?>
        <div class="socialura-contact-grid">
          <div class="socialura-contact-field"><label for="scf_name">Nom *</label><input id="scf_name" name="name" type="text" required placeholder="Votre nom"></div>
          <div class="socialura-contact-field"><label for="scf_email">Email *</label><input id="scf_email" name="email" type="email" required placeholder="vous@example.com"></div>
        </div>
        <div class="socialura-contact-field"><label for="scf_subject">Sujet</label><input id="scf_subject" name="subject" type="text" placeholder="Sujet de votre demande"></div>
        <div class="socialura-contact-field"><label for="scf_message">Message *</label><textarea id="scf_message" name="message" required placeholder="Comment pouvons-nous vous aider ?"></textarea></div>
        <button class="socialura-contact-button" type="submit">Envoyer le message</button>
      </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('socialura_contact_form', 'socialura_contact_form_shortcode');

function socialura_contact_submit_handler() {
    $back = wp_get_referer() ?: home_url('/contact/');
    if ( ! isset($_POST['socialura_contact_nonce']) || ! wp_verify_nonce($_POST['socialura_contact_nonce'], 'socialura_contact_submit') ) {
        wp_safe_redirect(add_query_arg('contact_error','1',$back)); exit;
    }
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    if ( $name === '' || $email === '' || $message === '' || ! is_email($email) ) {
        wp_safe_redirect(add_query_arg('contact_error','1',$back)); exit;
    }
    $upload = wp_upload_dir();
    $dir = trailingslashit($upload['basedir']) . 'socialura-contact-submissions';
    if ( ! file_exists($dir) ) { wp_mkdir_p($dir); }
    $entry = array(
        'created_at' => current_time('mysql'),
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
    );
    file_put_contents(trailingslashit($dir).'submissions.jsonl', wp_json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    wp_safe_redirect(add_query_arg('contact_sent','1', remove_query_arg('contact_error',$back))); exit;
}
add_action('admin_post_socialura_contact_submit', 'socialura_contact_submit_handler');
add_action('admin_post_nopriv_socialura_contact_submit', 'socialura_contact_submit_handler');
