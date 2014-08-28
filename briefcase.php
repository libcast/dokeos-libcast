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

// Hack to not include the #header3 course header
unset($_course);

// access control
api_block_anonymous_users();

// Setting the section of this file (for the tabs)
$this_section = 'libcast';

// showing the header
$actualHeaderState = isset($_SESSION['header_state']) ? $_SESSION['header_state'] : null;
$_SESSION['header_state'] = 'expanded';
Display::display_header();
if (!is_null($acutualHeaderState)) $_SESSION['header_state'] = $actualHeaderState;

// Create a session in the backend
;
?>
<style type="text/css">#main {width: 100%;}</style>
<iframe id="briefcase" src="<?php echo $libcast->getAdminLink($_user) ?>" width="100%" marginheight="0" frameborder="0"></iframe>
<script type="text/javascript">
$(function() {
    var aboveHeight = $("#wrapper").outerHeight(true);
    $(window).resize(function() {
        $('#briefcase').height( $(window).height() - aboveHeight );
    }).resize();
});
</script>
<?php
// Displaying the footer
Display :: display_footer();

$libcast->requestSynchronization($_user);
