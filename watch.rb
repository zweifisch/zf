
CODES = {
	:reset   => "\e[0m",
	:cyan    => "\e[36m",
	:magenta => "\e[35m",
	:red     => "\e[31m",
	:yellow  => "\e[33m"
}

results = {}

def colorize(string, color_code)
	"#{CODES[color_code] || color_code}#{string}#{CODES[:reset]}"
end

watch('.*\.php') { |f|
	puts colorize `date`, :red
	system "vendor/bin/phpunit -c tests"
}

