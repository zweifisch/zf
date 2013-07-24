<?

require '../vendor/autoload.php';

$app = new zf\App;
$app->register('db', '\zf\PDO');
$app->set('pretty');

$app->cmd('users', function(){
	$users = $this->db->users
		->list(['limit'=> (int)$this->params->limit, 'skip'=> (int)$this->params->skip]);
	$this->send($users);
})->options(['limit'=>2, 'skip'=>0]);

$app->run();

