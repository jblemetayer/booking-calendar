<div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
    <form action="/calendar-booking" method="post" id="bookingForm">
      <header class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-calendar-check"></i> Réservation</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </header>
      <div class="modal-body text-center">
        <input type="hidden" name="bookingDate" value="<?php echo $date->format('Y-m-d') ?>" />
        <p>Je confirme ma réservation à la date du</p>
        <h3 class="fw-bold mb-3"><?php echo $date->format('d/m/Y') ?></h3>
        <p>Pour</p>
        <?php foreach ($events as $event): ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="event" id="event_<?php echo $event['id'] ?>" value="<?php echo $event['id'] ?>" required<?php if (count($events) == 1): ?> checked<?php endif; ?><?php if ($event['bookings'] >= $event['max_booking_per_date']): ?> disabled<?php endif; ?> />
          <label class="form-check-label fw-bold" for="event_<?php echo $event['id'] ?>"><?php echo $event['title'] ?></label>
        </div>
        <?php endforeach ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <input type="submit" class="btn btn-success" value="Je confirme" name="bookingConfirm" />
      </div>
    </form>
  </div>
</div>
