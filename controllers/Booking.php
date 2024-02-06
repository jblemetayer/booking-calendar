<?php
class Booking {

  function calendarList($f3) {
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $rows = $booking->find();
    $datas = [];
    foreach ($rows as $row) {
      $e = [
        'id' => $row->id,
        'title' => $row->title,
        'start' => $row->start_date,
        'end' => $row->end_date
      ];
      if ($row->bgcolor) {
        $e['backgroundColor'] = $row->bgcolor;
      }
      if ($row->bdcolor) {
        $e['borderColor'] = $row->bdcolor;
      }
      if ($row->txtcolor) {
        $e['textColor'] = $row->txtcolor;
      }
      if ($row->readonly) {
        $e['editable'] = false;
      }
      $datas[] = $e;
    }
    header('Content-Type: application/json');
  	echo json_encode($datas);
  }

  function bookableDate($f3) {
    if (!DateTime::createFromFormat('Y-m-d', $f3->get('PARAMS.date'))) {
      echo 'false'; exit;
    }
    if ($f3->get('PARAMS.date') < date('Y-m-d')) {
      echo 'false'; exit;
    }
    if (!in_array(date_format(date_create($f3->get('PARAMS.date')), 'w'), [1,2,4,5])) {
      echo 'false'; exit;
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $items = $booking->find(['start_date <= ? AND end_date >= ?', $f3->get('PARAMS.date'), $f3->get('PARAMS.date')]);
    if (count($items) >= $f3->get('max_booking_per_date')) {
      echo 'false'; exit;
    }
    foreach ($items as $item) {
      if ($item->readonly) {
        echo 'false'; exit;
      }
    }
    echo 'true'; exit;
  }

  function calendarBooking($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_active=?', $f3->get('PARAMS.login'), true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $bookingDate = $f3->get('POST.bookingDate');
    if ($bookingDate && strtotime($bookingDate)) {
      if (!$booking->load(['user_id=? AND start_date=?', $user->id, $bookingDate])) {
        $booking->user_id = $user->id;
        $booking->start_date = $bookingDate;
        $booking->end_date = $bookingDate;
        $booking->title = "$user->category — $user->firstname $user->lastname";
        $booking->save();
        $f3->set('user', $user);
        $f3->set('date', date('d/m/Y', strtotime($bookingDate)));
        $message = \Template::instance()->render('emails/confirmation.html');
        $sended = mail($user->email, $f3->get('email.subject_confirmation'), $message, ['From' => $f3->get('email.from')]);
      }
    }
    $f3->reroute("/$user->login?date=$bookingDate");
  }

  function freeForm($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_admin=? AND is_active=?', $f3->get('PARAMS.login'), true, true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $f3->set('title', $f3->get('home_title'));
    $f3->set('content', 'freeBooking.html');
    $f3->set('user', $user);
    $f3->set('colors', $f3->get('colors'));
    echo \Template::instance()->render('layout.html');
  }

  function freeSave($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_admin=? AND is_active=?', $f3->get('PARAMS.login'), true, true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $booking->copyfrom('POST');
    $booking->bgcolor = $f3->get('colors.'.$f3->get('POST.eventColor'))['bgcolor'];
    $booking->bdcolor = $f3->get('colors.'.$f3->get('POST.eventColor'))['bdcolor'];
    $booking->txtcolor = $f3->get('colors.'.$f3->get('POST.eventColor'))['txtcolor'];
    $booking->readonly = ($f3->get('POST.readonly'))? 1 : 0;
    $booking->user_id = null;
    $booking->save();
    $f3->reroute("/$user->login?date=$booking->start_date");
  }

  function userForm($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_admin=? AND is_active=?', $f3->get('PARAMS.login'), true, true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $f3->set('title', $f3->get('home_title'));
    $f3->set('content', 'userBooking.html');
    $f3->set('user', $user);
    $f3->set('users', $user->find(['is_active=?', true]));
    echo \Template::instance()->render('layout.html');
  }

  function userSave($f3) {
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_admin=? AND is_active=?', $f3->get('PARAMS.login'), true, true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $booking->copyfrom('POST');
    $u = $user->findone(['id=?', $booking->user_id]);
    $booking->end_date = $booking->start_date;
    $booking->title = "$u->category — $u->firstname $u->lastname";
    $booking->save();
    $f3->reroute("/$user->login?date=$booking->start_date");
  }

  function delete($f3) {
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $booking->load(['id=?', $f3->get('PARAMS.id')]);
    if (!$booking->id) {
      $f3->error(404);
    }
    $booking->erase();
    $f3->reroute("/".$f3->get('PARAMS.login')."?date=$booking->start_date");
  }

  function download($f3) {
    $rows = $f3->get('DB')->exec('SELECT u.login, u.category, u.firstname, u.lastname, u.email, b.start_date, b.end_date FROM user u INNER JOIN booking b ON u.id = b.user_id ORDER BY start_date;');
    $csv = "LOGIN,CATEGORY,FIRSTNAME,LASTNAME,EMAIL,START_DATE,END_DATE\n";
    foreach ($rows as $row) {
      $csv .= $row['login'].','.$row['category'].','.$row['firstname'].','.$row['lastname'].','.$row['email'].','.$row['start_date'].','.$row['end_date']."\n";
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.date('YmdHi').'_bookings.csv'.'"');
    echo $csv;
  }

  function removeAll($f3) {
      $f3->get('DB')->exec('DELETE FROM booking;');
      $f3->reroute('/admin/users');
  }
}
