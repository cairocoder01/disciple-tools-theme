// Reference: https://web.dev/learn/pwa/installation-prompt

if ('serviceWorker' in navigator) {
  Notification.requestPermission();
  // Register a service worker hosted at the root of the
  // site using the default scope.
  navigator.serviceWorker.register('/service-worker.js').then(
    (registration) => {
      console.log('Service worker registration succeeded:', registration);
    },
    (error) => {
      console.error(`Service worker registration failed: ${error}`);
    },
  );

  navigator.serviceWorker.ready.then((registration) => {
    registration.active.postMessage(
      {
        "nonce": window.wpApiShare.nonce,
        "template_dir": window.wpApiShare.template_dir,
        "root": window.wpApiShare.root,
        "translations": window.wpPwa.translations,
      },
    );
  });
} else {
  console.error('Service workers are not supported.');
}

let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
  // Prevents the default mini-infobar or install dialog from appearing on mobile
  e.preventDefault();
  // Save the event because you'll need to trigger it later.
  deferredPrompt = e;
  //debugger;
  // Show your customized install prompt for your PWA
  // Your own UI doesn't have to be a single element, you
  // can have buttons in different locations, or wait to prompt
  // as part of a critical journey.
  console.log('prompt!');
  // showInAppInstallPromotion();
});

// Gather the data from your custom install UI event listener
/*installButton.addEventListener('click', async () => {
  // deferredPrompt is a global variable we've been using in the sample to capture the `beforeinstallevent`
  deferredPrompt.prompt();
  // Find out whether the user confirmed the installation or not
  const { outcome } = await deferredPrompt.userChoice;
  // The deferredPrompt can only be used once.
  deferredPrompt = null;
  // Act on the user's choice
  if (outcome === 'accepted') {
    console.log('User accepted the install prompt.');
  } else if (outcome === 'dismissed') {
    console.log('User dismissed the install prompt');
  }
});*/