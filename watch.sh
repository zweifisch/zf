#!/bin/sh

inotifywait -mr --timefmt '%d/%m/%y %H:%M' --format '%T %w %f' \
    --exclude '(/\.git/*|/vendor/*|.*\.log)' \
    -e modify . |\
    while read date time dir file; do
        echo "${dir}${file} at ${date} ${time}"

        CONFIG_FILE=test/configs.php vendor/bin/phpunit -c test
done
