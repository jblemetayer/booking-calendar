<div class="modal-dialog modal-dialog-centered">
  <div class="modal-content">
    <form action="/login" method="post" id="bookingForm">
      <input type="hidden" name="bookingDate" />
      <header class="modal-header">
        <h2 class="modal-title fs-5 fw-bold"><i class="bi bi-box-arrow-in-right"></i> Identification</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </header>
      <div class="modal-body">
        <p class="text-center"><i class="bi bi-exclamation-triangle"></i></p>
        <p class="text-center">Vous devez être identifié pour réserver une date.</p>
        <p>Pour celà, veuillez indiquer votre login qui vous a été communiqué par la personne en charge de l'application.</p>
        <div class="row align-items-center">
          <div class="col-3">
            <label for="userLogin" class="col-form-label">Votre login :</label>
          </div>
          <div class="col-6">
            <input type="text" id="userLogin" class="form-control" name="login" required />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <input type="submit" class="btn btn-success" value="S'identifier" name="bookingLogin" />
      </div>
    </form>
  </div>
</div>
