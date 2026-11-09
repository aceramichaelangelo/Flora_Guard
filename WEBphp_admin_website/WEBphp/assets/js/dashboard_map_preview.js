/**
 * Mini Mapbox map on dashboard.php (Location Overview card).
 */
(function () {
  var cfg = window.TG_DASHBOARD_MAP;
  var el = document.getElementById("dashboardMapPreview");
  if (!cfg || !el || typeof mapboxgl === "undefined") return;

  var token = String(cfg.token || "");
  var points = Array.isArray(cfg.points) ? cfg.points : [];

  if (!token || token.indexOf("pk.") !== 0) {
    el.innerHTML =
      '<div class="map-preview-fallback"><p>Invalid Mapbox token.</p></div>';
    return;
  }

  mapboxgl.accessToken = token;

  var defaultCenter = [125.8078, 7.4475];
  var defaultZoom = 8.4;

  var map = new mapboxgl.Map({
    container: el,
    style: "mapbox://styles/mapbox/light-v11",
    center: defaultCenter,
    zoom: defaultZoom,
    attributionControl: false,
    interactive: true,
    scrollZoom: false,
    boxZoom: false,
    dragRotate: false,
    pitchWithRotate: false,
    touchPitch: false,
  });

  map.addControl(
    new mapboxgl.NavigationControl({ showCompass: false }),
    "top-right"
  );

  map.on("load", function () {
    if (points.length === 0) {
      return;
    }

    var bounds = new mapboxgl.LngLatBounds();

    points.forEach(function (p) {
      var lat = Number(p.lat);
      var lng = Number(p.lng);
      if (!isFinite(lat) || !isFinite(lng)) return;

      var markerEl = document.createElement("div");
      markerEl.className = "map-preview-marker";

      new mapboxgl.Marker({ element: markerEl, anchor: "center" })
        .setLngLat([lng, lat])
        .addTo(map);

      bounds.extend([lng, lat]);
    });

    if (!bounds.isEmpty()) {
      map.fitBounds(bounds, {
        padding: { top: 36, bottom: 56, left: 28, right: 28 },
        maxZoom: 13,
        duration: 0,
      });
    }
  });

  map.on("click", function () {
    window.location.href = "map.php";
  });
  el.style.cursor = "pointer";
  el.setAttribute("title", "Open full map");
})();
