const DB_NAME = 'disciple-tools';
const STORE_NAME = 'service-worker';
const LAST_CHECKED_KEY = 'lastChecked';

function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, 1);

    request.onerror = (event) => {
      console.error('Error opening database:', event.target.errorCode);
      reject(event.target.error);
    };

    request.onsuccess = (event) => {
      resolve(event.target.result);
    };

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME);
      }
    };
  });
}

function saveLastChecked(date) {
  return openDatabase().then((db) => {
    return new Promise((resolve, reject) => {
      const transaction = db.transaction(STORE_NAME, 'readwrite');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.put(date, LAST_CHECKED_KEY);

      request.onsuccess = () => {
        resolve();
      };

      request.onerror = (event) => {
        console.error('Error saving data:', event.target.errorCode);
        reject(event.target.error);
      };

      transaction.oncomplete = () => {
        db.close();
      };
    });
  });
}

function getLastChecked() {
  return openDatabase().then((db) => {
    return new Promise((resolve, reject) => {
      const transaction = db.transaction(STORE_NAME, 'readonly');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.get(LAST_CHECKED_KEY);

      request.onsuccess = (event) => {
        resolve(event.target.result); // Will be a Date object or undefined
      };

      request.onerror = (event) => {
        console.error('Error retrieving data:', event.target.errorCode);
        reject(event.target.error);
      };

      transaction.oncomplete = () => {
        db.close();
      };
    });
  });
}

self.addEventListener('install', () => {
  self.skipWaiting();
});

let notification_data;
addEventListener('message', (event) => {
  // console.log('message', event);
  notification_data = event.data;
});

let notificationRequest;
let last_checked = new Date(0);
self.addEventListener('activate', (event) => {
  // console.log('Service worker activated');
  event.waitUntil(
    // get lastChecked value from indexeddb - from last time service worker was running
    getLastChecked().then((lastCheckedFromStorage) => {
      if (lastCheckedFromStorage) {
        console.log('Last checked from storage:', lastCheckedFromStorage);
        last_checked = lastCheckedFromStorage;
      } else {
        console.log('No last checked value found in storage.');
      }
      notificationRequest = setInterval(fetchNotifications, 30000); // Execute every 30000 milliseconds (30 seconds)
    }),
  );
});

const fetchNotifications = () => {
  let payload = {
    all: false,
    page: 0,
    limit: 25,
    mentions: false,
  };
  if (notification_data) {
    fetch(notification_data.root + 'dt/v1/notifications/get_notifications', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
        'X-WP-Nonce': notification_data.nonce,
      },
      body: JSON.stringify(payload),
    })
      .then((response) => {
        return response.json();
      })
      .then((notifications) => {
        console.log('api response. last_checked:', last_checked);
        let maxDate = last_checked;
        for (const [index, notification] of notifications.entries()) {
          let notification_date = new Date(notifications[index].date_notified);
          console.log({
            notification_date,
            last_checked,
            isNew: notification_date > last_checked,
          });
          if (notification_date > last_checked) {
            // keep track of the most recent notification date to update last_checked later
            if (notification_date > maxDate) {
              maxDate = notification_date;
            }
            // Timeout function so that all notifications aren't sent at once
            setTimeout(function () {
              let notify_title;
              let notify_body;
              let notify_url;

              // Extract post url from response data
              const regex = /href="(.*)"/gm;
              //const matches = notification.notification_note.match(regex);
              const matches = regex.exec(notification.notification_note);
              if (matches) {
                notify_url = matches[1];
              }

              // Build notification based on response data
              switch (notification.notification_name) {
                case 'created':
                  notify_title = notification_data.translations.created_title;
                  notify_body = notification_data.translations.created_body;
                  break;
                case 'assigned_to':
                  notify_title =
                    notification_data.translations.assigned_to_title;
                  notify_body = notification_data.translations.assigned_to_body;
                  break;
                case 'assigned_to_other':
                  notify_title =
                    notification_data.translations.assigned_to_other_title;
                  notify_body =
                    notification_data.translations.assigned_to_other_body;
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
                  notify_title =
                    notification_data.translations.subassigned_title;
                  notify_body = notification_data.translations.subassigned_body;
                  break;
                case 'milestone':
                  notify_title = notification_data.translations.milestone_title;
                  notify_body = notification_data.translations.milestone_body;
                  break;
                case 'requires_update':
                  notify_title =
                    notification_data.translations.requires_update_title;
                  notify_body =
                    notification_data.translations.requires_update_body;
                  break;
                case 'contact_info_update':
                  notify_title =
                    notification_data.translations.contact_info_update_title;
                  notify_body =
                    notification_data.translations.contact_info_update_body;
                  break;
                case 'assignment_declined':
                  notify_title =
                    notification_data.translations.assignment_declined_title;
                  notify_body =
                    notification_data.translations.assignment_declined_body;
                  break;
                default:
                // code
              }

              // get window thing from pwa.js for icon
              let options = {
                body: notify_body,
                icon: `${notification_data.template_dir}/dt-assets/images/dt-caret.png`, // dt-assets/images/dt-caret.png
                actions: [
                  {
                    action: 'open_link',
                    title: notification_data.translations.action_title,
                  },
                ],
                data: {
                  link: notify_url,
                  id: notifications[index].id,
                  nonce: notification_data.nonce,
                  root: notification_data.root,
                },
              };
              console.log('showNotification', options);
              self.registration
                .showNotification(notify_title, options)
                .catch((err) => {
                  console.error(err);
                });

              // Timeout set to 1000 milliseconds (1 second) between each notification, so they don't all show at once
            }, 1000 * index);
          } else {
            break;
          }
        }

        // update last_checked
        if (notifications.length > 0 && maxDate !== last_checked) {
          last_checked = maxDate;
          saveLastChecked(maxDate);
        }

        return notifications;
      })
      .catch((reason) => {
        console.log('reason:');
        console.log(reason);
      });
  } else {
    console.log('Waiting, data not initialized');
  }
};

self.addEventListener('notificationclick', (event) => {
  const linkURL = event.notification.data['link'];

  event.notification.close();

  event.waitUntil(
    self.clients.matchAll({ type: 'window' }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === '/' && 'focus' in client) {
          return client.focus();
        }
      }
      if (self.clients.openWindow && linkURL) {
        return self.clients.openWindow(linkURL);
      }
    }),
  );

  const url =
    event.notification.data.root +
    `dt/v1/notifications/mark_viewed/${event.notification.data['id']}`;
  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
      'X-WP-Nonce': event.notification.data['nonce'],
    },
  })
    .then((response) => {
      return response.json();
    })
    .catch((reason) => {
      console.log('reason:');
      console.log(reason);
    });
});
