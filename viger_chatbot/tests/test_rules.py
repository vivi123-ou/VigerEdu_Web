import pytest

from chatbot.data import get_catalog
from chatbot.engine import make_engine
from chatbot.rules import RuleEngine


@pytest.fixture(scope="module")
def eng():
    return RuleEngine(get_catalog())


def ask(eng, text):
    return eng.reply([{"role": "user", "content": text}])


@pytest.mark.parametrize("text,intent", [
    ("Xin chào", "greeting"),
    ("truong dh ung dung cong lap o bayern", "search"),
    ("Trường ở Munich", "search"),
    ("Có bao nhiêu trường tư thục ở NRW?", "search_count"),
    ("bang Sachsen thế nào", "region_info"),
    ("Bavaria", "region_info"),
    ("chi phí sống ở Bayern", "region_info"),
    ("học phí ở Đức bao nhiêu", "faq:hoc_phi"),
    ("visa cần gì", "faq:visa"),
    ("Studienkolleg là gì", "faq:du_bi"),
    ("tôi muốn học ngành cơ khí", "faq:nganh"),
    ("RWTH Aachen", "school_detail"),
    ("TU Berlin có gì", "school_detail"),
    ("sdt cua em 0912 345 678", "lead"),
    ("email em la a.b@gmail.com", "lead"),
    ("cảm ơn", "thanks"),
    ("asdfgh", "fallback"),
    ("cho em xem danh sách trường", "ask_filter"),
])
def test_intents(eng, text, intent):
    assert ask(eng, text).intent == intent


def test_search_respects_filters(eng):
    r = ask(eng, "ĐH ứng dụng công lập ở Bayern")
    assert "Bayern" in r.text and "ĐH Ứng dụng" in r.text
    assert "Tư thục" not in r.text and "ĐH Nghiên cứu" not in r.text


def test_city_aliases(eng):
    assert "München" in ask(eng, "trường ở munich").text
    assert "Köln" in ask(eng, "trường ở cologne").text


def test_empty_result(eng):
    assert ask(eng, "trường nghệ thuật tư thục ở Saarland").intent in ("search_empty", "search")


def test_suggestions_always_present(eng):
    for t in ["Xin chào", "bang Berlin", "asdfgh", "trường ở Berlin"]:
        assert ask(eng, t).suggestions


def test_make_engine_defaults_to_rules(monkeypatch):
    monkeypatch.setenv("ANTHROPIC_API_KEY", "x")
    monkeypatch.delenv("CHATBOT_ENGINE", raising=False)
    assert isinstance(make_engine(get_catalog()), RuleEngine)
