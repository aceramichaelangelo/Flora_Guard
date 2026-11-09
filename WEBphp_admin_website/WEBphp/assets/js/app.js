(function () {
  var btn = document.getElementById("sidebarToggle");
  var side = document.getElementById("sidebar");
  if (!btn || !side) return;
  btn.addEventListener("click", function () {
    side.classList.toggle("is-open");
  });
  side.querySelectorAll("a").forEach(function (a) {
    a.addEventListener("click", function () {
      if (window.matchMedia("(max-width: 900px)").matches) {
        side.classList.remove("is-open");
      }
    });
  });
})();




(function () {
  var root = document.querySelector("[data-dd]");
  if (!root) return;
  var btn = root.querySelector("[data-dd-btn]");
  var menu = root.querySelector("[data-dd-menu]");
  if (!btn || !menu) return;

  function close() {
    root.classList.remove("is-open");
    btn.setAttribute("aria-expanded", "false");
  }
  function open() {
    root.classList.add("is-open");
    btn.setAttribute("aria-expanded", "true");
  }
  function toggle() {
    if (root.classList.contains("is-open")) close();
    else open();
  }

  btn.addEventListener("click", function (e) {
    e.preventDefault();
    toggle();
  });

  document.addEventListener("click", function (e) {
    if (!root.contains(e.target)) close();
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") close();
  });
})();

(function () {
  var modal = document.getElementById("scanImageModal");
  var img = document.getElementById("scanImageModalImg");
  var title = document.getElementById("scanImageModalTitle");
  var closeBtn = document.getElementById("scanImageModalClose");
  var doneBtn = document.getElementById("scanImageModalDone");
  var openLink = document.getElementById("scanImageModalOpen");
  if (!modal || !img) return;

  function openPreview(src, label) {
    img.src = src;
    img.alt = label || "Captured scan";
    if (title) title.textContent = label || "Captured scan";
    if (openLink) {
      openLink.href = src;
      openLink.style.display = src ? "" : "none";
    }
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function closePreview() {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
    img.removeAttribute("src");
    img.classList.remove("scan-image-modal__img--zoomed");
    if (openLink) openLink.removeAttribute("href");
    document.body.style.overflow = "";
  }

  document.querySelectorAll("[data-scan-image]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var src = btn.getAttribute("data-scan-image");
      var label = btn.getAttribute("data-scan-title") || "Captured scan";
      if (src) openPreview(src, label);
    });
  });

  document.querySelectorAll("img[data-scan-thumb]").forEach(function (thumb) {
    thumb.addEventListener("error", function () {
      thumb.classList.add("scan-thumb-img--error");
      var wrap = thumb.closest(".scan-card__media");
      if (wrap) {
        var hint = wrap.querySelector(".scan-thumb-hint");
        if (hint) {
          hint.textContent = "Image failed to load (check Storage rules)";
          hint.classList.add("scan-thumb-hint--muted");
        }
      }
    });
  });

  img.addEventListener("click", function () {
    img.classList.toggle("scan-image-modal__img--zoomed");
  });

  if (closeBtn) closeBtn.addEventListener("click", closePreview);
  if (doneBtn) doneBtn.addEventListener("click", closePreview);
  modal.addEventListener("click", function (e) {
    if (e.target === modal) closePreview();
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && modal.classList.contains("is-open")) closePreview();
  });

  window.tgOpenImagePreview = openPreview;
})();

(function () {
  function openOverlay(el) {
    if (!el) return;
    el.classList.add("is-open");
    el.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }
  function closeOverlay(el) {
    if (!el) return;
    el.classList.remove("is-open");
    el.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
  }
  function parseTreeRow(btn) {
    var tr = btn.closest("tr[data-tree-row]");
    if (!tr) return null;
    try {
      return JSON.parse(tr.getAttribute("data-tree-row") || "{}");
    } catch (e) {
      return null;
    }
  }

  var viewModal = document.getElementById("treeViewModal");
  var deleteModal = document.getElementById("treeDeleteModal");
  if (!viewModal && !deleteModal) return;

  var viewTitle = document.getElementById("treeViewTitle");
  var viewSci = document.getElementById("treeViewScientific");
  var viewSummary = document.getElementById("treeViewSummary");
  var viewImageWrap = document.getElementById("treeViewImageWrap");
  var viewImage = document.getElementById("treeViewImage");
  var viewImageBtn = document.getElementById("treeViewImageBtn");
  var viewScansLink = document.getElementById("treeViewScansLink");
  var viewClose = document.getElementById("treeViewClose");

  var deleteSubtitle = document.getElementById("treeDeleteSubtitle");
  var deleteUid = document.getElementById("treeDeleteUid");
  var deleteRid = document.getElementById("treeDeleteRid");
  var deleteCancel = document.getElementById("treeDeleteCancel");

  function summaryItem(label, value) {
    var item = document.createElement("div");
    item.className = "modal-summary-item";
    item.innerHTML = "<strong>" + label + "</strong><span></span>";
    item.querySelector("span").textContent = value || "—";
    return item;
  }

  document.querySelectorAll(".tree-btn-view").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var row = parseTreeRow(btn);
      if (!row || !viewModal) return;
      if (viewTitle) viewTitle.textContent = row.title || "Tree";
      if (viewSci) viewSci.innerHTML = "<em>" + (row.scientific || "—") + "</em>";
      if (viewSummary) {
        viewSummary.innerHTML = "";
        viewSummary.appendChild(summaryItem("Status", row.statusLabel));
        viewSummary.appendChild(summaryItem("Location", row.location));
        viewSummary.appendChild(summaryItem("Coordinates", row.coords));
        viewSummary.appendChild(summaryItem("Scanned by", row.officer));
        viewSummary.appendChild(summaryItem("Date", row.when));
        viewSummary.appendChild(
          summaryItem("Scan review", row.scanStatusLabel || row.scanStatus)
        );
        if (row.confidence > 0) {
          viewSummary.appendChild(
            summaryItem("Confidence", Math.round(row.confidence) + "%")
          );
        }
      }
      if (viewImageWrap && viewImage) {
        if (row.imageUrl) {
          viewImage.src = row.imageUrl;
          viewImage.alt = row.title || "Tree photo";
          viewImageWrap.hidden = false;
        } else {
          viewImage.removeAttribute("src");
          viewImageWrap.hidden = true;
        }
      }
      openOverlay(viewModal);
    });
  });

  if (viewImageBtn && viewImage) {
    viewImageBtn.addEventListener("click", function () {
      var src = viewImage.getAttribute("src");
      if (src && window.tgOpenImagePreview) {
        window.tgOpenImagePreview(src, viewTitle ? viewTitle.textContent : "Tree photo");
      }
    });
  }
  if (viewClose) viewClose.addEventListener("click", function () { closeOverlay(viewModal); });
  if (viewModal) {
    viewModal.addEventListener("click", function (e) {
      if (e.target === viewModal) closeOverlay(viewModal);
    });
  }

  document.querySelectorAll(".tree-btn-delete").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var row = parseTreeRow(btn);
      if (!row || !deleteModal) return;
      if (deleteSubtitle) {
        deleteSubtitle.textContent =
          "Delete \"" + (row.title || "this tree") + "\"? This cannot be undone.";
      }
      if (deleteUid) deleteUid.value = row.uid || "";
      if (deleteRid) deleteRid.value = row.id || "";
      openOverlay(deleteModal);
    });
  });
  if (deleteCancel) deleteCancel.addEventListener("click", function () { closeOverlay(deleteModal); });
  if (deleteModal) {
    deleteModal.addEventListener("click", function (e) {
      if (e.target === deleteModal) closeOverlay(deleteModal);
    });
  }

  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    if (viewModal && viewModal.classList.contains("is-open")) closeOverlay(viewModal);
    else if (deleteModal && deleteModal.classList.contains("is-open")) closeOverlay(deleteModal);
  });
})();
