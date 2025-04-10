self.addEventListener("install", () => {
  self.skipWaiting();
});

let notification_data;
addEventListener("message", (event) => {
  notification_data = event.data;
});

let notificationRequest;
self.addEventListener("activate", () => {

  let payload = {
    all: false,
    page: 0,
    limit: 100,
    mentions: false,
  }
  let last_checked = new Date(0);
  notificationRequest = setInterval(function() {
    if (notification_data) {
      fetch(notification_data.root + 'dt/v1/notifications/get_notifications',{
        method: "POST",
        headers: {
          "Content-Type": "application/json; charset=utf-8",
          "X-WP-Nonce": notification_data.nonce,
        },
        body: JSON.stringify(payload),
      })
        .then((response) => {
          return response.json();
        })
        .then((notifications) => {
          for (const [index, notification] of notifications.entries()) {
            let notification_date = new Date(notifications[index].date_notified);
            if (notification_date > last_checked) {

              // Timeout function so that all notifications aren't sent at once
              setTimeout(function() { 
                let notify_title;
                let notify_body;

                // Extract post url from response data
                const regex = /href="(.*)"/gm;
                //const matches = notification.notification_note.match(regex);
                const matches = regex.exec(notification.notification_note);
                if (matches) {
                  notify_url = matches[1];
                }
                
                // Build notification based on response data
                switch(notification.notification_name) {
                  case 'created':
                    notify_title = notification_data.translations.created_title;
                    notify_body = notification_data.translations.created_body;
                    break;
                  case 'assigned_to':
                    notify_title = notification_data.translations.assigned_to_title;
                    notify_body = notification_data.translations.assigned_to_body;
                    break;
                  case 'assigned_to_other':
                    notify_title = notification_data.translations.assigned_to_other_title;
                    notify_body = notification_data.translations.assigned_to_other_body;
                    break;
                  case 'share':
                    notify_title = notification_data.translations.share_title;
                    notify_body = notification_data.translations.share_body;
                    break;
                  case 'mention':
                    notify_title = notification_data.translations.mention_title;
                    notify_body = notification_data.translations.mention_body;
                    break;
                  case 'comment':
                    notify_title = notification_data.translations.comment_title;
                    notify_body = notification_data.translations.comment_body;
                    break;
                  case 'subassigned':
                    notify_title = notification_data.translations.subassigned_title;
                    notify_body = notification_data.translations.subassigned_body;
                    break;
                  case 'milestone':
                    notify_title = notification_data.translations.milestone_title;
                    notify_body = notification_data.translations.milestone_body;
                    break;
                  case 'requires_update':
                    notify_title = notification_data.translations.requires_update_title;
                    notify_body = notification_data.translations.requires_update_body;
                    break;
                  case 'contact_info_update':
                    notify_title = notification_data.translations.contact_info_update_title;
                    notify_body = notification_data.translations.contact_info_update_body;
                    break;
                  case 'assignment_declined':
                    notify_title = notification_data.translations.assignment_declined_title;
                    notify_body = notification_data.translations.assignment_declined_body;
                    break;
                  default:
                    // code
                }
                
                // get window thing from pwa.js for icon
                self.registration.showNotification(notify_title, {
                  body: notify_body,
                  icon: `${notification_data.template_dir}/dt-assets/images/dt-caret.png`, // dt-assets/images/dt-caret.png
                  actions: [
                    { action: 'open_link', title: notification_data.translations.action_title }
                  ],
                  data: {
                    link: notify_url,
                    id: notifications[index].id,
                    nonce: notification_data.nonce,
                    root: notification_data.root,
                  }
                });

              // Timeout set to 1000 milliseconds (1 second) between each notification, so they don't all show at once
              }, 1000 * index); 
            } else {
              break;
            }
          }
          
          if (notifications.length > 0) {
            last_checked = new Date();
          }

          return notifications;
        })
        .catch((reason) => {
          console.log("reason:");
          console.log(reason);
        }); 
    } else {
      console.log('Waiting, data not initialized');
    }
  }, 60000); // Execute every 60000 milliseconds (60 seconds)
});


self.addEventListener('notificationclick', (event) => {
  const linkURL = event.notification.data["link"];

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

  const url = event.notification.data.root + `dt/v1/notifications/mark_viewed/${event.notification.data["id"]}`;
  fetch(url,{
      method: "POST",
      headers: {
        "Content-Type": "application/json; charset=utf-8",
        "X-WP-Nonce": event.notification.data["nonce"],
      },
    })
      .then((response) => {
        return response.json();
      })
      .catch((reason) => {
        console.log("reason:");
        console.log(reason);
      });
});