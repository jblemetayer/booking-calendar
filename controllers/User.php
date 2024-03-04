<?php
class User {

  static function generateLoginCode($user) {
    $found = true;
    do {
      $login = chr(rand(65,90)).rand(0,9).rand(0,9).chr(rand(65,90)).chr(rand(65,90));
      if (!$user->count(['login=?', $login])) {
        $found = false;
      }
    } while($found);
    return $login;
  }

  function list($f3) {
    if ($message = $f3->get('SESSION.flash_message')) {
      $f3->set('message', $message);
      $f3->set('SESSION.flash_message', null);
    }
    $f3->set(
      'rows',
      $f3->get('DB')->exec('select u.id, u.login, u.category, u.firstname, u.lastname, u.email, u.is_admin, u.is_active, count(b.user_id) as booking_counter from user u LEFT JOIN booking b ON u.id = b.user_id GROUP BY u.id ORDER BY booking_counter DESC, u.id ASC')
    );
    $f3->set('content', 'admin/user/list.html');
    echo \Template::instance()->render('admin/layout.html');
  }

  function edit($f3) {
    $id = $f3->get('PARAMS.id');
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $suggestions = [];
    $items = $user->find();
    foreach ($items as $item) {
      $suggestions[] = $item->category;
    }
    if ($id == 'new') {
      $user = $user->cast();
      $user['id'] = 'new';
    } else {
      $user->load(['id=?', $id]);
      if (!$user->id) {
        $f3->error(404);
      }
    }
    $f3->set('content', 'admin/user/form.html');
    $f3->set('user', $user);
    $f3->set('suggestions', $suggestions);
    echo \Template::instance()->render('admin/layout.html');
  }

  function save($f3) {
    $id = $f3->get('PARAMS.id');
    $datas = $f3->get('POST.user');
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    if ($id == 'new') {
      $user->login = self::generateLoginCode($user);
    } else {
        $user->load(['id=?', $id]);
        if (!$user->id) {
          $f3->error(404);
        }
    }
    $user->copyfrom('POST');
    if (!$f3->get('POST.is_admin')) {
      $user->is_admin = 0;
    }
    if (!$f3->get('POST.is_active')) {
      $user->is_active = 0;
    }
    $user->save();
    $f3->reroute('/admin/users');
  }

  function delete($f3) {
    $id = $f3->get('PARAMS.id');
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['id=?', $id]);
    if (!$user->id) {
      $f3->error(404);
    }
    $user->erase();
    $f3->reroute('/admin/users');
  }

  function email($f3) {
    $id = $f3->get('PARAMS.id');
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['id=?', $id]);
    if (!$user->id) {
      $f3->error(404);
    }
    if ($user->email && $user->is_active) {
      $f3->set('user', $user);
      $message = \Template::instance()->render('emails/invitation.html');
      $sended = mb_send_mail($user->email, $f3->get('email.subject_invitation'), $message, ['From' => $f3->get('email.from'), 'Content-Type' => 'text/plain; charset=UTF-8', 'MIME-Version' => '1.0', 'Content-Transfer-Encoding' => '8bit']);
      $f3->set('SESSION.flash_message', ($sended)? 'Email envoyé avec succès' : 'Erreur lors de l\'envoi de l\'email');
    }
    $f3->reroute('/admin/users');
  }

  function emails($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $rows = $user->find();
    $counter = 0;
    $success = 0;
    foreach ($rows as $row) {
      if ($row->email && $row->is_active) {
        $f3->set('user', $row);
        $message = \Template::instance()->render('emails/invitation.html');
        $sended = mb_send_mail($row->email, $f3->get('email.subject_invitation'), $message, ['From' => $f3->get('email.from'), 'Content-Type' => 'text/plain; charset=UTF-8', 'MIME-Version' => '1.0', 'Content-Transfer-Encoding' => '8bit']);
        if ($sended) {
          $success++;
        }
        $counter++;
      }
    }
    $f3->set('SESSION.flash_message', "$counter emails a envoyer ($success envoyé(s) avec succès)");
    $f3->reroute('/admin/users');
  }

  function download($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $rows = $user->find();
    $csv = "LOGIN,CATEGORY,FIRSTNAME,LASTNAME,EMAIL\n";
    foreach ($rows as $row) {
      $csv .= "$row->login,$row->category,$row->firstname,$row->lastname,$row->email\n";
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.date('YmdHi').'_users.csv'.'"');
    echo $csv;
  }

  function upload($f3) {
    if ($f3->get('SERVER.REQUEST_METHOD') == 'GET') {
      $f3->set('content', 'admin/user/upload.html');
      echo \Template::instance()->render('admin/layout.html');
    } elseif ($f3->get('SERVER.REQUEST_METHOD') == 'POST') {
      $files = \Web::instance()->receive(function($file,$formFieldName) {
        if (strpos(\Web::instance()->mime($file['tmp_name'], true), 'text/csv') !== 0) {
          return false;
        }
        return true;
      }, false, true);
      foreach ($files as $file => $uploaded) {
        if (($handle = fopen($file, 'r')) !== false) {
          while (($datas = fgetcsv($handle)) !== false) {
            if (!trim($datas[1])||!trim($datas[4])) continue;
            $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
            if (trim($datas[0])) {
              $user->load(['login=?', trim($datas[0])]);
              if (!$user->id)
                continue;
            } else {
              $user->login = self::generateLoginCode($user);
            }
            $user->category = trim($datas[1]);
            $user->firstname = trim($datas[2]);
            $user->lastname = trim($datas[3]);
            $user->email = trim($datas[4]);
            $user->save();
          }
          fclose($handle);
          unlink($file);
        }
      }
      $f3->reroute('/admin/users');
    }
  }

}
