# ZOOM-PHP

ZOOM-PHP is a simple Zoom API library for using oAuth Zoom API. Which handles refresh token logic in itself.

## Installation

Install easily via composer package

```bash
composer require aghilanbaskar/zoom-library
```

## Usage
initialize the library with the required credentials

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

$zoom = new ZoomLibrary\Zoom([
  'client_id' => 'your-client-id',
  'client_secret' => 'your-client-secret',
  'redirect_uri' => 'your-redirect-uri',
  'credential_path' => 'zoom-oauth-credentials.json'
]);
```

OAuth URL
```php
$oAuthURL = $zoom->oAuthUrl();
echo "<a href='{$oAuthURL}'>{$oAuthURL}</a><br>";
```

Once verification is successfull. It will redirect you to the specified callback URL with **CODE** in GET parameter.
Pass the code to the Library
```php
$zoom->token($_GET['code']);
```

## Available methods
**List Meetings:**

```php
$meetings = $zoom->listMeeting();
or
$meetings = $zoom->listMeeting($user_id, $query);

if($meetings['status'] === false){
 echo 'Request failed - Reason: '.$meetings['message'];
 return;
}
$meetingsData = $meetings['data'];
```
**Create Meetings:**

```php
$meeting = $zoom->createMeeting($user_id, $json);

if($meeting['status'] === false){
 echo 'Request failed - Reason: '.$meeting['message'];
 return;
}
$meetingData = $meeting['data'];
```
**Delete Meetings:**

```php
$meetings = $zoom->createMeeting($meeting_id, $query);

if($meetings['status'] === false){
 echo 'Request failed - Reason: '.$meetings['message'];
 return;
}
echo $meetings['message'];
```
**Add Meeting Registrant:**

```php
$meetings = $zoom->addMeetingRegistrant($meeting_id, $json);

if($meetings['status'] === false){
 echo 'Request failed - Reason: '.$meetings['message'];
 return;
}
$registrationData = $meetings['data'];
```
## Zoom Documentation:
[Zoom Documentation](https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings)
Pass required parameter as it is in Documentation

## output format
All response body is JSON decode to output as an array

All the method call will return an associative array with **status**, **data**, **message**
### status - true
The API call is a success and its response body will be available in **data**
### status - false
The API call is failed and its reason for failure will be available in **message**

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)
