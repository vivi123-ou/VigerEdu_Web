import os
from types import SimpleNamespace

os.environ["CHATBOT_ENGINE"] = "rules"
os.environ["CRM_API_TOKEN"] = ""

import pytest
from fastapi.testclient import TestClient

import app as app_module
from chatbot import crm
from chatbot.data import get_catalog, norm
from chatbot.engine import ClaudeEngine
from chatbot.tools import run_tool


@pytest.fixture(scope="module")
def cat():
    return get_catalog()


def test_counts(cat):
    assert len(cat.regions) == 16
    assert len(cat.schools) == 390


def test_norm():
    assert norm("München") == "munchen"
    assert norm("Đại học") == "dai hoc"


def test_find_region_by_name_code_and_alias(cat):
    assert cat.find_region("BY")["code"] == "BY"
    assert cat.find_region("bayern")["code"] == "BY"
    assert cat.find_region("Bavaria")["code"] == "BY"
    assert cat.find_region("xyz") is None


def test_search_filters(cat):
    res = cat.search_schools(region="Bayern", school_type="HAW", ownership="public", limit=50)
    assert res["total"] > 0
    assert len(res["schools"]) <= 30
    assert all(s["loai"] == "ĐH Ứng dụng" and s["hinh_thuc"] == "Công lập" for s in res["schools"])
    assert cat.search_schools(region="Atlantis")["total"] == 0


def test_school_detail(cat):
    d = cat.school_detail("RWTH Aachen")
    assert d["code"] == "T001" and "rwth" in d["website"]


def test_run_tool_unknown_and_errors(cat):
    _, err = run_tool(cat, "khong_co", {})
    assert err
    out, err = run_tool(cat, "thong_tin_bang", {"region": "Berlin"})
    assert not err and "Berlin" in out


def test_holland_without_config(cat, monkeypatch):
    monkeypatch.delenv("HOLLAND_API_TOKEN", raising=False)
    out, err = run_tool(cat, "ket_qua_holland", {"token": "ABCD1234"})
    assert "Chưa cấu hình" in out


def test_chat_endpoint_mock_and_session():
    client = TestClient(app_module.app)
    r = client.post("/chat", json={"message": "Trường ứng dụng công lập ở Bayern?"})
    assert r.status_code == 200
    data = r.json()
    assert data["reply"].startswith("Có ") and data["suggestions"]
    r2 = client.post("/chat", json={"message": "cảm ơn", "session_code": data["session_code"]})
    assert r2.json()["session_code"] == data["session_code"]
    assert client.get("/health").json()["schools"] == 390


def test_chat_validation():
    client = TestClient(app_module.app)
    assert client.post("/chat", json={"message": ""}).status_code == 422
    assert client.post("/chat", json={"message": "x" * 1001}).status_code == 422


def test_crm_lead_payload():
    history = [{"role": "user", "content": "Trường ở Berlin"}, {"role": "assistant", "content": "Có 32 trường"},
               {"role": "user", "content": "sđt 0912 345 678"}]
    p = crm.build_lead({"phone": "0912345678", "x": 1}, history, "CB1")
    assert p["schema_version"] == "lead/2" and p["channel"] == "chatbot" and p["event_id"]
    assert p["consent"]["agreed"] is True and p["consent"]["text"]
    assert p["contact"] == {"phone": "0912345678"}
    assert "Khách: Trường ở Berlin" in p["extra_fields"]["loi_nhan"]
    assert len(p["extra_fields"]["loi_nhan"]) <= 2000


def test_crm_not_configured_returns_false(monkeypatch):
    monkeypatch.delenv("CRM_API_URL", raising=False)
    assert crm.send_lead(crm.build_lead({"phone": "0912345678"}, [], "CB1")) is False


def test_crm_send_lead_posts_with_token(monkeypatch):
    sent = []
    monkeypatch.setenv("CRM_API_URL", "http://crm.test/api/v1/")
    monkeypatch.setenv("CRM_API_TOKEN", "tok")
    monkeypatch.setattr(crm.httpx, "post", lambda url, **kw: sent.append((url, kw)) or SimpleNamespace(status_code=201))
    assert crm.send_lead(crm.build_lead({"phone": "0912345678"}, [], "CB1")) is True
    assert sent[0][0] == "http://crm.test/api/v1/leads"
    assert sent[0][1]["headers"]["X-Api-Token"] == "tok"


def test_crm_does_not_retry_on_422(monkeypatch):
    calls = []
    monkeypatch.setenv("CRM_API_URL", "http://crm.test/api/v1")
    monkeypatch.setenv("CRM_API_TOKEN", "tok")
    monkeypatch.setattr(crm.httpx, "post", lambda url, **kw: calls.append(1) or SimpleNamespace(status_code=422))
    assert crm.send_lead(crm.build_lead({"phone": "0912345678"}, [], "CB1")) is False
    assert len(calls) == 1


def test_chat_sends_lead_once_per_new_phone(monkeypatch):
    sent = []
    monkeypatch.setattr(app_module.crm, "send_lead", lambda payload: sent.append(payload) or True)
    client = TestClient(app_module.app)
    code = client.post("/chat", json={"message": "Trường ở Berlin"}).json()["session_code"]
    client.post("/chat", json={"message": "sđt em 0912 345 678", "session_code": code})
    client.post("/chat", json={"message": "lại là 0912345678", "session_code": code})
    assert len(sent) == 1
    assert sent[0]["contact"]["phone"] == "0912345678"
    assert "Trường ở Berlin" in sent[0]["extra_fields"]["loi_nhan"]


def test_claude_engine_tool_loop(cat):
    """Giả lập API Claude: lượt 1 gọi công cụ, lượt 2 trả lời."""
    calls = []

    def create(**kw):
        calls.append(kw)
        if len(calls) == 1:
            return SimpleNamespace(stop_reason="tool_use", content=[
                SimpleNamespace(type="tool_use", id="t1", name="tim_truong", input={"region": "Berlin", "limit": 2})])
        return SimpleNamespace(stop_reason="end_turn", content=[SimpleNamespace(type="text", text="Có vài trường ở Berlin.")])

    fake = SimpleNamespace(beta=SimpleNamespace(messages=SimpleNamespace(create=create)))
    eng = ClaudeEngine(cat, client=fake)
    assert eng.reply([{"role": "user", "content": "Trường ở Berlin"}]).text == "Có vài trường ở Berlin."
    tool_result = calls[1]["messages"][-1]["content"][0]
    assert tool_result["type"] == "tool_result" and "Berlin" in tool_result["content"]


def test_claude_engine_refusal(cat):
    fake = SimpleNamespace(beta=SimpleNamespace(messages=SimpleNamespace(
        create=lambda **kw: SimpleNamespace(stop_reason="refusal", content=[]))))
    assert "tư vấn viên" in ClaudeEngine(cat, client=fake).reply([{"role": "user", "content": "?"}]).text
