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
      eventBackgroundColor: '#cfe2ff',
      eventBorderColor: '#9ec5fe',
      eventTextColor: '#052c65',
      headerToolbar: {
        start: 'prev',
        center: 'title',
        end: 'next'
      },
      eventSources: [
        { url: '/booking' }
      ],
      eventClick: function(info) {
        const link = document.getElementById("bookingDeleteAction");
        if (link) {
          link.href = link.href.replace('_id_', info.event.id);
        }
        document.querySelector('#eventInfo .eventDate').textContent = (info.event.start.getDate()).toString().padStart(2, '0') + '/' + (info.event.start.getMonth()+1).toString().padStart(2, '0') + '/' + info.event.start.getFullYear();
        document.querySelector('#eventInfo .eventTitle').textContent = info.event.title;
        if ((info.event.title).indexOf('—') == -1) {
          document.querySelector('#eventInfo .eventWithUser').style.display = 'none';
        } else {
          document.querySelector('#eventInfo .eventWithUser').style.display = 'inline';
        }
        const eventDelete = new bootstrap.Modal('#eventInfo');
        eventDelete.show();
      },
      dateClick: function(info) {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function() {
          if (this.responseText == 'true') {
            const inputs = document.getElementById("bookingForm").elements;
            inputs["bookingDate"].value = info.dateStr;
            document.querySelector('#dateConfirm .eventDate').textContent = (info.date.getDate()).toString().padStart(2, '0') + '/' + (info.date.getMonth()+1).toString().padStart(2, '0') + '/' + info.date.getFullYear();
            const dateConfirm = new bootstrap.Modal('#dateConfirm');
            dateConfirm.show();
          } else {
            document.querySelector('#dateUnavailable .eventDate').textContent = (info.date.getDate()).toString().padStart(2, '0') + '/' + (info.date.getMonth()+1).toString().padStart(2, '0') + '/' + info.date.getFullYear();
            const dateUnavailable = new bootstrap.Modal('#dateUnavailable');
            dateUnavailable.show();
          }
        }
        const inputs = document.getElementById("bookingForm").elements;
        if (inputs["bookingConfirm"] === undefined) {
          const dateConfirm = new bootstrap.Modal('#dateConfirm');
          dateConfirm.show();
        } else {
          xhttp.open("GET", "/booking/"+info.dateStr+"/bookable");
          xhttp.send();
        }

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
