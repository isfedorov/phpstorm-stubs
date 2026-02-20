# Stub Data Providers

This directory contains data providers for accessing PHP stub files in tests.

## Available Providers

### `AllStubsDataProvider`
Returns all stub files from all directories (Core, Bundled, External, and PECL extensions).

**Use when:** You need to test against all available stubs.

```php
$provider = new AllStubsDataProvider();
$allFiles = $provider->getAllStubFiles(); // ~526 files
```

### `CoreStubsDataProvider`
Returns stub files filtered by category (Core, Bundled, External, or PECL).

**Use when:** 
- You need to test only specific categories of stubs
- You want to improve test performance by reducing the number of files
- You're validating core PHP functionality (e.g., type hints)

```php
use StubTests\Sources\DataProvider\CoreStubsDataProvider;
use StubTests\Sources\DataProvider\StubCategory;

// Only Core stubs (~75 files - 85.7% reduction)
$provider = new CoreStubsDataProvider(StubCategory::CORE);

// Core + Bundled stubs
$provider = new CoreStubsDataProvider([StubCategory::CORE, StubCategory::BUNDLED]);

// Only PECL extensions
$provider = new CoreStubsDataProvider(StubCategory::PECL);

// Core + Bundled + External (recommended for type hint validation)
$provider = new CoreStubsDataProvider([
    StubCategory::CORE,
    StubCategory::BUNDLED,
    StubCategory::EXTERNAL
]);
```

## Stub Categories

### `StubCategory::CORE`
Core PHP functionality and essential extensions that are always available.

**Directories:** Core, date, filter, fpm, hash, meta, pcre, random, Phar, Reflection, regex, session, SPL, standard, superglobals, tokenizer, uri

### `StubCategory::BUNDLED`
Extensions bundled with PHP but can be disabled at compile time.

**Directories:** apache, bcmath, calendar, ctype, dba, exif, fileinfo, ftp, gd, iconv, intl, json, litespeed, mbstring, pcntl, PDO, posix, shmop, sockets, sqlite3, sysvmsg, sysvsem, sysvshm, xmlrpc, zlib

### `StubCategory::EXTERNAL`
External extensions that are commonly available but require separate compilation.

**Directories:** aerospike, bz2, curl, dom, enchant, gettext, gmp, imap, ldap, libxml, mcrypt, mssql, mysql, mysqli, oci8, odbc, openssl, pdo_ibm, pdo_mysql, pdo_pgsql, pdo_sqlite, pgsql, pspell, readline, recode, SimpleXML, snmp, soap, sodium, sybase, tidy, wddx, xml, xmlreader, xmlwriter, xsl, Zend OPcache, zip

### `StubCategory::PECL`
PECL extensions - third-party extensions not bundled with PHP (installed via `pecl install`).

**Examples:** redis, mongodb, xdebug, imagick, memcached, etc.

## Performance Benefits

Using `CoreStubsDataProvider` with specific categories significantly improves test performance:

```
Core only:              75 files (~2-3ms)
Core + Bundled:        ~150 files
Core + Bundled + Ext:  ~250 files
All stubs:             526 files (~50-80ms)
```

**Performance gain:** 85.7% fewer files when using Core only vs All stubs.

## Usage Examples

### Example 1: Parse Only Core Stubs

```php
use StubTests\Sources\DataProvider\CoreStubsDataProvider;
use StubTests\Sources\DataProvider\StubCategory;
use StubTests\Sources\Parsers\Entities\Stubs\AllStubsParser;

$dataProvider = new CoreStubsDataProvider(StubCategory::CORE);
$parser = new AllStubsParser(
    $dataProvider,
    $storageManager,
    [new StubClassParser(), new StubFunctionParser()]
);
$parser->parseAll();
```

### Example 2: Type Hint Validation (Exclude PECL)

```php
// Type hint validation should only check official PHP extensions
$dataProvider = new CoreStubsDataProvider([
    StubCategory::CORE,
    StubCategory::BUNDLED,
    StubCategory::EXTERNAL
]);
```

### Example 3: Test Only Third-Party Extensions

```php
// Test PECL extensions separately
$dataProvider = new CoreStubsDataProvider(StubCategory::PECL);
```

## When to Use Which Provider

| Test Type | Recommended Provider | Reason |
|-----------|---------------------|---------|
| PhpDoc validation | `AllStubsDataProvider` | All stubs should have proper documentation |
| Type hint validation | `CoreStubsDataProvider([CORE, BUNDLED, EXTERNAL])` | Only official extensions have strict type requirements |
| Core functionality tests | `CoreStubsDataProvider(CORE)` | Faster, focuses on essential PHP |
| PECL extension tests | `CoreStubsDataProvider(PECL)` | Isolate third-party extensions |
| General validation | `AllStubsDataProvider` | Test everything |

## Implementation Details

Both providers implement the `StubsDataProvider` interface:

```php
interface StubsDataProvider
{
    public function getStubFileContent(string $path): string;
    public function getAllStubFiles(): array;
}
```

`CoreStubsDataProvider` internally:
1. Filters directories based on category configuration
2. Recursively collects PHP files from matching directories
3. Excludes: vendor, tests, .git, .idea, PhpStormStubsMap.php, .phpstorm.meta.php
4. Caches results for performance

## Testing

See test files for examples:
- `tests/Unit/DataProviders/CoreStubsDataProviderTest.php` - Unit tests
- `tests/Unit/DataProviders/CoreStubsDataProviderUsageExampleTest.php` - Usage examples
