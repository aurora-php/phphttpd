phphttpd
========

Description
-----------

This project is _not_ about implementing a httpd daemon in PHP. It's just a little
helper file to make it more easy to write self-contained scripts that should be 
executed by the webserver built right into the PHP executable since PHP 5.4.0.

In fact it's possible to wrap this script in a single executable PHP script that
is able to start the PHP webserver as a background process and act as routing 
script as well.

I myself am using this to build tools that use a webinterface to interact with
the user. Usually i pack these tools in a single executable PHP script.

Usage
-----

As written above this script may either be included from another PHP script or to
build single executable scripts. The functionality of this script should be executed
first in your tool. It tries to start a webserver that is listening on 127.0.0.1:8888
by default, but it also supports option switches to change these settings, when run
from command-line.

The following parameters are supported:

### Parameters

    -b, --bind-ip    A single IP that the webserver will be listening on.
                     (defaults to 127.0.0.1)
    -p, --port       A port number the webserver will be listening on.
                     (defaults to 8888)
    -r, --router     A php script used as request router.
                     (defaults to the current executed script)
    --doc-root       Alternative document-root directory
    --env            Additional environment variable(s) to set. This option
                     can be specified multiple times and the option value has
                     to be in the form 'name=value'.
    --pid-file       A file to write the pid to. the file will be overwritten.
                     (default does not write a PID file)

Example
-------

    #!/usr/bin/env php
    <?php

    require_once(__DIR__ . '/phphttpd.inc.php');

    ?>
    <html>
        <body>
            <h1>hello from phpttpd</h1>
        </body>
    </html>

Requirements
------------

* PHP >= 5.4.0 is required to use this script

License
-------

phphttpd

Copyright (c) 2012-2013, Harald Lapp <harald.lapp@gmail.com>.
All rights reserved.

Redistribution and use in source and binary forms, with or without 
modification, are permitted provided that the following conditions
are met:

  * Redistributions of source code must retain the above copyright
    notice, this list of conditions and the following disclaimer.

  * Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in
    the documentation and/or other materials provided with the
    distribution.

  * Neither the name of Harald Lapp nor the names of its
    contributors may be used to endorse or promote products derived
    from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
