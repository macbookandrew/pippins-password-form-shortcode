<?php
/**
 * Plugin Name: Pippin’s Password Form Shortcode
 * Plugin URI: https://pippinsplugins.com/change-password-form-short-code/
 * GitHub Plugin URI: https://github.com/macbookandrew/pippins-password-form-shortcode
 * Description: Provides a [password_form] shortcode that outputs a form for changing the user’s password
 * Version: 1.0
 * Author: Pippin Williamson
 * Author URI: https://pippinsplugins.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


function pippin_change_password_form() {
    global $post;

    if (is_singular()) {
       $current_url = get_permalink($post->ID);
    } else {
       $pageURL = 'http';
       if ($_SERVER["HTTPS"] == "on") $pageURL .= "s";
       $pageURL .= "://";
       if ($_SERVER["SERVER_PORT"] != "80") $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
       else $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
       $current_url = $pageURL;
    }
    $redirect = $current_url;

    ob_start();

        // show any error messages after form submission
        pippin_show_error_messages(); ?>

        <?php if(isset($_GET['password-reset']) && $_GET['password-reset'] == 'true') { ?>
            <div class="pippin_message success">
                <span><?php _e('Password changed successfully', 'rcp'); ?></span>
            </div>
        <?php } ?>
        <form id="pippin_password_form" method="POST" action="<?php echo $current_url; ?>">
            <fieldset>
                <p>
                    <label for="pippin_user_pass"><?php _e('New Password', 'rcp'); ?></label>
                    <input name="pippin_user_pass" id="pippin_user_pass" class="required" type="password"/>
                </p>
                <p>
                    <label for="pippin_user_pass_confirm"><?php _e('Password Confirm', 'rcp'); ?></label>
                    <input name="pippin_user_pass_confirm" id="pippin_user_pass_confirm" class="required" type="password"/>
                </p>
                <p>
                    <input type="hidden" name="pippin_action" value="reset-password"/>
                    <input type="hidden" name="pippin_redirect" value="<?php echo $redirect; ?>"/>
                    <input type="hidden" name="pippin_password_nonce" value="<?php echo wp_create_nonce('rcp-password-nonce'); ?>"/>
                    <input id="pippin_password_submit" type="submit" value="<?php _e('Change Password', 'pippin'); ?>"/>
                </p>
            </fieldset>
        </form>
    <?php
    return ob_get_clean();
}

// password reset form
function pippin_reset_password_form() {
    if(is_user_logged_in()) {
        return pippin_change_password_form();
    }
}
add_shortcode('password_form', 'pippin_reset_password_form');


function pippin_reset_password() {
    // reset a users password
    if(isset($_POST['pippin_action']) && $_POST['pippin_action'] == 'reset-password') {

        global $user_ID;

        if(!is_user_logged_in())
            return;

        if(wp_verify_nonce($_POST['pippin_password_nonce'], 'rcp-password-nonce')) {

            if($_POST['pippin_user_pass'] == '' || $_POST['pippin_user_pass_confirm'] == '') {
                // password(s) field empty
                pippin_errors()->add('password_empty', __('Please enter a password, and confirm it', 'pippin'));
            }
            if($_POST['pippin_user_pass'] != $_POST['pippin_user_pass_confirm']) {
                // passwords do not match
                pippin_errors()->add('password_mismatch', __('Passwords do not match', 'pippin'));
            }

            // retrieve all error messages, if any
            $errors = pippin_errors()->get_error_messages();

            if(empty($errors)) {
                // change the password here
                $user_data = array(
                    'ID' => $user_ID,
                    'user_pass' => $_POST['pippin_user_pass']
                );
                wp_update_user($user_data);
                // send password change email here (if WP doesn't)
                wp_redirect(add_query_arg('password-reset', 'true', $_POST['pippin_redirect']));
                exit;
            }
        }
    }
}
add_action('init', 'pippin_reset_password');

if(!function_exists('pippin_show_error_messages')) {
    // displays error messages from form submissions
    function pippin_show_error_messages() {
        if($codes = pippin_errors()->get_error_codes()) {
            echo '<div class="pippin_message error">';
                // Loop error codes and display errors
               foreach($codes as $code){
                    $message = pippin_errors()->get_error_message($code);
                    echo '<span class="pippin_error"><strong>' . __('Error', 'rcp') . '</strong>: ' . $message . '</span><br/>';
                }
            echo '</div>';
        }
    }
}

if(!function_exists('pippin_errors')) {
    // used for tracking error messages
    function pippin_errors(){
        static $wp_error; // Will hold global variable safely
        return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
    }
}
