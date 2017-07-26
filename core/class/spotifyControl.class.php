<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';


class spotifyControl extends eqLogic
{
  private static function generateRandomString($length = 10)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }


  private function getSpotifyApi()
  {
    $session = new SpotifyWebAPI\Session(
      $this->getConfiguration('clientId', ''),
      $this->getConfiguration('clientSecret', ''),
      $this->getConfiguration('redirectUri', '')
    );

    $tokenExpiration = $this->getConfiguration('tokenExpiration', null);
    if ($tokenExpiration === null || time() > $tokenExpiration - 10) {
      $session->refreshAccessToken($this->getConfiguration('refreshToken', null));
      $this->saveTokens($session->getAccessToken(), $session->getRefreshToken(), $session->getTokenExpiration());
    }

    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($this->getConfiguration('accessToken', null));

    return $api;
  }

  /**
   * Starts user's playback on current device
   */
  public function play()
  {
    $this->getSpotifyApi()->play();
  }

  /**
   * Pauses user's playback on current device
   */
  public function pause()
  {
    $this->getSpotifyApi()->pause();
  }

  public function preInsert()
  {

  }

  public function postInsert()
  {

  }

  public function preSave()
  {

  }

  public function postSave()
  {
    $play = $this->getCmd(null, 'play');
    if (!is_object($play)) {
      $play = new spotifyControlCmd();
      $play->setLogicalId('play');
      $play->setIsVisible(1);
      $play->setName('Play');
    }
    $play->setEqLogic_id($this->getId());
    $play->setType('action');
    $play->setSubType('other');
    $play->save();

    $pause = $this->getCmd(null, 'pause');
    if (!is_object($pause)) {
      $pause = new spotifyControlCmd();
      $pause->setLogicalId('pause');
      $pause->setIsVisible(1);
      $pause->setName('Pause');
    }
    $pause->setEqLogic_id($this->getId());
    $pause->setType('action');
    $pause->setSubType('other');
    $pause->save();
  }

  public function preUpdate()
  {

  }

  public function postUpdate()
  {

  }

  public function preRemove()
  {

  }

  public function postRemove()
  {

  }

  public function saveTokens($accessToken, $refreshToken, $tokenExpiration)
  {
    $this->setConfiguration('accessToken', $accessToken);
    $this->setConfiguration('refreshToken', $refreshToken);
    $this->setConfiguration('tokenExpiration', $tokenExpiration);

    $this->save();
  }

  public function toHtml($_version = 'dashboard')
  {
    $replace = [];
    $jeedomVersion = jeedom::versionAlias($_version);

    $refreshToken = $this->getConfiguration('refreshToken', null);
    if ($refreshToken === null || $refreshToken === '') {
      return $this->loginHtml($_version, $jeedomVersion);
    }

    // User's devices
    $replace['#devices#'] = json_encode($this->getSpotifyApi()->getMyDevices());

    // Commands
    $playCmd = $this->getCmd(null, 'play');
    $replace['#play_id#'] = is_object($playCmd) ? $playCmd->getId() : '';

    $pauseCmd = $this->getCmd(null, 'pause');
    $replace['#pause_id#'] = is_object($pauseCmd) ? $pauseCmd->getId() : '';

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $jeedomVersion, 'main', 'spotifyControl')));
  }

  private function loginHtml($_version, $jeedomVersion)
  {
    $state = self::generateRandomString();
    $this->setConfiguration('state', $state);
    $this->save();

    $replace['#clientid#'] = $this->getConfiguration('clientId', '');
    $replace['#redirecturi#'] = $this->getConfiguration('redirectUri', '');
    $replace['#state#'] = $this->getConfiguration('state', $state);

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $jeedomVersion, 'login', 'spotifyControl')));
  }
}

class spotifyControlCmd extends cmd
{
  public function execute()
  {
    $cmd = $this->getLogicalId();

    switch ($cmd) {
      case 'play':
        $this->getEqLogic()->play();
        break;
      case 'pause':
        $this->getEqLogic()->pause();
        break;
    }

    return false;
  }
}
