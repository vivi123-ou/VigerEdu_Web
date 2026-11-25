"""Nạp danh mục bang + trường từ file Excel đã chuẩn hóa và tra cứu trong bộ nhớ."""
from __future__ import annotations

import unicodedata
from functools import lru_cache
from pathlib import Path

from openpyxl import load_workbook

DEFAULT_FILE = Path(__file__).resolve().parent.parent / "data" / "DU_LIEU_16_BANG_390_TRUONG_DA_CHUAN_HOA.xlsx"


def norm(text) -> str:
    """Chữ thường, bỏ dấu (cả tiếng Việt lẫn ä ö ü ß) để so khớp."""
    s = str(text or "").lower().replace("đ", "d").replace("ß", "ss")
    s = unicodedata.normalize("NFKD", s)
    return "".join(c for c in s if not unicodedata.combining(c)).strip()


def _read_sheet(wb, name: str) -> list[dict]:
    rows = wb[name].iter_rows(values_only=True)
    header = [str(h).strip() for h in next(rows)]
    out = []
    for r in rows:
        if not any(v not in (None, "") for v in r):
            continue
        out.append({h: ("" if v is None else v) for h, v in zip(header, r)})
    return out


class Catalog:
    def __init__(self, regions: list[dict], schools: list[dict]):
        self.regions = [r for r in regions if str(r.get("is_active", 1)) in ("1", "True", "true")]
        self.schools = [s for s in schools if str(s.get("is_active", 1)) in ("1", "True", "true")]
        self.region_by_code = {r["code"]: r for r in self.regions}

    @classmethod
    def from_excel(cls, path: Path | str = DEFAULT_FILE) -> "Catalog":
        wb = load_workbook(path, read_only=True, data_only=True)
        return cls(_read_sheet(wb, "DM_KHU_VUC"), _read_sheet(wb, "DM_TRUONG"))

    # ---- bang ----
    def find_region(self, query: str) -> dict | None:
        q = norm(query)
        if not q:
            return None
        for r in self.regions:
            if q == norm(r["code"]):
                return r
        for r in self.regions:
            names = [norm(r.get("name")), norm(r.get("name_display")), norm(r.get("capital"))]
            if any(q == n or (len(q) >= 4 and q in n) for n in names):
                return r
        return None

    def region_info(self, query: str) -> dict | None:
        r = self.find_region(query)
        if not r:
            return None
        keys = ["code", "name_display", "capital", "population", "area_km2", "gdp_per_capita_eur",
                "hdi", "highlights", "description", "cost_level", "note"]
        info = {k: r.get(k, "") for k in keys}
        info["so_truong"] = sum(1 for s in self.schools if s.get("region_code") == r["code"])
        return info

    # ---- trường ----
    def search_schools(self, region: str = "", city: str = "", school_type: str = "",
                       ownership: str = "", keyword: str = "", limit: int = 10) -> dict:
        res = self.schools
        if region:
            r = self.find_region(region)
            if not r:
                return {"total": 0, "schools": [], "note": f"Không tìm thấy bang '{region}'"}
            res = [s for s in res if s.get("region_code") == r["code"]]
        if city:
            c = norm(city)
            res = [s for s in res if c in norm(s.get("city"))]
        if school_type:
            t = school_type.upper()
            res = [s for s in res if str(s.get("type", "")).upper() == t]
        if ownership:
            o = ownership.lower()
            res = [s for s in res if str(s.get("ownership", "")).lower() == o]
        if keyword:
            k = norm(keyword)
            res = [s for s in res if k in norm(" ".join(str(s.get(f, "")) for f in
                                                        ("name", "display_name", "short_name", "city")))]
        res = sorted(res, key=lambda s: (int(s.get("priority") or 9), -int(s.get("students") or 0)))
        limit = max(1, min(int(limit or 10), 30))
        return {"total": len(res), "schools": [self.school_brief(s) for s in res[:limit]]}

    def school_brief(self, s: dict) -> dict:
        region = self.region_by_code.get(s.get("region_code"), {})
        return {
            "code": s.get("code"),
            "ten": s.get("display_name") or s.get("name"),
            "ten_day_du": s.get("name"),
            "loai": s.get("type_vi"),
            "hinh_thuc": s.get("ownership_vi"),
            "bang": region.get("name_display", s.get("region_code")),
            "thanh_pho": s.get("city"),
            "so_sinh_vien": s.get("students"),
            "nam_thanh_lap": s.get("founded_year"),
            "website": s.get("website"),
            "chi_phi_song_eur_thang": s.get("living_cost_eur_month") or None,
        }

    def school_detail(self, query: str) -> dict | None:
        q = norm(query)
        for s in self.schools:
            if q in (norm(s.get("code")), norm(s.get("short_name")), norm(s.get("display_name")), norm(s.get("name"))):
                break
        else:
            hits = [s for s in self.schools if q and q in norm(f"{s.get('name')} {s.get('display_name')}")]
            if not hits:
                return None
            s = hits[0]
        d = self.school_brief(s)
        d.update({"dia_chi": f"{s.get('street')}, {s.get('postal_code')} {s.get('city')}".strip(", "),
                  "dien_thoai": s.get("phone"), "quyen_tien_si": s.get("phd_rights")})
        return d


@lru_cache(maxsize=1)
def get_catalog() -> Catalog:
    import os
    return Catalog.from_excel(os.getenv("CHATBOT_DATA_FILE") or DEFAULT_FILE)
