#!/bin/bash
# 
# TaxPilot for WooCommerce — WordPress.org Release Packager
# This script bundles the plugin into a clean, production-ready .zip file
# for submission to the WordPress.org SVN repository.

# Exit on any error
set -e

# Configuration
PLUGIN_SLUG="taxpilot"
VERSION=$(grep -o "Stable tag: [0-9\.]*" readme.txt | cut -d' ' -f3)
BUILD_DIR="release-build"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo "==========================================="
echo "📦 Building TaxPilot Release v${VERSION}"
echo "==========================================="

# Ensure we're in the plugin root
if [ ! -f "taxpilot.php" ]; then
    echo "❌ Error: Must run from the plugin root directory."
    exit 1
fi

# Step 1: Clean previous builds
echo "🧹 Cleaning up previous builds..."
rm -rf $BUILD_DIR
rm -f $ZIP_NAME
mkdir -p $BUILD_DIR/$PLUGIN_SLUG

# Step 2: Build fresh production assets
echo "🏗️ Building fresh JavaScript/CSS assets..."
npm run build --silent

# Step 3: Copy files while respecting .distignore
echo "📂 Copying files to build directory..."
rsync -av --progress ./ $BUILD_DIR/$PLUGIN_SLUG/ \
    --exclude-from='.distignore' \
    --exclude="$BUILD_DIR" \
    --exclude="*.zip"

# Step 4: Install PRODUCTION PHP dependencies ONLY
echo "🐘 Preparing production PHP dependencies..."
cd $BUILD_DIR/$PLUGIN_SLUG
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader --no-progress --quiet
fi
cd ../../

# Step 5: Generate the ZIP file
echo "🗜️ Compressing into $ZIP_NAME..."
cd $BUILD_DIR
zip -rq ../$ZIP_NAME $PLUGIN_SLUG/
cd ..

# Step 6: Cleanup
echo "🗑️ Removing temporary build directory..."
rm -rf $BUILD_DIR

echo "==========================================="
echo "✅ Success! Release packaged: $ZIP_NAME"
echo "==========================================="
echo "You can now upload $ZIP_NAME to WordPress.org SVN."
