#!/bin/bash

NOW=$(date)
BUILD="Build: $2:$3 ($NOW)"
TARGET_DIR=$1

if [ -z "$1" ]; then TARGET_DIR="."; fi

TARGET_FILE="$TARGET_DIR/Prestashop_Invipay_Paygate.zip"

echo "Packaging PrestaShop Plugin ($BUILD) into: $TARGET_FILE"

if [ -f "$TARGET_FILE" ]; then rm "$TARGET_FILE"; fi

echo "$BUILD" > invipaypaygate/VERSION.md
zip -r "$TARGET_FILE" invipaypaygate -x *.DS_Store* -x *.git* -x *.gitmodules*