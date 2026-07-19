#!/bin/bash

set -euo pipefail

#######################################
# Configuration
#######################################

QUALITY=82
THUMB_QUALITY=75

MAX_SIZE="1920x1920>"
THUMB_SIZE="400x400"

#######################################
# Preflight checks
#######################################

echo "== Preflight Check =="
echo ""

# Check ImageMagick
if ! command -v magick >/dev/null 2>&1; then
    echo "ERROR: ImageMagick (magick) nicht gefunden."
    echo "Installation:"
    echo "  brew install imagemagick"
    exit 1
fi

echo "OK: ImageMagick gefunden:"
magick -version | head -1

# Check libvips
if ! command -v vipsthumbnail >/dev/null 2>&1; then
    echo "ERROR: libvips (vipsthumbnail) nicht gefunden."
    echo "Installation:"
    echo "  brew install vips"
    exit 1
fi

echo "OK: libvips gefunden:"
vipsthumbnail --version

# Check ImageMagick WebP support
FORMATS="$(magick -list format)"

if echo "$FORMATS" | grep -i "WEBP" >/dev/null 2>&1; then
    echo "OK: WebP Support"
else
    echo "ERROR: ImageMagick ohne WebP Support."
    exit 1
fi

# Check HEIC support
if echo "$FORMATS" | grep -i "HEIC" >/dev/null 2>&1; then
    echo "OK: HEIC Support"
else
    echo "WARNING: Kein HEIC Support gefunden"
fi

# Check libvips smartcrop
if vipsthumbnail --help 2>&1 | grep "smartcrop" >/dev/null 2>&1; then
    echo "OK: libvips Smart Crop Support"
else
    echo "ERROR: libvips ohne Smart Crop Support."
    exit 1
fi

echo ""

#######################################
# Arguments
#######################################

if [ $# -lt 2 ]; then
    echo "Verwendung:"
    echo ""
    echo "  $0 <Quellordner> <Zielordner>"
    echo ""
    exit 1
fi

SOURCE="$1"
TARGET="$2"

if [ ! -d "$SOURCE" ]; then
    echo "ERROR: Quellordner existiert nicht:"
    echo "$SOURCE"
    exit 1
fi

mkdir -p "$TARGET"

if [ ! -w "$TARGET" ]; then
    echo "ERROR: Zielordner nicht beschreibbar:"
    echo "$TARGET"
    exit 1
fi

echo "================================"
echo "Starte Konvertierung"
echo "================================"
echo "Quelle:"
echo "$SOURCE"
echo ""
echo "Ziel:"
echo "$TARGET"
echo ""

#######################################
# Conversion
#######################################

COUNT=0

find "$SOURCE" -type f | while IFS= read -r FILE
do
    EXT="${FILE##*.}"
    EXT=$(echo "$EXT" | tr '[:upper:]' '[:lower:]')

    case "$EXT" in
        jpg|jpeg|png|heic|heif|tif|tiff|webp|bmp|gif)
            ;;
        *)
            continue
            ;;
    esac

    BASENAME="$(basename "$FILE")"
    NAME="${BASENAME%.*}"

    WEB_OUT="$TARGET/${NAME}.webp"
    THUMB_OUT="$TARGET/${NAME}.thumb.webp"

    echo "--------------------------------"
    echo "Bild:"
    echo "$BASENAME"

    ###################################
    # Main Web Image
    ###################################

    magick "$FILE" \
        -auto-orient \
        -resize "$MAX_SIZE" \
        -strip \
        -quality "$QUALITY" \
        -define webp:method=6 \
        "$WEB_OUT"

    ###################################
    # Smart Thumbnail
    ###################################

    vipsthumbnail "$FILE" \
        --size "$THUMB_SIZE" \
        --smartcrop attention \
        -o "$THUMB_OUT"

    # Thumbnail WebP Qualität optimieren
    magick "$THUMB_OUT" \
        -strip \
        -quality "$THUMB_QUALITY" \
        -define webp:method=6 \
        "$THUMB_OUT"

    COUNT=$((COUNT + 1))
done

echo ""
echo "================================"
echo "Fertig!"
echo "Verarbeitet:"
echo "$COUNT Bilder"
echo ""
echo "Ausgabe:"
echo "$TARGET"
echo "================================"
