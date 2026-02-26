/*
global makeRequestOnPosts
 */
((dtDashboard) => {
  'use strict';

  const handleContactAction = async (contactId, action) => {
    let status;
    if (action === 'accept') {
      status = 'active';
    } else if (action === 'decline') {
      status = 'unassigned';
    } else {
      return;
    }

    try {
      makeRequestOnPosts('POST', `contacts/${contactId}/accept`, {
        overall_status: status,
      }).then(function () {
        const card = document.getElementById(`contact-card-${contactId}`);
        if (card) {
          // Apply fade-out animation
          card.classList.add('transition-out');

          // Remove the card from the DOM after the animation completes
          setTimeout(() => {
            card.remove();
            const pendingContactsSection =
              document.getElementById('pending-contacts');
            // If no more pending contacts, hide the section
            if (
              pendingContactsSection &&
              pendingContactsSection.querySelectorAll('.contact-card')
                .length === 0
            ) {
              pendingContactsSection.style.display = 'none';
            }
          }, 300); // Match the 300ms duration of the opacity transition
        }
      });
    } catch (error) {
      console.error(error);
      alert(`Could not ${action} contact. Please try again.`);
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    const pendingContactsSection = document.getElementById('pending-contacts');

    if (pendingContactsSection) {
      pendingContactsSection.addEventListener('click', (event) => {
        const target = event.target;
        const contactId = target.dataset.contactId;
        const action = target.dataset.action;
        handleContactAction(contactId, action);
      });
    }
  });
})(window.dtDashboard);
