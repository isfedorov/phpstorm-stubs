<?php

// Start of Core v.5.3.6-13ubuntu3.2

/**
 * Fatal run-time errors. These indicate errors that can not be
 * recovered from, such as a memory allocation problem.
 * Execution of the script is halted.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_ERROR', 1);

/**
 * Catchable fatal error. It indicates that a probably dangerous error
 * occurred, but did not leave the Engine in an unstable state. If the error
 * is not caught by a user defined handle (see also
 * <b>set_error_handler</b>), the application aborts as it
 * was an <b>E_ERROR</b>.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_RECOVERABLE_ERROR', 4096);

/**
 * Run-time warnings (non-fatal errors). Execution of the script is not
 * halted.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_WARNING', 2);

/**
 * Compile-time parse errors. Parse errors should only be generated by
 * the parser.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_PARSE', 4);

/**
 * Run-time notices. Indicate that the script encountered something that
 * could indicate an error, but could also happen in the normal course of
 * running a script.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_NOTICE', 8);

/**
 * Enable to have PHP suggest changes
 * to your code which will ensure the best interoperability
 * and forward compatibility of your code.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_STRICT', 2048);

/**
 * Run-time notices. Enable this to receive warnings about code
 * that will not work in future versions.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_DEPRECATED', 8192);

/**
 * Fatal errors that occur during PHP's initial startup. This is like an
 * <b>E_ERROR</b>, except it is generated by the core of PHP.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_CORE_ERROR', 16);

/**
 * Warnings (non-fatal errors) that occur during PHP's initial startup.
 * This is like an <b>E_WARNING</b>, except it is generated
 * by the core of PHP.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_CORE_WARNING', 32);

/**
 * Fatal compile-time errors. This is like an <b>E_ERROR</b>,
 * except it is generated by the Zend Scripting Engine.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_COMPILE_ERROR', 64);

/**
 * Compile-time warnings (non-fatal errors). This is like an
 * <b>E_WARNING</b>, except it is generated by the Zend
 * Scripting Engine.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_COMPILE_WARNING', 128);

/**
 * User-generated error message. This is like an
 * <b>E_ERROR</b>, except it is generated in PHP code by
 * using the PHP function <b>trigger_error</b>.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_USER_ERROR', 256);

/**
 * User-generated warning message. This is like an
 * <b>E_WARNING</b>, except it is generated in PHP code by
 * using the PHP function <b>trigger_error</b>.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_USER_WARNING', 512);

/**
 * User-generated notice message. This is like an
 * <b>E_NOTICE</b>, except it is generated in PHP code by
 * using the PHP function <b>trigger_error</b>.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_USER_NOTICE', 1024);

/**
 * User-generated warning message. This is like an
 * <b>E_DEPRECATED</b>, except it is generated in PHP code by
 * using the PHP function <b>trigger_error</b>.
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_USER_DEPRECATED', 16384);

/**
 * All errors and warnings, as supported, except of level
 * <b>E_STRICT</b> prior to PHP 5.4.0.
 * Value of <b>E_ALL</b> is 32767 since PHP 5.4.x,
 * 30719 in PHP 5.3.x, 6143 in PHP 5.2.x, 2047 previously
 * @link https://php.net/manual/en/errorfunc.constants.php
 */
define('E_ALL', 30719);
define('DEBUG_BACKTRACE_PROVIDE_OBJECT', 1);
define('DEBUG_BACKTRACE_IGNORE_ARGS', 2);
define('S_MEMORY', 1);
define('S_VARS', 4);
define('S_FILES', 8);
define('S_INCLUDE', 16);
define('S_SQL', 32);
define('S_EXECUTOR', 64);
define('S_MAIL', 128);
define('S_SESSION', 256);
define('S_MISC', 2);
define('S_INTERNAL', 536870912);
define('S_ALL', 511);

define('true', (bool)1, true);
define('false', (bool)0, true);
define('null', null, true);
define('ZEND_THREAD_SAFE', false);
define('ZEND_DEBUG_BUILD', false);
define('PHP_WINDOWS_VERSION_BUILD', 0);
define('PHP_WINDOWS_VERSION_MAJOR', 0);
define('PHP_WINDOWS_VERSION_MINOR', 0);
define('PHP_WINDOWS_VERSION_PLATFORM', 0);
define('PHP_WINDOWS_VERSION_PRODUCTTYPE', 0);
define('PHP_WINDOWS_VERSION_SP_MAJOR', 0);
define('PHP_WINDOWS_VERSION_SP_MINOR', 0);
define('PHP_WINDOWS_VERSION_SUITEMASK', 0);
define('PHP_WINDOWS_NT_DOMAIN_CONTROLLER', 2);
define('PHP_WINDOWS_NT_SERVER', 3);
define('PHP_WINDOWS_NT_WORKSTATION', 1);
/**
 * @since 7.4
 */
define('PHP_WINDOWS_EVENT_CTRL_C', 0);
/**
 * @since 7.4
 */
define('PHP_WINDOWS_EVENT_CTRL_BREAK', 1);
define('PHP_VERSION', "5.3.6-13ubuntu3.2");
define('PHP_MAJOR_VERSION', 5);
define('PHP_MINOR_VERSION', 3);
define('PHP_RELEASE_VERSION', 6);
define('PHP_EXTRA_VERSION', "-13ubuntu3.2");
define('PHP_VERSION_ID', 50306);
define('PHP_ZTS', 0);
define('PHP_DEBUG', 0);
define('PHP_OS', "Linux");
/**
 * The operating system family PHP was built for. Either of 'Windows', 'BSD', 'Darwin', 'Solaris', 'Linux' or 'Unknown'. Available as of PHP 7.2.0.
 * @since 7.2
 */
define('PHP_OS_FAMILY', "Linux");
define('PHP_SAPI', "cli");
/**
 * @since 7.4
 */
define('PHP_CLI_PROCESS_TITLE', 1);
define('DEFAULT_INCLUDE_PATH', ".:/usr/share/php:/usr/share/pear");
define('PEAR_INSTALL_DIR', "/usr/share/php");
define('PEAR_EXTENSION_DIR', "/usr/lib/php5/20090626");
define('PHP_EXTENSION_DIR', "/usr/lib/php5/20090626");
/**
 * Specifies where the binaries were installed into.
 * @link https://php.net/manual/en/reserved.constants.php
 */
define('PHP_BINARY', '/usr/local/php/bin/php');
define('PHP_PREFIX', "/usr");
define('PHP_BINDIR', "/usr/bin");
define('PHP_LIBDIR', "/usr/lib/php5");
define('PHP_DATADIR', "/usr/share");
define('PHP_SYSCONFDIR', "/etc");
define('PHP_LOCALSTATEDIR', "/var");
define('PHP_CONFIG_FILE_PATH', "/etc/php5/cli");
define('PHP_CONFIG_FILE_SCAN_DIR', "/etc/php5/cli/conf.d");
define('PHP_SHLIB_SUFFIX', "so");
define('PHP_EOL', "\n");
define('SUHOSIN_PATCH', 1);
define('SUHOSIN_PATCH_VERSION', "0.9.10");
define('PHP_MAXPATHLEN', 4096);
define('PHP_INT_MAX', 9223372036854775807);
define('PHP_INT_MIN', -9223372036854775808);
define('PHP_INT_SIZE', 8);
/**
 * Number of decimal digits that can be rounded into a float and back without precision loss. Available as of PHP 7.2.0.
 * @since 7.2
 */
define('PHP_FLOAT_DIG', 15);
/**
 * Smallest representable positive number x, so that x + 1.0 != 1.0. Available as of PHP 7.2.0.
 * @since 7.2
 */
define('PHP_FLOAT_EPSILON', 2.2204460492503e-16);

/**
 * Largest representable floating point number. Available as of PHP 7.2.0.
 * @since 7.2
 */
define('PHP_FLOAT_MAX', 1.7976931348623e+308);
/**
 * Smallest representable floating point number. Available as of PHP 7.2.0.
 * @since 7.2
 */
define('PHP_FLOAT_MIN', 2.2250738585072e-308);
define('ZEND_MULTIBYTE', 0);
define('PHP_OUTPUT_HANDLER_START', 1);
define('PHP_OUTPUT_HANDLER_CONT', 2);
define('PHP_OUTPUT_HANDLER_END', 4);
define('UPLOAD_ERR_OK', 0);
define('UPLOAD_ERR_INI_SIZE', 1);
define('UPLOAD_ERR_FORM_SIZE', 2);
define('UPLOAD_ERR_PARTIAL', 3);
define('UPLOAD_ERR_NO_FILE', 4);
define('UPLOAD_ERR_NO_TMP_DIR', 6);
define('UPLOAD_ERR_CANT_WRITE', 7);
define('UPLOAD_ERR_EXTENSION', 8);
define('STDIN', fopen('php://stdin', 'r'));
define('STDOUT', fopen('php://stdout', 'w'));
define('STDERR', fopen('php://stderr', 'w'));

define('PHP_FD_SETSIZE', 1024);

/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_WRITE', 0);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_FLUSH', 4);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_CLEAN', 2);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_FINAL', 8);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_CLEANABLE', 16);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_FLUSHABLE', 32);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_REMOVABLE', 64);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_STDFLAGS', 112);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_STARTED', 4096);
/** @link https://php.net/manual/en/outcontrol.constants.php */
define('PHP_OUTPUT_HANDLER_DISABLED', 8192);

/**
 * @since 8.4
 */
const PHP_SBINDIR = '/usr/local/sbin', PHP_OUTPUT_HANDLER_PROCESSED = 16384;

/**
 * Specifies where the manpages were installed into.
 * @since 5.3.7
 * @link https://php.net/manual/en/reserved.constants.php
 */
define('PHP_MANDIR', '/usr/local/php/php/man');
