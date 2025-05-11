import './bootstrap';

// Import Alpine.js if using Breeze default
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// --- Add FullCalendar Imports ---
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
// import interactionPlugin from '@fullcalendar/interaction'; // If needed later

// Make them globally accessible for the inline script in calendar.blade.php
window.FullCalendar = {
    Calendar: Calendar,
    // Plugins need to be accessed via their exports
};
window.FullCalendarDayGrid = { dayGridPlugin };
window.FullCalendarTimeGrid = { timeGridPlugin };
window.FullCalendarList = { listPlugin };
// window.FullCalendarInteraction = { interactionPlugin }; // If needed later

// Ensure npm run dev is running to bundle these changes