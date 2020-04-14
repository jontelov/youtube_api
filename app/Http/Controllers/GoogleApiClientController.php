<?php

namespace App\Http\Controllers;

use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_YouTube;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class GoogleApiClientController extends Controller
{
    /**
     * Perform Authentication
     */
    public function getAuthGoogleApi()
    {
        $client = new Google_Client();

        $client->setApplicationName('Autopost Telegram Bot');

        $client->setScopes(['https://www.googleapis.com/auth/youtube.readonly',]);

        //$client->setAuthConfig(env('CLIENT_SECRET')); 

        $client->setClientId(env('CLIENT_ID'));

        $client->setClientSecret(env('CLIENT_SECRET_PASS'));

        $client->setDeveloperKey(env('YOUTUBE_API_KEY', 'YOUR_API_KEY'));

        $client->setAccessType('offline');

        $client->setLoginHint(env('LOGIN_HINT'));

        $fileExists = Storage::disk('private')->exists(env('TOKEN_FILE'));

        //Callback URL
        // if (env('APP_ENV') === 'local') {
        //     $redirect_uri = URL::current();
        // } else {

        //     $redirect_uri = URL::current();

        //     // return   print($redirect_uri);
        // }
        //check if file xists on the disk
        if ($fileExists != false) {

            $file = Storage::disk('private')->get(env('TOKEN_FILE'));

            $client->setAccessToken($file);

            /* Refresh token when expired */
            if ($client->isAccessTokenExpired()) {

                // the new access token comes with a refresh token as well
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                Storage::disk('private')->put(env('TOKEN_FILE'),  json_encode($client->getAccessToken()), 'private');

                return $client;
            }

            return $client;
        }

        $redirect_uri = URL::current();
        //set redirect URL
        $client->setRedirectUri($redirect_uri);

        // create auth url
        $url = $client->createAuthUrl();

        return Redirect::intended($url);

    }

    /**
     * Get User Playlists
     */
    public function getPlaylists(Request $request)
    {
        // $callback = "";
        //check if auth code returned
        $code = $request->input('code');

        $client = $this->getAuthGoogleApi();

        if (isset($code)) {

            // Google Client Object
            $accessToken = $client->getAccessToken();

            $client->setAccessToken($accessToken);

            //Save refresh Token to file
            Storage::disk('private')->put(env('TOKEN_FILE'),  json_encode($accessToken), 'private');

            //Init Service
            $service =  new Google_Service_YouTube($client);

            $queryParams = [
                'maxResults' => 25,
                'mine' => true
            ];

            $response = $service->playlists->listPlaylists('snippet,contentDetails', $queryParams);

            $response = response()->json($response);
            return $response;

        }

        return $client;
    }
}
