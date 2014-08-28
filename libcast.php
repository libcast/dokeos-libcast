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

$cidReq = strtolower(htmlspecialchars($_GET['cidReq']));

header('Location: '.($link = $libcast->getStreamLink($cidReq, $_user)));
$libcast->requestSynchronization($_user);
?>
<!doctype html>
<html>
    <header>
        <script type="text/javascript">window.location.href = "<?php echo $link ?>"</script>
    </header>
</html>

