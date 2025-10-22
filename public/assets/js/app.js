document.addEventListener('DOMContentLoaded', () => {
  if (window.M && typeof M.updateTextFields === 'function') {
    M.updateTextFields();
    const textareas = document.querySelectorAll('.materialize-textarea');
    textareas.forEach((el) => {
      if (typeof M.textareaAutoResize === 'function') {
        M.textareaAutoResize(el);
      }
    });
  }

  const mapEl = document.getElementById('climateMap');
  if (!mapEl || typeof L === 'undefined') {
    return;
  }

  const summaryEl = document.getElementById('mapSummary');
  const defaultMetric = mapEl.dataset.defaultMetric || 'tmean_c';
  const metricLabels = {
    tmean_c: 'Mean Temperature (°C)',
    rain_mm: 'Rainfall (mm)'
  };

  const map = L.map('climateMap', {
    scrollWheelZoom: false,
    attributionControl: true
  }).setView([9.145, 40.489], 6);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  const renderSummary = (features) => {
    if (!summaryEl) {
      return;
    }
    if (!features.length) {
      summaryEl.innerHTML = '<p>No spatial records are available yet. Run an ingest to populate the GIS report.</p>';
      return;
    }

    const temps = [];
    const rain = [];
    let hottest = null;
    let wettest = null;

    features.forEach((feature) => {
      const props = feature.properties || {};
      if (props.tmean_c !== null && props.tmean_c !== undefined) {
        temps.push(props.tmean_c);
        if (!hottest || props.tmean_c > hottest.tmean_c) {
          hottest = props;
        }
      }
      if (props.rain_mm !== null && props.rain_mm !== undefined) {
        rain.push(props.rain_mm);
        if (!wettest || props.rain_mm > wettest.rain_mm) {
          wettest = props;
        }
      }
    });

    const avg = (arr) => (arr.length ? (arr.reduce((a, b) => a + b, 0) / arr.length) : null);
    const avgTemp = avg(temps);
    const avgRain = avg(rain);

    const parts = [
      `<strong>${features.length}</strong> health service areas reported in the last cycle.`
    ];

    if (avgTemp !== null) {
      parts.push(`Average temperature: <strong>${avgTemp.toFixed(1)}°C</strong>.`);
    }
    if (avgRain !== null) {
      parts.push(`Average rainfall: <strong>${avgRain.toFixed(1)} mm</strong>.`);
    }
    if (hottest) {
      parts.push(`Warmest location: <strong>${hottest.name}</strong> (${hottest.tmean_c.toFixed(1)}°C).`);
    }
    if (wettest) {
      parts.push(`Wettest location: <strong>${wettest.name}</strong> (${wettest.rain_mm.toFixed(1)} mm).`);
    }

    summaryEl.innerHTML = `<p>${parts.join(' ')}</p>`;
  };

  fetch(`/api.php?fn=gis-report&metric=${encodeURIComponent(defaultMetric)}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error('Failed to load GIS data');
      }
      return response.json();
    })
    .then((data) => {
      if (!data || !Array.isArray(data.features)) {
        throw new Error('Malformed GIS payload');
      }
      const group = L.featureGroup();

      data.features.forEach((feature) => {
        const { geometry, properties } = feature;
        if (!geometry || geometry.type !== 'Point' || !Array.isArray(geometry.coordinates)) {
          return;
        }
        const [lon, lat] = geometry.coordinates;
        const marker = L.circleMarker([lat, lon], {
          radius: 8,
          color: '#fff',
          weight: 1,
          fillColor: properties?.colour || '#ff7f11',
          fillOpacity: 0.9
        });
        const metricLabel = metricLabels[defaultMetric] || 'Metric';
        const popupHtml = `
          <div class="map-popup">
            <h3>${properties?.name || 'Unknown location'}</h3>
            <p><strong>${metricLabel}:</strong> ${properties?.[defaultMetric] ?? 'n/a'}</p>
            <p><strong>Date:</strong> ${properties?.date_utc || 'n/a'}</p>
            <p><strong>Source:</strong> ${properties?.source || 'n/a'}</p>
          </div>
        `;
        marker.bindPopup(popupHtml);
        marker.addTo(group);
      });

      if (!group.getLayers().length) {
        renderSummary([]);
        return;
      }

      group.addTo(map);
      map.fitBounds(group.getBounds().pad(0.25));
      renderSummary(data.features);
    })
    .catch((err) => {
      if (summaryEl) {
        summaryEl.innerHTML = `<p class="red-text text-darken-2">${err.message}. Preview payload to ensure data is available.</p>`;
      }
    });
});
