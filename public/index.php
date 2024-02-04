<?php
require __DIR__ . '/../vendor/autoload.php';

$f3 = \Base::instance();

$f3->set('ROOT', __DIR__.'/../');
$f3->set('AUTOLOAD', $f3->get('ROOT').'/controllers/');
$f3->set('UI', $f3->get('ROOT')."/views/");
$f3->set('DB', new \DB\SQL('sqlite:'. $f3->get('ROOT').'/database/datas.sqlite'));
$f3->set('ENCODING','UTF-8');
$f3->set('LANGUAGE','fr-FR');
$f3->set('UPLOADS', sys_get_temp_dir()."/bc-uploads/");
$f3->config($f3->get('ROOT').'/config/app.conf');
$f3->config($f3->get('ROOT').'/config/routes.conf');
$f3->set('DEBUG', $f3->get('debug_level'));

$f3->run();
