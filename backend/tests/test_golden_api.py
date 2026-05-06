"""Backend API tests for GOLDEN plugin demo."""
import os
import pytest
import requests

BASE_URL = os.environ.get("REACT_APP_BACKEND_URL", "https://geo-distance-edu.preview.emergentagent.com").rstrip("/")
API = f"{BASE_URL}/api"


@pytest.fixture(scope="module")
def session():
    s = requests.Session()
    s.headers.update({"Content-Type": "application/json"})
    return s


# ---------- Courses ----------
def test_get_courses_returns_8(session):
    r = session.get(f"{API}/courses", timeout=30)
    assert r.status_code == 200
    data = r.json()
    assert isinstance(data, list)
    assert len(data) == 8
    sample = data[0]
    for f in ("id", "code", "name", "category"):
        assert f in sample, f"Missing field {f} in course"


# ---------- Students ----------
def test_get_students_returns_450(session):
    r = session.get(f"{API}/students", timeout=60)
    assert r.status_code == 200
    data = r.json()
    assert isinstance(data, list)
    assert len(data) == 450
    s = data[0]
    for f in ("lat", "lng", "effective_grade", "country"):
        assert f in s, f"Missing field {f}"


def test_get_students_course_filter(session):
    r = session.get(f"{API}/students", params={"course_id": "c1"}, timeout=60)
    assert r.status_code == 200
    data = r.json()
    assert 0 < len(data) < 450
    for s in data:
        assert "c1" in s["enrolled_courses"]


def test_get_students_grade_range(session):
    r = session.get(f"{API}/students", params={"min_grade": 80, "max_grade": 100}, timeout=60)
    assert r.status_code == 200
    data = r.json()
    assert len(data) > 0
    for s in data:
        assert 80 <= s["effective_grade"] <= 100


# ---------- Stats ----------
def test_get_stats_overall(session):
    r = session.get(f"{API}/stats", timeout=30)
    assert r.status_code == 200
    d = r.json()
    assert d["total_students"] == 450
    assert d["countries_covered"] > 0
    assert d["avg_grade"] > 0
    assert d["active_courses"] == 8
    assert isinstance(d["top_countries"], list) and len(d["top_countries"]) <= 10
    assert isinstance(d["grade_distribution"], list) and len(d["grade_distribution"]) == 10


def test_get_stats_course_scoped(session):
    r = session.get(f"{API}/stats", params={"course_id": "c2"}, timeout=30)
    assert r.status_code == 200
    d = r.json()
    assert d["total_students"] < 450
    assert d["total_students"] > 0


# ---------- Hotspots ----------
def test_get_hotspots(session):
    r = session.get(f"{API}/hotspots", timeout=30)
    assert r.status_code == 200
    d = r.json()
    assert "points" in d and "count" in d
    assert d["count"] == len(d["points"])
    if d["points"]:
        p = d["points"][0]
        assert len(p) == 3  # [lat, lng, weight]


# ---------- Plugin info ----------
def test_plugin_info(session):
    r = session.get(f"{API}/plugin-info", timeout=30)
    assert r.status_code == 200
    d = r.json()
    assert d["name"] == "local_golden"
    assert "version" in d
    assert d["moodle_min"] == "4.3"


# ---------- Plugin ZIP download ----------
def test_plugin_zip_downloadable(session):
    r = session.get(f"{BASE_URL}/local_golden.zip", timeout=30)
    assert r.status_code == 200
    assert len(r.content) > 1000
    # ZIP magic bytes
    assert r.content[:2] == b"PK"
