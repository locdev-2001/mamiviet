import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.tsx';
import { AppContentProvider } from './lib/contexts/AppContentContext';
import './lib/i18n';

createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <AppContentProvider>
      <App />
    </AppContentProvider>
  </React.StrictMode>
);
