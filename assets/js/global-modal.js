// GLOBAL MODAL SYSTEM
(function () {
  // Prevent duplicate injection
  if (window.showModal) return;

  // Inject modal HTML once
  const modalHTML = `
    <div id="globalModalOverlay" style="
      position:fixed;
      inset:0;
      background:rgba(0,0,0,0.45);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:99999;
    ">
      <div id="globalModalBox" style="
        background:#ffffff;
        max-width:480px;
        width:92%;
        border-radius:16px;
        padding:28px 26px 24px;
        box-shadow:0 25px 60px rgba(0,0,0,0.25);
        position:relative;
        animation:modalFade .2s ease-out;
      ">
        <h3 id="globalModalTitle" style="
          margin:0 0 14px;
          font-size:18px;
          font-weight:700;
        "></h3>
        <div id="globalModalBody" style="
          font-size:14px;
          line-height:1.6;
          word-break:break-word;
          overflow-wrap:anywhere;
        "></div>

        <div style="margin-top:22px;text-align:right;">
          <button id="globalModalOk" style="
            background:#111;
            color:#fff;
            border:none;
            padding:10px 18px;
            border-radius:8px;
            font-weight:600;
            cursor:pointer;
          ">OK</button>
        </div>
      </div>
    </div>

    <style>
      @keyframes modalFade {
        from { transform:translateY(10px); opacity:0; }
        to { transform:translateY(0); opacity:1; }
      }
    </style>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  const overlay = document.getElementById("globalModalOverlay");
  const titleEl = document.getElementById("globalModalTitle");
  const bodyEl = document.getElementById("globalModalBody");
  const okBtn = document.getElementById("globalModalOk");

  function closeModal() {
    overlay.style.display = "none";
  }

  okBtn.onclick = closeModal;
  overlay.onclick = (e) => {
    if (e.target === overlay) closeModal();
  };

  // GLOBAL FUNCTION
  window.showModal = function (title, html, type = "info") {
    titleEl.textContent = title || "Notice";
    bodyEl.innerHTML = html || "";

    // Color logic
    if (type === "success") {
      titleEl.style.color = "#1A7A45";
    } else if (type === "error") {
      titleEl.style.color = "#C0392B";
    } else {
      titleEl.style.color = "#111";
    }

    overlay.style.display = "flex";
  };
})();