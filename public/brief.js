(() => {
  const main = document.querySelector("main[data-edition]");
  if (!main) return;

  const edition = main.dataset.edition;
  const rows = [...main.querySelectorAll(".row")];
  const buttons = [...main.querySelectorAll("[data-filter]")];
  const key = "qibu:brief:progress:v1";
  const mark = main.querySelector("[data-mark]");
  const catchup = main.querySelector("[data-catchup]");
  const url = new URL(location.href);
  let topic = url.searchParams.get("topic") || "全部";
  let unreadOnly = url.searchParams.get("unread") === "1";
  let state = null;
  let persistent = true;

  function valid(snapshot) {
    return snapshot && typeof snapshot.day === "string" && /^\d{4}-\d{2}-\d{2}$/.test(snapshot.day)
      && Array.isArray(snapshot.tokens) && snapshot.tokens.every(token => typeof token === "string");
  }
  function load() {
    if (!persistent) return;
    try {
      const value = JSON.parse(localStorage.getItem(key));
      state = valid(value) ? value : null;
    } catch { persistent = false; }
  }
  function updateUrl() {
    const next = new URL(location.href);
    if (topic === "全部") next.searchParams.delete("topic");
    else next.searchParams.set("topic", topic);
    if (unreadOnly) next.searchParams.set("unread", "1");
    else next.searchParams.delete("unread");
    history.replaceState(null, "", next);
  }
  function render() {
    if (!buttons.some(button => button.dataset.filter === topic)) topic = "全部";
    const seen = new Set(state ? state.tokens : []);
    const newerIssueSaved = state && state.day > edition;
    const unread = state && !newerIssueSaved ? rows.filter(row => !seen.has(row.dataset.token)) : [];
    const canCatchUp = unread.length > 0;
    if (!canCatchUp) unreadOnly = false;
    let matches = 0;
    for (const row of rows) {
      row.hidden = (topic !== "全部" && row.dataset.kind !== topic) || (unreadOnly && seen.has(row.dataset.token));
      if (!row.hidden) matches++;
    }
    for (const button of buttons) {
      button.setAttribute("aria-pressed", String(!unreadOnly && button.dataset.filter === topic));
    }
    main.querySelector("[data-results]").textContent = (unreadOnly ? "补读" : topic) + "，共 " + matches + " 条";
    main.querySelector("[data-empty]").hidden = matches !== 0 || rows.length === 0;
    catchup.hidden = !canCatchUp;
    catchup.textContent = "补读新增或更新 " + unread.length + " 条 ↓";
    let message = "读完后可在本设备记下进度。";
    if (state) message = canCatchUp ? "自上次读完，近七天新增或更新 " + unread.length + " 条。" : "已读至 " + state.day + "。";
    if (state && state.day < main.dataset.start) message += "更早内容可到归档补读。";
    if (!persistent) message += "当前进度仅在本页有效。";
    main.querySelector("[data-progress]").textContent = message;
    const current = state && state.day === edition && !canCatchUp;
    mark.disabled = Boolean(newerIssueSaved) || !rows.length;
    mark.textContent = newerIssueSaved ? "本期已读" : current ? "撤销本次标记" : "标记本次已读";
    mark.setAttribute("aria-pressed", String(Boolean(current || newerIssueSaved)));
  }
  function choose(value, unread) {
    topic = value;
    unreadOnly = unread;
    render();
    updateUrl();
  }
  for (const button of buttons) button.addEventListener("click", () => choose(button.dataset.filter, false));
  main.querySelector("[data-reset]").addEventListener("click", () => { choose("全部", false); buttons[0].focus(); });
  catchup.addEventListener("click", () => choose("全部", true));
  mark.addEventListener("click", () => {
    load();
    if (state && state.day > edition) { render(); return; }
    const previous = state && { day: state.day, tokens: state.tokens };
    const tokens = rows.map(row => row.dataset.token);
    const covered = state && state.day === edition && tokens.every(token => state.tokens.includes(token));
    state = covered ? (valid(state.previous) ? state.previous : null) : { day: edition, tokens, previous };
    if (persistent) {
      try {
        if (state) localStorage.setItem(key, JSON.stringify(state));
        else localStorage.removeItem(key);
      } catch { persistent = false; }
    }
    choose("全部", false);
  });
  function sync() { load(); render(); }
  window.addEventListener("pageshow", sync);
  window.addEventListener("storage", event => { if (event.key === key || event.key === null) sync(); });
  sync();
  main.querySelector(".filters").hidden = !rows.length;
  main.querySelector(".progress").hidden = !rows.length;
})();
