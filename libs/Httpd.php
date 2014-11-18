<?php

/*
 * This file is part of the 'octris/phphttpd' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris;

/**
 * Httpd class.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Httpd
{
    /**
     * Minimum required PHP Version.
     *
     * @type    string
     */
    const PHP_VERSION = '5.4.0';

    /**
     * Bind to IP address.
     *
     * @type    string
     */
    protected $bind_ip = '127.0.0.1';

    /**
     * Bind to port.
     *
     * @type    int
     */
    protected $bind_port = 8888;

    /**
     * SSL Certificate file.
     *
     * @type    string
     */
    protected $ssl_pem = __DIR__ . '/../etc/stunnel.pem';

    /**
     * Bind to SSL port.
     *
     * @type    string
     */
    protected $bind_ssl = null;

    /**
     * Router script.
     *
     * @type    string
     */
    protected $router = null;

    /**
     * PID file.
     *
     * @type    string
     */
    protected $pid_file = null;

    /**
     * Document root.
     *
     * @type    string
     */
    protected $docroot = null;

    /**
     * Environment.
     *
     * @type    array
     */
    protected $env = array();

    /**
     * PHP settings.
     *
     * @type    array
     */
    protected $settings = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Main application.
     */
    public function main()
    {
        global $argv;

        if (php_sapi_name() == 'cli') {
            // php version check
            if (version_compare(PHP_VERSION, self::PHP_VERSION) < 0) {
                die(sprintf(
                    "Unable to start webserver. Please upgrade to PHP version >= '%s'. Your version is '%s'\n",
                    self::PHP_VERSION,
                    PHP_VERSION
                ));
            }

            // process command-line arguments
            $options = getopt(
                'b:p:r:h',
                array('bind-ip:', 'port:', 'router:', 'pid-file:', 'help', 'env:', 'define:', 'doc-root:', 'ssl:')
            );

            if (isset($options['h']) || isset($options['help'])) {
                printf("Usage: %s [OPTIONS]\n", $argv[0]);
                print "  -b, --bind-ip    A single IP that the webserver will be listening on.\n";
                printf("                   (defaults to %s)\n", $this->bind_ip);
                print "  -p, --port       A port number the webserver will be listening on.\n";
                printf("                   (defaults to %d)\n", $this->bind_port);
                print "  -r, --router     A php script used as request router.\n";
                print "                   (defaults to the current executed script)\n";
                print "  --ssl            Enable SSL with the specified port. This requires 'stunnel'\n";
                print "                   to work.\n";
                print "  --doc-root       Alternative document-root directory\n";
                print "  --env            Additional environment variable(s) to set. This option\n";
                print "                   can be specified multiple times and the option value has\n";
                print "                   to be in the form 'name=value'.\n";
                print "  --define         Additional INI entries to set for PHP. Note however, that\n";
                print "                   'output_buffering' cannot be modified using this option.\n";
                print "  --pid-file       A file to write the pid to. the file will be overwritten.\n";
                print "                   (default does not write a PID file)\n";

                die(0);
            }

            if (isset($options['b'])) {
                $this->bind_ip = $options['b'];
            } elseif (isset($options['bind-ip'])) {
                $this->bind_ip = $options['bind-ip'];
            }
            if (isset($options['p'])) {
                $this->bind_port = $options['p'];
            } elseif (isset($options['port'])) {
                $this->bind_port = $options['port'];
            }
            if (isset($options['ssl'])) {
                if (`which stunnel` == '') {
                    print "SSL requires 'stunnel' to be installed!\n";
                    die(255);
                }

                $this->bind_ssl = $options['ssl'];
            }

            if (isset($options['r'])) {
                $this->router = $options['r'];
            } elseif (isset($options['router'])) {
                $this->router = $options['router'];
            } elseif (is_null($this->router)) {
                $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                $tmp    = array_pop($trace);

                $this->router = $tmp['file'];
            }

            if (!is_file($this->router)) {
                printf(
                    "Request routing script not found '%s'.\n",
                    $this->router
                );
                die(255);
            }

            if (isset($options['doc-root'])) {
                $this->docroot = $options['doc-root'];

                if (!is_dir($this->docroot)) {
                    printf(
                        "Document-root is no directory '%s'.\n",
                        $this->docroot
                    );
                    die(255);
                }
            }

            if (isset($options['pid-file'])) {
                $this->pid_file = $options['pid-file'];
            }

            if (!is_null($this->pid_file) && !touch($this->pid_file)) {
                printf(
                    "Unable to create PID file or PID file is not writable '%s'.\n",
                    $this->pid_file
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
                        $this->env[] = $match[1] . '=' . escapeshellarg($match[2]);
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
                        $this->env[] = $match[1] . '=' . escapeshellarg($match[2]);
                    }
                }
            }

            // start stunnel
            if (!is_null($this->bind_ssl)) {
                $pid_ssl = exec(sprintf("((echo \"
                        cert = %s
                        key  = %s

                        sslVersion = SSLv3

                        socket = l:TCP_NODELAY=1
                        socket = r:TCP_NODELAY=1

                        foreground = yes
                        pid =

                        [https]
                        accept  = %s:%d
                        connect = %s:%d
                    \" | stunnel -fd 0 1>/dev/null 2>&1 & echo $!) &)",
                    $this->ssl_pem,
                    $this->ssl_pem,
                    $this->bind_ip, $this->bind_ssl,
                    $this->bind_ip, $this->bind_port
                ));
                sleep(1);

                if (ctype_digit($pid_ssl) && posix_kill($pid_ssl, 0)) {
                    printf(
                        "%s listening SSL on '%s:%s', PID is %d\n",
                        basename($argv[0]),
                        $this->bind_ip,
                        $this->bind_ssl,
                        $pid_ssl
                    );
                } else {
                    printf(
                        "Unable to start %s SSL on '%s:%s'\n",
                        basename($argv[0]),
                        $this->bind_ip,
                        $this->bind_ssl
                    );
                    die(255);
                }
            }

            // start php's builtin webserver
            $pid = exec(sprintf(
                '((%s %s -d output_buffering=on %s -S %s:%s %s 1>/dev/null 2>&1 & echo $!) &)',
                implode(' ', $this->env),
                PHP_BINARY,
                (!is_null($this->docroot) ? ' -t ' . $this->docroot : ''),
                $this->bind_ip,
                $this->bind_port,
                $this->router
            ));
            sleep(1);

            if (ctype_digit($pid) && posix_kill($pid, 0)) {
                printf(
                    "%s listening on '%s:%s', PID is %d\n",
                    basename($argv[0]),
                    $this->bind_ip,
                    $this->bind_port,
                    $pid
                );
                die(0);
            } else {
                printf(
                    "Unable to start %s on '%s:%s'\n",
                    basename($argv[0]),
                    $this->bind_ip,
                    $this->bind_port
                );
                die(255);
            }

            if (!is_null($this->pid_file)) {
                file_put_contents($this->pid_file, $pid);
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
    }
}

