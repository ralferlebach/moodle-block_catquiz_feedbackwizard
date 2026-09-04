#!/usr/bin/env bash
##############################################################################
# Fetch the CAT engine plugins this block depends on.
#
# Unlike local_catquizlab, block_catquiz_feedbackwizard declares HARD plugin
# dependencies in version.php. Moodle refuses to install the block when they
# are missing, so every job that runs "moodle-plugin-ci install" needs this
# script — including the lint jobs, which stay engine-free in catquizlab.
#
# The directory produced here is passed to moodle-plugin-ci as
# --extra-plugins. Each plugin has to sit in a directory named exactly like
# its component's plugin name, because moodle-plugin-ci derives the target
# path inside the Moodle tree from that name.
#
# Usage:
#   ENGINE_DIR=/path/to/engine bash .github/scripts/fetch-engine.sh
##############################################################################

set -euo pipefail

ENGINE_DIR="${ENGINE_DIR:-$PWD/engine}"

# Repository, branch and target directory for each engine plugin. The branch is
# pinned deliberately: mod_adaptivequiz 3.0 introduced the adaptivequizcatmodel
# subplugin type that local_catquiz now builds on, and a checkout of the older
# default branch would install but behave differently.
#
# Directories carry the FULL component name. Using the bare plugin name would
# make local_catquiz and adaptivequizcatmodel_catquiz collide on "catquiz" and
# silently drop one of them. moodle-plugin-ci reads the component from each
# plugin's version.php, so the directory name only has to be unique.
#
# mod_adaptivequiz is listed first because it defines the adaptivequizcatmodel
# subplugin type that adaptivequizcatmodel_catquiz belongs to.
ENGINE_PLUGINS=(
    "https://github.com/ralferlebach/moodle-mod_adaptivequiz.git|v-3.0|mod_adaptivequiz"
    "https://github.com/ralferlebach/moodle-adaptivequizcatmodel_catquiz.git|v-3.0|adaptivequizcatmodel_catquiz"
    "https://github.com/Wunderbyte-GmbH/moodle-local_wunderbyte_table.git|main|local_wunderbyte_table"
    "https://github.com/ralferlebach/moodle-local_catquiz.git|main|local_catquiz"
)

mkdir -p "$ENGINE_DIR"

for entry in "${ENGINE_PLUGINS[@]}"; do
    IFS='|' read -r repo branch name <<< "$entry"
    target="$ENGINE_DIR/$name"

    if [ -d "$target" ]; then
        echo "==> $name already present, skipping."
        continue
    fi

    echo "==> Cloning $repo ($branch) into $target"
    if ! git clone --depth 1 --branch "$branch" "$repo" "$target"; then
        echo "!! Could not clone $repo at branch $branch." >&2
        exit 1
    fi

    rm -rf "$target/.git"
done

echo
echo "Engine plugins in $ENGINE_DIR:"
for entry in "${ENGINE_PLUGINS[@]}"; do
    IFS='|' read -r repo branch name <<< "$entry"
    version=$(grep -oP '\$plugin->version\s*=\s*\K[0-9]+' "$ENGINE_DIR/$name/version.php" 2>/dev/null || echo 'unknown')
    component=$(grep -oP "\\\$plugin->component\s*=\s*'\K[^']+" "$ENGINE_DIR/$name/version.php" 2>/dev/null || echo 'unknown')
    if [ "$component" != "$name" ]; then
        echo "!! $name reports component '$component' - directory and component must match." >&2
        exit 1
    fi
    printf '  %-32s %s\n' "$component" "$version"
done
