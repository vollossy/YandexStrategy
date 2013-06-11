<?php

class YandexStrategy extends OpauthStrategy{
    public $expects = array('app_id', 'app_secret');

    public $defaults = array(
        'redirect_uri' => '{complete_url_to_strategy}oauth2callback',
        'expires'      => 10000000
    );

    public function request()
    {
        $url = "https://oauth.yandex.ru/authorize";
        $params = array(
            'client_id' => $this->strategy['app_id'],
            'response_type' => 'code'
        );

        $this->clientGet($url,$params);
    }

    public function oauth2callback()
    {
        if(isset($_GET['code'])){
            $params = array(
                'code' => $_GET['code'],
                'client_id' => $this->strategy['app_id'],
                'client_secret' => $this->strategy['app_secret'],
                'grant_type' => 'authorization_code'
            );

            $response = $this->serverPost('https://oauth.yandex.ru/token', $params);
            $result= json_decode($response);
            $userInfo = $this->userInfo($result->access_token);

            $this->auth = array(
                'uid' => $userInfo['id'],
                'info' => array(),
                'credentials' => array(
                    'token' => $result->access_token
                ),
                'raw' => $userInfo
            );
            
            if($this->strategy['expires'])
            {
                $this->auth['credentials']['expires'] = date('c', time() + $this->strategy['expires']);
            }

            $this->mapProfile($userInfo, 'real_name', 'info.name');
            $this->mapProfile($userInfo, 'default_email', 'info.email');

            $this->callback();
        }
    }

    protected function userInfo($accessToken)
    {
        $url = 'https://login.yandex.ru/info';
        $userInfo = $this->serverGet($url, array('oauth_token' => $accessToken));
        return $this->recursiveGetObjectVars(json_decode($userInfo));

    }
}
