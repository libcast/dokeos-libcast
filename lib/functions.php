<?php

/*
 * This file is part of the Libcast Dokeos module.
 *
 * (c) Libcast <contact@libcast.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


// Libcast development and compatibility functions


/**
 * Write the file name and line from which it was called, and an optionnal message
 *
 * @param string $message
 */
function mark($message = '')
{
    $db = debug_backtrace();
    var_dump($msg = $db[0]['file'].':'.$db[0]['line']." $message\n");
    file_put_contents('/var/log/apache2/dokeos.error.log', $msg, FILE_APPEND);
}

/**
 * Similar as `mark()` but put the message in the JavaScript console
 *
 * @param type $message
 */
function jslog($message = '')
{
    $db = debug_backtrace();
    echo sprintf('<script type="text/javascript">console.log("%s (line %s): %s")</script>', $db[0]['file'], $db[0]['line'], str_replace('"', '\\"', $message));
}

if (!function_exists('session_unregister'))
{
    /**
     * Enable Dokeos to run with PHP 5.4+
     *
     * `session_unregister()` has been deprecated in PHP 5.3 and removed in PHP 5.4
     *
     * @see http://php.net/session_unregister
     *
     * @param string $name
     */
    function session_unregister($name)
    {
        unset($_SESSION[$name]);
    }
}
