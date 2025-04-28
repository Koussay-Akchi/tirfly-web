import "../scss/app.scss";

// TirFly (required)
import "./modules/bootstrap";
import "./modules/sidebar";
import "./modules/theme";
import "./modules/feather";

// Charts
import "./modules/chartjs";

// Forms
import "./modules/flatpickr";

// Maps
import "./modules/vector-maps";

// Add deep link handling for Capacitor
import { App } from '@capacitor/app';

// Listen for deep link events
App.addListener('appUrlOpen', (event) => {
  console.log('Deep link opened:', event.url);

  // Navigate the webview to the deep link URL
  window.location.href = event.url;
});

// Log when the app is initialized (for debugging)
console.log('App initialized, listening for deep links');