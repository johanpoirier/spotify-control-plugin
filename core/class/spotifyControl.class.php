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
  const EQ_LOGICAL_ID = 'spotifyControl';

  const RESET_CMD_ID = 'reset';
  const PLAY_CMD_ID = 'play';
  const PAUSE_CMD_ID = 'pause';
  const VOLUME_CMD_ID = 'volume';
  const SET_VOLUME_CMD_ID = 'setVolume';
  const CHANGE_DEVICE_CMD_ID = 'changeDevice';

  private static function generateRandomString($length = 10)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
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
   *
   * @return boolean
   */
  public function play()
  {
    log::add(self::EQ_LOGICAL_ID, 'debug', 'Play');
    return $this->getSpotifyApi()->play();
  }

  /**
   * Pauses user's playback on current device
   *
   * @return boolean
   */
  public function pause()
  {
    log::add(self::EQ_LOGICAL_ID, 'debug', 'Pause');
    return $this->getSpotifyApi()->pause();
  }

  /**
   * Changes user's active device
   *
   * @param int $deviceId
   * @return boolean
   */
  public function changeDevice($deviceId)
  {
    $this->pause();

    log::add(self::EQ_LOGICAL_ID, 'debug', "Change device with id $deviceId");
    $result = $this->getSpotifyApi()->changeMyDevice([
      'device_ids' => [$deviceId],
      'play' => true
    ]);
    $this->refreshWidget();

    return $result;
  }

  /**
   * Set volume on active device
   *
   * @param $percent
   * @return boolean
   */
  public function setVolume($percent)
  {
    log::add(self::EQ_LOGICAL_ID, 'debug', "Change volume to $percent");
    return $this->getSpotifyApi()->changeVolume([
      'volume_percent' => $percent
    ]);
  }

  /**
   * Set volume on active device
   */
  public function reset()
  {
    log::add(self::EQ_LOGICAL_ID, 'debug', 'Reset');
    $this->saveTokens(null, null, null);
  }

  /**
   *
   */
  public function postUpdate()
  {
    $play = $this->getCmd(null, self::PLAY_CMD_ID);
    if (!is_object($play)) {
      $play = new spotifyControlCmd();
    }
    $play->setName('Play');
    $play->setLogicalId(self::PLAY_CMD_ID);
    $play->setEqLogic_id($this->getId());
    $play->setType('action');
    $play->setSubType('other');
    $play->setIsVisible(1);
    $play->setDisplay('generic_type', 'SPOTIFY_PLAY');
    $play->save();

    $pause = $this->getCmd(null, self::PAUSE_CMD_ID);
    if (!is_object($pause)) {
      $pause = new spotifyControlCmd();
    }
    $pause->setName('Pause');
    $pause->setLogicalId(self::PAUSE_CMD_ID);
    $pause->setEqLogic_id($this->getId());
    $pause->setType('action');
    $pause->setSubType('other');
    $pause->setIsVisible(1);
    $pause->setDisplay('generic_type', 'SPOTIFY_PAUSE');
    $pause->save();

    $changeDevice = $this->getCmd(null, self::CHANGE_DEVICE_CMD_ID);
    if (!is_object($changeDevice)) {
      $changeDevice = new spotifyControlCmd();
    }
    $changeDevice->setName('Change device');
    $changeDevice->setEqLogic_id($this->getId());
    $changeDevice->setLogicalId(self::CHANGE_DEVICE_CMD_ID);
    $changeDevice->setType('action');
    $changeDevice->setSubType('slider');
    $changeDevice->setIsVisible(1);
    $changeDevice->setDisplay('generic_type', 'SPOTIFY_CHANGE_DEVICE');
    $changeDevice->save();

    $volume = $this->getCmd(null, self::VOLUME_CMD_ID);
    if (!is_object($volume)) {
      $volume = new spotifyControlCmd();
    }
    $volume->setName('Volume');
    $volume->setUnite('%');
    $volume->setEqLogic_id($this->getId());
    $volume->setLogicalId(self::VOLUME_CMD_ID);
    $volume->setType('info');
    $volume->setSubType('numeric');
    $volume->setConfiguration('repeatEventManagement', 'never');
    $volume->setIsVisible(1);
    $volume->setDisplay('generic_type', 'SPOTIFY_VOLUME');
    $volume->save();

    $setVolume = $this->getCmd(null, self::SET_VOLUME_CMD_ID);
    if (!is_object($setVolume)) {
      $setVolume = new spotifyControlCmd();
    }
    $setVolume->setName('Set volume');
    $setVolume->setLogicalId(self::SET_VOLUME_CMD_ID);
    $setVolume->setEqLogic_id($this->getId());
    $setVolume->setType('action');
    $setVolume->setSubType('slider');
    $setVolume->setIsVisible(1);
    $setVolume->setDisplay('generic_type', 'SPOTIFY_SET_VOLUME');
    $setVolume->save();

    $reset = $this->getCmd(null, self::RESET_CMD_ID);
    if (!is_object($reset)) {
      $reset = new spotifyControlCmd();
    }
    $reset->setName('Reset');
    $reset->setLogicalId(self::RESET_CMD_ID);
    $reset->setEqLogic_id($this->getId());
    $reset->setType('action');
    $reset->setSubType('other');
    $reset->setIsVisible(1);
    $reset->setDisplay('generic_type', 'SPOTIFY_RESET');
    $reset->save();
  }

  public function saveTokens($accessToken, $refreshToken, $tokenExpiration)
  {
    $this->setConfiguration('accessToken', $accessToken);
    if ($refreshToken !== null && $refreshToken !== '') {
      $this->setConfiguration('refreshToken', $refreshToken);
    }
    $this->setConfiguration('tokenExpiration', $tokenExpiration);

    $this->save();
  }

  public function toHtml($_version = 'dashboard')
  {
    // To remove when dev is over
    cache::flush();

    $variables = $this->preToHtml($_version);
    if (!is_array($variables)) {
      return $variables;
    }
    $version = jeedom::versionAlias($_version);

    $refreshToken = $this->getConfiguration('refreshToken', null);
    if ($refreshToken === null || $refreshToken === '') {
      $html = $this->getLoginHtml($version, $variables);
    } else {
      $html = $this->getMainHtml($version, $variables);
    }

    return $this->postToHtml($_version, $html);
  }

  private function getMainHtml($version, $variables)
  {
    // User's devices
    $variables['#devices#'] = json_encode($this->getSpotifyApi()->getMyDevices());

    // Commands
    $playCmd = $this->getCmd(null, self::PLAY_CMD_ID);
    $variables['#' . self::PLAY_CMD_ID . '_id#'] = is_object($playCmd) ? $playCmd->getId() : '';

    $pauseCmd = $this->getCmd(null, self::PAUSE_CMD_ID);
    $variables['#' . self::PAUSE_CMD_ID . '_id#'] = is_object($pauseCmd) ? $pauseCmd->getId() : '';

    $changeDeviceCmd = $this->getCmd(null, self::CHANGE_DEVICE_CMD_ID);
    $variables['#' . self::CHANGE_DEVICE_CMD_ID . '_id#'] = is_object($changeDeviceCmd) ? $changeDeviceCmd->getId() : '';

    $setVolumeCmd = $this->getCmd(null, self::SET_VOLUME_CMD_ID);
    $variables['#' . self::SET_VOLUME_CMD_ID . '_id#'] = is_object($setVolumeCmd) ? $setVolumeCmd->getId() : '';

    return template_replace($variables, getTemplate('core', $version, 'main', self::EQ_LOGICAL_ID));
  }

  private function getLoginHtml($version, $variables)
  {
    $state = self::generateRandomString();
    $this->setConfiguration('state', $state);
    $this->save();

    $variables['#clientid#'] = $this->getConfiguration('clientId', '');
    $variables['#redirecturi#'] = $this->getConfiguration('redirectUri', '');
    $variables['#state#'] = $state;

    return template_replace($variables, getTemplate('core', $version, 'login', self::EQ_LOGICAL_ID));
  }
}

class spotifyControlCmd extends cmd
{
  public function execute($options = [])
  {
    $cmd = $this->getLogicalId();
    log::add(spotifyControl::EQ_LOGICAL_ID, 'debug', "$cmd with options: " . json_encode($options));

    switch ($cmd) {
      case spotifyControl::PLAY_CMD_ID:
        return $this->getEqLogic()->play();
      case spotifyControl::PAUSE_CMD_ID:
        return $this->getEqLogic()->pause();
      case spotifyControl::CHANGE_DEVICE_CMD_ID:
        $deviceId = $options['slider'];
        return $this->getEqLogic()->changeDevice($deviceId) ? 'Device changed' : 'error';
      case spotifyControl::SET_VOLUME_CMD_ID:
        $percent = $options['slider'];
        return $this->getEqLogic()->setVolume($percent) ? 'Volume changed' : 'failed';
      case spotifyControl::RESET_CMD_ID:
        $this->getEqLogic()->reset();
        break;
    }

    return false;
  }
}
