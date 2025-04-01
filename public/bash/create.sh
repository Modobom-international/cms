#!/bin/bash

# ==========================
#      CHECK INPUT VARIABLES
# ==========================
if [ -z "$1" ]; then
    exit 1
fi

if [ -z "$2" ]; then
    exit 2
fi

# ==========================
#      CONFIG VARIABLES
# ==========================
SOURCE_DIR="$1"
SLUG="$2"
DEST_DIR="/home/modobom/landing_page"
NGINX_CONF="/etc/nginx/landing.modobom.com.conf"
NEW_ENTRY="        /vi/$SLUG     $HTML_FILE;"
TEMP_FILE=$(mktemp)

# ==========================
#      CREATE DIR AND COPY DIR
# ==========================
mkdir -p "$DEST_DIR"
cp -r "$SOURCE_DIR"/* "$DEST_DIR/"
if [ $? -eq 0 ]; then
    echo "Sao chép thành công!"
else
    exit 3
fi

# ==========================
#      CHANGE CONFIG NGINX IN TEMP FILE
# ==========================
awk -v new_line="$NEW_ENTRY" '
    /map \$uri \$html_file/ {in_map=1}
    in_map && /default[[:space:]]+404.html;/ {
        print new_line
        print
        next
    }
    {print}
    /}/ {in_map=0}
' "$NGINX_CONF" >"$TEMP_FILE"

# ==========================
#      REPLACE FILE NGINX CONFIG
# ==========================
mv "$TEMP_FILE" "$NGINX_CONF"
if [ $? -eq 0 ]; then
    echo "Đã thêm dòng '$NEW_ENTRY' vào block map trong $NGINX_CONF!"
else
    exit 4
fi

# ==========================
#      RESTART NGINX
# ==========================
nginx -t && systemctl reload nginx

exit 0
