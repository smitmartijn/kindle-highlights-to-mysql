<?php
/**
 *  Make this file publically available (webserver) and visit it with your browser.
 *  - You will be redirected to the Evernote website to give the app permissions on your account
 *  - After giving permission, you will be redirected back to this file and it will display an OAuth token which you need to store in config.inc.php
 **/
require __DIR__ . '/../composer/vendor/autoload.php';
require __DIR__ . '/../config.inc.php';

@session_start();

$china   = false;
$oauth_handler = new \Evernote\Auth\OauthHandler($CONFIG['evernote_sandbox'], false, $china);

$key      = $CONFIG['evernote_key'];
$secret   = $CONFIG['evernote_secret'];
$callback = $CONFIG['evernote_callback_url'];

try
{
  $oauth_data  = $oauth_handler->authorize($key, $secret, $callback);
  echo "Success! Store this token in config.inc.php in variable \$CONFIG['evernote_user_token']<br />";
  echo "Token: " . $oauth_data['oauth_token'];
}
catch (Evernote\Exception\AuthorizationDeniedException $e)
{
    // If the user decline the authorization, an exception is thrown.
    echo "Declined";
}
