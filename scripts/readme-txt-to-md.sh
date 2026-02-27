#!/bin/bash
#
# Convert WordPress readme.txt to GitHub-flavored README.md.
# Usage: bash scripts/readme-txt-to-md.sh
#

set -e

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
README_TXT="$PLUGIN_DIR/readme.txt"
README_MD="$PLUGIN_DIR/README.md"

if [[ ! -f "$README_TXT" ]]; then
	echo "❌ readme.txt not found!"
	exit 1
fi

{
	# ---------------------------------------------------------------
	# 1. Extract plugin name from the first line: === Plugin Name ===
	# ---------------------------------------------------------------
	PLUGIN_NAME=$(head -1 "$README_TXT" | sed 's/^=== *//;s/ *===$//')

	# Header with badges.
	STABLE_TAG=$(grep -i "^Stable tag:" "$README_TXT" | sed 's/[^:]*: *//')
	WP_MIN=$(grep -i "^Requires at least:" "$README_TXT" | sed 's/[^:]*: *//')
	WP_TESTED=$(grep -i "^Tested up to:" "$README_TXT" | sed 's/[^:]*: *//')
	PHP_MIN=$(grep -i "^Requires PHP:" "$README_TXT" | sed 's/[^:]*: *//')
	LICENSE=$(grep -i "^License:" "$README_TXT" | head -1 | sed 's/[^:]*: *//')

	echo "# $PLUGIN_NAME"
	echo ""
	echo "![Version](https://img.shields.io/badge/version-${STABLE_TAG}-blue)"
	echo "![WP](https://img.shields.io/badge/WordPress-${WP_MIN}%2B-21759b)"
	echo "![PHP](https://img.shields.io/badge/PHP-${PHP_MIN}%2B-777bb4)"
	echo "![License](https://img.shields.io/badge/license-${LICENSE// /%20}-green)"
	echo ""

	# ---------------------------------------------------------------
	# 2. Short description (first non-blank line after header block)
	# ---------------------------------------------------------------
	SHORT_DESC=$(awk '/^$/{found++} found==1 && !/^$/{print; exit}' "$README_TXT")
	if [[ -n "$SHORT_DESC" ]]; then
		echo "> $SHORT_DESC"
		echo ""
	fi

	# ---------------------------------------------------------------
	# 3. Process the body: convert WP readme markup to Markdown
	# ---------------------------------------------------------------
	# Skip the header block (everything before first == Section ==)
	BODY=$(sed -n '/^== /,$p' "$README_TXT")

	echo "$BODY" | while IFS= read -r line; do
		# == Section == → ## Section
		if [[ "$line" =~ ^==\ (.+)\ ==$ ]]; then
			echo "## ${BASH_REMATCH[1]}"
			echo ""
		# = Sub-section = → ### Sub-section
		elif [[ "$line" =~ ^=\ (.+)\ =$ ]]; then
			echo "### ${BASH_REMATCH[1]}"
			echo ""
		else
			echo "$line"
		fi
	done

} > "$README_MD"

# Clean up consecutive blank lines.
sed -i '' '/^$/N;/^\n$/d' "$README_MD"

echo "✅ README.md generated from readme.txt ($(wc -l < "$README_MD" | xargs) lines)"
