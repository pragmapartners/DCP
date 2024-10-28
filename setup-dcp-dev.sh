#!/bin/bash

# Automated script to clone DCP repo, create both main and latest release branches, create symlink, and update composer.json

# Variables
REPO="pragmapartners/DCP"
DCP_CLONE_DIR="../DCP"
CURRENT_DIR=$(pwd)
COMPOSER_FILE="$CURRENT_DIR/composer.json"
SYMLINK_PATH="$CURRENT_DIR/web/modules/contrib/dcp" # Adjust if needed

# Step 1: Detect the correct contrib directory
TARGET_CONTRIB_DIR=""
if [ -d "$CURRENT_DIR/web/modules" ]; then
  TARGET_CONTRIB_DIR="$CURRENT_DIR/web/modules/contrib"
elif [ -d "$CURRENT_DIR/docroot/modules" ]; then
  TARGET_CONTRIB_DIR="$CURRENT_DIR/docroot/modules/contrib"
else
  TARGET_CONTRIB_DIR="$CURRENT_DIR/modules/contrib"
fi

# Ensure contrib directory exists
mkdir -p "$TARGET_CONTRIB_DIR"

# Step 2: Clone the repo if not already present
if [ ! -d "$DCP_CLONE_DIR" ]; then
  echo "Cloning DCP repository..."
  git clone --no-single-branch "https://github.com/$REPO.git" "$DCP_CLONE_DIR"
else
  echo "DCP repository already cloned at $DCP_CLONE_DIR"
fi

cd "$DCP_CLONE_DIR"

# Step 3: Fetch the latest release tag
echo "Fetching latest release tag..."
LATEST_RELEASE_TAG=$(curl -s "https://api.github.com/repos/$REPO/releases/latest" | sed -n 's/.*"tag_name": "\([^"]*\)".*/\1/p')

if [ -z "$LATEST_RELEASE_TAG" ]; then
  echo "No release tags found. Defaulting to 'main'."
  LATEST_RELEASE_TAG="main"
else
  echo "Latest release tag found: $LATEST_RELEASE_TAG"
fi

# Step 4: Ensure both main and latest release branches exist

# Create local main branch tracking remote main, if it doesn't exist
git fetch origin main
if ! git branch --list | grep -q "main"; then
  echo "Creating local main branch..."
  git switch -c main origin/main
else
  echo "Main branch already exists locally."
fi

# Create and switch to the latest release branch
if ! git branch --list | grep -q "$LATEST_RELEASE_TAG"; then
  echo "Creating and switching to latest release branch: $LATEST_RELEASE_TAG..."
  git checkout -b "$LATEST_RELEASE_TAG" "$LATEST_RELEASE_TAG"
else
  echo "Branch for latest release ($LATEST_RELEASE_TAG) already exists."
fi

# Step 5: Go back to the project root directory
cd "$CURRENT_DIR"

# Step 6: Remove existing symlink or directory, if any
if [ -e "$SYMLINK_PATH" ] || [ -L "$SYMLINK_PATH" ]; then
  echo "Removing existing dcp at $SYMLINK_PATH..."
  rm -rf "$SYMLINK_PATH"
fi

# Step 7: Create the symlink from cloned DCP to the contrib directory
REALPATH_CLONE=$(realpath "$DCP_CLONE_DIR")
echo "Creating symlink: $SYMLINK_PATH -> $REALPATH_CLONE"
ln -s "$REALPATH_CLONE" "$SYMLINK_PATH"

# Verify symlink creation
if [ -L "$SYMLINK_PATH" ]; then
  echo "Symlink created successfully: $SYMLINK_PATH -> $(readlink -f "$SYMLINK_PATH")"
else
  echo "Error: Failed to create symlink. Check permissions or paths."
  exit 1
fi

# Step 8: Add path repository to composer.json if not already present
if ! grep -q "\"url\": \"$REALPATH_CLONE\"" "$COMPOSER_FILE"; then
  echo "Adding path repository to composer.json..."

  if command -v jq &>/dev/null; then
    jq '.repositories += [{"type": "path", "url": "'$REALPATH_CLONE'", "options": {"symlink": true}}]' \
      "$COMPOSER_FILE" >"$COMPOSER_FILE.tmp" && mv "$COMPOSER_FILE.tmp" "$COMPOSER_FILE"
  else
    sed -i '' 's|"repositories": \[|"repositories": [\n    {"type": "path", "url": "'$REALPATH_CLONE'", "options": {"symlink": true}},|' "$COMPOSER_FILE"
  fi

  echo "composer.json updated with path repository."
else
  echo "Path repository already exists in composer.json."
fi

# Final message
echo "DCP module setup completed successfully!"
