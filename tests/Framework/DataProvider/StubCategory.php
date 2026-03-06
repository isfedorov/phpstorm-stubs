<?php

namespace StubTests\Sources\DataProvider;

/**
 * Defines categories of PHP stubs based on their bundling with PHP.
 * 
 * This categorization helps differentiate between:
 * - Core PHP functionality
 * - Extensions bundled with PHP
 * - External extensions typically compiled separately
 * - PECL extensions installed via pecl
 */
enum StubCategory: string
{
    /**
     * Core PHP functionality and essential extensions.
     * Directories: Core, date, filter, fpm, hash, meta, pcre, random, Phar, 
     * Reflection, regex, session, SPL, standard, superglobals, tokenizer, uri
     */
    case CORE = 'core';
    
    /**
     * Extensions bundled with PHP but can be disabled at compile time.
     * Directories: apache, bcmath, calendar, ctype, dba, exif, fileinfo, ftp, 
     * gd, iconv, intl, json, litespeed, mbstring, pcntl, PDO, posix, shmop, 
     * sockets, sqlite3, sysvmsg, sysvsem, sysvshm, xmlrpc, zlib
     */
    case BUNDLED = 'bundled';
    
    /**
     * External extensions that are commonly available but require separate compilation.
     * Directories: aerospike, bz2, curl, dom, enchant, gettext, gmp, imap, ldap, 
     * libxml, mcrypt, mssql, mysql, mysqli, oci8, odbc, openssl, pdo_ibm, 
     * pdo_mysql, pdo_pgsql, pdo_sqlite, pgsql, pspell, readline, recode, 
     * SimpleXML, snmp, soap, sodium, sybase, tidy, wddx, xml, xmlreader, 
     * xmlwriter, xsl, Zend OPcache, zip
     */
    case EXTERNAL = 'external';
    
    /**
     * PECL extensions - third-party extensions not bundled with PHP.
     * All directories not in CORE, BUNDLED, or EXTERNAL categories.
     */
    case PECL = 'pecl';

    /**
     * Get the list of directories for this category.
     *
     * @return string[]
     */
    public function getDirectories(): array
    {
        return match($this) {
            self::CORE => [
                'Core',
                'date',
                'filter',
                'fpm',
                'hash',
                'meta',
                'pcre',
                'random',
                'Phar',
                'Reflection',
                'regex',
                'session',
                'SPL',
                'standard',
                'superglobals',
                'tokenizer',
                'uri'
            ],
            self::BUNDLED => [
                'apache',
                'bcmath',
                'calendar',
                'ctype',
                'dba',
                'exif',
                'fileinfo',
                'ftp',
                'gd',
                'iconv',
                'intl',
                'json',
                'litespeed',
                'mbstring',
                'pcntl',
                'PDO',
                'posix',
                'shmop',
                'sockets',
                'sqlite3',
                'sysvmsg',
                'sysvsem',
                'sysvshm',
                'xmlrpc',
                'zlib'
            ],
            self::EXTERNAL => [
                'aerospike',
                'bz2',
                'curl',
                'dom',
                'enchant',
                'gettext',
                'gmp',
                'imap',
                'ldap',
                'libxml',
                'mcrypt',
                'mssql',
                'mysql',
                'mysqli',
                'oci8',
                'odbc',
                'openssl',
                'pdo_ibm',
                'pdo_mysql',
                'pdo_pgsql',
                'pdo_sqlite',
                'pgsql',
                'pspell',
                'readline',
                'recode',
                'SimpleXML',
                'snmp',
                'soap',
                'sodium',
                'sybase',
                'tidy',
                'wddx',
                'xml',
                'xmlreader',
                'xmlwriter',
                'xsl',
                'Zend OPcache',
                'zip'
            ],
            self::PECL => [
                'Ev',
                'FFI',
                'LuaSandbox',
                'Parle',
                'SQLite',
                'SaxonC',
                'SplType',
                'ZendCache',
                'ZendDebugger',
                'ZendUtils',
                'amqp',
                'apcu',
                'ast',
                'blackfire',
                'brotli',
                'cassandra',
                'com_dotnet',
                'couchbase',
                'couchbase_v2',
                'crypto',
                'cubrid',
                'decimal',
                'dio',
                'ds',
                'eio',
                'elastic_apm',
                'event',
                'expect',
                'fann',
                'ffmpeg',
                'frankenphp',
                'gearman',
                'geoip',
                'geos',
                'gmagick',
                'gnupg',
                'grpc',
                'http',
                'ibm_db2',
                'igbinary',
                'imagick',
                'inotify',
                'interbase',
                'judy',
                'leveldb',
                'libevent',
                'libsodium',
                'libvirt-php',
                'lua',
                'lzf',
                'mailparse',
                'mapscript',
                'memcache',
                'memcached',
                'meminfo',
                'ming',
                'mongo',
                'mongodb',
                'mosquitto-php',
                'mqseries',
                'msgpack',
                'mysql_xdevapi',
                'ncurses',
                'newrelic',
                'oauth',
                'opentelemetry',
                'pam',
                'parallel',
                'pcov',
                'pdflib',
                'phpdbg',
                'pq',
                'pthreads',
                'radius',
                'rar',
                'rdkafka',
                'redis',
                'relay',
                'rpminfo',
                'rrd',
                'simdjson',
                'simple_kafka_client',
                'snappy',
                'solr',
                'sqlsrv',
                'ssh2',
                'stats',
                'stomp',
                'suhosin',
                'svm',
                'svn',
                'swoole',
                'sync',
                'uopz',
                'uploadprogress',
                'uuid',
                'uv',
                'v8js',
                'win32service',
                'winbinder',
                'wincache',
                'xcache',
                'xdebug',
                'xdiff',
                'xhprof',
                'xlswriter',
                'xxtea',
                'yaf',
                'yaml',
                'yar',
                'zend',
                'zmq',
                'zookeeper',
                'zstd',
            ]
        };
    }

    /**
     * Check if a given directory path belongs to this category.
     *
     * @param string $directoryName The directory name (not full path)
     * @return bool
     */
    public function containsDirectory(string $directoryName): bool
    {
        return in_array($directoryName, $this->getDirectories(), true);
    }
}
