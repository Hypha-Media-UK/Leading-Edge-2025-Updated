<?php
/**
 * craftagram plugin for Craft CMS 4.x / 5.x
 *
 * Grab Instagram content through the Instagram API

 * @copyright Copyright (c) 2024 Joshua Martin
 */

namespace jsmrtn\craftagram\services;

use jsmrtn\craftagram\Craftagram;
use jsmrtn\craftagram\records\SettingsRecord as SettingsRecord;

use Craft;
use craft\base\Component;
use craft\services\Plugins;
use craft\helpers\Db;

class CraftagramService extends Component {

    /**
     * Grab the long access token based on supplied site
     *
     * @return string
     */
    public function getLongAccessTokenSetting($siteId) {

        $params = [
            'craftagramSiteId' => $siteId
        ];

        $longAccessTokenRecord = SettingsRecord::findOne($params);

        if (!$longAccessTokenRecord) {
            Craftagram::info('getLongAccessTokenSetting:37: An access token has not been obtained from Instagram', 'craftagram');
            return false;
        }

        return $longAccessTokenRecord->getAttribute('longAccessToken');
    }

    /**
     * Check if the API endpoint is secured for this site
     *
     * @return string
     */
    public function checkIfSecured($siteId) {

        if ($siteId == 0) {
            $siteId = Craft::$app->sites->primarySite->id;
        }

        $params = [
            'craftagramSiteId' => $siteId
        ];

        $isSecured = SettingsRecord::findOne($params);

        if (!$isSecured) {
            Craftagram::info('checkIfSecured:62: This site does not have a linked instagram account', 'craftagram');
            return false;
        }

        return $isSecured->getAttribute('secureApiEndpoint');
    }

    /**
     * Loop all enabled sites and refresh long access tokens
     *
     * @return bool true if refreshs where successful, otherwise false
     */
    public function refreshToken() {
        $siteIds = Craft::$app->sites->getAllSiteIds();

        $allRefreshsSuccessful = true;

        foreach ($siteIds as $siteId) {
            if (!$this->refreshTokenForSiteId($siteId)) {
                $allRefreshsSuccessful = false;
            }
        }

        return $allRefreshsSuccessful;
    }

    /**
     * Refresh the long access token for a given SiteId,
     * by sending a curl request to the Instagram API.
     *
     * If no longAccessRecord is found for the given siteId
     * we don't bother the API.
     *
     * @param  integer $siteId siteId to refresh the long access token for
     * @return bool true if extend was successful, otherwise false
     */
    public function refreshTokenForSiteId(int $siteId) {

        $longAccessTokenRecord = Craftagram::$plugin->craftagramService->getLongAccessTokenSetting($siteId);

        if (!$longAccessTokenRecord) {
            Craftagram::info('refreshTokenForSiteId:103: No long access token record', 'craftagram');
            return false;
        }

        $ch = curl_init();

        $params = [
            'access_token' => $longAccessTokenRecord,
            'grant_type' => 'ig_refresh_token'
        ];

        curl_setopt($ch, CURLOPT_URL,'https://graph.instagram.com/refresh_access_token?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        try {
            $expires = json_decode($res)->expires_in;
            Craftagram::info('refreshTokenForSiteId:123: Successfully refreshed authentication token. Expires in ' . $expires, 'craftagram');
        } catch (\Exception $e) {
            Craftagram::info('refreshTokenForSiteId:125: Failed to refresh authentication token. Error: ' . $res, 'craftagram');
            return false;
        }

        return true;

    }

    /**
     * Get short access token from Instagram
     */
    public function getShortAccessToken($code, $siteId) {
        $ch = curl_init();

        $getSettings = [
            'craftagramSiteId' => $siteId
        ];

        $longAccessTokenRecord = SettingsRecord::findOne($getSettings);

        $params = [
            'client_id' => Craft::parseEnv($longAccessTokenRecord->appId),
            'client_secret' => Craft::parseEnv($longAccessTokenRecord->appSecret),
            'grant_type' => 'authorization_code',
            'redirect_uri' => rtrim(Craft::parseEnv(Craft::$app->sites->primarySite->baseUrl), '/') . '/actions/craftagram/default/auth',
            'code' => $code
        ];

        curl_setopt($ch, CURLOPT_URL,'https://api.instagram.com/oauth/access_token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        try {
            $shortAccessToken = json_decode($res)->access_token;
        } catch (\Exception $e) {
            Craftagram::info('getShortAccessToken:164: No short access token granted. Error: ' . $res, 'craftagram');
            return false;
        }

        return Craftagram::$plugin->craftagramService->getLongAccessToken($shortAccessToken, $siteId, $longAccessTokenRecord->appSecret);
    }

    /**
     * Get long access token from instagram and save it
     *
     * @return string
     */
    public function getLongAccessToken($shortAccessToken, $siteId, $secret) {
        $ch = curl_init();

        $params = [
            'client_secret' => Craft::parseEnv($secret),
            'grant_type' => 'ig_exchange_token',
            'access_token' => $shortAccessToken
        ];

        curl_setopt($ch, CURLOPT_URL,'https://graph.instagram.com/access_token?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        try {
            $token = json_decode($res)->access_token;
        } catch (\Exception $e) {
            Craftagram::info('getLongAccessToken:195: No long access token granted. Error: ' . $res, 'craftagram');
            return false;
        }

        $plugin = Craft::$app->getPlugins()->getPlugin('craftagram');

        if ($plugin !== null) {
            $params = [
                'craftagramSiteId' => $siteId
            ];

            $longAccessTokenRecord = SettingsRecord::findOne($params);

            $longAccessTokenRecord->setAttribute('longAccessToken', $token);
            if ($longAccessTokenRecord->save()) {
                return $token;
            } else {
                Craftagram::info('getLongAccessToken:212: Failed to save long access token. Error: ' . $res, 'craftagram');
                return false;
            }
        }

        return false;
    }

     /**
     * Get instagram profile information
     *
     * @return string|null
     */
    public function getInstagramProfileInformation($siteId) {

        if ($siteId == 0) {
            $siteId = Craft::$app->sites->primarySite->id;
        }

        $longAccessTokenRecord = Craftagram::$plugin->craftagramService->getLongAccessTokenSetting($siteId);

        if (!$longAccessTokenRecord) {
            Craftagram::info('getInstagramProfileInformation:234: Failed to fetch $longAccessTokenRecord', 'craftagram');
            return false;
        }

        $ch = curl_init();

        $params = [
            'fields' => 'id,user_id,username,name,account_type,profile_picture_url,followers_count,follows_count,media_count',
            'access_token' => $longAccessTokenRecord,
        ];

        curl_setopt($ch, CURLOPT_URL,'https://graph.instagram.com/v21.0/me?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res);

        if (!isset($res)) {
            Craftagram::info('getInstagramProfileInformation:255: Failed to get data. Response from Instagram: ' . json_encode($res), 'craftagram');
        }

        return (isset($res) ? $res : null);
    }

    /**
     * Get instagram feed media IDs
     *
     * @return string|null
     */
    public function getInstragramMediaIDs($limit, $siteId, $after) {

        if ($siteId == 0) {
            $siteId = Craft::$app->sites->primarySite->id;
        }

        $longAccessTokenRecord = Craftagram::$plugin->craftagramService->getLongAccessTokenSetting($siteId);

        if (!$longAccessTokenRecord) {
            Craftagram::info('getInstagramMediaIDs:258: Failed to fetch $longAccessTokenRecord', 'craftagram');
            return false;
        }

        $instaProfileInfo = Craftagram::$plugin->craftagramService->getInstagramProfileInformation($siteId);

        if (property_exists($instaProfileInfo, 'error')) {
            Craftagram::info('getInstagramMediaIDs:265: $instaProfileInfo error: ' . json_encode($instaProfileInfo), 'craftagram');
            return false;
        }

        $IG_ID = Craftagram::$plugin->craftagramService->getInstagramProfileInformation($siteId)->user_id;

        $ch = curl_init();

        $params = [
            'access_token' => $longAccessTokenRecord,
            'limit' => $limit
        ];

        if ($after != '') {
            $params['after'] = $after;
        }

        curl_setopt($ch, CURLOPT_URL,'https://graph.instagram.com/v21.0/'.$IG_ID.'/media?'.http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res);

        if (!isset($res->data)) {
            Craftagram::info('getInstagramMediaIDs:292: Failed to get data. Response from Instagram: ' . json_encode($res), 'craftagram');
        }

        return (isset($res->data) ? $res : null);
    }

    /**
     * Get instagram feed media IDs
     *
     * @return string|null
     */
    public function getInstagramFeed($limit, $siteId, $after) {

        if ($siteId == 0) {
            $siteId = Craft::$app->sites->primarySite->id;
        }

        $longAccessTokenRecord = Craftagram::$plugin->craftagramService->getLongAccessTokenSetting($siteId);

        if (!$longAccessTokenRecord) {
            Craftagram::info('getInstagramFeed:312: Failed to get $longAccessTokenRecord', 'craftagram');
            return false;
        }

        $mediaIDs = Craftagram::$plugin->craftagramService->getInstragramMediaIDs($limit, $siteId, $after);

        if (!$mediaIDs) {
            Craftagram::info('getInstagramFeed:319: Failed to get $mediaIDs', 'craftagram');
            return false;
        }

        if (sizeof($mediaIDs->data) === 0) {
            Craftagram::info('getInstagramFeed:319: No data in $mediaIDs', 'craftagram');
            return false;
        }

        $groupedMediaRecords = [];

        foreach($mediaIDs->data as $mediaID) {
            $ch = curl_init();

            $params = [
                'fields' => 'caption,comments_count,id,is_shared_to_feed,media_product_type,media_type,media_url,owner,permalink,shortcode,thumbnail_url,timestamp',
                'access_token' => $longAccessTokenRecord
            ];

            if ($after != '') {
                $params['after'] = $after;
            }

            curl_setopt($ch, CURLOPT_URL,'https://graph.instagram.com/v21.0/'.$mediaID->id.'?'.http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            curl_close($ch);

            if (!isset($res->id)) {
                Craftagram::info('getInstagramFeed:345: Failed to get data for this $mediaID. Response from Instagram: ' . json_encode($res), 'craftagram');
            }

            $groupedMediaRecords['data'][] = json_decode($res);
        }

        $groupedMediaRecords['paging']['cursors']['before'] = $mediaIDs->paging->cursors->before;
        $groupedMediaRecords['paging']['cursors']['after'] = $mediaIDs->paging->cursors->after;

        return $groupedMediaRecords;
    }

    /**
     * Get instagram feed
     *
     * @return mixed
     */
    public function handleAuthentication()
    {
        list($username, $password) = Craft::$app->getRequest()->getAuthCredentials();

        if (!$username || !$password) {
            return false;
        }

        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail(Db::escapeParam($username));

        if (!$user) {
            Craftagram::info('handleAuthentication:373: No user', 'craftagram');
            return false;
        }

        if (!$user->authenticate($password)) {
            Craftagram::info('handleAuthentication:378: Failed to authenticate', 'craftagram');
            return false;
        }

        return true;
    }
}