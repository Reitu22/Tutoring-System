document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const navigation = document.querySelector('.nav-links');
  if (toggle && navigation) toggle.addEventListener('click', () => {
    const open = navigation.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  document.querySelectorAll('[data-confirm]').forEach(link => link.addEventListener('click', event => {
    if (!window.confirm(link.dataset.confirm)) event.preventDefault();
  }));
  const bookingForm = document.querySelector('[data-booking-form]');
  if (bookingForm) bookingForm.addEventListener('submit', event => {
    const date = bookingForm.elements.session_date.value;
    const start = bookingForm.elements.start_time.value;
    const end = bookingForm.elements.end_time.value;
    if (date) {
      const day = new Date(`${date}T12:00:00`).getDay();
      if (day === 0 || day === 6) { event.preventDefault(); alert('Please choose a weekday, Monday to Friday.'); return; }
    }
    if (start && end && start >= end) { event.preventDefault(); alert('The end time must be later than the start time.'); }
  });
});