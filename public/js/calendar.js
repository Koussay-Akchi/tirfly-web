document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            events: '/api/evenements',
            height: 'auto', // Hauteur dynamique
            contentHeight: 'auto', // Pas de défilement inutile
            aspectRatio: 1.5, // Ratio pour un look compact
            eventClick: function(info) {
                // Remplir la modale avec les détails
                document.getElementById('eventTitle').textContent = info.event.title;
                document.getElementById('eventDescription').textContent = info.event.extendedProps.description;
                document.getElementById('eventPrice').textContent = info.event.extendedProps.price + ' €';
                document.getElementById('eventDestination').textContent = info.event.extendedProps.destination;
                document.getElementById('eventDates').textContent = 
                    'Du ' + info.event.start.toLocaleDateString('fr-FR') + 
                    ' au ' + (info.event.end ? info.event.end.toLocaleDateString('fr-FR') : info.event.start.toLocaleDateString('fr-FR'));

                // Afficher la modale
                var modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            },
            eventContent: function(arg) {
                // Affichage ultra-compact
                return {
                    html: `
                        <div class="fc-event-main" title="${arg.event.title}">
                            <strong>${arg.event.title.slice(0, 15)}${arg.event.title.length > 15 ? '...' : ''}</strong>
                            <small>${arg.event.extendedProps.price} €</small>
                        </div>
                    `
                };
            },
            dayMaxEvents: 2, // Max 2 événements par jour
            moreLinkContent: '+', // Lien "plus" minimaliste
            moreLinkClassNames: 'fc-more-link',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }
        });

        calendar.render();
    } else {
        console.error('Calendar element not found');
    }
});