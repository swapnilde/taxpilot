#!/bin/bash
#
# Validate readme.txt and version sync across plugin files.
# Usage: bash scripts/check-readme.sh
#

set -e

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
README="$PLUGIN_DIR/readme.txt"
MAIN_FILE="$PLUGIN_DIR/taxpilot.php"
PACKAGE_JSON="$PLUGIN_DIR/package.json"

ERRORS=0

echo "🔍 Checking WordPress readme.txt..."
echo ""

# -------------------------------------------------------------------
# 1. readme.txt must exist
# -------------------------------------------------------------------
if [[ ! -f "$README" ]]; then
	echo "❌ readme.txt not found!"
	exit 1
fi
echo "  ✅ readme.txt exists"

# -------------------------------------------------------------------
# 2. Required headers
# -------------------------------------------------------------------
REQUIRED_HEADERS=("Contributors" "Tags" "Requires at least" "Tested up to" "Requires PHP" "Stable tag" "License" "License URI")
for header in "${REQUIRED_HEADERS[@]}"; do
	if ! grep -qi "^${header}:" "$README"; then
		echo "  ❌ Missing header: $header"
		ERRORS=$((ERRORS + 1))
	fi
done
echo "  ✅ All required headers present"

# -------------------------------------------------------------------
# 3. Required sections
# -------------------------------------------------------------------
REQUIRED_SECTIONS=("Description" "Installation" "Changelog")
for section in "${REQUIRED_SECTIONS[@]}"; do
	if ! grep -q "== ${section} ==" "$README"; then
		echo "  ❌ Missing section: == ${section} =="
		ERRORS=$((ERRORS + 1))
	fi
done
echo "  ✅ All required sections present"

# -------------------------------------------------------------------
# 4. Short description (first non-blank line after headers) ≤ 150 chars
# -------------------------------------------------------------------
SHORT_DESC=$(awk '/^$/{found=1; next} found{print; exit}' "$README")
DESC_LEN=${#SHORT_DESC}
if [[ $DESC_LEN -gt 150 ]]; then
	echo "  ❌ Short description is $DESC_LEN chars (max 150)"
	ERRORS=$((ERRORS + 1))
else
	echo "  ✅ Short description length OK ($DESC_LEN/150)"
fi

# -------------------------------------------------------------------
# 5. Tags count ≤ 12
# -------------------------------------------------------------------
TAG_LINE=$(grep -i "^Tags:" "$README" | head -1)
TAG_COUNT=$(echo "$TAG_LINE" | tr ',' '\n' | wc -l | xargs)
if [[ $TAG_COUNT -gt 12 ]]; then
	echo "  ❌ Too many tags: $TAG_COUNT (max 12)"
	ERRORS=$((ERRORS + 1))
else
	echo "  ✅ Tag count OK ($TAG_COUNT/12)"
fi

# -------------------------------------------------------------------
# 6. Version sync: Stable tag vs plugin header vs package.json
# -------------------------------------------------------------------
README_VER=$(grep -i "^Stable tag:" "$README" | head -1 | sed 's/[^:]*: *//')
PLUGIN_VER=$(grep -i "Version:" "$MAIN_FILE" | head -1 | sed 's/.*Version: *//;s/ *$//')
PKG_VER=$(grep '"version"' "$PACKAGE_JSON" | head -1 | sed 's/.*: *"//;s/".*//')
CONST_VER=$(grep "TAXPILOT_VERSION" "$MAIN_FILE" | head -1 | grep -oE "[0-9]+\.[0-9]+\.[0-9]+")
echo ""
echo "📦 Version sync check:"
echo "  readme.txt (Stable tag):  $README_VER"
echo "  taxpilot.php header: $PLUGIN_VER"
echo "  taxpilot.php const:  $CONST_VER"
echo "  package.json:             $PKG_VER"

if [[ "$README_VER" != "$PLUGIN_VER" ]]; then
	echo "  ❌ Stable tag ($README_VER) ≠ plugin header ($PLUGIN_VER)"
	ERRORS=$((ERRORS + 1))
fi
if [[ "$README_VER" != "$PKG_VER" ]]; then
	echo "  ❌ Stable tag ($README_VER) ≠ package.json ($PKG_VER)"
	ERRORS=$((ERRORS + 1))
fi
if [[ "$README_VER" != "$CONST_VER" ]]; then
	echo "  ❌ Stable tag ($README_VER) ≠ TAXPILOT_VERSION ($CONST_VER)"
	ERRORS=$((ERRORS + 1))
fi

if [[ "$README_VER" == "$PLUGIN_VER" && "$README_VER" == "$PKG_VER" && "$README_VER" == "$CONST_VER" ]]; then
	echo "  ✅ All versions match: $README_VER"
fi

# -------------------------------------------------------------------
# 7. WP version sync: Requires at least
# -------------------------------------------------------------------
echo ""
echo "📋 WordPress version sync:"
README_WP_MIN=$(grep -i "^Requires at least:" "$README" | head -1 | sed 's/[^:]*: *//')
PLUGIN_WP_MIN=$(grep -i "Requires at least:" "$MAIN_FILE" | head -1 | sed 's/.*Requires at least: *//;s/ *$//')
echo "  readme.txt:          $README_WP_MIN"
echo "  plugin header:       $PLUGIN_WP_MIN"
if [[ "$README_WP_MIN" != "$PLUGIN_WP_MIN" ]]; then
	echo "  ❌ WP minimum version mismatch"
	ERRORS=$((ERRORS + 1))
else
	echo "  ✅ WP minimum versions match"
fi

README_PHP_MIN=$(grep -i "^Requires PHP:" "$README" | head -1 | sed 's/[^:]*: *//')
PLUGIN_PHP_MIN=$(grep -i "Requires PHP:" "$MAIN_FILE" | head -1 | sed 's/.*Requires PHP: *//;s/ *$//')
echo "  PHP min (readme):    $README_PHP_MIN"
echo "  PHP min (header):    $PLUGIN_PHP_MIN"
if [[ "$README_PHP_MIN" != "$PLUGIN_PHP_MIN" ]]; then
	echo "  ❌ PHP minimum version mismatch"
	ERRORS=$((ERRORS + 1))
else
	echo "  ✅ PHP minimum versions match"
fi

# -------------------------------------------------------------------
# Summary
# -------------------------------------------------------------------
echo ""
if [[ $ERRORS -gt 0 ]]; then
	echo "❌ $ERRORS issue(s) found. Please fix before release."
	exit 1
else
	echo "✅ All readme checks passed!"
	exit 0
fi
