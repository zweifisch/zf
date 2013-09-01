. assert.sh

host=localhost:5000
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
assert "curl -s $host/foo\?keyword=nil\&page=1\&size=100 | json input" 100
assert "curl -s $host/foo\?query=nil | json input" null

assert_end validation

kill $pid
exit 0

assert "curl -sH 'Content-Type: application/json' -d '\''{"thing":{"key":"value"}}'\'' $host/thing"
assert "curl -sH "Content-Type: application/json" -d '\''{"thin":{"key":"value"}}'\'' $host/thing"
assert "curl -sI $host/cache-control"
assert "curl -si -d a=b $host/debug"
assert "curl -si -d a=b $host/debug | sed -n '/X-ZF-Debug/ s/.* // p' | json a" b

assert_end misc

assert "curl -s $host/foo/bar | json compact.bar" bar
assert "curl -s $host/foo/bar?offset=100 | json compact.offset" 100
assert "curl -s $host/foo/bar/soft?offset=100 | json compact.opt" soft
assert "curl -s $host/foo/bar/soft?opt=hard | json compact.opt" soft

assert "curl -s $host/bar?q=1 | json compact.q" 1
assert_raises "curl -i $host/bar | grep 404"

assert_end params

kill $pid
