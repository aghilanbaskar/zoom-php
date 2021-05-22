<?php
require_once __DIR__ . '/../vendor/autoload.php';

$zoom = new ZoomLibrary\Zoom([
  'client_id' => 'your-client-i',
  'client_secret' => 'your-cient-secret',
  'redirect_uri' => 'your-redirect-url',
  'credential_path' =>  'your-json-file-path'
]);

$oAuthURL = $zoom->oAuthUrl();
echo "<a href='{$oAuthURL}'>{$oAuthURL}</a><br>";

// save oauth code
if(isset($_GET['code'])){
  $is_token_saved = $zoom->token($_GET['code']);
  print_r($is_token_saved);
}

print_r($zoom->listMeeting());
