<div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
    <header class="modal-header">
      <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-calendar-check"></i> <?php if($booking->start_date != $booking->end_date): echo date('d/m/Y', strtotime($booking->start_date)).' - '.date('d/m/Y', strtotime($booking->end_date)); else: echo date('d/m/Y', strtotime($booking->start_date)); endif; ?></h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </header>
    <div class="modal-body">
      <p><span class="eventTitle fw-bold"><?php echo $booking->title ?></span><?php if($booking->start_date == $booking->end_date && !$booking->readonly): ?> confirmé à cette date.<?php endif; ?></p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
        <?php if($user->is_admin || ($user->id == $booking->user_id && $booking->start_date != date('Y-m-d'))): ?>
        <a href="/delete-booking/<?php echo $booking->id ?>" class="btn btn-danger" id="bookingDeleteAction">Annuler la réservation</a>
        <?php endif; ?>
    </div>
  </div>
</div>
