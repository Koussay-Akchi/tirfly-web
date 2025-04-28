document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            events: '/api/evenements',
            height: 'auto',
            contentHeight: 'auto',
            aspectRatio: 1.5,
            eventClick: function(info) {
                console.log('Événement cliqué : ', info.event.title);
                const title = info.event.title;
                const description = info.event.extendedProps.description || 'Aucune description';
                const price = info.event.extendedProps.price ? info.event.extendedProps.price + ' €' : 'Prix non défini';
                const destination = info.event.extendedProps.destination || 'Destination non spécifiée';
                const dates = 'Du ' + info.event.start.toLocaleDateString('fr-FR') + 
                              ' au ' + (info.event.end ? info.event.end.toLocaleDateString('fr-FR') : info.event.start.toLocaleDateString('fr-FR'));

                Swal.fire({
                    title: title,
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Description :</strong> ${description}</p>
                            <p><strong>Prix :</strong> ${price}</p>
                            <p><strong>Destination :</strong> ${destination}</p>
                            <p><strong>Dates :</strong> ${dates}</p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Fermer',
                    confirmButtonColor: '#198754',
                    customClass: {
                        popup: 'swal2-popup-custom',
                        title: 'swal2-title-custom',
                        htmlContainer: 'swal2-html-container-custom'
                    }
                });
            },
            dateClick: function(info) {
                // Rediriger directement vers la page d'ajout avec la date sélectionnée
                window.location.href = `/admin/evenement/new?date=${info.dateStr}`;
            },
            eventContent: function(arg) {
                return {
                    html: `
                        <div class="fc-event-main" title="${arg.event.title}">
                            <strong>${arg.event.title.slice(0, 15)}${arg.event.title.length > 15 ? '...' : ''}</strong>
                            <small>${arg.event.extendedProps.price} €</small>
                        </div>
                    `
                };
            },
            dayMaxEvents: 2,
            moreLinkContent: '+',
            moreLinkClassNames: 'fc-more-link',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }
        });

        calendar.render();
    } else {
        console.error('Élément calendrier introuvable');
    }
});