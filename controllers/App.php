<?php
class App {

  function home($f3) {
    $user = $this->getUser();
    if ($user->id && $f3->get('home_counter')) {
      $bookings = new \DB\SQL\Mapper($f3->get('DB'),'booking');
      $counter = str_replace('%SUMMARY%', $this->getSummary(), $f3->get('home_counter'));
      $f3->set('counter', $counter);
    }
    $f3->set('title', $f3->get('home_title'));
    $f3->set('content', 'home.html');
    $f3->set('user', $user);
    $f3->set('date', $f3->get('GET.date'));
    echo \Template::instance()->render('layout.html');
  }

  function getSummary() {
    $user = $this->getUser();
    $bookings = new \DB\SQL\Mapper(\Base::instance()->get('DB'),'booking');
    $events = $this->getConfEvents();
    $summary = '<ul class="m-0 list-unstyled">';
    foreach ($events as $event) {
      $summary .= '<li>'.$event['title'].' : '.$bookings->count(['user_id=? AND start_date<? AND event=?',$user->id, date('Y-m-d'), $event['title']]).' réalisé(s) — '.$bookings->count(['user_id=? AND start_date>=? AND event=?',$user->id, date('Y-m-d'), $event['title']]).' réservé(s)</li>';
    }
    $summary .= '</ul>';
    return $summary;
  }

  function getUser() {
    $f3 = \Base::instance();
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_active=?', $f3->get('SESSION.user'), true]);
    return $user;
  }

  function login($f3) {
    $login = $f3->get('PARAMS.login');
    if ($f3->get('POST.login')) {
      $login = $f3->get('POST.login');
    }
    $user = new \DB\SQL\Mapper($f3->get('DB'),'user');
    $user->load(['login=? AND is_active=?', $login, true]);
    if (!$user->id) {
      $f3->error(404);
    }
    $f3->set('SESSION.user', $user->login);
    $f3->reroute('/');
  }

  function logout($f3) {
      if ($f3->get('SESSION.user')) {
          $f3->set('SESSION.user', null);
      }
      $f3->reroute('/');
  }

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

  function getConfEvents() {
    return \Base::instance()->get('events');
  }

  function getConfEvent($eventId) {
    $events = $this->getConfEvents();
    return (isset($events[$eventId])) ? $events[$eventId] : null;
  }

  function getConfEventsByDate($date) {
    $events = $this->getConfEvents();
    $day = strtolower(date('l', strtotime($date)));
    $result = [];
    foreach ($events as $key => $event) {
      if (isset($event['max_booking_per_date'][$day]) && $event['max_booking_per_date'][$day] > 0) {
        $event['max_booking_per_date'] = $event['max_booking_per_date'][$day];
        $result[$key] = $event;
      }
    }
    return $result;
  }

  function getConfColors() {
    return \Base::instance()->get('colors');
  }

  function getConfColor($colorId) {
    $colors = $this->getConfColors();
    return (isset($colors[$colorId])) ? $colors[$colorId] : null;
  }

  function booking($f3) {
    $user = $this->getUser();
    if (!$user->id) {
      echo \View::instance()->render('modals/modalLogin.php', 'text/html'); exit;
    }
    if (!DateTime::createFromFormat('Y-m-d', $f3->get('PARAMS.date'))) {
      echo \View::instance()->render('modals/modalUnavailable.php', 'text/html'); exit;
    }
    $date = new \DateTime($f3->get('PARAMS.date'));
    if ($f3->get('PARAMS.date') < date('Y-m-d')) {
      echo \View::instance()->render('modals/modalUnavailable.php', 'text/html', compact('date')); exit;
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $items = $booking->find(['start_date <= ? AND end_date >= ?', $f3->get('PARAMS.date'), $f3->get('PARAMS.date')]);
    $events = array_map(
      function ($item) { $item['bookings'] = 0; return $item; },
      $this->getConfEventsByDate($f3->get('PARAMS.date'))
    );
    $toDelete = [];
    foreach ($items as $item) {
      if ($item->readonly) {
        echo \View::instance()->render('modals/modalUnavailable.php', 'text/html', compact('date')); exit;
      }
      if ($item->event) {
        foreach ($events as $eventId => $event) {
          if ($event['title'] == $item->event) {
            $event['bookings']++;
            $events[$eventId] = $event;
            if ($event['bookings'] >= $event['max_booking_per_date']) {
              $toDelete[] = $eventId;
            }
          }
        }
      }
    }
    foreach ($toDelete as $eventId) {
      unset($events[$eventId]);
    }
    if (!$events) {
      echo \View::instance()->render('modals/modalUnavailable.php', 'text/html', compact('date')); exit;
    }
    echo \View::instance()->render('modals/modalBooking.php', 'text/html', compact('date', 'events')); exit;
  }

  function calendarBooking($f3) {
    $user = $this->getUser();
    if (!$user->id) {
      $f3->error(404);
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $bookingDate = $f3->get('POST.bookingDate');
    $event = $this->getConfEvent($f3->get('POST.event'));
    if (!$event) {
      $f3->error(404);
    }
    if ($bookingDate && strtotime($bookingDate)) {
      if (!$booking->load(['user_id=? AND start_date=? AND event=?', $user->id, $bookingDate, $event['title']])) {
        $booking->user_id = $user->id;
        $booking->start_date = $bookingDate;
        $booking->end_date = $bookingDate;
        $booking->title = $event['title']." — $user->firstname $user->lastname";
        $booking->event = $event['title'];
        if ($color = $this->getConfColor($event['color'])) {
          $booking->bgcolor = $color['bgcolor'];
          $booking->bdcolor = $color['bdcolor'];
          $booking->txtcolor = $color['txtcolor'];
        }
        $booking->save();
        $f3->set('user', $user);
        $f3->set('booking', $booking);
        $f3->set('date', date('d/m/Y', strtotime($bookingDate)));
        $message = \Template::instance()->render('emails/confirmation.html');
        $sended = mb_send_mail($user->email, $f3->get('email.subject_confirmation'), $message, ['From' => $f3->get('email.from'), 'Content-Type' => 'text/plain; charset=UTF-8', 'MIME-Version' => '1.0', 'Content-Transfer-Encoding' => '8bit']);
      }
    }
    $f3->reroute("/?date=$bookingDate");
  }

  function freeForm($f3) {
    $user = $this->getUser();
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
    $user = $this->getUser();
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
    $f3->reroute("/?date=$booking->start_date");
  }

  function userForm($f3) {
    $user = $this->getUser();
    if (!$user->id) {
      $f3->error(404);
    }
    $f3->set('title', $f3->get('home_title'));
    $f3->set('content', 'userBooking.html');
    $f3->set('user', $user);
    $f3->set('users', $user->find(['is_active=?', true]));
    $f3->set('events', $this->getConfEvents());
    echo \Template::instance()->render('layout.html');
  }

  function userSave($f3) {
    $user = $this->getUser();
    if (!$user->id) {
      $f3->error(404);
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $booking->copyfrom('POST');
    $u = $user->findone(['id=?', $booking->user_id]);
    $event = $this->getConfEvent($f3->get('POST.event'));
    if (!$event) {
      $f3->error(404);
    }
    $booking->end_date = $booking->start_date;
    $booking->title = $event['title']." — $u->firstname $u->lastname";
    $booking->event = $event['title'];
    if ($color = $this->getConfColor($event['color'])) {
      $booking->bgcolor = $color['bgcolor'];
      $booking->bdcolor = $color['bdcolor'];
      $booking->txtcolor = $color['txtcolor'];
    }
    $booking->save();
    $f3->reroute("/?date=$booking->start_date");
  }

  function delete($f3) {
    $user = $this->getUser();
    if (!$user->id) {
      $f3->error(404);
    }
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $booking->load(['id=?', $f3->get('PARAMS.id')]);
    if (!$booking->id) {
      $f3->error(404);
    }
    if (($user->id == $booking->user_id && $booking->start_date != date('Y-m-d'))||($user->is_admin)) {
      $booking->erase();
    }
    $f3->reroute("/?date=$booking->start_date");
  }

  function showBooking($f3) {
    $user = $this->getUser();
    $booking = new \DB\SQL\Mapper($f3->get('DB'),'booking');
    $booking->load(['id=?', $f3->get('PARAMS.id')]);
    if (!$booking->id) {
      echo \View::instance()->render('modals/modalError.php', 'text/html'); exit;
    }
    echo \View::instance()->render('modals/modalInfo.php', 'text/html', compact('user', 'booking')); exit;
  }
}
