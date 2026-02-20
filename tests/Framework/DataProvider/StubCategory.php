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
            self::PECL => []  // PECL is everything not in other categories
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
        if ($this === self::PECL) {
            // PECL contains everything not in other categories
            foreach ([self::CORE, self::BUNDLED, self::EXTERNAL] as $category) {
                if ($category->containsDirectory($directoryName)) {
                    return false;
                }
            }
            return true;
        }
        
        return in_array($directoryName, $this->getDirectories(), true);
    }
}
