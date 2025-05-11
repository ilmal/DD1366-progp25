#!/usr/bin/env bash

OUTPUT_FILE="all_contents.txt"

# Remove old output file if it exists
[ -f "$OUTPUT_FILE" ] && rm "$OUTPUT_FILE"

echo "This is the structure: " >> "$OUTPUT_FILE"
tree . >> "$OUTPUT_FILE"

# Loop through all files, skipping .git and checking if they're text and readable
find . -type f -not -path '*/.git/*' | while read -r FILE; do
  if [ -r "$FILE" ] && file "$FILE" | grep -q text; then
    echo "----- $FILE -----" >> "$OUTPUT_FILE"
    cat "$FILE" >> "$OUTPUT_FILE"
    echo -e "\n" >> "$OUTPUT_FILE"
  fi
done

echo "All text contents have been written to $OUTPUT_FILE."