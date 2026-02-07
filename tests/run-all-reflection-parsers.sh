#!/bin/bash

###############################################################################
# Run reflection parser across all PHP Docker versions
#
# This script:
# 1. Builds Docker images for each PHP version (5.6 - 8.4)
# 2. Runs legacy reflection adapter in each container (Stage 1)
# 3. Processes adapted data with modern PHP (Stage 2)
# 4. Outputs JSON files to tests/cache/Reflection{version}.json
#
# Usage:
#   ./tests/run-all-reflection-parsers.sh
#   ./tests/run-all-reflection-parsers.sh --skip-build  # Skip Docker build
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# PHP versions to process
PHP_VERSIONS=("5.6" "7.0" "7.1" "7.2" "7.3" "7.4" "8.0" "8.1" "8.2" "8.3" "8.4")

# Parse arguments
SKIP_BUILD=false
if [[ "$1" == "--skip-build" ]]; then
    SKIP_BUILD=true
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}PHP Reflection Parser - Multi-Version${NC}"
echo -e "${BLUE}========================================${NC}"
echo "Project Root: $PROJECT_ROOT"
echo "PHP Versions: ${PHP_VERSIONS[*]}"
echo "Skip Build: $SKIP_BUILD"
echo -e "${BLUE}========================================${NC}\n"

# Statistics
TOTAL_VERSIONS=${#PHP_VERSIONS[@]}
SUCCESS_COUNT=0
FAILED_COUNT=0
SKIPPED_COUNT=0
declare -a FAILED_VERSIONS
declare -a SUCCESS_VERSIONS

# Create cache directory if it doesn't exist
mkdir -p "$SCRIPT_DIR/cache"

# Process each PHP version
for VERSION in "${PHP_VERSIONS[@]}"; do
    echo -e "\n${YELLOW}========================================${NC}"
    echo -e "${YELLOW}Processing PHP $VERSION${NC}"
    echo -e "${YELLOW}========================================${NC}"

    # Check if Dockerfile exists
    DOCKERFILE_PATH="$SCRIPT_DIR/DockerImages/$VERSION/Dockerfile"
    if [ ! -f "$DOCKERFILE_PATH" ]; then
        echo -e "${RED}✗ Dockerfile not found: $DOCKERFILE_PATH${NC}"
        SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
        continue
    fi

    # Build Docker image
    if [ "$SKIP_BUILD" = false ]; then
        echo -e "${BLUE}[1/4] Building Docker image for PHP $VERSION...${NC}"
        if PHP_VERSION=$VERSION docker compose -f "$PROJECT_ROOT/docker-compose.yml" build 2>&1 | grep -v "WARNING"; then
            echo -e "${GREEN}      ✓ Docker image built successfully${NC}"
        else
            echo -e "${RED}✗ Failed to build Docker image for PHP $VERSION${NC}"
            FAILED_VERSIONS+=("$VERSION")
            FAILED_COUNT=$((FAILED_COUNT + 1))
            continue
        fi
    else
        echo -e "${BLUE}[1/4] Skipping Docker build (--skip-build flag)${NC}"
    fi

    # Run reflection adapter in container (Stage 1 - Legacy PHP 5.6+ compatible)
    echo -e "${BLUE}[2/4] Running reflection adapter for PHP $VERSION (Stage 1)...${NC}"

    # Create temp directory for intermediate data
    TEMP_DATA_FILE="$SCRIPT_DIR/cache/.tmp-reflection-$VERSION.dat"

    # Use docker compose to run the legacy adapter
    if PHP_VERSION=$VERSION docker compose -f "$PROJECT_ROOT/docker-compose.yml" run --rm \
        php_under_test \
        php tests/adapt-legacy-reflection.php $VERSION "/opt/project/phpstorm-stubs/tests/cache/.tmp-reflection-$VERSION.dat"; then
        echo -e "${GREEN}      ✓ Reflection adaptation completed${NC}"
    else
        echo -e "${RED}✗ Failed to run reflection adapter for PHP $VERSION${NC}"
        FAILED_VERSIONS+=("$VERSION")
        FAILED_COUNT=$((FAILED_COUNT + 1))
        continue
    fi

    # Verify temp data file exists
    if [ ! -f "$TEMP_DATA_FILE" ]; then
        echo -e "${RED}✗ Temp data file not found: $TEMP_DATA_FILE${NC}"
        FAILED_VERSIONS+=("$VERSION")
        FAILED_COUNT=$((FAILED_COUNT + 1))
        continue
    fi

    # Process extracted data with modern PHP (Stage 2)
    echo -e "${BLUE}[3/4] Processing reflection data for PHP $VERSION (Stage 2)...${NC}"

    OUTPUT_FILE="$SCRIPT_DIR/cache/Reflection$VERSION.json"

    # Run processor with test_runner container (latest stable PHP)
    if docker compose -f "$PROJECT_ROOT/docker-compose.yml" run --rm \
        test_runner \
        php tests/run-reflection-processor.php "/opt/project/phpstorm-stubs/tests/cache/.tmp-reflection-$VERSION.dat" "/opt/project/phpstorm-stubs/tests/cache/Reflection$VERSION.json"; then
        echo -e "${GREEN}      ✓ Reflection processing completed${NC}"
        # Clean up temp file
        rm -f "$TEMP_DATA_FILE"
    else
        echo -e "${RED}✗ Failed to process reflection data for PHP $VERSION${NC}"
        FAILED_VERSIONS+=("$VERSION")
        FAILED_COUNT=$((FAILED_COUNT + 1))
        continue
    fi

    # Verify output file
    echo -e "${BLUE}[4/4] Verifying output file...${NC}"
    OUTPUT_FILE="$SCRIPT_DIR/cache/Reflection$VERSION.json"

    if [ -f "$OUTPUT_FILE" ]; then
        FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        echo -e "${GREEN}      ✓ Output file created: Reflection$VERSION.json ($FILE_SIZE)${NC}"
        SUCCESS_VERSIONS+=("$VERSION")
        SUCCESS_COUNT=$((SUCCESS_COUNT + 1))
    else
        echo -e "${RED}✗ Output file not found: $OUTPUT_FILE${NC}"
        FAILED_VERSIONS+=("$VERSION")
        FAILED_COUNT=$((FAILED_COUNT + 1))
    fi
done

# Summary
echo -e "\n${BLUE}========================================${NC}"
echo -e "${BLUE}Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo "Total Versions: $TOTAL_VERSIONS"
echo -e "${GREEN}Success: $SUCCESS_COUNT${NC}"
echo -e "${RED}Failed: $FAILED_COUNT${NC}"
echo -e "${YELLOW}Skipped: $SKIPPED_COUNT${NC}"
echo -e "${BLUE}========================================${NC}"

if [ $SUCCESS_COUNT -gt 0 ]; then
    echo -e "\n${GREEN}Successful versions:${NC}"
    for VERSION in "${SUCCESS_VERSIONS[@]}"; do
        echo "  ✓ PHP $VERSION"
    done
fi

if [ $FAILED_COUNT -gt 0 ]; then
    echo -e "\n${RED}Failed versions:${NC}"
    for VERSION in "${FAILED_VERSIONS[@]}"; do
        echo "  ✗ PHP $VERSION"
    done
fi

# List generated files
echo -e "\n${BLUE}Generated files:${NC}"
ls -lh "$SCRIPT_DIR/cache/Reflection"*.json 2>/dev/null || echo "No files found"

echo -e "\n${BLUE}========================================${NC}"
if [ $FAILED_COUNT -eq 0 ] && [ $SUCCESS_COUNT -gt 0 ]; then
    echo -e "${GREEN}✓ All versions processed successfully!${NC}"
    exit 0
else
    if [ $SUCCESS_COUNT -gt 0 ]; then
        echo -e "${YELLOW}⚠ Completed with $FAILED_COUNT failures${NC}"
        exit 1
    else
        echo -e "${RED}✗ All versions failed!${NC}"
        exit 1
    fi
fi
