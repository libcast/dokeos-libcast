<?php
/*
 * This file is part of the Libcast Dokeos module.
 *
 * (c) Libcast <contact@libcast.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// This file is not for production, only for tests and developments,
// thus, we stop here.
die;

require_once realpath(__DIR__.'/../inc/global.inc.php');
require_once realpath(__DIR__.'/../inc/lib/course.lib.php');
require_once realpath(__DIR__.'/../inc/lib/usermanager.lib.php');

error_reporting(E_ALL);
ini_set('display_errors', true);


$u = UserManager::get_user_info_by_id(isset($_GET['user_id']) ? $_GET['user_id'] : 4);

$_SESSION['_user'] = $u;

header('Location: http://scandola.univ-corse.fr/dokeos/index.php');
