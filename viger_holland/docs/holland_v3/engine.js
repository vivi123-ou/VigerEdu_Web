// HollandEngine (JS) – cùng quy tắc và kết quả với engine/HollandEngine.php
const ORDER = 'RIASEC';
function hexD(a, b) { const d = Math.abs(ORDER.indexOf(a) - ORDER.indexOf(b)); return Math.min(d, 6 - d); }
const REL = { 1: 'Liền kề', 2: 'Cách một', 3: 'Đối kháng' };
const CONS = { 1: 'Cao', 2: 'Trung bình', 3: 'Thấp' };
function cIndex(student, major) {
  let c = 0;
  [3, 2, 1].forEach((w, i) => { if (i < major.length && i < student.length) c += w * (3 - hexD(student[i], major[i])); });
  return c;
}
function cmp(a, b) { for (let i = 0; i < a.length; i++) { if (a[i] < b[i]) return -1; if (a[i] > b[i]) return 1; } return 0; }
const r4 = (x) => Math.round(x * 10000) / 10000;

function makeEngine(D) {
  const S = D.settings;
  const band = (m) => { for (const [max, l] of S.bands) if (m <= max + 1e-9) return l; return S.bands[S.bands.length - 1][1]; };
  const pairKey = (a, b) => (ORDER.indexOf(a) < ORDER.indexOf(b) ? a + b : b + a);

  function recommend(code3, groups) {
    const byId = Object.fromEntries(D.majors.map((m) => [m.id, m]));
    const pair = D.riasec_pairs.find((p) => p.pair === pairKey(code3[0], code3[1]));
    const best = (mj) => {
      let b = null;
      for (const c of mj.codes) {
        const ci = cIndex(code3, c);
        let w = 0; [3, 2, 1].forEach((wt, i) => { if (c[i]) w += wt * groups[c[i]].mean; });
        if (!b || cmp([ci, w], [b.c, b.w]) > 0) b = { c: ci, w: r4(w), code: c };
      }
      return b;
    };
    const label = (c) => { if (c === null || c === undefined) return 'Chưa có mã ngành'; for (const [min, l] of S.c_labels) if (c >= min) return l; return null; };
    const pairRows = (pair ? pair.majors : []).filter((id) => byId[id] && byId[id].active).map((id, k) => {
      const mj = byId[id]; const b = best(mj) || {};
      return { id, name_vi: mj.name_vi, name_de: mj.name_de, source: 'pair', pair: pair.pair, code: b.code ?? null, c: b.c ?? null, w: b.w ?? 0, orig: k, label: 'Ngành nền của cặp ' + pair.pair };
    });
    pairRows.sort((x, y) => cmp([y.c ?? -1, y.w, -y.orig], [x.c ?? -1, x.w, -x.orig]));
    const others = [];
    for (const mj of D.majors) {
      if (!mj.active || (pair && pair.majors.includes(mj.id)) || !mj.codes.length) continue;
      const b = best(mj);
      if (groups[b.code[0]].mean <= S.interest_mark) continue;
      const l = label(b.c); if (l === null) continue;
      others.push({ id: mj.id, name_vi: mj.name_vi, name_de: mj.name_de, source: 'catalog', pair: null, code: b.code, c: b.c, w: b.w, label: l });
    }
    others.sort((x, y) => cmp([y.c, y.w], [x.c, x.w]) || (x.id < y.id ? -1 : x.id > y.id ? 1 : 0));
    return [...pairRows, ...others].map((r, i) => { const o = { ...r, rank: i + 1 }; delete o.orig; return o; });
  }

  function flags(answers, groups) {
    const f = [];
    const scored = answers.filter((a) => !a.check);
    const kbTotal = Object.values(groups).reduce((s, g) => s + g.dont_know, 0);
    if (kbTotal > S.dont_know_flag_total) f.push({ code: 'nhieu_khong_biet', level: 'nhe', detail: `${kbTotal} câu "Không biết"` });
    const short = Object.values(groups).filter((g) => !g.enough).map((g) => g.code);
    if (short.length) f.push({ code: 'nhom_thieu_du_lieu', level: short.length >= 2 ? 'nang' : 'nhe', detail: short.join(', ') });
    const vals = scored.map((a) => a.value).filter((v) => v !== null);
    if (vals.length) {
      const cnt = {}; vals.forEach((v) => (cnt[v] = (cnt[v] || 0) + 1));
      const share = Math.max(...Object.values(cnt)) / scored.length;
      if (share >= S.one_option_share) f.push({ code: 'mot_muc_chiem', level: 'nang', detail: Math.round(share * 100) + '% cùng một mức' });
    }
    let run = 0, longest = 0, prev = 'x';
    for (const a of scored) { run = a.value !== null && a.value === prev ? run + 1 : 1; prev = a.value; longest = Math.max(longest, run); }
    if (longest >= S.longstring) f.push({ code: 'chuoi_giong_nhau', level: 'nhe', detail: `${longest} câu liên tiếp cùng mức` });
    const ms = answers.map((a) => a.ms).filter((v) => v !== null && v !== undefined);
    if (ms.length === answers.length && ms.length) {
      const avg = ms.reduce((s, v) => s + Math.min(v, 60000), 0) / ms.length / 1000;
      if (avg < S.fast_avg_seconds) f.push({ code: 'qua_nhanh', level: 'nang', detail: Math.round(avg * 10) / 10 + ' giây/câu' });
      let rr = 0, b = 0; for (const v of ms) { rr = v < S.rushed_seconds * 1000 ? rr + 1 : 0; b = Math.max(b, rr); }
      if (b >= S.rushed_run) f.push({ code: 'doan_bam_voi', level: 'nhe', detail: `${b} câu liên tiếp dưới 1 giây` });
    }
    const heavy = f.filter((x) => x.level === 'nang').length;
    return { reliability: heavy ? 'Chưa đủ tin cậy' : f.length ? 'Cần xem lại' : 'Tin cậy', items: f };
  }

  function score(answers) {
    const sum = {}, cnt = {}, kb = {};
    for (const g of ORDER) { sum[g] = 0; cnt[g] = 0; kb[g] = 0; }
    for (const a of answers) { if (a.check) continue; if (a.value === null) kb[a.group]++; else { sum[a.group] += a.value; cnt[a.group]++; } }
    const groups = {};
    for (const g of ORDER) {
      const mean = cnt[g] ? sum[g] / cnt[g] : 0;
      groups[g] = { code: g, mean: r4(mean), answered: cnt[g], dont_know: kb[g], enough: cnt[g] >= S.min_valid_per_group, band: cnt[g] ? band(mean) : 'Chưa đủ dữ liệu' };
    }
    const order = Object.values(groups).sort((x, y) => cmp([y.enough ? 1 : 0, y.mean, -ORDER.indexOf(y.code)], [x.enough ? 1 : 0, x.mean, -ORDER.indexOf(x.code)])).map((g) => g.code);
    const [g1, g2, g3, g4] = order; const m = (g) => groups[g].mean;
    const code2 = g1 + g2, code3 = g1 + g2 + g3;
    const thirdDetail = m(g3) > S.third_letter_threshold && (!S.third_letter_requires_full || groups[g3].answered >= S.items_per_group);
    const alternates = [];
    if (m(g3) - m(g4) <= S.near_tie_gap + 1e-9) alternates.push(g1 + g2 + g4);
    if (m(g2) - m(g3) <= S.near_tie_gap + 1e-9) alternates.push(g1 + g3 + g2);
    if (m(g1) - m(g2) <= S.near_tie_gap + 1e-9) alternates.push(g2 + g1 + g3);
    const rel = (a, b) => ({ pair: a + b, distance: hexD(a, b), relation: REL[hexD(a, b)] });
    const relations = { '1-2': rel(g1, g2), '1-3': rel(g1, g3), '2-3': rel(g2, g3) };
    let x = 0, y = 0;
    [...ORDER].forEach((g, i) => { x += m(g) * Math.cos((i * 60 * Math.PI) / 180); y += m(g) * Math.sin((i * 60 * Math.PI) / 180); });
    const means = [...ORDER].map(m);
    return {
      schema: 'holland-result/3', groups, order, code2, code3, third_detail: thirdDetail,
      third: thirdDetail ? D.third_letters.find((t) => t.code === g3) : null, alternates, relations, consistency: CONS[relations['1-2'].distance],
      profile: { elevation: r4(means.reduce((s, v) => s + v, 0) / 6), differentiation: r4(Math.max(...means) - Math.min(...means)), axis_x: r4(x), axis_y: r4(y), strength: r4(Math.hypot(x, y)) },
      majors: recommend(code3, groups), flags: flags(answers, groups),
    };
  }
  return { score, band, cIndex, hexD, pairKey, recommend };
}
if (typeof module !== 'undefined') module.exports = { makeEngine, hexD, cIndex, ORDER, REL, CONS };
