#!/usr/bin/env bash

set -euo pipefail

VIDEO_CODEC="libx264"
AUDIO_CODEC="aac"

VIDEO_QUALITY="22"
AUDIO_BITRATE="192k"

MAX_WIDTH="1920"

THUMB_TIME="00:00:01"
THUMB_WIDTH="640"

MAX_FILE_SIZE_MB=100

echo "== Preflight Check =="

if ! command -v ffmpeg >/dev/null 2>&1; then
    echo "ERROR: ffmpeg nicht gefunden."
    echo "Installation: brew install ffmpeg"
    exit 1
fi

echo "OK: ffmpeg gefunden:"
ffmpeg -version | head -1

if ! command -v ffprobe >/dev/null 2>&1; then
    echo "ERROR: ffprobe nicht gefunden."
    exit 1
fi

echo "OK: ffprobe gefunden"

if command -v magick >/dev/null 2>&1; then
    echo "OK: ImageMagick gefunden (Thumbnail WebP)"
else
    echo "WARNING: ImageMagick fehlt, Thumbnails bleiben JPG."
fi

echo ""

if [ $# -lt 2 ]; then
    echo "Verwendung:"
    echo "$0 <Quellordner> <Zielordner>"
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
echo "Starte Videokonvertierung"
echo "================================"
echo "Quelle:"
echo "$SOURCE"
echo ""
echo "Ziel:"
echo "$TARGET"
echo ""
echo "Maximale Quelldateigröße:"
echo "${MAX_FILE_SIZE_MB} MB"
echo ""

COUNT=0
FAILED=0
SKIPPED=0

while IFS= read -r -d '' FILE
do
    EXT="${FILE##*.}"
    EXT=$(echo "$EXT" | tr '[:upper:]' '[:lower:]')

    case "$EXT" in
        mp4|m4v|mov|avi|mkv|wmv|webm|mpeg|mpg)
            ;;
        *)
            continue
            ;;
    esac

    if [ ! -f "$FILE" ]; then
        echo "WARNING: Datei nicht gefunden:"
        echo "$FILE"
        continue
    fi

    FILE_SIZE_BYTES=$(wc -c < "$FILE")
    FILE_SIZE_MB=$((FILE_SIZE_BYTES / 1024 / 1024))

    BASENAME="$(basename "$FILE")"

    if [ "$FILE_SIZE_MB" -gt "$MAX_FILE_SIZE_MB" ]; then
        echo "--------------------------------"
        echo "Überspringe (zu groß):"
        echo "$BASENAME"
        echo "Größe: ${FILE_SIZE_MB} MB"
        SKIPPED=$((SKIPPED + 1))
        continue
    fi

    NAME="${BASENAME%.*}"

    VIDEO_OUT="$TARGET/${NAME}.mp4"
    THUMB_JPG="$TARGET/${NAME}.thumb.jpg"
    THUMB_OUT="$TARGET/${NAME}.thumb.webp"

    echo "--------------------------------"
    echo "Video:"
    echo "$BASENAME"

    if [ -f "$VIDEO_OUT" ] && [ -f "$THUMB_OUT" ]; then
        echo "Überspringe (bereits vorhanden)"
        continue
    fi

    if [ ! -f "$VIDEO_OUT" ]; then
        echo "Erzeuge:"
        echo "$VIDEO_OUT"

        if ! ffmpeg \
            -hide_banner \
            -loglevel warning \
            -analyzeduration 100M \
            -probesize 100M \
            -y \
            -i "$FILE" \
            -map 0:v:0 \
            -map 0:a:0? \
            -vf "scale='min(${MAX_WIDTH},iw)':-2" \
            -c:v "$VIDEO_CODEC" \
            -preset slow \
            -crf "$VIDEO_QUALITY" \
            -pix_fmt yuv420p \
            -movflags +faststart \
            -c:a "$AUDIO_CODEC" \
            -ac 2 \
            -af "loudnorm=I=-16:LRA=11:TP=-1.5" \
            -b:a "$AUDIO_BITRATE" \
            "$VIDEO_OUT"
        then
            echo "ERROR: Video-Konvertierung fehlgeschlagen:"
            echo "$FILE"
            FAILED=$((FAILED + 1))
            rm -f "$VIDEO_OUT"
            continue
        fi
    fi

    if [ ! -f "$THUMB_OUT" ]; then
        echo "Erzeuge Thumbnail"

        if ffmpeg \
            -hide_banner \
            -loglevel error \
            -y \
            -ss "$THUMB_TIME" \
            -i "$FILE" \
            -frames:v 1 \
            -vf "scale=${THUMB_WIDTH}:-2" \
            "$THUMB_JPG"
        then
            if command -v magick >/dev/null 2>&1; then
                magick \
                    "$THUMB_JPG" \
                    -strip \
                    -quality 80 \
                    "$THUMB_OUT"

                rm -f "$THUMB_JPG"
            else
                mv "$THUMB_JPG" "$THUMB_OUT"
            fi
        else
            echo "WARNING: Thumbnail konnte nicht erzeugt werden:"
            echo "$FILE"
        fi
    fi

    COUNT=$((COUNT + 1))

done < <(find "$SOURCE" -type f -print0)

echo ""
echo "================================"
echo "Fertig!"
echo "Verarbeitet:"
echo "$COUNT Videos"
echo "Übersprungen:"
echo "$SKIPPED Videos"
echo "Fehler:"
echo "$FAILED Videos"
echo ""
echo "Ausgabe:"
echo "$TARGET"
echo "================================"
