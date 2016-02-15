#!/bin/bash

TARGET_DIR=$1

if [ -z "$1" ]; then TARGET_DIR="."; fi

TARGET_FILE="$TARGET_DIR/Prestashop_Invipay_Paygate.zip"

if [ -f "$TARGET_FILE" ]; then rm "$TARGET_FILE"; fi

echo "Packaging PrestaShop plugin into: $TARGET_FILE"
zip -r "$TARGET_FILE" invipaypaygate -x *.DS_Store* -x *.git* -x *.gitmodules*