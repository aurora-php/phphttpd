#!/usr/bin/env php
<?php

require_once(__DIR__ . '/libs/Httpd.php');

(new \Octris\Httpd())->main();

?>
<html>
    <body>
        <h1>hello from phpttpd</h1>
    </body>
</html>
