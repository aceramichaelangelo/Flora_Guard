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

  function parseUserRow(btn) {
    var tr = btn.closest("tr[data-user-row]");
    if (!tr) return null;
    try {
      return JSON.parse(tr.getAttribute("data-user-row") || "{}");
    } catch (e) {
      return null;
    }
  }

  function summaryItem(label, value) {
    var item = document.createElement("div");
    item.className = "modal-summary-item";
    item.innerHTML = "<strong>" + label + "</strong><span></span>";
    item.querySelector("span").textContent = value || "—";
    return item;
  }

  var viewModal = document.getElementById("userViewModal");
  var editModal = document.getElementById("userEditModal");
  var deleteModal = document.getElementById("userDeleteModal");
  if (!viewModal && !editModal && !deleteModal) return;

  var viewTitle = document.getElementById("userViewTitle");
  var viewEmail = document.getElementById("userViewEmail");
  var viewSummary = document.getElementById("userViewSummary");
  var viewClose = document.getElementById("userViewClose");

  var editSubtitle = document.getElementById("userEditSubtitle");
  var editUid = document.getElementById("userEditUid");
  var editName = document.getElementById("userEditName");
  var editRole = document.getElementById("userEditRole");
  var editCancel = document.getElementById("userEditCancel");

  var deleteSubtitle = document.getElementById("userDeleteSubtitle");
  var deleteUid = document.getElementById("userDeleteUid");
  var deleteCancel = document.getElementById("userDeleteCancel");

  document.querySelectorAll(".user-btn-view").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var row = parseUserRow(btn);
      if (!row || !viewModal) return;
      if (viewTitle) viewTitle.textContent = row.fullName || "User";
      if (viewEmail) viewEmail.textContent = row.email || "—";
      if (viewSummary) {
        viewSummary.innerHTML = "";
        viewSummary.appendChild(summaryItem("Role", row.roleLabel));
        viewSummary.appendChild(summaryItem("Joined", row.joined));
        viewSummary.appendChild(summaryItem("User ID", row.uid));
      }
      openOverlay(viewModal);
    });
  });

  if (viewClose) viewClose.addEventListener("click", function () { closeOverlay(viewModal); });
  if (viewModal) {
    viewModal.addEventListener("click", function (e) {
      if (e.target === viewModal) closeOverlay(viewModal);
    });
  }

  document.querySelectorAll(".user-btn-edit").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var row = parseUserRow(btn);
      if (!row || !editModal) return;
      if (editSubtitle) {
        editSubtitle.textContent = (row.fullName || "User") + " · " + (row.email || "");
      }
      if (editUid) editUid.value = row.uid || "";
      if (editName) editName.value = row.fullName || "";
      if (editRole) {
        var r = (row.role || "user").toLowerCase();
        if (r === "field" || r === "field_officer" || r === "officer") r = "user";
        editRole.value = r === "admin" || r === "viewer" ? r : "user";
      }
      openOverlay(editModal);
    });
  });

  if (editCancel) editCancel.addEventListener("click", function () { closeOverlay(editModal); });
  if (editModal) {
    editModal.addEventListener("click", function (e) {
      if (e.target === editModal) closeOverlay(editModal);
    });
  }

  document.querySelectorAll(".user-btn-delete").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (btn.disabled) return;
      var row = parseUserRow(btn);
      if (!row || !deleteModal) return;
      if (deleteSubtitle) {
        deleteSubtitle.textContent =
          'Remove "' + (row.fullName || "this user") + '" from the database?';
      }
      if (deleteUid) deleteUid.value = row.uid || "";
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
    else if (editModal && editModal.classList.contains("is-open")) closeOverlay(editModal);
    else if (deleteModal && deleteModal.classList.contains("is-open")) closeOverlay(deleteModal);
  });
})();
