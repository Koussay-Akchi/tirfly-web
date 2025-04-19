document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            events: '/api/evenements', // Appelle l'API pour récupérer les événements
            eventClick: function(info) {
                // Afficher les détails de l'événement dans une alerte ou une modale
                alert(
                    'Événement: ' + info.event.title + '\n' +
                    'Description: ' + info.event.extendedProps.description + '\n' +
                    'Prix: ' + info.event.extendedProps.price + ' €\n' +
                    'Destination: ' + info.event.extendedProps.destination + '\n' +
                    'Du: ' + info.event.start.toLocaleDateString() + '\n' +
                    'Au: ' + (info.event.end ? info.event.end.toLocaleDateString() : info.event.start.toLocaleDateString())
                );
            },
            eventContent: function(arg) {
                // Personnaliser l'affichage des événements
                return {
                    html: `
                        <div class="fc-event-main">
                            <strong>${arg.event.title}</strong><br>
                            <small>${arg.event.extendedProps.destination}</small><br>
                            <small>${arg.event.extendedProps.price} €</small>
                        </div>
                    `
                };
            }
        });

        calendar.render();
    } else {
        console.error('Calendar element not found');
    }
});