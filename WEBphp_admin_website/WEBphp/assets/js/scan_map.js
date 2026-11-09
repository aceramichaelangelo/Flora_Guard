(function () {
  var modal = document.getElementById("scanMapModal");
  var mapEl = document.getElementById("scanMapCanvas");
  var titleEl = document.getElementById("scanMapModalTitle");
  var metaEl = document.getElementById("scanMapModalMeta");
  var detailsEl = document.getElementById("scanMapDetails");
  var closeBtn = document.getElementById("scanMapModalClose");
  var doneBtn = document.getElementById("scanMapModalDone");
  var token = window.TG_MAPBOX_TOKEN || "";

  if (!modal || !mapEl || typeof mapboxgl === "undefined") return;
  if (typeof token !== "string" || token.indexOf("pk.") !== 0) return;

  var map = null;
  var marker = null;

  var detailSections = [
    {
      title: "Identification",
      keys: [
        ["Common name", "title"],
        ["Scientific name", "scientific"],
        ["Species ID", "speciesId"],
        ["AI confidence", "confidence"],
      ],
    },
    {
      title: "Review & conservation",
      keys: [
        ["Scan status", "scanReviewStatus"],
        ["Tree conservation", "conservationStatus"],
        ["Flora conservation", "floraConservation"],
      ],
    },
    {
      title: "Location",
      keys: [
        ["Place name", "location"],
        ["Coordinates", "coordinates"],
        ["Latitude", "latitude"],
        ["Longitude", "longitude"],
      ],
    },
    {
      title: "Capture",
      keys: [
        ["Scanned by", "scannedBy"],
        ["Date & time", "scannedAt"],
        ["Photo uploaded", "hasImage"],
        ["Record ID", "recordId"],
        ["User ID", "userId"],
      ],
    },
    {
      title: "Species profile",
      keys: [
        ["Origin", "origin"],
        ["Distribution", "distribution"],
        ["Habitat", "habitat"],
        ["Climate", "climate"],
      ],
    },
  ];

  function closeMap() {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }

  function ensureMap(lng, lat) {
    mapboxgl.accessToken = token;
    if (!map) {
      map = new mapboxgl.Map({
        container: mapEl,
        style: "mapbox://styles/mapbox/satellite-streets-v12",
        center: [lng, lat],
        zoom: 16,
        attributionControl: true,
      });
      map.addControl(new mapboxgl.NavigationControl({ showCompass: true }), "top-right");
      marker = new mapboxgl.Marker({ color: "#14532d" })
        .setLngLat([lng, lat])
        .addTo(map);
      map.on("load", function () {
        map.resize();
      });
    } else {
      marker.setLngLat([lng, lat]);
      map.flyTo({ center: [lng, lat], zoom: 16, essential: true });
    }
    window.setTimeout(function () {
      if (map) map.resize();
    }, 280);
  }

  function detailValue(data, key) {
    var v = data[key];
    if (v === undefined || v === null || v === "" || v === "—") return null;
    return String(v);
  }

  function renderDetails(data) {
    if (!detailsEl) return;
    detailsEl.innerHTML = "";

    if (data.description) {
      var descBlock = document.createElement("div");
      descBlock.className = "scan-map-details-desc";
      var descTitle = document.createElement("strong");
      descTitle.textContent = "Description";
      var descText = document.createElement("p");
      descText.textContent = data.description;
      descBlock.appendChild(descTitle);
      descBlock.appendChild(descText);
      detailsEl.appendChild(descBlock);
    }

    detailSections.forEach(function (section) {
      var items = section.keys
        .map(function (pair) {
          var val = detailValue(data, pair[1]);
          if (!val) return null;
          return { label: pair[0], value: val };
        })
        .filter(Boolean);

      if (items.length === 0) return;

      var block = document.createElement("div");
      block.className = "scan-map-details-block";

      var heading = document.createElement("h5");
      heading.className = "scan-map-details-block__title";
      heading.textContent = section.title;
      block.appendChild(heading);

      var grid = document.createElement("div");
      grid.className = "scan-map-details-block__grid";

      items.forEach(function (item) {
        var cell = document.createElement("div");
        cell.className = "scan-map-detail-item";
        var lbl = document.createElement("span");
        lbl.className = "scan-map-detail-item__label";
        lbl.textContent = item.label;
        var val = document.createElement("span");
        val.className = "scan-map-detail-item__value";
        val.textContent = item.value;
        cell.appendChild(lbl);
        cell.appendChild(val);
        grid.appendChild(cell);
      });

      block.appendChild(grid);
      detailsEl.appendChild(block);
    });
  }

  function openMap(btn) {
    var lat = parseFloat(btn.getAttribute("data-lat") || "");
    var lng = parseFloat(btn.getAttribute("data-lng") || "");
    if (!isFinite(lat) || !isFinite(lng)) return;

    var data = {};
    try {
      data = JSON.parse(btn.getAttribute("data-scan-detail") || "{}");
    } catch (e) {
      data = {};
    }

    var title = data.title || "Scan";
    var location = data.location || "";
    var officer = data.scannedBy || "";
    var when = data.scannedAt || "";

    if (titleEl) titleEl.textContent = title;
    if (metaEl) {
      var parts = [];
      if (location && location !== "—") parts.push(location);
      if (officer) parts.push("by " + officer);
      if (when && when !== "—") parts.push(when);
      metaEl.textContent = parts.length ? parts.join(" · ") : "Tagged scan location";
    }

    renderDetails(data);

    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";

    ensureMap(lng, lat);
  }

  document.querySelectorAll(".scan-map-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      openMap(btn);
    });
  });

  if (closeBtn) closeBtn.addEventListener("click", closeMap);
  if (doneBtn) doneBtn.addEventListener("click", closeMap);
  modal.addEventListener("click", function (e) {
    if (e.target === modal) closeMap();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && modal.classList.contains("is-open")) closeMap();
  });
})();
