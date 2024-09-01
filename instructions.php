<?php
/*
Plugin Name: LSJ Memberships
Plugin URI: http://lifestylenadjoy.com
Description: Branding for the signup page and the welcome email
Author: Rob Blyth
Version: 0.1
Author URI: http://lifestyleandjoy.com
*/

// Replace the logo with css override
function lsj_member_login_logo_css() {
    ?>
        <style type="text/css">
            .login #login {
                width: 100%;
                max-width: 640px;
            }
            #login h1 a, .login h1 a {
                background-image: url("https://rwblyth.com/wp-content/uploads/2024/08/smile-logo.svg");
            height: 85px;
            width: 85px;
            background-size: 320px 85px;
            background-repeat: no-repeat;
                padding-bottom: 30px;
            }
            #login p#backtoblog {
                display: none;
            }
            #login .message, #login .notice, #login .success {
                border-left: none;
            }
        </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'lsj_member_login_logo_css' );


// Link from logo to mysite home instead of wordpress.org
function lsj_member_login_logo_url() {
    return home_url();
}

add_filter( 'login_headerurl', 'lsj_member_login_logo_url' );

// Update the hover text on the home logo
function lsj_member_login_logo_hover_text() {
    return "Go to the " . get_bloginfo() . " homepage";
}

add_filter( 'login_headertext', 'lsj_member_login_logo_hover_text');

// Change registration and other placeholder text
function lsj_member_registration_title( $message ) {
    $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
    //$errors = new WP_Error();

    if ( isset( $_GET['key'] ) ) {
        $action = 'resetpass';
    }

    if ( isset( $_GET['checkemail'] ) ) {
        $action = 'checkemail';
    }

    switch ( $action ):
        case 'register':
            $message = '<p class="message">' . __( "Hello! We are excited to have you in the Life, Style and Joy community. Just enter a username and your email, and we'll have you on board in no time.", 'text_domain' ) . '</p>';
            break;
        case 'checkemail':
            // remove the old message, add the new one
            global $errors;
            $error_codes = $errors->get_error_codes();
            if (in_array('confirm', $error_codes)) {
                // need to confirm their email address
                $errors->remove('confirm');
                $message = '<div id="login-message" class="notice notice-info message"><p>Welcome to Life, Style and Joy!</p><p>We have sent an email with your confirmation link. Please click on it to activate your account, and then go to the <a href="' . wp_login_url() . '">login page</a>.</p><p>Check your junk folder if it has not arrived in a few minutes</p></div>';
            }

            if (in_array('registered', $error_codes)) {
                // they should be registered already. Shouldn't also have 'confirm' but keep as two ifs and override message just in case
                $errors->remove('registered');
                $message = '<div id="login-message" class="notice notice-info message"><p>Welcome to Life, Style and Joy!</p><p>Please check your email for information or go to the <a href="' . wp_login_url() . '">login page</a>.</p></div>';
            }

            break;

        case 'rp':
            // select a password after password reset
            $message = '<div class="notice notice-info message reset-pass"><p>Enter your new password below, or hit "Generate Password" to generate a strong password.</p></div>';
            break;

        case 'resetpass':
            // they reset the password and should now log in
            $message = '<div class="notice notice-info message reset-pass"><p>You are ready to go! You can now <a href="https://rwblyth.com/wp-login.php">log in</a></p></div>';
            break;

        case 'lostpassword':
            // the default is fine
            // $message = '<p class="message">' . __( 'Show message before lost password form.', 'text_domain' ) . '</p>';
            break;
        default:
            // this message will show in login screen, before the login form.
            // leave it blank which is the default
            // $message = '<p class="message">' . __( 'Show message before login form.', 'text_domain' ) . '</p>';
            break;
    endswitch;

    return $message;
}

add_filter( 'login_message', 'lsj_member_registration_title' );

// // TODO Prevent lost password enumeration of signed up email addresses
// add_action( 'lost_password', 'lsj_login_prevent_lost_password_enumeration', 10, 1 );

// function lsj_login_prevent_lost_password_enumeration($errors) {

//     $http_post = 'POST' === $_SERVER['REQUEST_METHOD'];

//     if (!$http_post) return;

//     //Allow an empty message to show
//     if (empty(trim(strval($_POST['user_login'] ?? '')))) return;

//     $redirect_to = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'wp-login.php?checkemail=confirm';

//     wp_safe_redirect($redirect_to);
//     exit;

// }

// Customise the welcome email
add_filter( 'wp_new_user_notification_email', 'lsj_member_custom_new_user_notification_email', 10, 3 );

function lsj_member_custom_new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {

    $wp_new_user_notification_email['subject'] = "Welcome To Life, Style and Joy!";

    $key = get_password_reset_key( $user );
    $message = sprintf(__('You are almost there.')) . "\r\n\r\n";
    $message .= 'Please confirm your email address and set a password at: ' . "\r\n\r\n";
    $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "\r\n\r\n";
    $message .= "Kind regards," . "\r\n";
    $message .= "Life, Style and Joy Team" . "\r\n";
    $wp_new_user_notification_email['message'] = $message;

    // TODO: change the sender details properly, need to update SMPTOGO
    $site_url = site_url();
    $url = preg_replace("(^https?://)", "", $site_url );
    $wp_new_user_notification_email['headers'] = 'From: Life Style And Joy <welcome@' . $url . '>';

    return $wp_new_user_notification_email;
}

// Permission to see the dashboard / admin bar
function lsj_member_can_see_admin_area($user = null) {
    if (!$user) {
        $user = wp_get_current_user();
    }

    $allowed_roles = array( 'administrator', 'editor', 'author', 'contributor' );

    return $user && array_intersect( $allowed_roles, $user->roles );
}

function lsj_member_restrict_admin_area() {
    if ( !lsj_member_can_see_admin_area() && ( !wp_doing_ajax() ) ) {
		wp_safe_redirect( site_url() ); 
		exit;
	}
}

// TODO: redirect immediately upon login, this breaks for admins
// function lsj_member_redirect_login($url, $request, $user) {
//     if (lsj_member_can_see_admin_area($user)) {
//         $url = admin_url('index.php');
//     } else {
//         $url = site_url();
//     }
// }

add_filter( 'show_admin_bar' , 'lsj_member_can_see_admin_area' );
add_action( 'admin_init', 'lsj_member_restrict_admin_area', 1 );
// add_filter('login_redirect', 'lsj_member_redirect_login', 10, 3);

?>

