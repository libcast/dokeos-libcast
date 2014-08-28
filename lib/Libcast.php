<?php

/*
 * This file is part of the Libcast Dokeos module.
 *
 * (c) Libcast <contact@libcast.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Libcast\Client\Entity\User;
use \Libcast\Client\Entity\Platform;
use \Libcast\Client\Entity\Stream;
use \Libcast\Client\Entity\Token;
use \Libcast\Client\Entity\Access;
use \Libcast\Client\Entity\Role;

/**
 *
 * @require PHP 5.3
 */
class Libcast
{
    /**
     * Host to the web services
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Username used to connect to the web services
     *
     * @var string
     */
    protected $username;

    /**
     * Api key associated to the username
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Media in which to publish
     *
     * @var string
     */
    protected $media;

    /**
     * Profile given to the students in Libcast application
     *
     * @var string
     */
    protected $studentProfile;

    /**
     * Role given to the students accesses on course stream in Libcast application
     *
     * @var string
     */
    protected $studentRole;

    /**
     * Profile given to the professors in Libcast application
     *
     * @var string
     */
    protected $professorProfile;

    /**
     * Role given to the professors on course stream in Libcast application
     *
     * @var string
     */
    protected $professorRole;

    /**
     *
     * @var Platform
     */
    protected $client;

    /**
     *
     * @var type
     */
    protected $clientPlatform;

    /**
     * Constructor
     *
     * @param string $baseUrl
     * @param string $username
     * @param string $apiKey
     * @param string $media
     * @param string $studentProfile
     * @param string $professorProfile
     */
    public function __construct($baseUrl, $username, $apiKey, $media, $studentProfile, $studentRole, $professorProfile, $professorRole)
    {
        $this->baseUrl = $baseUrl;
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->media = $media;
        $this->studentProfile = $studentProfile;
        $this->studentRole = $studentRole;
        $this->professorProfile = $professorProfile;
        $this->professorRole = $professorRole;

        ob_start(array($this, 'outputHandler'));
        register_shutdown_function(array($this, 'shutdown'));
        spl_autoload_register(array($this, 'autoload'));
        \Display::css($this->getHomeUrl().'/css/libcast.css');
    }

    public function shutdown()
    {
// Included directly in main/inc/lib/add_course.lib.inc.php
//         if ($code = $this->isCourseCreated()) {
//             $this->onCourseCreated($code);
//         }
    }

    /**
     * Detect wether a new course is created or not
     *
     * @return boolean
     */
    protected function isCourseCreated()
    {
        if (!api_is_session_admin() and !api_is_allowed_to_create_course()) {
            return false;
        }

        if (empty($GLOBALS['form'])) {
            return false;
        }

        /* @var $form FormValidator*/
        $form = $GLOBALS['form'];

        if ('add_course' != $form->getAttribute('name')) {
            return false;
        }

        return ($form->validate() and !empty($GLOBALS['keys']['currentCourseId'])) ? $GLOBALS['keys']['currentCourseId'] : false;
    }

    /**
     *
     * @param string $courseName
     */
    public function onCourseCreated($courseCode)
    {
        $this->log("Create tool links for course $courseCode in Dokeos.");

        // Add Libcast tool in production section
        $sql = sprintf("INSERT INTO %s VALUES (NULL, 'videocast','%s','videocast.gif','%d','0','squaregrey.gif','NO','_self','authoring','0')",
                $this->getToolTableName($courseCode),
                $this->getHomeName().'/libcast.php',
                (int) string2binary(api_get_setting('course_create_active_tools', 'videocast')));
        $this->log($sql);

        \Database::query($sql, __FILE__, __LINE__);

        // Add a link to the Course stream in Libcast backend
        $sql = sprintf("INSERT INTO %s VALUES (NULL, 'libcast','%s','videocast.gif','%d','1','squaregrey.gif',1,'_self','admin','0')",
                $this->getToolTableName($courseCode),
                $this->getHomeName().'/manage.php',
                (int) string2binary(api_get_setting('course_create_active_tools', 'videocast')));
        $this->log($sql);

        \Database::query($sql, __FILE__, __LINE__);
    }

    /**
     *
     * @param string $name
     *
     * @return Stream
     */
    public function createStream($name)
    {
        $this->log("Create stream $name in Libcast.");

        $stream = new Stream($name);
        $stream = $this->getClient()->createStream($stream, $this->getClient()->media($this->media));

        require_once api_get_path(LIBRARY_PATH).'course.lib.php';
        $data = CourseManager::get_course_information($name);
        $stream->setTitle($data['title']);

        return $this->getClient()->updateStream($stream);
    }

    /**
     *
     * @param string $cidReq
     * @param array $user
     *
     * @return string
     */
    public function getStreamAdminLink($cidReq, $user = null)
    {
        $stream = $this->getStreamOrCreate($cidReq, $user);

        $token = $this->createTokenForUser($user);

        return $this->baseUrl.'admin/publications/stream/'.basename($stream->getHref()).'?lc_theme=chromeless&lc_token='.$token->getValue();
    }

    /**
     *
     * @param string $cidReq
     * @param array $user
     *
     * @return string
     */
    public function getStreamLink($cidReq, $user = null)
    {
        $stream = $this->getStreamOrCreate($cidReq, $user);

        $token = $this->createTokenForUser($user);

        return $this->baseUrl.basename($stream->getHref()).'?lc_token='.$token->getValue();
    }

    /**
     *
     * @param string $cidReq
     * @param array $userData
     *
     * @return Stream
     */
    protected funCtion getStreamOrCreate($cidReq, $userData = null)
    {
        try {
            return $this->getClient()->stream(strtolower($cidReq));
        } catch (Exception $e) {
        }

        $stream = $this->createStream($cidReq);

        if ($userData) {
            $user = $this->getUserOrCreate($userData);
            $role = $this->retrieveRoleForUser($userData);
            $this->createAccess($user, $stream, $role);
        }

        return $stream;
    }

    /**
     *
     * @param array $user
     *
     * @return string
     */
    public function getAdminLink($user = null)
    {
        $token = $this->createTokenForUser($user);

        return $this->baseUrl.'admin/folder?lc_theme=chromeless&lc_token='.$token->getValue();
    }

    /**
     *
     * @param array $user
     *
     * @return Token
     */
    protected function createTokenForUser($user = null)
    {
        // Retrieve a User instance
        $user = $this->getUserOrCreate($user);

        // Create an authentication token for this user
        $token = new Token($user, null, 1);

        return $this->getClient()->createToken($token);
    }

    /**
     *
     * @param array $user
     *
     * @return User
     */
    protected function getUserOrCreate($user = null)
    {
        global $_user;

        // $_user contains data of the current session user in Dokeos
        if (is_null($user)) {
            $user = $_user;
        }

        // Retrieve an instance of the user
        if (!$user = $this->getClient()->user($user['username'])) {
            return $this->createUser($user);
        }

        return $user;
    }

    /**
     *
     * @param array $user
     *
     * @return User
     */
    protected function createUser($user)
    {
        $this->log("Create user {$user['username']} in Libcast.");

        switch ($user['status'])
        {
            case STUDENT:
                $profile = $this->studentProfile;
                break;
            case COURSEMANAGER:
            case SESSIONADMIN:
            case DRH:
                $profile = $this->professorProfile;
                break;
            case ANONYMOUS:
            default:
                throw new \RuntimeException("Impossible to create the user {$user['username']} in Libcast application: the status {$user['status']} cannot be linked to a Libcast Profile.");
                break;
        }

        $entity = new User($user['username'], md5(uniqid()) /* Random */, $user['email'], $profile);

        return $this->getPlatform()->addUser($entity);
    }

    /**
     *
     * @param array $user
     */
    public function requestSynchronization($user)
    {
        $url = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'/sync.php?id='.$user['user_id'];
        $this->callInBackground($url);
    }

    /**
     *
     * @throws \RuntimeException
     *
     * @param array $userData
     *
     * @return int Number of courses synchronized
     */
    public function synchronizeUser($userData)
    {
        $this->log("Synchronize accesses for user {$userData['username']} from Dokeos to Libcast.");

        // Force user creation at least
        $user = $this->getUserOrCreate($userData);

        $query = sprintf('SELECT course_code FROM %s WHERE user_id = %d AND status = %d', Database::get_main_table(TABLE_MAIN_COURSE_USER), $userData['user_id'], $userData['status']);
        if (!$stmt = Database::query($query, __FILE__, __LINE__) or !Database::num_rows($stmt)) {
            $this->log("No course to synchronize.");
            return 0;
        }

        $streams = array();
        $codes = array();
        while ($course = Database::fetch_array($stmt)) {
            $code = $course['course_code'];

            // Some duplicate smay exist, don't process them twice
            if (array_key_exists($code, $codes)) {
                $this->log("Course $code is already processed.");
                continue;
            }
            $codes[$code] = $code;

            // Some codes are erroneous
            if (!CourseManager::is_existing_course_code($code)) {
                $this->log("Course $code does not exist.");
                continue;
            }

            $stream = $this->getStreamOrCreate($code);
            $url = $stream->getHref();
            $streams[$url] = $stream;

            $this->log("Course $code ($url) to synchronize.");
        }

        // Remove the streams to which the user has already access
        foreach ($this->getClient()->accesses($user) as $access) {
            $this->log("  Checking access on $url...");
            if (array_key_exists($url = $access->getObject()->getHref(), $streams)) {
                $this->log("Course $url has already an access, removing it.");
                unset($streams[$url]);
            }
        }

        // No stream left to synchronize
        if (!$streams) {
            $this->log("No stream left to synchronize.");
            return 0;
        }

        $role = $this->retrieveRoleForUser($userData);

        // Create an access for all remaining streams
        foreach ($streams as $url => $stream) {
            $this->log("Creating an access on stream $url...");
            $this->createAccess($user, $stream, $role);
        }

        return count($streams);
    }

    /**
     *
     * @param User $user
     * @param Stream $stream
     * @param Role $role
     *
     * @return Access
     */
    protected function createAccess(User $user, Stream $stream, Role $role)
    {
        $access = new Access();
        $access->setSubject($user);
        $access->setObject($stream);
        $access->setRole($role);

        return $this->getClient()->createAccess($access);
    }

    /**
     * Retrieve an instance of the Libcast Role to give to $userData on a Stream
     *
     * @param array $userData
     *
     * @return Role
     */
    protected function retrieveRoleForUser($userData)
    {
        if (STUDENT == $userData['status']) {
            if (!$this->studentRole instanceof Role) {
                $this->studentRole = $this->getClient()->role($this->studentRole);
            }

            return $this->studentRole;
        }

        if (in_array($userData['status'], array(COURSEMANAGER, SESSIONADMIN, DRH))) {
            if (!$this->professorRole instanceof Role) {
                $this->professorRole = $this->getClient()->role($this->professorRole);
            }

            return $this->professorRole;
        }

        $message = "Impossible to synchronize the user {$userData['username']} in Libcast application: the status {$userData['status']} cannot be linked to a Libcast Role.";
        $this->log($message);

        throw new \RuntimeException($message);
    }

    /**
     *
     * @return \Libcast\Client\Entity\Platform
     */
    protected function getPlatform()
    {
        if (!$this->clientPlatform) {
            $this->clientPlatform = $this->getClient()->media($this->media)->getPlatform();
        }

        return $this->clientPlatform;
    }

    /**
     * Determine the name of the `tool` table in the $courseName database
     *
     * @param string $courseCode
     *
     * @return string
     */
    protected function getToolTableName($courseCode)
    {
        $info = api_get_course_info($courseCode);
        $dbName = $info['dbName'] ? $info['dbName'] : $courseCode;

        return \Database::get_course_table(TABLE_TOOL_LIST, $dbName);
    }

    /**
     * Return the web home of the Libcast module
     *
     * @return string
     */
    protected function getHomeUrl()
    {
        return api_get_path(WEB_CODE_PATH).'/'.$this->getHomeName();
    }

    /**
     * Return the name of the home directory of the Libcast module
     *
     * @return string
     */
    protected function getHomeName()
    {
        return basename(realpath(__DIR__.'/..'));
    }

    /**
     * Alter the page output buffer if needed
     *
     * @return string|false String if the content has been altered, FALSE otherwise
     */
    public  function outputHandler($content)
    {
        return $this->injectMainMenuEntry($content);
    }

    /**
     * Add a "Videocast" entry to the main menu under conditions
     *
     * @param string $content
     *
     * @return string|false String if the content has been altered, FALSE otherwise
     */
    protected function injectMainMenuEntry($content)
    {
        global $this_section;

        // The user must be authenticated
        if (!api_get_user_id()) {
            return false;
        }

        // The user must be able to create courses to access its briefcase
        if (!api_is_allowed_to_create_course() and !api_is_session_admin()) {
            return false;
        }

        return preg_replace('/(<ul id="dokeostabs">.+)<\/ul>/m', sprintf('${1}<a href="%s" target="_top"><li class="tab_libcast%s"><div><span>Videocast</span></div></li></a></ul>', $this->getHomeUrl().'/briefcase.php', $this_section == 'libcast' ? '_current' : ''), $content);
    }

    /**
     *
     * @return \Libcast\Client\LibcastClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new \Libcast\Client\LibcastClient($this->baseUrl.'services/dev.php/', $this->username, $this->apiKey);
        }

        return $this->client;
    }

    protected function forceFlush()
    {
        ob_start();
        ob_end_clean();
        flush();
        while (@ob_end_flush());
    }

    /**
     *
     * @see http://stackoverflow.com/questions/45953/php-execute-a-background-process/45966#answer-4832703
     *
     * @param string $url
     */
    protected function callInBackground($url)
    {
//        You must use the popen/pclose for this to work properly.
//
//        The wget options:
//
//        -q    keeps wget quite.
//        -O -  outputs to stdout.
//        -b    works on background
        $proc_command = "wget '$url' -q -O - -b";
        $proc = popen($proc_command, "r");
        pclose($proc);
    }

    /**
     *
     * @param string $message
     */
    protected function log($message)
    {
        $message = " - $message";

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $message = $_SERVER['REMOTE_ADDR'].$message;
        }

        $message = date('c').' - '.$message."\n";

        @file_put_contents('/var/log/dokeos.libcast.log', $message, FILE_APPEND);
    }

    /**
     *
     * @param string $class
     */
    public function autoload($class)
    {
        $composerAutoloadPath = api_get_path(SYS_PATH).'vendor/autoload.php';
        if (!in_array($composerAutoloadPath, get_included_files()) and file_exists($composerAutoloadPath)) {
            /* @var $autoloader \Composer\Autoload\ClassLoader */
            $autoloader = require_once $composerAutoloadPath;

            return $autoloader->loadClass($class);
        }
    }
}

