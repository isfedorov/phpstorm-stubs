#!/bin/bash

if [[ -z "$1" || -z "$2" ]] ; then
  echo "
  Usage:   ./cloneToWt.sh <target directory> <branch>
  Example: ./cloneToWt.sh ~/stubs_53 PHP_5.3"
  exit 1
fi

NEW_REPO="$1"
BRANCH="$2"

if [[ "$BRANCH" == origin/* ]]; then
  BRANCH="${BRANCH/origin\//}"
fi

MAIN_REPO="$(cd "$(dirname "$0")"; pwd)"

if [ -d "$NEW_REPO" ]; then
  echo "Directory '$NEW_REPO' already exists"
  exit 2
fi


if [ ! -d "${MAIN_REPO}/.git" ]; then
  echo "E: git repo '${MAIN_REPO}' not found"
  exit 3
fi

g="git --git-dir=${MAIN_REPO}/.git"
exists=$($g worktree list | grep -F "[$BRANCH]")
if [ -n "$exists" ]; then
  echo "E: Branch '$BRANCH' is already checked out in some other worktree"
  exit 3
fi
unset exists g

set -e # Any command which returns non-zero exit code will cause this shell script to exit immediately

echo
g="git --git-dir=${MAIN_REPO}/.git"
exists=$($g branch "$BRANCH" --list)
if [ -n "$exists" ]; then
  echo "checkout existent $BRANCH"
  echo
  $g --work-tree="${MAIN_REPO}" worktree add "${NEW_REPO}" "$BRANCH"
else
  echo "checkout new origin/$BRANCH"
  echo
  $g --work-tree="${MAIN_REPO}" worktree add -b "$BRANCH" "${NEW_REPO}" "origin/$BRANCH"
fi
unset exists g

test -f "$MAIN_REPO/.idea/workspace.xml" && cp -a "$MAIN_REPO/.idea/workspace.xml" "$NEW_REPO/.idea/"
test -f "$NEW_REPO/.idea/workspace.xml" && sed -i.bak '/<component name="ProjectId"/d' "$NEW_REPO/.idea/workspace.xml"
test -f "$NEW_REPO/.idea/workspace.xml.bak" && rm "$NEW_REPO/.idea/workspace.xml.bak"

echo
echo OK.
