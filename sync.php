<?php

/*
 * This file is part of the Libcast Dokeos module.
 *
 * (c) Libcast <contact@libcast.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once realpath(__DIR__.'/../inc/global.inc.php');
require_once realpath(__DIR__.'/../inc/lib/usermanager.lib.php');
require_once realpath(__DIR__.'/../inc/lib/course.lib.php');

if (!$userId = strtolower(htmlspecialchars($_GET['id']))) {
    return;
}

if (!$user = UserManager::get_user_info_by_id($userId)) {
    return;
}

$libcast->synchronizeUser($user);

