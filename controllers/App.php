<?php
class App {

  function home($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_active=?', $f3->get('PARAMS.login'), true]);
    if ($user->id && $f3->get('home_counter')) {
      $bookings = new \DB\SQL\Mapper($f3->get('DB'),'booking');
      $counter = str_replace(
        ['%PREV%', '%NEXT%'],
        [$bookings->count(['user_id=? AND start_date<?',$user->id, date('Y-m-d')]), $bookings->count(['user_id=? AND start_date>=?',$user->id, date('Y-m-d')])],
        $f3->get('home_counter')
      );
      $f3->set('counter', $counter);
    }
    $f3->set('title', $f3->get('home_title'));
    $f3->set('content', 'home.html');
    $f3->set('user', $user);
    $f3->set('date', $f3->get('GET.date'));
    echo \Template::instance()->render('layout.html');
  }

  function login($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_active=?', $f3->get('POST.login'), true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $f3->reroute("/$user->login");
  }

  function admin($f3) {
    $f3->reroute('/admin/users');
  }
}
