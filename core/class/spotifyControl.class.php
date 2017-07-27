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
   *
   * @return boolean
   */
  public function play()
  {
    log::add('spotifyControl', 'debug', 'Play');
    return $this->getSpotifyApi()->play();
  }

  /**
   * Pauses user's playback on current device
   *
   * @return boolean
   */
  public function pause()
  {
    log::add('spotifyControl', 'debug', 'Pause');
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
    log::add('spotifyControl', 'debug', "Change device with id $deviceId");
    return $this->getSpotifyApi()->changeMyDevice([
      'device_ids' => [ $deviceId ],
      'play' => true
    ]);
  }

  /**
   * Set volume on active device
   *
   * @param $percent
   * @return boolean
   */
  public function setVolume($percent)
  {
    log::add('spotifyControl', 'debug', "Change volume to $percent");
    return $this->getSpotifyApi()->changeVolume([
      'volume_percent' => $percent
    ]);
  }

  /**
   * Set volume on active device
   */
  public function reset()
  {
    log::add('spotifyControl', 'debug', 'Reset');
    $this->saveTokens(null, null, null);
  }

  /**
   *
   */
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

    $changeDevice = $this->getCmd(null, 'changeDevice');
    if (!is_object($changeDevice)) {
      $changeDevice = new spotifyControlCmd();
      $changeDevice->setLogicalId('changeDevice');
      $changeDevice->setIsVisible(1);
      $changeDevice->setName('Change device');
    }
    $changeDevice->setEqLogic_id($this->getId());
    $changeDevice->setType('action');
    $changeDevice->setSubType('slider');
    $changeDevice->save();

    $volume = $this->getCmd(null, 'volume');
    if (!is_object($volume)) {
      $volume = new spotifyControlCmd();
      $volume->setLogicalId('volume');
      $volume->setName('Volume');
    }
    $volume->setUnite('%');
    $volume->setType('info');
    $volume->setSubType('numeric');
    $volume->setConfiguration('repeatEventManagement', 'never');
    $volume->setEqLogic_id($this->getId());
    $volume->save();

    $setVolume = $this->getCmd(null, 'setVolume');
    if (!is_object($setVolume)) {
      $setVolume = new spotifyControlCmd();
      $setVolume->setLogicalId('setVolume');
      $setVolume->setIsVisible(1);
      $setVolume->setName('Set volume');
    }
    $setVolume->setEqLogic_id($this->getId());
    $setVolume->setType('action');
    $setVolume->setSubType('slider');
    $setVolume->save();

    $reset = $this->getCmd(null, 'reset');
    if (!is_object($reset)) {
      $reset = new spotifyControlCmd();
      $reset->setLogicalId('reset');
      $reset->setIsVisible(1);
      $reset->setName('Reset');
    }
    $reset->setEqLogic_id($this->getId());
    $reset->setType('action');
    $reset->setSubType('other');
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

    $changeDeviceCmd = $this->getCmd(null, 'changeDevice');
    $replace['#changeDevice_id#'] = is_object($changeDeviceCmd) ? $changeDeviceCmd->getId() : '';

    $setVolumeCmd = $this->getCmd(null, 'setVolume');
    $replace['#setVolume_id#'] = is_object($setVolumeCmd) ? $setVolumeCmd->getId() : '';

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $jeedomVersion, 'main', 'spotifyControl')));
  }

  private function loginHtml($_version, $jeedomVersion)
  {
    $state = self::generateRandomString();
    $this->setConfiguration('state', $state);
    $this->save();

    $replace['#clientid#'] = $this->getConfiguration('clientId', '');
    $replace['#redirecturi#'] = $this->getConfiguration('redirectUri', '');
    $replace['#state#'] = $state;

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $jeedomVersion, 'login', 'spotifyControl')));
  }
}

class spotifyControlCmd extends cmd
{
  public function execute($options = [])
  {
    $cmd = $this->getLogicalId();
    log::add('spotifyControl', 'debug', "$cmd with options: " . json_encode($options));

    switch ($cmd) {
      case 'play':
        return $this->getEqLogic()->play();
      case 'pause':
        return $this->getEqLogic()->pause();
      case 'changeDevice':
        $deviceId = $options['slider'];
        return $this->getEqLogic()->changeDevice($deviceId) ? 'Device changed' : 'error';
      case 'setVolume':
        $percent = $options['slider'];
        return $this->getEqLogic()->setVolume($percent) ? 'Volume changed' : 'failed';
      case 'reset':
        $this->getEqLogic()->reset();
        break;
    }

    return false;
  }
}
