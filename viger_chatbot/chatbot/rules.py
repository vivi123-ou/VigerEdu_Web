"""Chatbot theo từ khóa (miễn phí, không gọi AI): hiểu ý câu hỏi rồi tra dữ liệu thật."""
from __future__ import annotations

import json
import os
import re
from dataclasses import dataclass, field
from pathlib import Path

from .data import Catalog, norm

FAQ_FILE = Path(__file__).resolve().parent.parent / "data" / "faq.json"

# Tên khác của bang (tiếng Anh, viết tắt) -> mã bang. Tên tiếng Đức lấy từ dữ liệu.
REGION_ALIASES = {
    "nrw": "NW", "bavaria": "BY", "bavarian": "BY", "hesse": "HE", "lower saxony": "NI",
    "saxony anhalt": "ST", "saxony": "SN", "thuringia": "TH", "rhineland palatinate": "RP",
    "north rhine westphalia": "NW", "mecklenburg": "MV", "western pomerania": "MV",
    "baden wurttemberg": "BW", "baden wuerttemberg": "BW", "bw": "BW", "schleswig": "SH",
}
# Tên thành phố tiếng Anh / không dấu -> tên trong dữ liệu (đã norm)
CITY_ALIASES = {
    "munich": "munchen", "muenchen": "munchen", "cologne": "koln", "koeln": "koln",
    "nuremberg": "nurnberg", "nuernberg": "nurnberg", "hanover": "hannover",
    "dusseldorf": "dusseldorf", "duesseldorf": "dusseldorf", "frankfurt": "frankfurt",
}
TYPE_WORDS = [
    ("HAW", ["ung dung", "fachhochschule", "hochschule ung", "haw", "fh"]),
    ("UNI", ["nghien cuu", "tong hop", "universitat", "uni"]),
    ("ART", ["nghe thuat", "am nhac", "my thuat", "kunst"]),
]
OWNER_WORDS = [("public", ["cong lap", "nha nuoc"]), ("private", ["tu thuc", "tu nhan", "tu lap"]),
               ("church", ["nha tho", "cong giao", "tin lanh"])]
SCHOOL_WORDS = ["truong", "dai hoc", "dh","hoc o", "danh sach", "liet ke", "goi y", "university", "hochschule"]
REGION_INFO_WORDS = ["the nao", "ra sao", "thong tin", "gioi thieu", "co gi", "o dau", "dan so", "thu phu",
                     "noi bat", "song o", "chi phi"]
GREETING = re.compile(r"^(xin chao|chao|hello|hi|hey|alo|chao ban|chao em|chao ad|ad oi|shop oi)\b")
THANKS = re.compile(r"\b(cam on|thank|thanks|ok|oke|okay)\b")
PHONE_RE = re.compile(r"(?:\+?84|0)(?:[\s.-]?\d){9}")
EMAIL_RE = re.compile(r"[\w.+-]+@[\w-]+\.[\w.]+")

DEFAULT_SUGGESTIONS = ["Trường ở Berlin", "ĐH Ứng dụng công lập ở Bayern", "Học phí ở Đức", "Tư vấn trực tiếp"]
FALLBACK = ("Xin lỗi, mình chưa hiểu rõ câu hỏi. Mình có thể tìm trường đại học ở Đức theo bang, thành phố, "
            "loại trường (nghiên cứu / ứng dụng), công lập hay tư thục, và trả lời về học phí, visa, tiếng Đức. "
            "Hoặc bạn để lại số điện thoại để tư vấn viên Viger gọi lại nhé.")


@dataclass
class Reply:
    text: str
    suggestions: list[str] = field(default_factory=list)
    intent: str = ""


def _has(q: str, phrase: str) -> bool:
    return re.search(rf"(?<![a-z0-9]){re.escape(phrase)}(?![a-z0-9])", q) is not None


def load_faq(path: Path | str | None = None) -> list[dict]:
    p = Path(path or os.getenv("CHATBOT_FAQ_FILE") or FAQ_FILE)
    if not p.exists():
        return []
    items = json.loads(p.read_text(encoding="utf-8")).get("items", [])
    for it in items:
        it["_kw"] = [norm(k) for k in it.get("keywords", []) if k]
    return items


class RuleEngine:
    def __init__(self, catalog: Catalog, faq: list[dict] | None = None):
        self.catalog = catalog
        self.faq = load_faq() if faq is None else faq
        # bảng tra tên bang
        self.region_names: list[tuple[str, str]] = []
        for r in catalog.regions:
            names = {norm(r["name"]), norm(r["name_display"].split("(")[0])}
            names |= {norm(x) for x in re.findall(r"\(([^)]*)\)", r["name_display"])}
            for n in names:
                self.region_names.append((n.replace("-", " ").replace("–", " "), r["code"]))
        self.region_names += list(REGION_ALIASES.items())
        self.region_names.sort(key=lambda x: -len(x[0]))
        # bảng tra thành phố (bỏ các thành phố trùng tên bang: Berlin, Hamburg, Bremen)
        region_like = {n for n, _ in self.region_names}
        cities = {norm(s["city"]) for s in catalog.schools if s.get("city")}
        self.cities = sorted((c for c in cities if c not in region_like and len(c) >= 3), key=len, reverse=True)
        # tên trường để nhận diện "chi tiết trường X"
        self.school_names: list[tuple[str, str]] = []
        for s in catalog.schools:
            for n in {norm(s.get("display_name")), norm(s.get("name"))}:
                if len(n) >= 4:
                    self.school_names.append((n, s["code"]))
        self.school_names.sort(key=lambda x: -len(x[0]))

    # ---------- phân tích câu ----------
    def _region(self, q: str) -> str | None:
        qq = q.replace("-", " ")
        for name, code in self.region_names:
            if name and _has(qq, name):
                return code
        return None

    def _city(self, q: str) -> str | None:
        for alias, city in CITY_ALIASES.items():
            if _has(q, alias):
                return city
        for c in self.cities:
            if _has(q, c):
                return c
        return None

    def _school(self, q: str) -> str | None:
        for name, code in self.school_names:
            if _has(q, name):
                return code
        return None

    @staticmethod
    def _pick(q: str, table) -> str:
        for value, words in table:
            if any(_has(q, w) for w in words):
                return value
        return ""

    def _faq(self, q: str) -> tuple[dict | None, int]:
        best, score = None, 0
        for it in self.faq:
            s = sum(len(k) for k in it["_kw"] if _has(q, k))
            if s > score:
                best, score = it, s
        return best, score

    # ---------- trả lời ----------
    def reply(self, history: list[dict]) -> Reply:
        raw = history[-1]["content"]
        q = norm(raw)
        q = re.sub(r"[^\w\s@.+-]", " ", q)
        q = re.sub(r"\s+", " ", q).strip()

        if PHONE_RE.search(raw) or EMAIL_RE.search(raw):
            return Reply("Cảm ơn bạn! Mình đã ghi lại thông tin liên hệ, tư vấn viên Viger sẽ liên hệ bạn sớm nhất. "
                         "Trong lúc chờ, bạn có thể hỏi tiếp về trường hoặc bang bạn quan tâm.",
                         ["Học phí ở Đức", "Hồ sơ visa du học"], "lead")

        region = self._region(q)
        city = self._city(q)
        stype = self._pick(q, TYPE_WORDS)
        owner = self._pick(q, OWNER_WORDS)
        school = self._school(q)
        faq, faq_score = self._faq(q)
        asks_school = any(_has(q, w) for w in SCHOOL_WORDS)
        has_filter = bool(stype or owner or city)

        if school and not (stype or owner):  # tên trường thường chứa tên thành phố (RWTH Aachen)
            return self._school_detail(school)
        if region and faq and faq["id"] == "chi_phi_song" and not asks_school:
            return self._region_info(region)  # "chi phí sống ở Bayern" -> thông tin bang có mức chi phí
        if faq and faq_score and not has_filter and not (asks_school and region):
            return Reply(faq["answer"], faq.get("suggestions") or DEFAULT_SUGGESTIONS, f"faq:{faq['id']}")
        if region and not has_filter and not asks_school and (
                any(_has(q, w) for w in REGION_INFO_WORDS) or _has(q, "bang") or len(q.split()) <= 3):
            return self._region_info(region)
        if region or has_filter or asks_school:
            if not (region or has_filter):
                return Reply("Bạn muốn tìm trường ở bang hay thành phố nào? Bạn cũng có thể chọn loại trường "
                             "(nghiên cứu / ứng dụng) và công lập hay tư thục.",
                             ["Trường ở Berlin", "Trường ở München", "ĐH Ứng dụng ở Sachsen"], "ask_filter")
            return self._search(region, city, stype, owner, count_only=_has(q, "bao nhieu"))
        if GREETING.search(q):
            return Reply("Chào bạn! Mình là trợ lý tư vấn du học Đức của Viger. Mình có thể giúp bạn tìm trường "
                         "đại học theo bang, thành phố, loại trường và trả lời về học phí, visa, tiếng Đức.",
                         DEFAULT_SUGGESTIONS, "greeting")
        if THANKS.search(q):
            return Reply("Không có gì ạ! Bạn cần hỏi thêm gì cứ nhắn mình nhé.", DEFAULT_SUGGESTIONS, "thanks")
        return Reply(FALLBACK, DEFAULT_SUGGESTIONS, "fallback")

    def _school_detail(self, code: str) -> Reply:
        d = self.catalog.school_detail(code)
        lines = [f"**{d['ten']}**" + (f" ({d['ten_day_du']})" if d["ten_day_du"] != d["ten"] else ""),
                 f"- Loại: {d['loai']}, {d['hinh_thuc']}",
                 f"- Bang / thành phố: {d['bang']} – {d['thanh_pho']}"]
        if d.get("so_sinh_vien"):
            lines.append(f"- Số sinh viên: {int(d['so_sinh_vien']):,}".replace(",", "."))
        if d.get("nam_thanh_lap"):
            lines.append(f"- Thành lập: {d['nam_thanh_lap']}")
        if d.get("dia_chi"):
            lines.append(f"- Địa chỉ: {d['dia_chi']}")
        if d.get("website"):
            lines.append(f"- Website: {d['website']}")
        region = self.catalog.find_region(d["bang"].split(" (")[0])
        sugg = [f"Bang {region['name']} thế nào"] if region else []
        return Reply("\n".join(lines), sugg + ["Học phí ở Đức", "Tư vấn trực tiếp"], "school_detail")

    def _region_info(self, code: str) -> Reply:
        i = self.catalog.region_info(code)
        cost = {1: "thấp", 2: "khá thấp", 3: "trung bình", 4: "khá cao", 5: "cao"}.get(int(i["cost_level"] or 0), "")
        text = (f"**Bang {i['name_display']}**\n- Thủ phủ: {i['capital']}\n"
                f"- Dân số: {int(i['population']):,}".replace(",", ".") +
                f"\n- Lĩnh vực nổi bật: {i['highlights']}\n- Đặc điểm: {i['description']}")
        if cost:
            text += f"\n- Mức chi phí sinh hoạt: {cost}"
        if i.get("note"):
            text += f" ({i['note']})"
        text += f"\n- Có {i['so_truong']} trường đại học trong dữ liệu của Viger."
        name = self.catalog.region_by_code[code]["name"]
        return Reply(text, [f"Trường công lập ở {name}", f"ĐH Ứng dụng ở {name}", f"ĐH Nghiên cứu ở {name}"],
                     "region_info")

    def _search(self, region, city, stype, owner, count_only=False) -> Reply:
        res = self.catalog.search_schools(region=region or "", city=city or "", school_type=stype,
                                          ownership=owner, limit=5)
        parts = []
        if stype:
            parts.append({"HAW": "ĐH Ứng dụng", "UNI": "ĐH Nghiên cứu", "ART": "ĐH Nghệ thuật"}[stype])
        else:
            parts.append("trường")
        if owner:
            parts.append({"public": "công lập", "private": "tư thục", "church": "thuộc nhà thờ"}[owner])
        where = ""
        if city:
            where = f" ở {next((s['city'] for s in self.catalog.schools if norm(s['city']) == city), city)}"
        if region:
            where += f"{' (' if city else ' ở '}{self.catalog.region_by_code[region]['name']}{')' if city else ''}"
        what = " ".join(parts) + where
        if not res["total"]:
            return Reply(f"Mình không tìm thấy {what} trong dữ liệu. Bạn thử bỏ bớt điều kiện, hoặc để lại số "
                         "điện thoại để tư vấn viên tìm giúp nhé.", DEFAULT_SUGGESTIONS, "search_empty")
        head = f"Có {res['total']} {what}."
        if count_only:
            return Reply(head, [f"Liệt kê {what}"], "search_count")
        lines = [f"- **{s['ten']}** – {s['thanh_pho']}, {s['loai']}, {s['hinh_thuc']}"
                 + (f" – {s['website']}" if s.get("website") else "") for s in res["schools"]]
        tail = ""
        if res["total"] > len(res["schools"]):
            tail = f"\n(Hiển thị {len(res['schools'])} trường tiêu biểu. Bạn thêm điều kiện để thu hẹp, hoặc hỏi tư vấn viên.)"
        sugg = []
        if not stype:
            sugg.append(f"ĐH Ứng dụng{where}")
        if not owner:
            sugg.append(f"Trường công lập{where}")
        sugg.append("Tư vấn trực tiếp")
        return Reply(head + " Một số trường:\n" + "\n".join(lines) + tail, sugg, "search")
