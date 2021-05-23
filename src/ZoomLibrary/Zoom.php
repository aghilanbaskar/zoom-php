<?php
namespace ZoomLibrary;

class Zoom
{
    protected $CLIENT_ID;
    protected $CLIENT_SECRET;
    protected $REDIRECT_URI;
    protected $CLIENT;
    protected $CREDENTIAL_PATH;
    protected $CREDENTIAL_DATA;

    public function __construct(array $config = [])
    {
      $this->CLIENT_ID = isset($config['client_id'])?$config['client_id']:NULL;
      $this->CLIENT_SECRET = isset($config['client_secret'])?$config['client_secret']:NULL;
      $this->REDIRECT_URI = isset($config['redirect_uri'])?$config['redirect_uri']:NULL;
      $this->CLIENT = new \GuzzleHttp\Client(['base_uri' => 'https://api.zoom.us']);
      $this->CREDENTIAL_PATH = isset($config['credential_path'])?$config['credential_path']:NULL;
      $this->CREDENTIAL_DATA = json_decode(file_get_contents($this->CREDENTIAL_PATH), true);
    }

    public function oAuthUrl()
    {
        return "https://zoom.us/oauth/authorize?response_type=code&client_id={$this->CLIENT_ID}&redirect_uri={$this->REDIRECT_URI}";
    }

    public function token($code)
    {
      $response = $this->CLIENT->request('POST', '/oauth/token', [
          "headers" => [
              "Authorization" => "Basic ". base64_encode($this->CLIENT_ID.':'.$this->CLIENT_SECRET)
          ],
          'form_params' => [
              "grant_type" => "authorization_code",
              "code" => $code,
              "redirect_uri" => $this->REDIRECT_URI
          ],
      ]);
      $response_token =json_decode($response->getBody()->getContents(), true);
      $token = json_encode($response_token);
      file_put_contents($this->CREDENTIAL_PATH, $token);
      if (!file_exists($this->CREDENTIAL_PATH)) {
        return ['status' => false, 'message' => 'Error while saving file'];
      }
      $savedToken = json_decode(file_get_contents($this->CREDENTIAL_PATH), true); //getting json from saved json file
      if (!empty(array_diff($savedToken,$response_token))) { // checking reponse token and saved tokends are same
        return ['status' => false, 'message' => 'Error in saved token'];
      }
      return ['status' => true, 'message' => 'Token saved successfully'];
    }

    public function refreshToken()
    {
      try {
        $response = $this->CLIENT->request('POST', '/oauth/token', [
            "headers" => [
                "Authorization" => "Basic ".base64_encode($this->CLIENT_ID.':'.$this->CLIENT_SECRET)
            ],
            'form_params' => [
                "grant_type" => "refresh_token",
                "refresh_token" => $this->CREDENTIAL_DATA['refresh_token']
            ],
        ]);
        $response_token =json_decode($response->getBody()->getContents(), true);
        $token = json_encode($response_token);
        file_put_contents($this->CREDENTIAL_PATH, $token);
        if (!file_exists($this->CREDENTIAL_PATH)) {
          throw new Exception("Token file not exist");
        }
        $savedToken = json_decode(file_get_contents($this->CREDENTIAL_PATH), true); //getting json from saved json file
        if (!empty(array_diff($savedToken,$response_token))) { // checking reponse token and saved tokends are same
          throw new Exception("Token refreshed successfully But error in saved json token");
        }
        return true;
      } catch (\Exception $e) {
        echo 'Failed during refresh token '.$e->getMessage();
        return false;
      }
    }


    // https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetings
    public function listMeeting($user_id = 'me', $query=[])
    {
      try {
        $response = $this->CLIENT->request('GET', "/v2/users/{$user_id}/meetings", [
            "headers" => [
                "Authorization" => "Bearer ".$this->CREDENTIAL_DATA['access_token']
            ],
            'query' => $query
        ]);

        return array('status' => true, 'data' => json_decode($response->getBody(), true));
      } catch (\Exception $e) {
        if( $e->getCode() == 401 && $this->refreshToken()) {
          return $this->listMeeting($user_id, $query);
        } else {
          return array('status' => false, 'message' => $e->getMessage());
        }
      }
    }

  public function createMeeting($user_id = 'me',$json = [])
  {
    try {
      $response = $this->CLIENT->request('POST', "/v2/users/{$user_id}/meetings", [
          "headers" => [
              "Authorization" => "Bearer ".$this->CREDENTIAL_DATA['access_token']
          ],
          'json' => $json
      ]);

      if ($response->getStatusCode() == 201) {
        return array('status' => true, 'data' => json_decode($response->getBody(), true));
      }

      throw new Exception("Not able to find error");
    } catch (\Exception $e) {
      if( $e->getCode() == 401 && $this->refreshToken()) {
        return $this->createMeeting($user_id, $json);
      }
      if ($e->getCode() == 300) {
        return array('status' => false, 'message' => 'Invalid enforce_login_domains, separate multiple domains by semicolon. A maximum of {rateLimitNumber} meetings can be created/updated for a single user in one day.');
      }
      if ($e->getCode() == 404) {
        return array('status' => false, 'message' => 'User {userId} not exist or not belong to this account.');
      }
      if( $e->getCode() != 401 ) {
        return array('status' => false, 'message' => $e->getMessage());
      }
      return array('status' => false, 'message' => 'Not able to refresh token');
    }
  }

  public function deleteMeeting($meeting_id = '', $query = [])
  {
    try {
      $response = $this->CLIENT->request('DELETE', "/v2/meetings/{$meeting_id}", [
          "headers" => [
              "Authorization" => "Bearer ".$this->CREDENTIAL_DATA['access_token']
          ],
          'query' => $query
      ]);

      if ($response->getStatusCode() == 204) {
        return array('status' => true, 'message' => 'Meeting deleted.');
      }
      throw new Exception("Not able to find error");
    } catch (\Exception $e) {
      if( $e->getCode() == 401 && $this->refreshToken()) {
        return $this->deleteMeeting($meeting_id, $query);
      }
      if ($e->getCode() == 400) {
        return array('status' => false, 'message' => 'User does not belong to this account or dont have access');
      }
      if ($e->getCode() == 404) {
        return array('status' => false, 'message' => 'Meeting with this {meetingId} is not found or has expired.');
      }
      if( $e->getCode() != 401 ) {
        return array('status' => false, 'message' => $e->getMessage());
      }
      return array('status' => false, 'message' => 'Not able to refresh token');
    }
  }


  public function addMeetingRegistrant($meeting_id = '', $json = [])
  {
    try {
      $response = $this->CLIENT->request('POST', "/v2/meetings/{$meeting_id}/registrants", [
          "headers" => [
              "Authorization" => "Bearer ".$this->CREDENTIAL_DATA['access_token']
          ],
          'json' => $json
      ]);

      if ($response->getStatusCode() == 201) {
        return array('status' => true, 'message' => 'Registration successfull', 'data' => json_decode($response->getBody(), true) );
      }

      throw new Exception("Not able to find error");
    }
    catch (\Exception $e) {
      if( $e->getCode() == 401 && $this->refreshToken() ) {
        return $this->addMeetingRegistrant($meeting_id, $json);
      }
      if ($e->getCode() == 300) {
        return array('status' => false, 'message' => 'Meeting {meetingId} is not found or has expired.');
      }
      if ($e->getCode() == 400) {
        return array('status' => false, 'message' => 'Access error. Not have correct access. validation failed');
      }
      if ($e->getCode() == 404) {
        return array('status' => false, 'message' => 'Meeting not found or Meeting host does not exist: {userId}.');
      }
      if( $e->getCode() != 401 ) {
        return array('status' => false, 'message' => $e->getMessage());
      }
      return array('status' => false, 'message' => 'Not able to refresh token');
    }
  }
}

?>
