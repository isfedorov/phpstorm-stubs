#!/bin/bash

###############################################################################
# Run reflection parser for a single PHP version in Docker
#
# Usage:
#   ./tests/run-reflection-docker.sh 8.3
#   ./tests/run-reflection-docker.sh 8.3 --skip-build
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

# Parse arguments
if [ $# -lt 1 ]; then
    echo -e "${RED}Error: PHP version required${NC}"
    echo "Usage: $0 <php-version> [--skip-build]"
    echo "Example: $0 8.3"
    echo "Example: $0 8.3 --skip-build"
    exit 1
fi

VERSION=$1
SKIP_BUILD=false

if [[ "$2" == "--skip-build" ]]; then
    SKIP_BUILD=true
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}PHP Reflection Parser - Docker${NC}"
echo -e "${BLUE}========================================${NC}"
echo "PHP Version: $VERSION"
echo "Project Root: $PROJECT_ROOT"
echo "Skip Build: $SKIP_BUILD"
echo -e "${BLUE}========================================${NC}\n"

# Check if Dockerfile exists
DOCKERFILE_PATH="$SCRIPT_DIR/DockerImages/$VERSION/Dockerfile"
if [ ! -f "$DOCKERFILE_PATH" ]; then
    echo -e "${RED}✗ Dockerfile not found: $DOCKERFILE_PATH${NC}"
    echo "Available versions:"
    ls -1 "$SCRIPT_DIR/DockerImages" | grep -E "^[0-9]"
    exit 1
fi

# Create cache directory if it doesn't exist
mkdir -p "$SCRIPT_DIR/cache"

# Build Docker image
if [ "$SKIP_BUILD" = false ]; then
    echo -e "${BLUE}[1/4] Building Docker image for PHP $VERSION...${NC}"
    if PHP_VERSION=$VERSION docker compose -f "$PROJECT_ROOT/docker-compose.yml" build php_under_test; then
        echo -e "${GREEN}      ✓ Docker image built successfully${NC}\n"
    else
        echo -e "${RED}✗ Failed to build Docker image for PHP $VERSION${NC}"
        exit 1
    fi
else
    echo -e "${BLUE}[1/4] Skipping Docker build (--skip-build flag)${NC}\n"
fi

# Run reflection extractor in container (Stage 1 - Legacy PHP 5.6+ compatible)
echo -e "${BLUE}[2/4] Running reflection extractor for PHP $VERSION (Stage 1)...${NC}"

TEMP_DATA_FILE="$SCRIPT_DIR/cache/.tmp-reflection-$VERSION.dat"

if PHP_VERSION=$VERSION docker compose -f "$PROJECT_ROOT/docker-compose.yml" run --rm \
    php_under_test \
    php tests/run-reflection-extractor-legacy.php $VERSION "/opt/project/phpstorm-stubs/tests/cache/.tmp-reflection-$VERSION.dat"; then
    echo -e "${GREEN}      ✓ Reflection extraction completed${NC}\n"
else
    echo -e "${RED}✗ Failed to run reflection extractor for PHP $VERSION${NC}"
    exit 1
fi

# Verify temp data file exists
if [ ! -f "$TEMP_DATA_FILE" ]; then
    echo -e "${RED}✗ Temp data file not found: $TEMP_DATA_FILE${NC}"
    exit 1
fi

# Process extracted data with modern PHP (Stage 2)
echo -e "${BLUE}[3/4] Processing reflection data for PHP $VERSION (Stage 2)...${NC}"

OUTPUT_FILE="$SCRIPT_DIR/cache/Reflection$VERSION.json"

# Run processor with test_runner container (latest stable PHP)
if docker compose -f "$PROJECT_ROOT/docker-compose.yml" run --rm \
    test_runner \
    php tests/run-reflection-processor.php "/opt/project/phpstorm-stubs/tests/cache/.tmp-reflection-$VERSION.dat" "/opt/project/phpstorm-stubs/tests/cache/Reflection$VERSION.json"; then
    echo -e "${GREEN}      ✓ Reflection processing completed${NC}\n"
    # Clean up temp file
    rm -f "$TEMP_DATA_FILE"
else
    echo -e "${RED}✗ Failed to process reflection data for PHP $VERSION${NC}"
    exit 1
fi

# Verify output file
echo -e "${BLUE}[4/4] Verifying output file...${NC}"
OUTPUT_FILE="$SCRIPT_DIR/cache/Reflection$VERSION.json"

if [ -f "$OUTPUT_FILE" ]; then
    FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    ENTITY_COUNT=$(grep -o '"_type"' "$OUTPUT_FILE" | wc -l)
    echo -e "${GREEN}      ✓ Output file created: Reflection$VERSION.json${NC}"
    echo "      File Size: $FILE_SIZE"
    echo "      Entities: $ENTITY_COUNT"
else
    echo -e "${RED}✗ Output file not found: $OUTPUT_FILE${NC}"
    exit 1
fi

echo -e "\n${BLUE}========================================${NC}"
echo -e "${GREEN}✓ SUCCESS!${NC}"
echo -e "${BLUE}========================================${NC}"
echo "Output: $OUTPUT_FILE"
echo -e "${BLUE}========================================${NC}\n"
