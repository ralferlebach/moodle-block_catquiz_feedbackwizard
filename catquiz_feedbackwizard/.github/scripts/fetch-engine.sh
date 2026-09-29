#!/usr/bin/env bash
##############################################################################
# Fetch the CAT engine plugins this block depends on.
#
# There are two engine stacks, and they do not overlap:
#
#   legacy  local_catquiz 1.2.x    supported = [405, 405]   -> Moodle 4.5 only
#   v5      local_catquiz 1.3.x    supported = [501, 503]   -> Moodle 5.1+
#
# Moodle 5.0 is supported by NEITHER stack. There is no engine to install
# there, so the CI matrix must not pair any job with MOODLE_500_STABLE.
#
# Unlike local_catquizlab, block_catquiz_feedbackwizard declares HARD plugin
# dependencies in version.php. Moodle refuses to install the block when they
# are missing, so every job that runs "moodle-plugin-ci install" needs this
# script — including the lint jobs, which stay engine-free in catquizlab.
#
# The directory produced here is passed to moodle-plugin-ci as
# --extra-plugins. Directories carry the FULL component name: using the bare
# plugin name would make local_catquiz and adaptivequizcatmodel_catquiz
# collide on "catquiz" and silently drop one of them.
#
# Usage:
#   ENGINE_DIR=/path/to/engine ENGINE_STACK=legacy bash .github/scripts/fetch-engine.sh
#   MOODLE_BRANCH=MOODLE_501_STABLE bash .github/scripts/fetch-engine.sh
#
# ENGINE_STACK wins when set. Otherwise the stack is derived from
# MOODLE_BRANCH, so a workflow only has to pass the Moodle branch it already
# knows.
##############################################################################

set -euo pipefail

ENGINE_DIR="${ENGINE_DIR:-$PWD/engine}"
MOODLE_BRANCH="${MOODLE_BRANCH:-}"
ENGINE_STACK="${ENGINE_STACK:-}"

if [ -z "$ENGINE_STACK" ]; then
    case "$MOODLE_BRANCH" in
        MOODLE_405_STABLE)
            ENGINE_STACK=legacy
            ;;
        MOODLE_500_STABLE)
            echo "!! Moodle 5.0 is not supported by any CAT engine stack." >&2
            echo "   local_catquiz 1.2.x is [405,405], 1.3.x is [501,503]." >&2
            exit 1
            ;;
        MOODLE_50[1-9]_STABLE | MOODLE_5[1-9][0-9]_STABLE)
            ENGINE_STACK=v5
            ;;
        *)
            echo "!! Cannot derive the engine stack from MOODLE_BRANCH='$MOODLE_BRANCH'." >&2
            echo "   Set ENGINE_STACK to 'legacy' or 'v5' explicitly." >&2
            exit 1
            ;;
    esac
fi

case "$ENGINE_STACK" in
    legacy)
        # Moodle 4.5. local_catquiz 1.2.1 / adaptivequizcatmodel_catquiz 1.0.4.
        ENGINE_PLUGINS=(
            "https://github.com/ralferlebach/moodle-mod_adaptivequiz.git|ALiSe-v-1.2.0-legacy|mod_adaptivequiz"
            "https://github.com/ralferlebach/moodle-adaptivequizcatmodel_catquiz.git|ALiSe-v-1.2.0-legacy|adaptivequizcatmodel_catquiz"
            "https://github.com/Wunderbyte-GmbH/moodle-local_wunderbyte_table.git|main|local_wunderbyte_table"
            "https://github.com/ralferlebach/moodle-local_catquiz.git|ALiSe-v-1.2.0-legacy|local_catquiz"
        )
        ;;
    v5)
        # Moodle 5.1+. local_catquiz 1.3.0 / adaptivequizcatmodel_catquiz 1.3.0.
        ENGINE_PLUGINS=(
            "https://github.com/ralferlebach/moodle-mod_adaptivequiz.git|v-3.0|mod_adaptivequiz"
            "https://github.com/ralferlebach/moodle-adaptivequizcatmodel_catquiz.git|v-3.0|adaptivequizcatmodel_catquiz"
            "https://github.com/Wunderbyte-GmbH/moodle-local_wunderbyte_table.git|main|local_wunderbyte_table"
            "https://github.com/ralferlebach/moodle-local_catquiz.git|migration-zu-moodle-5.x|local_catquiz"
        )
        ;;
    *)
        echo "!! Unknown ENGINE_STACK '$ENGINE_STACK'. Use 'legacy' or 'v5'." >&2
        exit 1
        ;;
esac

echo "==> Engine stack: $ENGINE_STACK (Moodle branch: ${MOODLE_BRANCH:-unset})"
mkdir -p "$ENGINE_DIR"

for entry in "${ENGINE_PLUGINS[@]}"; do
    IFS='|' read -r repo branch name <<< "$entry"
    target="$ENGINE_DIR/$name"

    if [ -d "$target" ]; then
        echo "==> $name already present, skipping."
        continue
    fi

    echo "==> Cloning $repo ($branch) into $target"
    # --recurse-submodules is not optional. local_catquiz carries
    # catquizcentralhub/client and catquizcentralhub/host as submodules, and
    # db/subplugins.json declares catquizcentralhub as a plugin type. A clone
    # without them leaves two EMPTY directories there, which Moodle's plugin
    # manager then tries to load as plugins:
    #   include(.../catquizcentralhub/client/version.php): Failed to open stream
    # That warning fires on every path that enumerates plugins, and under
    # "moodle-plugin-ci phpunit --fail-on-warning" it fails the build.
    if ! git clone --depth 1 --recurse-submodules --shallow-submodules \
            --branch "$branch" "$repo" "$target"; then
        echo "!! Could not clone $repo at branch $branch." >&2
        exit 1
    fi

    find "$target" -name .git -maxdepth 4 -exec rm -rf {} + 2>/dev/null || true
done

# A subplugin directory without version.php is either an uninitialised
# submodule or a genuinely broken plugin. Both break the plugin manager, so
# catch it here rather than three jobs later.
if [ -f "$ENGINE_DIR/local_catquiz/db/subplugins.json" ]; then
    while read -r subdir; do
        [ -d "$subdir" ] || continue
        if [ ! -f "$subdir/version.php" ]; then
            echo "!! $subdir has no version.php - Moodle will try to load it as a plugin." >&2
            echo "   Most likely an uninitialised git submodule." >&2
            exit 1
        fi
    done < <(find "$ENGINE_DIR/local_catquiz/catquizcentralhub" -mindepth 1 -maxdepth 1 -type d 2>/dev/null)
fi

echo
echo "Engine plugins in $ENGINE_DIR:"
for entry in "${ENGINE_PLUGINS[@]}"; do
    IFS='|' read -r repo branch name <<< "$entry"
    version=$(grep -oP '\$plugin->version\s*=\s*\K[0-9]+' "$ENGINE_DIR/$name/version.php" 2>/dev/null || echo 'unknown')
    release=$(grep -oP "\\\$plugin->release\s*=\s*'\K[^']+" "$ENGINE_DIR/$name/version.php" 2>/dev/null || echo '?')
    component=$(grep -oP "\\\$plugin->component\s*=\s*'\K[^']+" "$ENGINE_DIR/$name/version.php" 2>/dev/null || echo 'unknown')
    if [ "$component" != "$name" ]; then
        echo "!! $name reports component '$component' - directory and component must match." >&2
        exit 1
    fi
    printf '  %-32s %-16s %s\n' "$component" "$release" "$version"
done
