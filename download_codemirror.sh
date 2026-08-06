#!/bin/bash
cd /tmp
declare -a files=(
  "codemirror.min.js"
  "codemirror.min.css"
  "theme/idea.min.css"
  "mode/sql/sql.min.js"
  "addon/edit/closebrackets.min.js"
  "addon/edit/matchbrackets.min.js"
  "addon/edit/placeholder.min.js"
  "addon/edit/continuelist.min.js"
  "addon/hint/show-hint.min.js"
  "addon/hint/sql-hint.min.js"
  "addon/hint/anyselect.min.js"
  "addon/search/search.min.js"
  "addon/display/placeholder.min.js"
  "addon/runonsave/runonsave.min.js"
)

for f in "${files[@]}"; do
  outname=$(echo "$f" | sed 's|/|_|g')
  echo "Downloading: $f -> $outname"
  wget -q -O "/tmp/${outname}" "https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/${f}" 2>&1
  if [ $? -ne 0 ]; then
    echo "  FAILED: $f"
  fi
done

echo ""
echo "Downloaded files:"
ls -la /tmp/*.js /tmp/*.css 2>/dev/null
