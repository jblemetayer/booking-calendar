<?php

require __DIR__ . '/../vendor/autoload.php';

$f3 = \Base::instance();

$f3->set('ROOT', __DIR__.'/..');
$f3->set('UI', $f3->get('ROOT')."/views/");
$f3->set('TEMP',$f3->get('ROOT').'/tmp/');
$f3->set('DB', new \DB\SQL('sqlite:'. $f3->get('ROOT').'/database/datas.sqlite'));
$f3->set('ENCODING','UTF-8');
$f3->set('LANGUAGE','fr-FR');
$f3->config($f3->get('ROOT').'/config/app.conf');
$f3->set('DEBUG', 3);

$today = strtotime(date('Y-m-d'));
$firstDayWeek = new \DateTime();
$lastDayWeek = new \DateTime();

$booking = new \DB\SQL\Mapper(\Base::instance()->get('DB'),'booking');
$events = $f3->get('events');
$creneaux = [];
foreach ($events as $event) {
  $firstDayWeek->setTimestamp(strtotime('next monday', $today));
  $lastDayWeek->setTimestamp(strtotime('next friday', $today));
  while ($firstDayWeek <= $lastDayWeek) {
    $readonly = $booking->count(['start_date <= ? AND end_date >= ? AND readonly=?', $firstDayWeek->format('Y-m-d'), $firstDayWeek->format('Y-m-d'), 1]);
    if ($readonly) {
      $firstDayWeek->modify('+1 day');
      continue;
    }
    $bookings = $booking->find(['start_date >= ? AND end_date <= ? AND event=?', $firstDayWeek->format('Y-m-d'), $firstDayWeek->format('Y-m-d'), $event['title']]);
    $day = strtolower($firstDayWeek->format('l'));
    if (isset($event['max_booking_per_date'][$day]) && $event['max_booking_per_date'][$day] > count($bookings)) {
      $creneaux[] =  $event['title'].' — '.($event['max_booking_per_date'][$day] - count($bookings))." personne(s) pour le ".$firstDayWeek->format('d/m/Y');
    }
    $firstDayWeek->modify('+1 day');
  }
}

if ($creneaux) {
  $rows = $f3->get('DB')->exec('select u.login, u.firstname, u.lastname, u.email from user u WHERE u.is_active = 1');
  $counter = 0;
  $success = 0;
  foreach ($rows as $row) {
    if (!$row['email'])
      continue;
    $f3->set('user', $row);
    $f3->set('creneaux', $creneaux);
    $message = \Template::instance()->render('emails/hebdo.html');
    $sended = mb_send_mail($row['email'], $f3->get('email.subject_hebdo'), $message, ['From' => $f3->get('email.from'), 'Content-Type' => 'text/plain; charset=UTF-8', 'MIME-Version' => '1.0', 'Content-Transfer-Encoding' => '8bit']);
    if ($sended) {
      $success++;
    }
    $counter++;
  }
  echo "$counter emails to send ($success sent successfully)";
} else {
  echo "0 email to send";
}
