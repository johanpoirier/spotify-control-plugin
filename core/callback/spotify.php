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

$spotifyControl = spotifyControl::byId($_GET['id']);

$session = new SpotifyWebAPI\Session(
  $spotifyControl->getConfiguration('clientId', ''),
  $spotifyControl->getConfiguration('clientSecret', ''),
  $spotifyControl->getConfiguration('redirectUri', '')
);

$session->requestAccessToken($_GET['code']);

$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($session->getAccessToken());

$spotifyControl->saveTokens($session->getAccessToken(), $session->getRefreshToken());

?>
<html>
<body>
<?= $session->getRefreshToken() ?>
<script type="text/javascript">
    //window.close();
</script>
</body>
</html>
