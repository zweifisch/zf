#!/bin/sh

inotifywait -mr --timefmt '%d/%m/%y %H:%M' --format '%T %w %f' \
	--exclude '/.git/*' \
	-e modify . |\
	while read date time dir file; do
		echo "${dir}${file} at ${date} ${time}"
		vendor/bin/phpunit -c tests
done
