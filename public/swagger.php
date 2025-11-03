<?php
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>Climate Health API Explorer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css" integrity="sha384-Uh1xAi8uKXuX4nHCqB6fBsB03F3qeRy6E5n4dbvOswXLJYJBMw3xihYEPn5+MB1Y" crossorigin="anonymous"/>
  <style>
    body { margin: 0; background: #0b3954; font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .swagger-ui { background: #fff; }
    .swagger-ui .topbar { display: none; }
    .swagger-ui .info h1 { color: #0b3954; }
    .swagger-ui .scheme-container { background: rgba(11, 57, 84, 0.05); }
    .page-wrapper { min-height: 100vh; display: flex; justify-content: center; align-items: stretch; padding: 32px 16px; }
    .swagger-card { width: 100%; max-width: 1080px; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.32); background: #fff; }
    .swagger-header { padding: 24px 32px; background: linear-gradient(135deg, rgba(11,57,84,0.92), rgba(11,57,84,0.75) 50%, rgba(255,127,17,0.9)); color: #fff; }
    .swagger-header h1 { margin: 0 0 8px; font-size: 1.8rem; letter-spacing: 0.06em; text-transform: uppercase; }
    .swagger-header p { margin: 0; max-width: 720px; line-height: 1.5; opacity: 0.92; }
    .swagger-body { padding: 0; }
    .swagger-footer { padding: 16px 32px 28px; background: #f8fafc; color: #475569; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
    .swagger-footer a { color: #0b3954; font-weight: 600; }
  </style>
</head>
<body>
  <div class="page-wrapper">
    <div class="swagger-card">
      <header class="swagger-header">
        <h1>Climate Health Data API</h1>
        <p>Interactive schema for integrating climate surveillance outputs with DHIS2 and other health information systems. Use the explorer below to review payload structures and test the endpoints.</p>
      </header>
      <div class="swagger-body">
        <div id="swagger-ui"></div>
      </div>
      <footer class="swagger-footer">
        <span>Need raw definitions? <a href="openapi.json" target="_blank" rel="noopener">Download OpenAPI JSON</a></span>
        <span>Return to <a href="index.php">Dashboard</a></span>
      </footer>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js" integrity="sha384-Yl3R7saEh1PQu2Iw8E0NL1p3HT5ZNSmx4WKqXSDAVBIiEKvbmbrxSTnT6ybvtVX6" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js" integrity="sha384-5TDttzQEfQJoL9XOB/Cw0Ma4qhxn1kvL1mWju6C5PRnXXlCujXloCB36nlPV95sB" crossorigin="anonymous"></script>
  <script>
    window.addEventListener('load', () => {
      SwaggerUIBundle({
        url: 'openapi.json',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        layout: 'BaseLayout',
        docExpansion: 'none',
        defaultModelsExpandDepth: 0,
        filter: true
      });
    });
  </script>
</body>
</html>
