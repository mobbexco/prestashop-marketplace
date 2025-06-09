#!/bin/sh

VER="1.2.2"

# Unified Version
PRESTAV="1.6-8.2"

if type 7z > /dev/null; then
    7z a -tzip "mobbex_marketplace.$VER.ps-$PRESTAV.zip" mobbex_marketplace
elif type zip > /dev/null; then
    zip mobbex_marketplace.$VER.ps-$PRESTAV.zip -r mobbex_marketplace
fi