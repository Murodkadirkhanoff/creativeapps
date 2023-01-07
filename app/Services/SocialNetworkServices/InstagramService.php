<?php


namespace App\Services\SocialNetworkServices;


use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

class InstagramService
{
    public function publishContent()
    {
        session_start();

        define('FACEBOOK_APP_ID', '644631963731915');
        define('FACEBOOK_APP_SECRET', 'dfbb6e1d52255c8c5ce5ce6b3245c51e');
        define('ENDPOINT_BASE', 'https://graph.facebook.com/v15.0/');

        // accessToken
        $accessToken = 'EAAJKShM1h8sBADoRNtFMGwez5MWyBae6jOznwSqRK6CFuLdrzJZBV0SHS0xWiyBZAsi48qv1nEqKtcU6OBnF6DE3doQHuhBShzqqe5jlkfdSlPJQGRIxpMHOPwQyy3ufaAW1uME6IkOEAAO78bkEFeKIF90gczzHhOX7Ftm6JGpWaHM8jZAe0BD4LjX9uiZA5uXCpRzd9dYdr03Yzy9Eb5PaAKer5bQIl2tibGICvhefAU6ehmyv';
        $accessToken = '644631963731915|3HmTKpxm77U7_MA7ecNArhQ3yYU';

        // page id
        $pageId = '101952039378381';

        // instagram business account id
        $instagramAccountId = '17841401733631544';

        // endpoint formats
        $imagesEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/media?image_url={image-url}&caption={caption}&access_token={access-token}';
        $videoEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/media?video_url={video-url}&media_type&caption={caption}&access_token={access-token}';
        $publishMediaEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/media_publish?creation_id={creation-id}&access_token={access-token}';
        $userApiLimitEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-user-id}/content_publishing_limit';
        $mediaObejctStatusEndpointFormat = 'https://graph.facebook.com/v5.0/{ig-container-id}?fields=status_code';

        /***
         * IMAGE
         */
        $imageMediaObjectEndpoint = ENDPOINT_BASE . $instagramAccountId . '/media';
        $imageMediaObjectEndpointParams = array( // POST
            'image_url' => 'http://justinstolpe.com/sandbox/ig_publish_content_img.png',
            'caption' => 'This image was posted through the Instagram Graph API with a script I wrote! Go check out the video tutorial on my YouTube channel.
			.
			youtube.com/justinstolpe
			.
			#instagram #graphapi #instagramgraphapi #code #coding #programming #php #api #webdeveloper #codinglife #developer #coder #tech #developerlife #webdev #youtube #instgramgraphapi
		',
            'access_token' => $accessToken
        );
        $imageMediaObjectResponseArray = $this->makeApiCall($imageMediaObjectEndpoint, 'POST', $imageMediaObjectEndpointParams);

        // set status to in progress
        $imageMediaObjectStatusCode = 'IN_PROGRESS';

        while ($imageMediaObjectStatusCode != 'FINISHED') { // keep checking media object until it is ready for publishing
            $imageMediaObjectStatusEndpoint = ENDPOINT_BASE . $imageMediaObjectResponseArray['id'];
            $imageMediaObjectStatusEndpointParams = array( // endpoint params
                'fields' => 'status_code',
                'access_token' => $accessToken
            );
            $imageMediaObjectResponseArray = $this->makeApiCall($imageMediaObjectStatusEndpoint, 'GET', $imageMediaObjectStatusEndpointParams);
            $imageMediaObjectStatusCode = $imageMediaObjectResponseArray['status_code'];
            sleep(5);
        }

        // publish image
        $imageMediaObjectId = $imageMediaObjectResponseArray['id'];
        $publishImageEndpoint = ENDPOINT_BASE . $instagramAccountId . '/media_publish';
        $publishEndpointParams = array(
            'creation_id' => $imageMediaObjectId,
            'access_token' => $accessToken
        );
        $publishImageResponseArray = $this->makeApiCall($publishImageEndpoint, 'POST', $publishEndpointParams);

        /***
         * API LIMIT
         */
        // check user api limit
        $limitEndpoint = ENDPOINT_BASE . $instagramAccountId . '/content_publishing_limit';
        $limitEndpointParams = array( // get params
            'fields' => 'config,quota_usage',
            'access_token' => $accessToken
        );
        $limitResponseArray = $this->makeApiCall($limitEndpoint, 'GET', $limitEndpointParams);

        return $limitResponseArray;
    }

    private function makeApiCall($endpoint, $type, $params)
    {
        $ch = curl_init();

        if ('POST' == $type) {
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ('GET' == $type) {
            curl_setopt($ch, CURLOPT_URL, $endpoint . '?' . http_build_query($params));
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getAccessToken()
    {

        session_start();

        define('FACEBOOK_APP_ID', '644631963731915');
        define('FACEBOOK_APP_SECRET', 'dfbb6e1d52255c8c5ce5ce6b3245c51e');
        define('ENDPOINT_BASE', 'https://graph.facebook.com/v15.0/');
        define('FACEBOOK_REDIRECT_URI', 'https://creativeapps.uz/get-instagram-token');

// facebook credentials array
        $creds = array(
            'app_id' => FACEBOOK_APP_ID,
            'app_secret' => FACEBOOK_APP_SECRET,
            'default_graph_version' => 'v15.0',
            'persistent_data_handler' => 'session'
        );

// create facebook object
        $facebook = new Facebook($creds);

// helper
        $helper = $facebook->getRedirectLoginHelper();

// oauth object
        $oAuth2Client = $facebook->getOAuth2Client();


        if (isset($_GET['code'])) { // get access token
            try {
                $accessToken = $helper->getAccessToken();
            } catch (FacebookResponseException $e) { // graph error
                echo 'Graph returned an error ' . $e->getMessage();
            } catch (FacebookSDKException $e) { // validation error
                echo 'Facebook SDK returned an error ' . $e->getMessage();
            }

            if (!$accessToken->isLongLived()) { // exchange short for long
                try {
                    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
                } catch (FacebookSDKException $e) {
                    echo 'Error getting long lived access token ' . $e->getMessage();
                }
            }

            echo '<pre>';
            var_dump($accessToken);

            $accessToken = (string)$accessToken;
            echo '<h1>Long Lived Access Token</h1>';
            print_r($accessToken);
        } else { // display login url
            $permissions = [
                'public_profile',
                'instagram_basic',
                'pages_show_list',
                'instagram_manage_insights',
                'instagram_manage_comments',
                'manage_pages',
                'ads_management',
                'business_management',
                'instagram_content_publish',
                'pages_read_engagement'
            ];
            $loginUrl = $helper->getLoginUrl(FACEBOOK_REDIRECT_URI, $permissions);

            echo '<a href="' . $loginUrl . '">
            Login With Facebook
        </a>';
        }
    }


}
