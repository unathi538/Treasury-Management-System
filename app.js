import {
  Lucid,
  Blockfrost,
  Constr,
  Data,
} from "https://unpkg.com/lucid-cardano@0.10.11/web/mod.js";

/* =====================================================
   CONFIG
===================================================== */

const BLOCKFROST_URL =
  "https://cardano-preprod.blockfrost.io/api/v0";

const BLOCKFROST_KEY =
  "preprodYjRkHfcazNkL0xxG9C2RdUbUoTrG7wip";

const NETWORK = "Preprod";

/* =====================================================
   SCRIPT (PASTE YOUR CBOR HERE)
===================================================== */

const SCRIPT_CBOR = "59085d010000323232332232323232323232323233223233223232323232322322322323253353232325335002102015335323235002222222222222533533355301912001321233001225335002210031001002502225335333573466e3c0380040bc0b84d40900045408c010840bc40b4d40108004d40048800840804cd5ce24811b747265617375726572207369676e6174757265206d697373696e670001f3333573466e1cd55cea80224000466442466002006004646464646464646464646464646666ae68cdc39aab9d500c480008cccccccccccc88888888888848cccccccccccc00403403002c02802402001c01801401000c008cd406806cd5d0a80619a80d00d9aba1500b33501a01c35742a014666aa03ceb94074d5d0a804999aa80f3ae501d35742a01066a03404e6ae85401cccd540780a1d69aba150063232323333573466e1cd55cea801240004664424660020060046464646666ae68cdc39aab9d5002480008cc8848cc00400c008cd40c9d69aba150023033357426ae8940088c98c80d4cd5ce01b81c01989aab9e5001137540026ae854008c8c8c8cccd5cd19b8735573aa004900011991091980080180119a8193ad35742a00460666ae84d5d1280111931901a99ab9c037038033135573ca00226ea8004d5d09aba2500223263203133573806606805e26aae7940044dd50009aba1500533501a75c6ae854010ccd540780908004d5d0a801999aa80f3ae200135742a004604c6ae84d5d1280111931901699ab9c02f03002b135744a00226ae8940044d5d1280089aba25001135744a00226ae8940044d5d1280089aba25001135744a00226ae8940044d55cf280089baa00135742a008602c6ae84d5d1280211931900f99ab9c02102201d3333573466e1d40152002212200123333573466e1d40192000212200223263201f33573804204403a0386666ae68cdc39aab9d5006480008c848c004008dd71aba135573ca00e464c6403a66ae7007c08006c407c584d55cf280089baa001135573a6ea80044dd5000990009aa80c1108911299a80089a80191000910999a802910011802001199aa9803890008028020008919118011bac001320013550182233335573e0024a014466a01260086ae84008c00cd5d100100c919191999ab9a3370e6aae7540092000233221233001003002300e35742a004600a6ae84d5d1280111931900b19ab9c018019014135573ca00226ea80048c8c8c8c8cccd5cd19b8735573aa00890001199991110919998008028020018011919191999ab9a3370e6aae7540092000233221233001003002301735742a00466a01e02c6ae84d5d1280111931900d99ab9c01d01e019135573ca00226ea8004d5d0a802199aa8043ae500735742a0066464646666ae68cdc3a800a4008464244460040086ae84d55cf280191999ab9a3370ea0049001119091118008021bae357426aae7940108cccd5cd19b875003480008488800c8c98c8074cd5ce00f81000d80d00c89aab9d5001137540026ae854008cd402dd71aba135744a004464c6402e66ae700640680544d5d1280089aba25001135573ca00226ea80044cd54005d73ad112232230023756002640026aa02a44646666aae7c008940208cd401ccc8848cc00400c008c018d55cea80118029aab9e500230043574400602e26ae840044488008488488cc00401000c488c8c8cccd5cd19b875001480008d401cc014d5d09aab9e500323333573466e1d400920022500723263201233573802802a02001e26aae7540044dd50008909118010018891000919191999ab9a3370ea002900311909111180200298039aba135573ca00646666ae68cdc3a8012400846424444600400a60126ae84d55cf280211999ab9a3370ea006900111909111180080298039aba135573ca00a46666ae68cdc3a8022400046424444600600a6eb8d5d09aab9e500623263201033573802402601c01a01801626aae7540044dd5000919191999ab9a3370e6aae7540092000233221233001003002300535742a0046eb4d5d09aba2500223263200c33573801c01e01426aae7940044dd50009191999ab9a3370e6aae75400520002375c6ae84d55cf280111931900519ab9c00c00d00813754002464646464646666ae68cdc3a800a401842444444400646666ae68cdc3a8012401442444444400846666ae68cdc3a801a40104664424444444660020120106eb8d5d0a8029bad357426ae8940148cccd5cd19b875004480188cc8848888888cc008024020dd71aba15007375c6ae84d5d1280391999ab9a3370ea00a900211991091111111980300480418061aba15009375c6ae84d5d1280491999ab9a3370ea00c900111909111111180380418069aba135573ca01646666ae68cdc3a803a400046424444444600a010601c6ae84d55cf280611931900999ab9c01501601101000f00e00d00c00b135573aa00826aae79400c4d55cf280109aab9e5001137540024646464646666ae68cdc3a800a4004466644424466600200a0080066eb4d5d0a8021bad35742a0066eb4d5d09aba2500323333573466e1d4009200023212230020033008357426aae7940188c98c8030cd5ce00700780500489aab9d5003135744a00226aae7940044dd5000919191999ab9a3370ea002900111909118008019bae357426aae79400c8cccd5cd19b875002480008c8488c00800cdd71aba135573ca008464c6401266ae7002c03001c0184d55cea80089baa00112232323333573466e1d400520042122200123333573466e1d40092002232122230030043006357426aae7940108cccd5cd19b87500348000848880088c98c8028cd5ce00600680400380309aab9d5001137540024646666ae68cdc3a800a4004400c46666ae68cdc3a80124000400c464c6400c66ae7002002401000c4d55ce9baa0014984880084880052410350543100120011123230010012233003300200200101";

const script = {
  type: "PlutusV2",
  script: SCRIPT_CBOR,
};

/* =====================================================
   DATUM
===================================================== */

const PoolDatum = Data.Object({
  treasurer: Data.Bytes(),
});

/* =====================================================
   REDEEMERS
===================================================== */

const depositRedeemer  = Data.to(new Constr(0, []));
const withdrawRedeemer = Data.to(new Constr(1, []));

/* =====================================================
   GLOBAL STATE
===================================================== */

let lucid;
let walletAddress;
let walletPkh;
let scriptAddress;

const POOL_ID = "main";

function lovelaceToAdaString(lovelace) {
  const ada = Number(lovelace) / 1_000_000;
  return ada.toLocaleString(undefined, { maximumFractionDigits: 6 }) + " ADA";
}

async function apiGet(url) {
  const res = await fetch(url, { credentials: "include" });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || "Request failed");
  return data;
}

async function apiPost(url, body) {
  const res = await fetch(url, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || "Request failed");
  return data;
}

async function logTransaction(tx_type, amount_lovelace, onchain_tx_hash, status = "submitted") {
  return apiPost("/backend/api/transactions/log.php", {
    pool_id: POOL_ID,
    tx_type,
    amount_lovelace: Number(amount_lovelace),
    onchain_tx_hash,
    status,
    actor_wallet_address: walletAddress, // ✅ ADD THIS
  });
}

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.innerText = text;
}

function setHTML(id, html) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = html;
}

async function loadLiveStats() {
  const s = await apiGet(`/backend/api/stats.php?pool_id=${encodeURIComponent(POOL_ID)}`);

  const depositedText = lovelaceToAdaString(s.total_deposited_lovelace);
  const withdrawnText = lovelaceToAdaString(s.total_withdrawn_lovelace);
  const balanceText   = lovelaceToAdaString(s.pool_balance_lovelace);

  setText("totalDeposited", depositedText);
  setText("totalWithdrawn", withdrawnText);
  setText("poolBalance", balanceText);

  const stripDeposited = depositedText.replace(" ADA", "");
  const stripWithdrawn = withdrawnText.replace(" ADA", "");

  setHTML("statDeposited", `${stripDeposited}<sup> ADA</sup>`);
  setHTML("statWithdrawn", `${stripWithdrawn}<sup> ADA</sup>`);
}

async function loadRecentTransactions() {
  const r = await apiGet(`/backend/api/transactions/recent.php?pool_id=${encodeURIComponent(POOL_ID)}&limit=10`);
  const el = document.getElementById("recentTransactions");

  el.innerHTML = "";

  r.items.forEach((tx) => {
    const row = document.createElement("div");
    row.style.padding = "10px";
    row.style.borderBottom = "1px solid rgba(255,255,255,0.08)";

    const sign = tx.tx_type === "deposit" ? "+" : "-";
    const amountStr = sign + " " + lovelaceToAdaString(tx.amount_lovelace);
    const hashShort = tx.onchain_tx_hash ? (tx.onchain_tx_hash.slice(0, 10) + "…") : "—";

    row.innerHTML = `
      <div style="display:flex; justify-content:space-between; gap:12px;">
        <strong>${tx.tx_type.toUpperCase()}</strong>
        <span>${amountStr}</span>
      </div>
      <div style="opacity:0.8; font-size:12px; margin-top:4px;">
        <span>Status: ${tx.status}</span> ·
        <span>Tx: ${hashShort}</span> ·
        <span>${tx.created_at}</span>
      </div>
    `;
    el.appendChild(row);
  });
}

async function refreshDashboard() {
  try {
    await Promise.all([loadLiveStats(), loadRecentTransactions()]);
  } catch (e) {
    console.warn("Dashboard refresh failed:", e.message);
  }
}

const API_BASE = new URL("backend/api/", window.location.href).toString().replace(/\/$/, "");

async function apiGetJSON(url) {
  const res = await fetch(url, {
    method: "GET",
    credentials: "include",     // ✅ IMPORTANT (send session cookie)
    cache: "no-store",
    headers: { "Accept": "application/json" },
  });

  const text = await res.text();
  let out;
  try { out = JSON.parse(text); }
  catch { throw new Error("Bad JSON: " + text.slice(0, 200)); }

  // support both {ok:false,error} and {error}
  if (!res.ok) throw new Error(out.error || "Request failed");
  if (out.ok === false) throw new Error(out.error || "Request failed");

  return out;
}

async function apiPostJSON(url, body) {
  const res = await fetch(url, {
    method: "POST",
    credentials: "include",     // ✅ IMPORTANT (send session cookie)
    cache: "no-store",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
    },
    body: JSON.stringify(body),
  });

  const text = await res.text();
  let out;
  try { out = JSON.parse(text); }
  catch { throw new Error("Bad JSON: " + text.slice(0, 200)); }

  if (!res.ok) throw new Error(out.error || "Request failed");
  if (out.ok === false) throw new Error(out.error || "Request failed");

  return out;
}

// replace this with how you already compute wallet address in your app.js
function getConnectedWalletAddress() {
  return walletAddress || "";
}

// ✅ REMOVE your old notifyModal-based showModal() completely.
// ✅ This helper will use the global modal (global-modal.js) everywhere.

function safeShowModal(title, html, type = "info") {
  if (typeof window.showModal === "function") {
    window.showModal(title, html, type);
  } else {
    // fallback if global modal not loaded (should not happen if you include global-modal.js everywhere)
    alert(title + "\n\n" + String(html || "").replace(/<[^>]*>/g, ""));
  }
}

async function enforceRegisteredWalletOrBindFirstTime() {
  // must be logged in
  let me;
  try {
    me = await apiGetJSON("/backend/auth/me.php");
  } catch (e) {
    safeShowModal(
      "Login Required",
      `<p>Please log in before connecting a wallet.</p>`,
      "error"
    );
    return { ok: false, reason: "not_logged_in" };
  }

  const user = me.user || me?.data?.user || null;
  if (!user?.id) {
    safeShowModal(
      "Login Required",
      `<p>Please log in before connecting a wallet.</p>`,
      "error"
    );
    return { ok: false, reason: "not_logged_in" };
  }

  const connected = getConnectedWalletAddress();
  if (!connected) {
    safeShowModal(
      "No Wallet Detected",
      `<p>No wallet detected. Please connect your wallet and try again.</p>`,
      "error"
    );
    return { ok: false, reason: "no_wallet" };
  }

  const registered = user.wallet_address;

  const escapeHtml = (s) =>
    String(s || "").replace(/[<>&]/g, (c) => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;" }[c]));

  // ✅ prevents address overflow outside modal
  const renderWalletCode = (addr) => `
    <div style="margin-top:10px;">
      <div style="font-size:12px; opacity:0.8; margin-bottom:6px;">Wallet Address</div>
      <code style="
        display:block;
        max-width:100%;
        padding:10px 12px;
        border-radius:10px;
        background:rgba(0,0,0,0.06);
        word-break:break-word;
        overflow-wrap:anywhere;
        white-space:pre-wrap;
        box-sizing:border-box;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;
        font-size:12px;
        line-height:1.4;
      ">${escapeHtml(addr)}</code>
    </div>
  `;

  // FIRST TIME: bind to the first connected wallet
  if (!registered) {
    try {
      const out = await apiPostJSON("/backend/api/users/bind_wallet.php", {
        wallet_address: connected,
      });

      const linkedWallet = out.wallet_address || connected;

      // ✅ ALWAYS show for first-time bind
      // show only once per page load
  if (!window.__walletConnectedShown) {
    window.__walletConnectedShown = true;

    safeShowModal(
      "Wallet Connected Successfully",
      `
        <p>You are connected with your registered wallet.</p>
        ${renderWalletCode(connected)}
      `
    );
  }

      return { ok: true, bound: true, wallet: linkedWallet };
    } catch (e) {
      const msg = String(e?.message || "").toLowerCase();

      // wallet already used by another account
      if (
        msg.includes("already linked") ||
        msg.includes("already registered") ||
        msg.includes("linked to another") ||
        msg.includes("another account") ||
        msg.includes("already bound") ||
        msg.includes("wallet is already") ||
        msg.includes("409")
      ) {
        safeShowModal(
          "Wallet Already Registered",
          `
            <p>This wallet address is already linked to another account.</p>
            <p>Please connect a different wallet or log in to the correct account.</p>
            ${renderWalletCode(connected)}
          `,
          "error"
        );
        return { ok: false, reason: "wallet_taken" };
      }

      safeShowModal(
        "Wallet Linking Failed",
        `<p>${escapeHtml(e?.message || "Unable to link wallet right now. Please try again.")}</p>`,
        "error"
      );
      return { ok: false, reason: "bind_failed" };
    }
  }

  // EXISTING USER: must match
  if (String(registered).trim() !== String(connected).trim()) {
    // ✅ Only show wrong wallet here (no success titles here)
    safeShowModal(
      "Wrong Wallet Connected",
      `
        <p>This account is registered to a different wallet.</p>
        <p>Please connect your <strong>registered wallet</strong> to continue.</p>
        ${renderWalletCode(connected)}
      `,
      "error"
    );
    return { ok: false, reason: "wallet_mismatch", registered, connected };
  }

  // ✅ Correct wallet connected: ALWAYS show modal (all pages)
  if (!window.__walletConnectedShown) {
  window.__walletConnectedShown = true;

  safeShowModal(
    "Wallet Connected Successfully",
    `
      <p>You are connected with your registered wallet.</p>
      ${renderWalletCode(connected)}
    `
  );
}

  return { ok: true, bound: false, wallet: connected };
}
/* =====================================================
   CONNECT WALLET
===================================================== */

async function connectWallet() {
  lucid = await Lucid.new(
    new Blockfrost(BLOCKFROST_URL, BLOCKFROST_KEY),
    NETWORK
  );

  const api = await window.cardano.lace.enable();
  lucid.selectWallet(api);

  walletAddress = await lucid.wallet.address();
  window.walletAddress = walletAddress; // ✅ expose for any UI helpers

  const check = await enforceRegisteredWalletOrBindFirstTime();
  if (!check.ok) {
    // optional: disconnect UI state / disable buttons
    return;
  }

  walletPkh =
    lucid.utils.getAddressDetails(walletAddress)
      .paymentCredential.hash;

  scriptAddress =
    lucid.utils.validatorToAddress(script);

  log("Wallet connected");
  log("Script address: " + scriptAddress);

  // proceed with loading stats/dashboard etc
  await loadLiveStats?.();
}

/* =====================================================
   BUILD DATUM
===================================================== */

function mkDatum(treasurerPkh) {
  return Data.to(
    { treasurer: treasurerPkh },
    PoolDatum
  );
}

/* =====================================================
   DEPOSIT
===================================================== */

async function deposit() {
  const check = await enforceRegisteredWalletOrBindFirstTime();
  if (!check.ok) return;

  const treasurerAddr =
    "addr_test1qpma2jn6l0684rmevytrd3k7x8q4fvzzqwydedwy2aezzxjacmj4jllasdrpk6rlyl5dhsg0wne5nssnt68z6s5f4x7s66s08j";

  const amount =
    BigInt(document.getElementById("depositAmount").value)
    * 1_000_000n;

  const treasurerPkh =
    lucid.utils.getAddressDetails(treasurerAddr)
      .paymentCredential.hash;

  const datum = mkDatum(treasurerPkh);

  const tx = await lucid
    .newTx()
    .payToContract(
      scriptAddress,
      { inline: datum },
      { lovelace: amount }
    )
    .addSignerKey(walletPkh)
    .complete();

  const signed = await tx.sign().complete();
  const txHash = await signed.submit();

    log("Deposit successful: " + txHash);

  // Log in DB
  try {
    await logTransaction("deposit", amount, txHash, "submitted");
  } catch (e) {
    console.warn("Failed to log deposit:", e.message);
  }

  // Refresh stats + recent list
  await refreshDashboard();

}

/* =====================================================
   WITHDRAW (TREASURER ONLY)
===================================================== */

async function withdraw() {
  const utxos = await lucid.utxosAt(scriptAddress);
  console.log("utxos", utxos);

  if (!utxos.length) return log("No funds in pool");

  const utxo = utxos[0];

  // ✅ read inline datum correctly (fallback if needed)
  const datumCbor = utxo.datum ?? (await lucid.datumOf(utxo));

  // ✅ decode using the schema so Bytes casts correctly
  const d = Data.from(datumCbor, PoolDatum); // { treasurer: <bytes> }

  // ✅ keep the same treasurer field
  const newDatum = Data.to({ treasurer: d.treasurer }, PoolDatum);

  const amount =
    BigInt(document.getElementById("withdrawAmount").value) * 1_000_000n;

  if (amount <= 0n) return log("Enter a valid withdraw amount");
  if (amount > utxo.assets.lovelace) return log("Insufficient pool funds");

  const remainder = utxo.assets.lovelace - amount;

  const tx = await lucid
    .newTx()
    .collectFrom([utxo], withdrawRedeemer)
    .attachSpendingValidator(script)
    // ✅ payToAddress expects an assets object
    .payToAddress(walletAddress, { lovelace: amount })
    .payToContract(
      scriptAddress,
      { inline: newDatum },
      { lovelace: remainder }
    )
    .addSignerKey(walletPkh)
    .complete();

  const signed = await tx.sign().complete();
  const txHash = await signed.submit();

    log("Withdraw successful: " + txHash);

  // Log in DB
  try {
    await logTransaction("withdraw", amount, txHash, "submitted");
  } catch (e) {
    console.warn("Failed to log withdraw:", e.message);
  }

  await refreshDashboard();

}

document.addEventListener("DOMContentLoaded", async () => {
  // If this page has poolBalance element, load stats here too
  if (document.getElementById("poolBalance")) {
    try { await loadLiveStats(); } catch (e) { console.error(e); }
  }
});

/* =====================================================
   UI HELPERS
===================================================== */

function log(msg) {
  document.getElementById("log").innerText += msg + "\n";
}

/* =====================================================
   BUTTONS
===================================================== */

window.connectWallet = async () => {
  await connectWallet();
  await refreshDashboard();

  // poll every 10 seconds for "live" updates
  setInterval(refreshDashboard, 10_000);
};

// Called by your withdraw-request.html
window.submitWithdrawRequest = async function () {
  const fullName = document.getElementById("fullName")?.value.trim();
  const amount   = document.getElementById("withdrawAmount")?.value;
  const addr     = document.getElementById("recipientAddress")?.value.trim();
  const category = document.getElementById("purposeCategory")?.value;
  const reason   = document.getElementById("justification")?.value.trim();

  if (!fullName || !amount || !addr || !category || !reason) {
    alert("Please fill in all fields before submitting.");
    return;
  }

  try {
    const r = await apiPost(`${API_BASE}/withdraw_requests/create.php`, {
      pool_id: "main",
      fullName,
      amount,
      addr,
      category,
      reason,
    });

    log(`Withdrawal request submitted (ID: ${r.id})`);
    alert("Request submitted successfully. Awaiting approval.");
  } catch (e) {
    console.error(e);
    alert(e.message || "Failed to submit request");
  }
};

window.deposit = deposit;
window.withdraw = withdraw;

