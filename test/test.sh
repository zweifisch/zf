. assert.sh

if [ -z "$PORT" ]; then
	PORT=17951
fi

host=localhost:$PORT
php -S $host index.php &> server.log &
pid=$!

sleep 1

assert "curl -sd name=zf -d passwd=secret -d _id=1 $host/user | json ok" true
assert "curl -sd name=dos -d passwd=secret -d _id=2 $host/user | json ok" true
assert "curl -sd name=dres -d passwd=secret -d _id=3 $host/user | json ok" true

assert "curl -s $host/users | json 2.passwd" secret
assert "curl -s $host/users/1,3 | json 1.passwd" secret
assert "curl -s $host/users/1,2\?callback=my_cb | sed 's/.*(//' | sed 's/])/]/' | json 1.passwd" secret

assert "curl -s -X DELETE $host/users/1,2 | json ok" true

assert "curl -s $host/users | json 0.passwd" secret
assert "curl -s -X DELETE $host/users/3 | json ok" true
assert "curl -s $host/users | json" "[]"

assert_end mongo

assert "curl -s $host/git/st" "st is not implemented"
assert "curl -s $host/time/Y-m-d" "$(date +%Y-%m-%d)"
assert_raises "curl -s $host | grep body"

assert_end routing

assert 'curl -sH "Content-Type: application/json" -d '\''{"a":{"b":"c"}}'\'' $host/dump | json a.b' c
assert "curl -sX PUT -d a=b -d b\[\]=c $host/dump | json b.0" c
assert "curl -sd a=b $host/dump | json a" b

assert_end content_type

assert "curl -s $host/foo\?keyword=nil\&page=1 | json keyword" nil
# assert "curl -s $host/foo\?keyword=nil\&page=1\&size=100 | json input" 100
# assert "curl -s $host/foo\?query=nil | json input" null

assert_end validation

# assert 'curl -sH "Content-Type: application/json" -d '\''{"thing":{"key":"value"}}'\'' $host/thing | json value' key
# assert_raises 'curl -siH "Content-Type: application/json" -d '\''{"thin":{"key":"value"}}'\'' $host/thing | grep 400'

assert_raises "curl -siI $host/cache-control | grep max-age"
assert_raises "curl -si -d a=b $host/debug | grep -i x-debug"
assert "curl -si -d a=b $host/debug | sed -n '/X-Debug/ s/.* // p' | json 0.1.a" b
assert_end misc


assert "curl -s $host/foo/bar | json compact.bar" bar
assert "curl -s $host/foo/bar?offset=100 | json compact.offset" 100
assert "curl -s $host/foo/bar/soft?offset=100 | json compact.opt" soft
assert "curl -s $host/foo/bar/soft?opt=hard | json compact.opt" soft

assert "curl -s $host/bar?q=1 | json compact.q" 1
assert_raises "curl -i $host/bar | grep 404"

assert_end params

assert 'curl -sH "Content-Type: application/json" -d '\''{"title":null}'\'' $host/posts | json errors.0.0' 'required'
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":null, "content":null}'\'' $host/posts | json errors.0.0' 'type'
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":"", "content":""}'\'' $host/posts | json ok' 1
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":"", "content":"", "cat": "12345"}'\'' $host/posts | json ok' 1
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":"", "content":"", "cat": "123456"}'\'' $host/posts | json errors.0.0' 'maxLength'
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":"", "content":"", "tags": "123456"}'\'' $host/posts | json errors.0.0' 'type'
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":"", "content":"", "tags": []}'\'' $host/posts | json ok' 1
assert 'curl -sH "Content-Type: application/json" -d '\''{"title":"", "content":"", "tags": [12]}'\'' $host/posts | json errors.0.0' 'type'

assert_end schema 

assert_raises 'curl -si $host/status | grep "Status: 404"'

assert_end numeric_return

assert "curl -s $host/path?key=val" "/path"

assert_end request

kill $pid
