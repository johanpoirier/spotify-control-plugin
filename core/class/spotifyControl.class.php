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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';


class spotifyControl extends eqLogic
{
  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */

  /*
   * Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {

    }
   */


  /*
   * Fonction exécutée automatiquement toutes les heures par Jeedom
    public static function cronHourly() {

    }
   */

  /*
   * Fonction exécutée automatiquement tous les jours par Jeedom
    public static function cronDayly() {

    }
   */
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

  /*     * *********************Méthodes d'instance************************* */

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

    $tokenExpiration = $this->getConfiguration('tokenExpiration', null);
    if ($tokenExpiration === null) {
      return $this->loginHtml($_version, $jeedomVersion);
    }

    $replace['#devices#'] = json_encode($this->getSpotifyApi()->getMyDevices());
    $replace['#expiration#'] = $this->getConfiguration('tokenExpiration', null);

    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $jeedomVersion, 'main', 'spotifyControl')));
  }

  private function getSpotifyApi()
  {
    $session = new SpotifyWebAPI\Session(
      $this->getConfiguration('clientId', ''),
      $this->getConfiguration('clientSecret', ''),
      $this->getConfiguration('redirectUri', '')
    );

    if ($this->getConfiguration('tokenExpiration', null)) {
      $session->refreshAccessToken($this->getConfiguration('refreshToken', null));
      $this->saveTokens($session->getAccessToken(), $session->getRefreshToken(), $session->getTokenExpiration());
    }

    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $api->setAccessToken($this->getConfiguration('accessToken', null));

    return $api;
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

  /*     * **********************Getteur Setteur*************************** */
}

class spotifyControlCmd extends cmd
{
  /*     * *************************Attributs****************************** */


  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
   * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
    return true;
    }
   */

  public function execute($_options = array())
  {

  }

  /*     * **********************Getteur Setteur*************************** */
}

?>
