<div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
      <header class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-calendar-x"></i> Date indisponible</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </header>
      <div class="modal-body text-center">
        <p><i class="bi bi-exclamation-triangle"></i></p>
        <p>Désolé, la date <span class="fw-bold"><?php echo (isset($date))? 'du '.$date->format('d/m/Y') : 'demandée' ?></span> n'est pas réservable car indisponible ou complète</p>
        <h3 class="eventDate" class="fw-bold"></h3>
      </div>
      <div class="modal-footer text-center">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">OK</button>
      </div>
  </div>
</div>
