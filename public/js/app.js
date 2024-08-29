document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  if (calendarEl) {
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      themeSystem: 'bootstrap5',
      locale: 'fr',
      aspectRatio: 1.8,
      hiddenDays: [0,6],
      businessHours: {
        daysOfWeek: [1,2,4,5],
      },
      height: '100%',
      headerToolbar: {
        start: 'prev',
        center: 'title',
        end: 'next'
      },
      eventSources: [
        { url: '/booking' }
      ],
      eventClick: function(info) {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function() {
          document.getElementById("eventModal").innerHTML = this.responseText;
          const eventModal = new bootstrap.Modal('#eventModal');
-         eventModal.show();
        }
        xhttp.open("GET", "/booking/show/"+info.event.id);
        xhttp.send();
      },
      dateClick: function(info) {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function() {
          document.getElementById("eventModal").innerHTML = this.responseText;
          const eventModal = new bootstrap.Modal('#eventModal');
-         eventModal.show();
        }
        xhttp.open("GET", "/booking/"+info.dateStr);
        xhttp.send();
      }
    });
    if (calendarEl.hasAttribute('data-gotodate')) {
      const date = calendarEl.getAttribute('data-gotodate');
      if (date) {
        calendar.gotoDate(date);
      }
    }
    calendar.render();
  }
});
