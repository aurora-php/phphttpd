<?php

/*

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

*/

if (php_sapi_name() == 'cli') {
    // initialization
    $version   = '5.4.0';
    $bind_ip   = '127.0.0.1';
    $bind_port = '8888';
    $router    = null;
    $pid_file  = null;
    $docroot   = null;
    $env       = array();
    $define    = array();

    // php version check
    if (version_compare(PHP_VERSION, $version) < 0) {
        die(sprintf(
            "Unable to start webserver. Please upgrade to PHP version >= '%s'. Your version is '%s'\n",
            $version,
            PHP_VERSION
        ));
    }

    // process command-line arguments
    $options = getopt(
        'b:p:r:h',
        array('bind-ip:', 'port:', 'router:', 'pid-file:', 'help', 'env:', 'define:', 'doc-root:')
    );

    if (isset($options['h']) || isset($options['help'])) {
        printf("Usage: %s [OPTIONS]\n", $argv[0]);
        print "  -b, --bind-ip    A single IP that the webserver will be listening on.\n";
        printf("                   (defaults to %s)\n", $bind_ip);
        print "  -p, --port       A port number the webserver will be listening on.\n";
        printf("                   (defaults to %d)\n", $bind_port);
        print "  -r, --router     A php script used as request router.\n";
        print "                   (defaults to the current executed script)\n";
        print "  --doc-root       Alternative document-root directory\n";
        print "  --env            Additional environment variable(s) to set. This option\n";
        print "                   can be specified multiple times and the option value has\n";
        print "                   to be in the form 'name=value'.\n";
        print "  --define         Additional INI entries to set for PHP. Note however, that\n"
        print "                   'output_buffering' cannot be modified using this option.\n";
        print "  --pid-file       A file to write the pid to. the file will be overwritten.\n";
        print "                   (default does not write a PID file)\n";

        die(0);
    }

    if (isset($options['b'])) {
        $bind_ip = $options['b'];
    } elseif (isset($options['bind-ip'])) {
        $bind_ip = $options['bind-ip'];
    }
    if (isset($options['p'])) {
        $bind_port = $options['p'];
    } elseif (isset($options['port'])) {
        $bind_port = $options['port'];
    }
    if (isset($options['r'])) {
        $router = $options['r'];
    } elseif (isset($options['router'])) {
        $router = $options['router'];
    } elseif (is_null($router)) {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $tmp    = array_pop($trace);
        $router = $tmp['file'];
    }

    if (!is_file($router)) {
        printf(
            "Request routing script not found '%s'.\n",
            $router
        );
        die(255);
    }

    if (isset($options['doc-root'])) {
        $docroot = $options['doc-root'];
        
        if (!is_dir($docroot)) {
            printf(
                "Document-root is no directory '%s'.\n",
                $docroot
            );
            die(255);
        }
    }

    if (isset($options['pid-file'])) {
        $pid_file = $options['pid-file'];
    }

    if (!is_null($pid_file) && !touch($pid_file)) {
        printf(
            "Unable to create PID file or PID file is not writable '%s'.\n",
            $pid_file
        );
        die(255);
    }

    if (isset($options['env'])) {
        $tmp = (is_array($options['env'])
                ? $options['env']
                : (array)$options['env']);

        foreach ($tmp as $_tmp) {
            if (!preg_match('/^([a-z_]+[a-z0-9_]*)=(.*)$/i', $_tmp, $match)) {
                printf(
                    "WARNING: skipping invalid environment variable '%s'.\n",
                    $_tmp
                );
            } else {
                $env[] = $match[1] . '=' . escapeshellarg($match[2]);
            }
        }
    }
    
    if (isset($options['define'])) {
        $tmp = (is_array($options['define'])
                ? $options['env']
                : (array)$options['define']);

        foreach ($tmp as $_tmp) {
            if (!preg_match('/^([a-z_]+[a-z0-9_]*)=(.*)$/i', $_tmp, $match)) {
                printf(
                    "WARNING: skipping invalid environment variable '%s'.\n",
                    $_tmp
                );
            } else {
                $env[] = $match[1] . '=' . escapeshellarg($match[2]);
            }
        }
    }

    // start php's builtin webserver
    $pid = exec(sprintf(
        '((%s %s -d output_buffering=on %s -S %s:%s %s 1>/dev/null 2>&1 & echo $!) &)',
        implode(' ', $env),
        PHP_BINARY,
        (!is_null($docroot) ? ' -t ' . $docroot : ''),
        $bind_ip,
        $bind_port,
        $router
    ));
    sleep(1);

    if (ctype_digit($pid) && posix_kill($pid, 0)) {
        printf(
            "%s listening on '%s:%s', PID is %d\n",
            basename($argv[0]),
            $bind_ip,
            $bind_port,
            $pid
        );
        die(0);
    } else {
        printf(
            "Unable to start %s on '%s:%s'\n",
            basename($argv[0]),
            $bind_ip,
            $bind_port
        );
        die(255);
    }

    if (!is_null($pid_file)) {
        file_put_contents($pid_file, $pid);
    }

    exit(0);
} elseif (php_sapi_name() != 'cli-server') {
    printf(
        "unable to execute '%s' in environment '%s'\n",
        basename($argv[0]),
        php_sapi_name()
    );
    die(255);
}

// remove possible shebang from output (started using '-d output_buffering=on' [see above])
ob_end_clean();
