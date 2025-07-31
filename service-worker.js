self.addEventListener('push', function(event) {
    const data = event.data.json();
    const title = data.title || "Notification";
    const options = {
        body: data.body,
        icon: '/icon.png', // Mets ici le chemin de ton icône si tu en as une
    };
    event.waitUntil(self.registration.showNotification(title, options));
});