<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Calendar View') }}
        </h2>
    </x-slot>

    @push('styles')
    <style>
        /* Custom styles for the calendar */
        #calendar-wrapper, :root {
            --fc-border-color: #e5e7eb;
            --fc-today-bg-color: #e0f2fe;
            --fc-button-bg-color: #38bdf8;
            --fc-button-text-color: #ffffff;
            --fc-event-bg-color: #7dd3fc;
            --fc-event-border-color: #38bdf8;
            --fc-event-text-color: #0c4a6e;
        }
        #calendar .fc-daygrid-day.fc-day-today {
            background-color: var(--fc-today-bg-color) !important;
        }
        #calendar .fc-button {
            text-transform: capitalize;
        }
        #calendar .fc-event {
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            font-size: 0.8rem;
            padding: 2px 4px;
        }
    </style>
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div id="calendar-wrapper" class="bg-white overflow-hidden shadow-lg sm:rounded-lg border border-gray-200">
                <div class="p-6 text-gray-900">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            // Check if FullCalendar and plugins are loaded
            if (typeof FullCalendar === 'undefined' || !FullCalendar.Calendar ||
                typeof FullCalendarDayGrid === 'undefined' || !FullCalendarDayGrid.dayGridPlugin ||
                typeof FullCalendarTimeGrid === 'undefined' || !FullCalendarTimeGrid.timeGridPlugin ||
                typeof FullCalendarList === 'undefined' || !FullCalendarList.listPlugin) {
                console.error("FullCalendar or its required plugins are not loaded!");
                calendarEl.innerHTML = '<p class="text-center text-red-600 font-medium p-10">Error: Calendar library failed to load. Please check the console.</p>';
                return;
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: [
                    FullCalendarDayGrid.dayGridPlugin,
                    FullCalendarTimeGrid.timeGridPlugin,
                    FullCalendarList.listPlugin
                ],
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                initialView: 'dayGridMonth',
                editable: false,
                selectable: false,
                events: '{{ route("calendar.events") }}',

                // ----> Added options for time display <----
                displayEventTime: true,
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                },

                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                },
                loading: function(isLoading) {
                    calendarEl.style.opacity = isLoading ? '0.5' : '1';
                },
                eventSourceFailure: function(error) {
                    console.error('Error fetching FullCalendar events:', error);
                    alert('Could not load calendar events. Please try refreshing the page or contact support if the problem persists.');
                }
            });

            calendar.render();
        });
    </script>
    @endpush

</x-app-layout>