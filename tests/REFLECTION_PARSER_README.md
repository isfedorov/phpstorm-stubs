# Reflection Parser Scripts

This directory contains scripts for parsing PHP reflection data across multiple PHP versions using Docker containers.

## Overview

The reflection parser system extracts runtime PHP information (classes, functions, interfaces, enums, constants) and saves it to JSON files for testing and comparison purposes.

## Available Scripts

### 1. `run-reflection-parser.php`
**Purpose**: Parse reflection data for the current PHP runtime

**Usage**:
```bash
# Use current PHP version
php tests/run-reflection-parser.php

# Specify version
php tests/run-reflection-parser.php 8.3
```

**Output**: `tests/cache/Reflection{version}.json`

### 2. `run-reflection-docker.sh`
**Purpose**: Parse reflection data for a single PHP version using Docker

**Usage**:
```bash
# Build and run for PHP 8.3
./tests/run-reflection-docker.sh 8.3

# Skip build (use existing image)
./tests/run-reflection-docker.sh 8.3 --skip-build
```

**Features**:
- Builds Docker image for specified PHP version
- Runs reflection parser inside container
- Verifies output and shows statistics
- Color-coded output for easy reading

### 3. `run-all-reflection-parsers.sh`
**Purpose**: Parse reflection data for ALL PHP versions (5.6 - 8.4)

**Usage**:
```bash
# Build and run for all versions
./tests/run-all-reflection-parsers.sh

# Skip build (use existing images)
./tests/run-all-reflection-parsers.sh --skip-build
```

**Features**:
- Processes PHP versions: 5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
- Shows progress for each version
- Provides summary statistics
- Lists successful and failed versions
- Color-coded output

**Output**: Multiple files in `tests/cache/`:
- `Reflection5.6.json`
- `Reflection7.0.json`
- `Reflection7.1.json`
- ... and so on

### 4. `run-stubs-parser.php`
**Purpose**: Parse all PHP stub files from the project

**Usage**:
```bash
# Use current PHP version
php tests/run-stubs-parser.php

# Specify version
php tests/run-stubs-parser.php 8.3
```

**Output**: `tests/cache/Stubs{version}.json`

## Output Format

All scripts generate JSON files with the following structure:

```json
[
  {
    "_type": "PHPClass",
    "name": "Exception",
    "id": "\\Exception",
    "namespace": "\\",
    "isFinal": false,
    "isReadonly": false
  },
  {
    "_type": "PHPFunction",
    "name": "strlen",
    "id": "\\strlen",
    "namespace": "\\",
    "isDeprecated": false,
    "returnType": "int",
    "parameters": [...]
  },
  ...
]
```

Supported entity types:
- `PHPClass` - Classes
- `PHPFunction` - Functions
- `PHPInterface` - Interfaces
- `PHPEnum` - Enums (PHP 8.1+)
- `PHPConstant` - Constants

## Docker Setup

### Available PHP Versions

Docker images are available in `tests/DockerImages/`:
- 5.6
- 7.0, 7.1, 7.2, 7.3, 7.4
- 8.0, 8.1, 8.2, 8.3, 8.4

### Docker Compose Configuration

The scripts use `docker-compose.yml` which defines:
- `php_under_test` service for running PHP code
- Volume mounting: `.:/opt/project/phpstorm-stubs`
- Environment variable: `PHP_VERSION`

### Manual Docker Commands

If you prefer manual control:

```bash
# Build specific version
PHP_VERSION=8.3 docker compose build php_under_test

# Run reflection parser
PHP_VERSION=8.3 docker compose run --rm php_under_test \
  php tests/run-reflection-parser.php 8.3

# Run stubs parser
PHP_VERSION=8.3 docker compose run --rm php_under_test \
  php tests/run-stubs-parser.php 8.3
```

## Examples

### Example 1: Parse reflection for PHP 8.3
```bash
./tests/run-reflection-docker.sh 8.3
```

Output:
```
========================================
PHP Reflection Parser - Docker
========================================
PHP Version: 8.3
...
[1/3] Building Docker image for PHP 8.3...
      ✓ Docker image built successfully

[2/3] Running reflection parser for PHP 8.3...
      ✓ Reflection parsing completed

[3/3] Verifying output file...
      ✓ Output file created: Reflection8.3.json
      File Size: 2.3M
      Entities: 3847
...
```

### Example 2: Parse all versions
```bash
./tests/run-all-reflection-parsers.sh
```

This will process all 11 PHP versions and show a summary:
```
========================================
Summary
========================================
Total Versions: 11
Success: 11
Failed: 0
Skipped: 0
========================================
```

### Example 3: Quick update (skip build)
```bash
# If images are already built, skip rebuild for faster execution
./tests/run-all-reflection-parsers.sh --skip-build
```

## Troubleshooting

### Error: "Dockerfile not found"
**Solution**: Ensure you're running from the project root or tests directory.

### Error: "Docker image build failed"
**Solution**:
1. Check Docker is running: `docker ps`
2. Check Dockerfile exists: `ls tests/DockerImages/{version}/Dockerfile`
3. Try building manually: `PHP_VERSION=8.3 docker compose build php_under_test`

### Error: "Output file not found"
**Solution**:
1. Check script had write permissions
2. Ensure cache directory exists: `mkdir -p tests/cache`
3. Check for errors in parser output

### Missing extensions in reflection data
**Solution**: The Docker images include many common extensions. To add more:
1. Edit `tests/DockerImages/{version}/Dockerfile`
2. Add `docker-php-ext-install {extension}`
3. Rebuild: `PHP_VERSION={version} docker compose build php_under_test`

## Performance

Typical execution times:
- Single version (with build): ~2-5 minutes
- Single version (skip build): ~10-30 seconds
- All versions (with build): ~30-60 minutes
- All versions (skip build): ~5-10 minutes

## Integration with Tests

The generated JSON files are automatically used by:
- `Runner::getReflection($version)` - Loads from cache
- Test suites comparing stubs vs reflection
- Type validation tests

## File Structure

```
tests/
├── cache/                          # Generated JSON files
│   ├── Reflection5.6.json
│   ├── Reflection7.0.json
│   ├── ...
│   └── Stubs8.3.json
├── DockerImages/                   # Docker configurations
│   ├── 5.6/Dockerfile
│   ├── 7.0/Dockerfile
│   ├── ...
│   └── 8.4/Dockerfile
├── run-reflection-parser.php       # PHP script (single version)
├── run-reflection-docker.sh        # Bash script (single version in Docker)
├── run-all-reflection-parsers.sh   # Bash script (all versions in Docker)
└── run-stubs-parser.php           # PHP script (parse stubs)
```

## See Also

- Main test runner: `docker compose -f docker-compose.yml run test_runner`
- PHPUnit tests: `vendor/bin/phpunit`
- Stub map generator: `php tests/Tools/generate-stub-map`
