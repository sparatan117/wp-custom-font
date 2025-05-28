<?php
/**
 * Plugin Name: Custom Font Uploader
 * Plugin URI: 
 * Description: Allows users to upload custom fonts through the WordPress general settings page.
 * Version: 1.0.0
 * Author: Austin Ross
 * Author URI: https://rossworks.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-font-uploader
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CFU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CFU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CFU_FONTS_DIR', CFU_PLUGIN_DIR . 'fonts/');
define('CFU_FONTS_URL', CFU_PLUGIN_URL . 'fonts/');

// Create fonts directory if it doesn't exist
if (!file_exists(CFU_FONTS_DIR)) {
    wp_mkdir_p(CFU_FONTS_DIR);
}

// Add settings link to plugins page
function cfu_add_settings_link($links) {
    $settings_link = '<a href="options-general.php#custom-font-uploader">' . __('Settings', 'custom-font-uploader') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cfu_add_settings_link');

// Add settings section to General Settings page
function cfu_add_settings_section() {
    add_settings_section(
        'custom_font_uploader_section',
        __('Custom Font Uploader', 'custom-font-uploader'),
        'cfu_settings_section_callback',
        'general'
    );

    add_settings_field(
        'custom_font_file',
        __('Upload Custom Font', 'custom-font-uploader'),
        'cfu_font_upload_field_callback',
        'general',
        'custom_font_uploader_section'
    );

    register_setting('general', 'custom_font_file');
}
add_action('admin_init', 'cfu_add_settings_section');

// Settings section description
function cfu_settings_section_callback() {
    echo '<p>' . __('Upload your custom font files here. Supported formats: .woff, .woff2, .ttf, .otf', 'custom-font-uploader') . '</p>';
}

// Font upload field callback
function cfu_font_upload_field_callback() {
    $uploaded_fonts = get_option('custom_font_file', array());
    ?>
    <div class="custom-font-uploader">
        <input type="file" name="custom_font_file" id="custom_font_file" accept=".woff,.woff2,.ttf,.otf" />
        <p class="description"><?php _e('Select a font file to upload', 'custom-font-uploader'); ?></p>
        
        <?php if (!empty($uploaded_fonts)) : ?>
            <h3><?php _e('Uploaded Fonts', 'custom-font-uploader'); ?></h3>
            <ul class="uploaded-fonts">
                <?php foreach ($uploaded_fonts as $font) : ?>
                    <li>
                        <?php echo esc_html($font['name']); ?>
                        <button type="button" class="button delete-font" data-font="<?php echo esc_attr($font['file']); ?>">
                            <?php _e('Delete', 'custom-font-uploader'); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
}

// Enqueue admin scripts and styles
function cfu_enqueue_admin_scripts($hook) {
    if ('options-general.php' !== $hook) {
        return;
    }

    wp_enqueue_style('custom-font-uploader-admin', CFU_PLUGIN_URL . 'css/admin.css', array(), '1.0.0');
    wp_enqueue_script('custom-font-uploader-admin', CFU_PLUGIN_URL . 'js/admin.js', array('jquery'), '1.0.0', true);
    
    wp_localize_script('custom-font-uploader-admin', 'cfuAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('custom-font-uploader-nonce')
    ));
}
add_action('admin_enqueue_scripts', 'cfu_enqueue_admin_scripts');

// Handle font upload
function cfu_handle_font_upload() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (!isset($_FILES['custom_font_file'])) {
        return;
    }

    $file = $_FILES['custom_font_file'];
    $allowed_types = array('woff', 'woff2', 'ttf', 'otf');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
        add_settings_error(
            'custom_font_file',
            'invalid_file_type',
            __('Invalid file type. Please upload .woff, .woff2, .ttf, or .otf files only.', 'custom-font-uploader')
        );
        return;
    }

    $uploaded_fonts = get_option('custom_font_file', array());
    $font_name = pathinfo($file['name'], PATHINFO_FILENAME);
    $new_filename = sanitize_file_name($font_name . '.' . $file_ext);
    $destination = CFU_FONTS_DIR . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $uploaded_fonts[] = array(
            'name' => $font_name,
            'file' => $new_filename
        );
        update_option('custom_font_file', $uploaded_fonts);
        add_settings_error(
            'custom_font_file',
            'font_uploaded',
            __('Font uploaded successfully.', 'custom-font-uploader'),
            'success'
        );
    } else {
        add_settings_error(
            'custom_font_file',
            'upload_failed',
            __('Failed to upload font file.', 'custom-font-uploader')
        );
    }
}
add_action('admin_init', 'cfu_handle_font_upload');

// Handle font deletion via AJAX
function cfu_delete_font() {
    check_ajax_referer('custom-font-uploader-nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $font_file = sanitize_file_name($_POST['font']);
    $uploaded_fonts = get_option('custom_font_file', array());
    
    foreach ($uploaded_fonts as $key => $font) {
        if ($font['file'] === $font_file) {
            $file_path = CFU_FONTS_DIR . $font_file;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            unset($uploaded_fonts[$key]);
            break;
        }
    }

    update_option('custom_font_file', array_values($uploaded_fonts));
    wp_send_json_success();
}
add_action('wp_ajax_cfu_delete_font', 'cfu_delete_font');

// Add font-face declarations to the site
function cfu_add_font_face_declarations() {
    $uploaded_fonts = get_option('custom_font_file', array());
    if (empty($uploaded_fonts)) {
        return;
    }

    $css = '<style type="text/css">';
    foreach ($uploaded_fonts as $font) {
        $font_url = CFU_FONTS_URL . $font['file'];
        $font_name = $font['name'];
        $file_ext = pathinfo($font['file'], PATHINFO_EXTENSION);
        
        $css .= "@font-face {";
        $css .= "font-family: '{$font_name}';";
        $css .= "src: url('{$font_url}') format('{$file_ext}');";
        $css .= "font-weight: normal;";
        $css .= "font-style: normal;";
        $css .= "}";
    }
    $css .= '</style>';

    echo $css;
}
add_action('wp_head', 'cfu_add_font_face_declarations'); 