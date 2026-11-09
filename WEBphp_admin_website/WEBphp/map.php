<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/barangay.php';

tg_require_login();

$records = tg_attach_barangays(tg_all_tree_records());
$counts = tg_conservation_counts($records);

$mappedPoints = 0;
$speciesSet = [];
$locationSet = [];
foreach ($records as $r) {
    $ll = tg_record_lat_lng($r);
    if ($ll !== null) {
        $mappedPoints++;
    }
    $sp = trim((string)($r['scientific'] ?? ''));
    if ($sp === '' || $sp === '-') {
        $sp = trim((string)($r['title'] ?? ''));
    }
    if ($sp !== '') {
        $speciesSet[$sp] = true;
    }
    $brgy = trim((string)($r['barangay'] ?? ''));
    if ($brgy !== '') {
        $locationSet[$brgy . '|' . trim((string)($r['municipality'] ?? ''))] = true;
    }
}
$talaingodJson = json_encode(TG_TALAINGOD_BARANGAYS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$pageTitle = 'Map Monitoring';
$navActive = 'map';
$mapboxToken = tg_mapbox_access_token();
$hasMapbox = strncmp(trim($mapboxToken), 'pk.', 3) === 0;
$tokenJson = json_encode($mapboxToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$extraScripts = '<link href="assets/vendor/mapbox-gl/mapbox-gl.css" rel="stylesheet">'
    . '<script src="assets/vendor/mapbox-gl/mapbox-gl.js"></script>'
    . '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
    . '<script src="assets/js/chart_theme.js"></script>';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Map Monitoring</h1>
        <p>Species filter, color-coded markers, and basemap styles — aligned with the Flora Guard mobile map.</p>
    </div>
    <div style="display:flex;gap:10px">
        <button type="button" class="btn btn-outline btn-sm" id="btnMapRefresh">Refresh Data</button>
        <button type="button" class="btn btn-outline btn-sm" id="btnMapLayers">Layers</button>
    </div>
</div>

<?php if (!$hasMapbox) : ?>
    <div class="alert alert-error" style="margin-bottom:14px">Mapbox token missing or invalid. Set <code>MAPBOX_ACCESS_TOKEN</code> in <code>config.php</code> (must start with <code>pk.</code>).</div>
<?php endif; ?>

<div class="map-analytics-grid">
    <section class="card map-main-card">
        <div class="map-head-kpis">
            <div class="map-kpi">
                <span class="lbl">Mapped Trees</span>
                <strong id="kpiMapped"><?= (int)$mappedPoints ?></strong>
            </div>
            <div class="map-kpi">
                <span class="lbl">Species</span>
                <strong id="kpiSpecies"><?= count($speciesSet) ?></strong>
            </div>
            <div class="map-kpi">
                <span class="lbl">Barangays</span>
                <strong id="kpiLocations"><?= count($locationSet) ?></strong>
            </div>
            <div class="map-kpi">
                <span class="lbl">Hotspots</span>
                <strong id="kpiHotspots">-</strong>
            </div>
        </div>
        <div class="map-toolbar" id="mapToolbar">
            <div class="map-chip-row" role="group" aria-label="Map style">
                <span class="map-chip-label">Map style</span>
                <button type="button" class="map-chip" data-style="mapbox://styles/mapbox/streets-v12">Standard</button>
                <button type="button" class="map-chip is-active" data-style="mapbox://styles/mapbox/satellite-streets-v12">Satellite</button>
                <button type="button" class="map-chip" data-style="mapbox://styles/mapbox/light-v11">Plain</button>
            </div>
            <div class="map-chip-row" role="group" aria-label="Species filter">
                <span class="map-chip-label">Species</span>
                <button type="button" class="map-chip is-active" data-species="all">All</button>
                <button type="button" class="map-chip" data-species="anahaw">Anahaw</button>
                <button type="button" class="map-chip" data-species="narra">Narra</button>
                <button type="button" class="map-chip" data-species="unknown">Unknown</button>
            </div>
            <div class="map-chip-row" role="group" aria-label="Barangay filter">
                <label class="map-chip-label" for="barangayFilter">Barangay</label>
                <select id="barangayFilter" class="map-select">
                    <option value="all">All barangays</option>
                    <optgroup label="Talaingod">
                        <?php foreach (array_keys(TG_TALAINGOD_BARANGAYS) as $b) : ?>
                            <option value="<?= h($b . '|Talaingod') ?>"><?= h($b) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Outside Talaingod" id="barangayOtherGroup"></optgroup>
                </select>
                <span class="map-filter-result" id="barangayResult" hidden></span>
            </div>
            <div class="map-chip-row" id="mapStyleBar" role="group" aria-label="Layers">
                <span class="map-chip-label">Layers</span>
                <button type="button" class="map-chip is-active" id="viewAll" data-view="all">All layers</button>
                <button type="button" class="map-chip" id="viewHeat" data-view="heat">Heatmap</button>
                <button type="button" class="map-chip" id="viewPoints" data-view="points">Points</button>
                <button type="button" class="map-chip is-active" id="toggleHeat">Heatmap: ON</button>
                <button type="button" class="map-chip is-active" id="toggleClusters">Clusters: ON</button>
            </div>
        </div>
        <div class="map-page-card map-page-card--xl">
            <div id="map"></div>
            <div class="map-legend map-legend--modern">
                <h4>Species</h4>
                <div class="legend-row"><span class="dot" style="background:#1B5E20"></span> Anahaw <span id="legendAnahaw">(0)</span></div>
                <div class="legend-row"><span class="dot" style="background:#1565C0"></span> Narra <span id="legendNarra">(0)</span></div>
                <div class="legend-row"><span class="dot" style="background:#E65100"></span> Unknown <span id="legendUnknown">(0)</span></div>
                <h4 style="margin-top:12px">Tag density (heatmap)</h4>
                <div class="map-heat-gradient" aria-hidden="true"></div>
                <div class="map-heat-labels"><span>Low</span><span>High</span></div>
            </div>
        </div>
    </section>

    <aside class="map-side-panel">
        <div class="card map-side-card">
            <h2>Tags per Barangay</h2>
            <p class="sub" id="barangayBreakdownSub">All species</p>
            <div id="barangayBreakdown" class="map-barangay-list">
                <p style="margin:0;color:var(--muted)">Loading barangays...</p>
            </div>
        </div>
        <div class="card map-side-card">
            <h2>Status Mix</h2>
            <p class="sub">Pie chart from realtime records</p>
            <div class="chart-wrap map-side-chart"><canvas id="mapPie"></canvas></div>
        </div>
        <div class="card map-side-card">
            <h2>Monthly Tags</h2>
            <p class="sub">Recent trend (6 months)</p>
            <div class="chart-wrap map-side-chart"><canvas id="mapTrend"></canvas></div>
        </div>
        <div class="card map-side-card">
            <h2>Top Hotspot Areas</h2>
            <p class="sub">Highest cluster density</p>
            <div id="hotspotList" class="map-hotspot-list">
                <p style="margin:0;color:var(--muted)">Loading hotspot analytics...</p>
            </div>
        </div>
    </aside>
</div>

<script>
(function () {
  var TOKEN = <?= $tokenJson ?>;
  var TALAINGOD = <?= $talaingodJson ?>;
  var mapEl = document.getElementById("map");
  if (!mapEl) return;

  function esc(v) {
    return String(v || "").replace(/[&<>'"]/g, function (ch) {
      return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;" })[ch];
    });
  }

  if (!TOKEN || TOKEN.indexOf("pk.") !== 0) {
    mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;padding:24px;text-align:center;color:#64748b;font:14px system-ui">Set a valid Mapbox public token (pk.) in <strong>config.php</strong>.</div>';
    return;
  }

  function loadScriptSequential(urls, done) {
    if (!urls.length) {
      done(false);
      return;
    }
    var url = urls.shift();
    var s = document.createElement("script");
    s.src = url;
    s.async = false;
    s.onload = function () { done(true); };
    s.onerror = function () { loadScriptSequential(urls, done); };
    document.head.appendChild(s);
  }

  function ensureMapboxCss() {
    if (document.querySelector('link[data-mapbox-local="1"]')) return;
    ["assets/vendor/mapbox-gl/mapbox-gl.css", "./assets/vendor/mapbox-gl/mapbox-gl.css", "/WEBphp/assets/vendor/mapbox-gl/mapbox-gl.css"]
      .forEach(function (href) {
        var l = document.createElement("link");
        l.rel = "stylesheet";
        l.href = href;
        l.setAttribute("data-mapbox-local", "1");
        document.head.appendChild(l);
      });
  }

  function startMapApp() {
    mapboxgl.accessToken = TOKEN;
    var map = new mapboxgl.Map({
    container: "map",
    style: "mapbox://styles/mapbox/satellite-streets-v12",
    center: [125.8078, 7.4475],
    zoom: 8.7,
    pitch: 40,
    antialias: true
  });

  map.addControl(new mapboxgl.NavigationControl({ showCompass: true, showZoom: true, visualizePitch: true }), "top-right");
  map.addControl(new mapboxgl.FullscreenControl(), "top-right");
  map.addControl(new mapboxgl.ScaleControl({ maxWidth: 120, unit: "metric" }), "bottom-right");

  var chartPie = null;
  var chartTrend = null;
  var heatVisible = true;
  var clustersVisible = true;
  var mapViewMode = "all";
  var speciesFilter = "all";
  var barangayFilter = "all";
  var allPoints = [];
  var SPECIES_COLORS = {
    anahaw: "#1B5E20",
    narra: "#1565C0",
    unknown: "#E65100"
  };

  function bucketStatus(status) {
    var s = String(status || "").toLowerCase().trim();
    if (["critical", "critically_endangered", "critically"].indexOf(s) >= 0) return "critical";
    if (s === "endangered") return "endangered";
    return "vulnerable";
  }

  function reviewStatus(rec) {
    var s = String(rec.scanStatus || rec.reviewStatus || "").toLowerCase().trim();
    if (s === "rejected") return "reclassified";
    if (s === "pending" || s === "approved" || s === "reclassified") return s;
    return "approved";
  }

  /** Match mobile app species filter keys. */
  function speciesKeyFromRecord(rec) {
    var sid = String(rec.speciesId || "").toLowerCase();
    var title = String(rec.title || "").toLowerCase();
    var sci = String(rec.scientific || "").toLowerCase();
    var hay = sid + " " + title + " " + sci;
    if (hay.indexOf("anahaw") >= 0 || hay.indexOf("saribus") >= 0) return "anahaw";
    if (hay.indexOf("narra") >= 0 || hay.indexOf("pterocarpus") >= 0) return "narra";
    if (hay.indexOf("unknown") >= 0) return "unknown";
    if (sid === "0" || sid === "anahaw") return "anahaw";
    if (sid === "1" || sid === "narra") return "narra";
    if (sid === "2") return "unknown";
    return "unknown";
  }

  function speciesLabel(key) {
    if (key === "anahaw") return "Anahaw";
    if (key === "narra") return "Narra";
    return "Unknown";
  }

  /** Barangay names repeat across towns (e.g. Santo Niño), so key on both. */
  function barangayKey(p) {
    return p.barangay ? p.barangay + "|" + (p.municipality || "") : "";
  }

  function barangayLabel(key) {
    var parts = String(key).split("|");
    return parts[1] && parts[1] !== "Talaingod" ? parts[0] + " (" + parts[1] + ")" : parts[0];
  }

  function parsePoint(rec) {
    var lat = Number(rec.latitude);
    var lng = Number(rec.longitude);
    if (isFinite(lat) && isFinite(lng) && !(Math.abs(lat) < 0.0001 && Math.abs(lng) < 0.0001)) {
      return { lat: lat, lng: lng };
    }
    var raw = String(rec.coordinates || "");
    if (!raw || raw === "-") return null;
    var parts = raw.trim().split(/[,\s]+/);
    if (parts.length < 2) return null;
    lat = Number(parts[0]);
    lng = Number(parts[1]);
    if (!isFinite(lat) || !isFinite(lng)) return null;
    if (Math.abs(lat) > 90 && Math.abs(lng) <= 90) {
      var tmp = lat; lat = lng; lng = tmp;
    }
    if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return null;
    return { lat: lat, lng: lng };
  }

  /** Show all tagged scans on the map (not only approved). */
  function isMappableRecord(rec) {
    var st = reviewStatus(rec);
    return st === "approved" || st === "pending" || st === "reclassified";
  }

  function groupHotspots(points) {
    var cell = 0.01;
    var bins = Object.create(null);
    points.forEach(function (p) {
      var gx = Math.floor(p.lat / cell);
      var gy = Math.floor(p.lng / cell);
      var key = gx + ":" + gy;
      if (!bins[key]) bins[key] = [];
      bins[key].push(p);
    });
    var out = Object.keys(bins).map(function (k) {
      var arr = bins[k];
      var lat = 0, lng = 0;
      arr.forEach(function (p) { lat += p.lat; lng += p.lng; });
      return { lat: lat / arr.length, lng: lng / arr.length, count: arr.length };
    });
    out.sort(function (a, b) { return b.count - a.count; });
    return out;
  }

  function buildTrend(records) {
    var now = new Date();
    var months = [];
    for (var i = 5; i >= 0; i--) {
      var d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      var key = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
      months.push({ key: key, label: d.toLocaleString(undefined, { month: "short" }), count: 0 });
    }
    var idx = {};
    months.forEach(function (m, i) { idx[m.key] = i; });
    records.forEach(function (r) {
      var ms = Number(r.createdAt || 0);
      if (!isFinite(ms) || ms <= 0) return;
      var d = new Date(ms);
      var key = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
      if (idx[key] !== undefined) months[idx[key]].count++;
    });
    return months;
  }

  function upsertCharts(counts, trendMonths) {
    var pieCtx = document.getElementById("mapPie");
    var trendCtx = document.getElementById("mapTrend");
    if (!pieCtx || !trendCtx || !window.Chart) return;

    var pieData = {
      labels: ["Critical", "Endangered", "Vulnerable"],
      datasets: [{
        data: [counts.critical, counts.endangered, counts.vulnerable],
        backgroundColor: ["#dc2626", "#ea580c", "#ca8a04"]
      }]
    };
    var trendData = {
      labels: trendMonths.map(function (m) { return m.label; }),
      datasets: [{
        label: "Tags",
        data: trendMonths.map(function (m) { return m.count; }),
        backgroundColor: "rgba(20,83,45,.2)",
        borderColor: "#14532d",
        borderWidth: 2,
        fill: true,
        tension: 0.35
      }]
    };

    if (chartPie) {
      chartPie.data = pieData;
      chartPie.update();
    } else {
      chartPie = new Chart(pieCtx, {
        type: "pie",
        data: pieData,
        options: { responsive: true, maintainAspectRatio: false }
      });
    }
    if (chartTrend) {
      chartTrend.data = trendData;
      chartTrend.update();
    } else {
      chartTrend = new Chart(trendCtx, {
        type: "line",
        data: trendData,
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
      });
    }
  }

  function setLegend(speciesCounts) {
    var a = document.getElementById("legendAnahaw");
    var n = document.getElementById("legendNarra");
    var u = document.getElementById("legendUnknown");
    if (a) a.textContent = "(" + (speciesCounts.anahaw || 0) + ")";
    if (n) n.textContent = "(" + (speciesCounts.narra || 0) + ")";
    if (u) u.textContent = "(" + (speciesCounts.unknown || 0) + ")";
  }

  function setKpis(metrics) {
    var mapped = document.getElementById("kpiMapped");
    var species = document.getElementById("kpiSpecies");
    var locations = document.getElementById("kpiLocations");
    var hotspots = document.getElementById("kpiHotspots");
    if (mapped) mapped.textContent = String(metrics.mapped);
    if (species) species.textContent = String(metrics.species);
    if (locations) locations.textContent = String(metrics.locations);
    if (hotspots) hotspots.textContent = String(metrics.hotspots);
  }

  function setHotspotList(items) {
    var el = document.getElementById("hotspotList");
    if (!el) return;
    if (!items.length) {
      el.innerHTML = '<p style="margin:0;color:var(--muted)">No hotspot clusters yet.</p>';
      return;
    }
    el.innerHTML = items.slice(0, 6).map(function (h, idx) {
      return '<div class="hotspot-item"><span class="hotspot-rank">#' + (idx + 1) + '</span><span class="hotspot-coord">' + h.lat.toFixed(4) + ', ' + h.lng.toFixed(4) + '</span><span class="hotspot-count">' + h.count + ' tags</span></div>';
    }).join("");
  }

  var heatPaint = {
    "heatmap-weight": [
      "interpolate", ["linear"], ["get", "weight"],
      0, 0.4,
      1, 1
    ],
    "heatmap-intensity": [
      "interpolate", ["linear"], ["zoom"],
      7, 0.6,
      10, 1.1,
      14, 1.6
    ],
    "heatmap-radius": [
      "interpolate", ["linear"], ["zoom"],
      7, 18,
      10, 28,
      14, 42
    ],
    "heatmap-opacity": [
      "interpolate", ["linear"], ["zoom"],
      7, 0.75,
      14, 0.55
    ],
    "heatmap-color": [
      "interpolate", ["linear"], ["heatmap-density"],
      0, "rgba(34,197,94,0)",
      0.15, "rgba(74,222,128,0.45)",
      0.35, "rgba(250,204,21,0.55)",
      0.55, "rgba(249,115,22,0.7)",
      0.75, "rgba(239,68,68,0.82)",
      1, "rgba(185,28,28,0.95)"
    ]
  };

  function buildGeoJson(points) {
    return {
      type: "FeatureCollection",
      features: points.map(function (p) {
        return {
          type: "Feature",
          properties: {
            title: p.title || "Tree",
            scientific: p.scientific || "-",
            locationName: p.locationName || "-",
            barangay: p.barangay ? barangayLabel(barangayKey(p)) : "Resolving…",
            status: p.bucket,
            species: p.species || "unknown",
            speciesLabel: speciesLabel(p.species || "unknown"),
            weight: 1
          },
          geometry: { type: "Point", coordinates: [p.lng, p.lat] }
        };
      })
    };
  }

  function addHeatLayer() {
    if (map.getLayer("heat")) return;
    map.addLayer({
      id: "heat",
      type: "heatmap",
      source: "trees-heat",
      maxzoom: 15,
      paint: heatPaint
    });
  }

  function addPointLayers() {
    if (map.getLayer("clusters")) return;

    map.addLayer({
      id: "clusters",
      type: "circle",
      source: "trees-points",
      filter: ["has", "point_count"],
      paint: {
        "circle-color": "#14532d",
        "circle-stroke-color": "#ffffff",
        "circle-stroke-width": 2,
        "circle-radius": [
          "step", ["get", "point_count"],
          14, 10, 18, 25, 22, 50, 28
        ]
      }
    });
    map.addLayer({
      id: "cluster-count",
      type: "symbol",
      source: "trees-points",
      filter: ["has", "point_count"],
      layout: { "text-field": ["get", "point_count_abbreviated"], "text-size": 12 },
      paint: { "text-color": "#fff" }
    });

    map.addLayer({
      id: "unclustered-glow",
      type: "circle",
      source: "trees-points",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-color": [
          "match", ["get", "species"],
          "anahaw", SPECIES_COLORS.anahaw,
          "narra", SPECIES_COLORS.narra,
          SPECIES_COLORS.unknown
        ],
        "circle-radius": 14,
        "circle-blur": 0.85,
        "circle-opacity": 0.28
      }
    });

    map.addLayer({
      id: "unclustered",
      type: "circle",
      source: "trees-points",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-color": [
          "match", ["get", "species"],
          "anahaw", SPECIES_COLORS.anahaw,
          "narra", SPECIES_COLORS.narra,
          SPECIES_COLORS.unknown
        ],
        "circle-stroke-color": "#ffffff",
        "circle-stroke-width": 1.5,
        "circle-radius": 6.5
      }
    });

    map.addLayer({
      id: "unclustered-ring",
      type: "circle",
      source: "trees-points",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-color": "rgba(0,0,0,0)",
        "circle-stroke-color": [
          "match", ["get", "species"],
          "anahaw", "#B9FFD7",
          "narra", "#BBDEFB",
          "#FFCC80"
        ],
        "circle-stroke-width": 2,
        "circle-radius": 10,
        "circle-opacity": 0.9
      }
    });

    map.on("click", "clusters", function (e) {
      var f = e.features && e.features[0];
      if (!f) return;
      var clusterId = f.properties.cluster_id;
      map.getSource("trees-points").getClusterExpansionZoom(clusterId, function (err, zoom) {
        if (err) return;
        map.easeTo({ center: f.geometry.coordinates, zoom: zoom + 0.3, duration: 500 });
      });
    });

    map.on("click", "unclustered", function (e) {
      var f = e.features && e.features[0];
      if (!f) return;
      var p = f.properties || {};
      new mapboxgl.Popup({ closeButton: true, maxWidth: 280 })
        .setLngLat(f.geometry.coordinates)
        .setHTML(
          '<div style="font-size:13px;line-height:1.45">' +
          '<strong>' + esc(p.title) + '</strong><br>' +
          '<span style="color:#475569"><i>' + esc(p.scientific) + '</i></span><br>' +
          '<span style="color:#64748b">Species: ' + esc(p.speciesLabel || p.species) + '</span><br>' +
          '<span style="color:#64748b">Barangay: ' + esc(p.barangay) + '</span><br>' +
          '<span style="color:#64748b">Source: ' + esc(p.locationName) + '</span>' +
          '</div>'
        )
        .addTo(map);
    });

    map.on("mouseenter", "clusters", function () { map.getCanvas().style.cursor = "pointer"; });
    map.on("mouseleave", "clusters", function () { map.getCanvas().style.cursor = ""; });
    map.on("mouseenter", "unclustered", function () { map.getCanvas().style.cursor = "pointer"; });
    map.on("mouseleave", "unclustered", function () { map.getCanvas().style.cursor = ""; });
  }

  function speciesMatches(p) {
    return speciesFilter === "all" || p.species === speciesFilter;
  }

  function filteredPoints() {
    return allPoints.filter(function (p) {
      return speciesMatches(p) && (barangayFilter === "all" || barangayKey(p) === barangayFilter);
    });
  }

  function syncBarangayOptions() {
    var group = document.getElementById("barangayOtherGroup");
    if (!group) return;
    var seen = Object.create(null);
    allPoints.forEach(function (p) {
      var k = barangayKey(p);
      if (k && k.split("|")[1] !== "Talaingod") seen[k] = true;
    });
    var keys = Object.keys(seen).sort(function (a, b) { return barangayLabel(a).localeCompare(barangayLabel(b)); });
    var current = Array.prototype.map.call(group.children, function (o) { return o.value; }).join("\n");
    if (current === keys.join("\n")) return;
    group.innerHTML = "";
    keys.forEach(function (k) {
      var o = document.createElement("option");
      o.value = k;
      o.textContent = barangayLabel(k);
      group.appendChild(o);
    });
    group.hidden = keys.length === 0;
    var sel = document.getElementById("barangayFilter");
    if (sel && sel.value !== barangayFilter) sel.value = barangayFilter;
  }

  function renderBarangayBreakdown() {
    var el = document.getElementById("barangayBreakdown");
    var sub = document.getElementById("barangayBreakdownSub");
    if (!el) return;
    if (sub) sub.textContent = (speciesFilter === "all" ? "All species" : speciesLabel(speciesFilter)) + " · tagged trees";
    var tally = Object.create(null);
    Object.keys(TALAINGOD).forEach(function (b) { tally[b + "|Talaingod"] = 0; });
    allPoints.forEach(function (p) {
      var k = barangayKey(p);
      if (!k || !speciesMatches(p)) return;
      tally[k] = (tally[k] || 0) + 1;
    });
    if (barangayFilter !== "all" && tally[barangayFilter] === undefined) tally[barangayFilter] = 0;
    var talaingod = Object.keys(TALAINGOD).map(function (b) { return b + "|Talaingod"; });
    var others = Object.keys(tally)
      .filter(function (k) { return talaingod.indexOf(k) < 0; })
      .sort(function (a, b) { return tally[b] - tally[a]; });

    function row(k) {
      var active = barangayFilter === k ? " is-active" : "";
      return '<button type="button" class="barangay-row' + active + '" data-barangay="' + esc(k) + '">' +
        '<span class="barangay-row__name">' + esc(barangayLabel(k)) + '</span>' +
        '<span class="barangay-row__count">' + tally[k] + '</span></button>';
    }
    el.innerHTML =
      '<div class="barangay-group-label">Talaingod</div>' + talaingod.map(row).join("") +
      (others.length ? '<div class="barangay-group-label">Outside Talaingod</div>' + others.map(row).join("") : "");
  }

  function renderBarangayResult(count) {
    var el = document.getElementById("barangayResult");
    if (!el) return;
    if (barangayFilter === "all") {
      el.hidden = true;
      return;
    }
    var what = speciesFilter === "all" ? "tree" + (count === 1 ? "" : "s") : speciesLabel(speciesFilter);
    el.textContent = count + " " + what + " tagged in " + barangayLabel(barangayFilter);
    el.hidden = false;
  }

  function setBarangayFilter(key) {
    barangayFilter = key || "all";
    var sel = document.getElementById("barangayFilter");
    if (sel) sel.value = barangayFilter;
    applySpeciesFilter(true);
  }

  function applySpeciesFilter(fitBounds) {
    var points = filteredPoints();
    syncBarangayOptions();
    renderBarangayBreakdown();
    renderBarangayResult(points.length);
    var speciesCounts = { anahaw: 0, narra: 0, unknown: 0 };
    allPoints.forEach(function (p) {
      if (speciesCounts[p.species] !== undefined) speciesCounts[p.species] += 1;
      else speciesCounts.unknown += 1;
    });
    setLegend(speciesCounts);
    var hotspots = groupHotspots(points);
    setHotspotList(hotspots);
    setKpis({
      mapped: points.length,
      species: Object.keys(allPoints.reduce(function (acc, p) {
        acc[p.species] = true;
        return acc;
      }, Object.create(null))).length,
      locations: Object.keys(allPoints.reduce(function (acc, p) {
        var k = barangayKey(p);
        if (k) acc[k] = true;
        return acc;
      }, Object.create(null))).length,
      hotspots: hotspots.length
    });
    rebuildMapLayers(points);
    if (!fitBounds) return;
    if (points.length > 0) {
      var b = new mapboxgl.LngLatBounds();
      points.forEach(function (p) { b.extend([p.lng, p.lat]); });
      map.fitBounds(b, { padding: 90, maxZoom: 14, duration: 800 });
    } else {
      var bbox = TALAINGOD[String(barangayFilter).split("|")[0]];
      if (bbox && String(barangayFilter).split("|")[1] === "Talaingod") {
        map.fitBounds([[bbox[0], bbox[1]], [bbox[2], bbox[3]]], { padding: 40, duration: 800 });
      }
    }
  }

  function rebuildMapLayers(points) {
    var data = buildGeoJson(points);

    if (map.getSource("trees-heat")) {
      map.getSource("trees-heat").setData(data);
      map.getSource("trees-points").setData(data);
      updateLayerVisibility();
      return;
    }

    map.addSource("trees-heat", { type: "geojson", data: data });
    map.addSource("trees-points", {
      type: "geojson",
      data: data,
      cluster: true,
      clusterMaxZoom: 14,
      clusterRadius: 42
    });

    addHeatLayer();
    addPointLayers();
    updateLayerVisibility();
  }

  function setViewMode(mode) {
    mapViewMode = mode;
    if (mode === "heat") {
      heatVisible = true;
      clustersVisible = false;
    } else if (mode === "points") {
      heatVisible = false;
      clustersVisible = true;
    } else {
      heatVisible = true;
      clustersVisible = true;
    }
    var styleBar = document.getElementById("mapStyleBar");
    if (styleBar) {
      styleBar.querySelectorAll("button[data-view]").forEach(function (b) {
        b.classList.toggle("is-active", b.getAttribute("data-view") === mode);
      });
    }
    var toggleHeat = document.getElementById("toggleHeat");
    var toggleClusters = document.getElementById("toggleClusters");
    if (toggleHeat) toggleHeat.textContent = "Heatmap: " + (heatVisible ? "ON" : "OFF");
    if (toggleClusters) toggleClusters.textContent = "Clusters: " + (clustersVisible ? "ON" : "OFF");
    if (toggleHeat) toggleHeat.classList.toggle("is-active", heatVisible);
    if (toggleClusters) toggleClusters.classList.toggle("is-active", clustersVisible);
    updateLayerVisibility();
  }

  function updateLayerVisibility() {
    if (map.getLayer("heat")) {
      map.setLayoutProperty("heat", "visibility", heatVisible ? "visible" : "none");
    }
    var clusterVis = clustersVisible ? "visible" : "none";
    ["clusters", "cluster-count", "unclustered", "unclustered-glow", "unclustered-ring"].forEach(function (id) {
      if (map.getLayer(id)) map.setLayoutProperty(id, "visibility", clusterVis);
    });
  }

  function refreshData(fitBounds) {
    fetch("api/tree_records.php?_t=" + Date.now())
      .then(function (r) { return r.json(); })
      .then(function (json) {
        var records = Array.isArray(json.records) ? json.records : [];
        var mapRecords = records.filter(isMappableRecord);
        var points = [];
        var speciesSet = Object.create(null);
        var locationSet = Object.create(null);
        var counts = { critical: 0, endangered: 0, vulnerable: 0 };
        mapRecords.forEach(function (rec) {
          var pt = parsePoint(rec);
          var bucket = bucketStatus(rec.status || "");
          counts[bucket] += 1;
          var species = speciesKeyFromRecord(rec);
          var sp = (rec.scientific && rec.scientific !== "-") ? String(rec.scientific) : String(rec.title || "");
          if (sp) speciesSet[sp] = true;
          var loc = String(rec.locationName || "").trim();
          if (loc && loc !== "-") locationSet[loc] = true;
          if (!pt) return;
          points.push({
            lat: pt.lat, lng: pt.lng,
            title: String(rec.title || "Tree"),
            scientific: String(rec.scientific || "-"),
            locationName: String(rec.locationName || "-"),
            barangay: String(rec.barangay || ""),
            municipality: String(rec.municipality || ""),
            bucket: bucket,
            species: species,
            createdAt: Number(rec.createdAt || 0)
          });
        });

        allPoints = points;
        var visible = filteredPoints();
        var hotspots = groupHotspots(visible);
        setHotspotList(hotspots);
        upsertCharts(counts, buildTrend(mapRecords));
        applySpeciesFilter(!!fitBounds);
      })
      .catch(function () {
        // keep last rendered state
      });
  }

  map.on("load", function () {
    refreshData(true);
  });

  setInterval(function () {
    refreshData(false);
  }, 12000);

  var toolbar = document.getElementById("mapToolbar");
  var styleBar = document.getElementById("mapStyleBar");
  if (toolbar) {
    toolbar.querySelectorAll("button[data-style]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var style = this.getAttribute("data-style");
        if (!style) return;
        toolbar.querySelectorAll("button[data-style]").forEach(function (b) { b.classList.remove("is-active"); });
        this.classList.add("is-active");
        map.setStyle(style);
        map.once("style.load", function () {
          refreshData(false);
        });
      });
    });
    toolbar.querySelectorAll("button[data-species]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        speciesFilter = this.getAttribute("data-species") || "all";
        toolbar.querySelectorAll("button[data-species]").forEach(function (b) { b.classList.remove("is-active"); });
        this.classList.add("is-active");
        applySpeciesFilter(true);
      });
    });
  }

  var barangaySelect = document.getElementById("barangayFilter");
  if (barangaySelect) {
    barangaySelect.addEventListener("change", function () { setBarangayFilter(this.value); });
  }
  var barangayList = document.getElementById("barangayBreakdown");
  if (barangayList) {
    barangayList.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-barangay]");
      if (!btn) return;
      var key = btn.getAttribute("data-barangay");
      setBarangayFilter(barangayFilter === key ? "all" : key);
    });
  }

  var toggleHeat = document.getElementById("toggleHeat");
  if (toggleHeat) {
    toggleHeat.addEventListener("click", function () {
      heatVisible = !heatVisible;
      this.textContent = "Heatmap: " + (heatVisible ? "ON" : "OFF");
      this.classList.toggle("is-active", heatVisible);
      syncViewModeButtons();
      updateLayerVisibility();
    });
  }
  var toggleClusters = document.getElementById("toggleClusters");
  if (toggleClusters) {
    toggleClusters.addEventListener("click", function () {
      clustersVisible = !clustersVisible;
      this.textContent = "Clusters: " + (clustersVisible ? "ON" : "OFF");
      this.classList.toggle("is-active", clustersVisible);
      syncViewModeButtons();
      updateLayerVisibility();
    });
  }

  function syncViewModeButtons() {
    var mode = "all";
    if (heatVisible && !clustersVisible) mode = "heat";
    else if (!heatVisible && clustersVisible) mode = "points";
    if (styleBar) {
      styleBar.querySelectorAll("button[data-view]").forEach(function (b) {
        b.classList.toggle("is-active", b.getAttribute("data-view") === mode);
      });
    }
  }

  ["viewAll", "viewHeat", "viewPoints"].forEach(function (id) {
    var btn = document.getElementById(id);
    if (!btn) return;
    btn.addEventListener("click", function () {
      setViewMode(this.getAttribute("data-view") || "all");
    });
  });
  var btnLayers = document.getElementById("btnMapLayers");
  if (btnLayers && toolbar) {
    btnLayers.addEventListener("click", function () {
      toolbar.classList.toggle("is-collapsed");
    });
  }
    var btnRefresh = document.getElementById("btnMapRefresh");
    if (btnRefresh) {
      btnRefresh.addEventListener("click", function () {
        refreshData(false);
      });
    }
  }

  if (typeof window.mapboxgl !== "undefined") {
    startMapApp();
  } else {
    ensureMapboxCss();
    loadScriptSequential(
      [
        "assets/vendor/mapbox-gl/mapbox-gl.js",
        "./assets/vendor/mapbox-gl/mapbox-gl.js",
        "/WEBphp/assets/vendor/mapbox-gl/mapbox-gl.js",
      ],
      function (ok) {
        if (!ok || typeof window.mapboxgl === "undefined") {
          mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;padding:24px;text-align:center;color:#64748b;font:14px system-ui">Mapbox library failed to load from local assets. Open <code>WEBphp/assets/vendor/mapbox-gl/</code> and verify files exist, then refresh.</div>';
          return;
        }
        startMapApp();
      }
    );
  }
})();
</script>

<?php
require __DIR__ . '/includes/layout_end.php';
