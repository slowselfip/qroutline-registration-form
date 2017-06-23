<?php
/**
 * @package QRoutline
 * @version 0.1
 */
/*
Plugin Name: QRoutline Wordpress Plugin
Description: The plugin is used to add Firebase Cloud Message push notification capabilities to a form field.
Author: Anders Tiger
Version: 0.1
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
use \Firebase\JWT\JWT;

/**
* Will check if the current user is logged in and if so generate a JWT. The token is used to validate messages
* sent by the client app to the message service.
* @return A JWT with the current logged in user id as payload.
*/
function getJWT() {
  if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $iv = openssl_random_pseudo_bytes(16);
    $key = QROUTLINE_KEY ? QROUTLINE_KEY : false;
    $tokenId = base64_encode($iv);
    $issuedAt = time();
    $data = array(
      'jti' => $tokenId,
      'iss' => 'http://infinigra.se',
      'iat' => $issuedAt,
      'data' => array(
        'userId' => $user->get('id')
      )
    );
    return JWT::encode($data, $key, 'HS512');
  }

  return '';
}

$manifest_url = plugins_dir_url(__FILE__).'manifest/manifest.json';

function add_manifest() {
  ?>
    <link rel="manifest" href="<?=$manifest_url?>">
  <?php
}

// Includes manifest file used to allow fcm to send push notifications to web app.
add_action('wp_head', 'add_manifest');

// Register and enqueue the jquery qrcode in the page, this is later used to present the firebase id token and jwt to the mobile registration client app.
wp_register_script( 'jquery.qrcode', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.qrcode/1.0/jquery.qrcode.min.js', array('jquery'), 1.0, false);
wp_enqueue_script('jquery.qrcode');

// Register fcm dependencies.
// TODO: Replace hardcoded url base 'https://www.gstatic.com/firebasejs/3.7.1/' with plugin option
wp_register('firebase-app', 'https://www.gstatic.com/firebasejs/3.7.1/firebase-app.js');
wp_register('firebase-messaging', 'https://www.gstatic.com/firebasejs/3.7.1/firebase-messaging.js');
wp_enqueue_script('firebase-app');
wp_enqueue_script('firebase-massaging', array('firebase-app'));

// Register with firebase through javascript.
wp_register_script('qroutline-fcm', plugins_dir_url(__FILE__).'js/qroutline-fcm.js', array('jquery.qrcode','firebase-messaging'));
wp_enqueue_script('qroutline-fcm');

$jwt = getJWT();

// Add jwt to fcm script
// TODO: Add message service url as an option and add that value as a localized variable right now it will have to be hard coded in javascript file.
wp_localize_script('qroutline-fcm', 'qroutlineCtx', array('jwt' => $jwt));

?>