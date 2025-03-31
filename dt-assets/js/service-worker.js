self.addEventListener("install", () => {
  self.skipWaiting();
});

self.addEventListener('notificationclick', (event) => {
  const linkURL = event.notification.data.link;

  event.notification.close();

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === '/' && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow && linkURL) {
        return clients.openWindow(linkURL);
      }
    })
  );
});

addEventListener("message", (event) => {

  const url = '/wp-json/dt/v1/notifications/get_notifications';
  let payload = {
    all: false,
    page: 0,
    limit: 100,
    mentions: false,
  }

  let last_checked = new Date();
  event.waitUntil(setInterval(function() {
    fetch(url,{
      method: "POST",
      headers: {
        "Content-Type": "application/json; charset=utf-8",
        "X-WP-Nonce": event.data["nonce"],
      },
      body: JSON.stringify(payload),
    })
      .then((response) => {
        return response.json();
      })
      .then((json) => {
        
        for (let i = 0; i < json.length; i++) {
          let notification_date = new Date(json[i].date_notified);
          if (notification_date < last_checked) {
            setTimeout(function() { 

              let notify_title;
              let notify_body;

              // Extract post url from response data
              let notify_url = json[i].notification_note;
              let url_start = notify_url.indexOf("\"", notify_url.indexOf("href="))+1;
              let url_end = notify_url.indexOf("\"", url_start);
              notify_url = notify_url.substring(url_start, url_end);
              
              // Build notification based on response data
              switch(json[i].notification_name) {
                case 'created':
                  notify_title = 'New Contact Created';
                  notify_body = 'A new contact was created and assigned to you.';
                  break;
                case 'assigned_to':
                  notify_title = 'New Contact Assigned';
                  notify_body = 'You have been assigned a new contact.';
                  break;
                case 'assigned_to_other':
                  notify_title = 'Contact Reassigned';
                  notify_body = 'A contact has been reassigned.';
                  break;
                case 'share':
                  notify_title = 'Contact Shared';
                  notify_body = 'A contact has been shared with you.';
                  break;
                case 'mention':
                  notify_title = 'New Mention';
                  notify_body = 'You were mentioned on a contact.';
                  break;
                case 'comment':
                  notify_title = 'New Comment';
                  notify_body = 'A new comment was left on a contact.';
                  break;
                case 'subassigned':
                  notify_title = 'New Contact Subassigned';
                  notify_body = 'A new contact has been subassigned to you.';
                  break;
                case 'milestone':
                  notify_title = 'New Milestone';
                  notify_body = 'A new milestone was added to a contact.';
                  break;
                case 'requires_update':
                  notify_title = 'Update Required';
                  notify_body = 'A contact requires an update.';
                  break;
                case 'contact_info_update':
                  notify_title = 'Contact Updated';
                  notify_body = 'A contact\'s details were modified.';
                  break;
                case 'assignment_declined':
                  notify_title = 'User Declined Assignment';
                  notify_body = 'A user declined assignment on a contact.';
                  break;
                default:
                  // code
              }
              
              // get window thing from pwa.js for icon
              self.registration.showNotification(notify_title, {
                body: notify_body,
                icon: `${event.data["template_dir"]}/dt-assets/images/dt-caret.png`, // dt-assets/images/dt-caret.png
                actions: [
                  { action: 'open_link', title: 'Click here to open the link' }
                ],
                data: { link: notify_url }
              });

            }, 1000 * i); 
          } else {
            break;
          }
        }
        if (json.length > 0) {
          last_checked = new Date(json[0].date_notified);
        }
        return json;

      })
      .catch((reason) => {
        console.log("reason:");
        console.log(reason);
      });
  }, 60000)); // Execute every 60000 milliseconds (60 seconds)
});