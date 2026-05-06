from fastapi import FastAPI, APIRouter, Query
from fastapi.responses import FileResponse
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
import random
import math
from pathlib import Path
from pydantic import BaseModel, Field
from typing import List, Optional
from datetime import datetime, timezone


ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

app = FastAPI(title="GOLDEN – Geospatial Model for Distance Education")
api_router = APIRouter(prefix="/api")

logging.basicConfig(level=logging.INFO, format='%(asctime)s %(levelname)s %(message)s')
logger = logging.getLogger("golden")


# ---------------- Models ---------------- #
class Course(BaseModel):
    id: str
    code: str
    name: str
    category: str


class Grade(BaseModel):
    course_id: str
    grade: float  # 0-100


class Student(BaseModel):
    id: str
    first_name: str
    last_name: str
    email: str
    ip: str
    country: str
    country_code: str
    city: str
    lat: float
    lng: float
    grades: List[Grade]
    overall_grade: float
    enrolled_courses: List[str]
    last_access: str


class StatsResponse(BaseModel):
    total_students: int
    countries_covered: int
    avg_grade: float
    active_courses: int
    top_countries: List[dict]
    grade_distribution: List[dict]


# ---------------- Seed data ---------------- #
COURSES_SEED = [
    {"code": "CS101", "name": "Introduction to Computer Science", "category": "Computer Science"},
    {"code": "CS305", "name": "Data Structures & Algorithms", "category": "Computer Science"},
    {"code": "MATH210", "name": "Linear Algebra", "category": "Mathematics"},
    {"code": "STAT240", "name": "Statistics for Data Science", "category": "Mathematics"},
    {"code": "GIS410", "name": "Geographic Information Systems", "category": "Geography"},
    {"code": "ENG150", "name": "Academic Writing", "category": "Humanities"},
    {"code": "PHY220", "name": "Classical Mechanics", "category": "Physics"},
    {"code": "BIO330", "name": "Molecular Biology", "category": "Biology"},
]

# Realistic city anchors (lat, lng, country, country_code, city)
CITY_ANCHORS = [
    (40.7128, -74.0060, "United States", "US", "New York"),
    (34.0522, -118.2437, "United States", "US", "Los Angeles"),
    (41.8781, -87.6298, "United States", "US", "Chicago"),
    (29.7604, -95.3698, "United States", "US", "Houston"),
    (51.5074, -0.1278, "United Kingdom", "GB", "London"),
    (53.4808, -2.2426, "United Kingdom", "GB", "Manchester"),
    (48.8566, 2.3522, "France", "FR", "Paris"),
    (45.7640, 4.8357, "France", "FR", "Lyon"),
    (52.5200, 13.4050, "Germany", "DE", "Berlin"),
    (48.1351, 11.5820, "Germany", "DE", "Munich"),
    (40.4168, -3.7038, "Spain", "ES", "Madrid"),
    (41.3851, 2.1734, "Spain", "ES", "Barcelona"),
    (41.9028, 12.4964, "Italy", "IT", "Rome"),
    (45.4642, 9.1900, "Italy", "IT", "Milan"),
    (55.7558, 37.6173, "Russia", "RU", "Moscow"),
    (28.6139, 77.2090, "India", "IN", "New Delhi"),
    (19.0760, 72.8777, "India", "IN", "Mumbai"),
    (12.9716, 77.5946, "India", "IN", "Bangalore"),
    (39.9042, 116.4074, "China", "CN", "Beijing"),
    (31.2304, 121.4737, "China", "CN", "Shanghai"),
    (22.3193, 114.1694, "China", "CN", "Hong Kong"),
    (35.6762, 139.6503, "Japan", "JP", "Tokyo"),
    (34.6937, 135.5023, "Japan", "JP", "Osaka"),
    (37.5665, 126.9780, "South Korea", "KR", "Seoul"),
    (1.3521, 103.8198, "Singapore", "SG", "Singapore"),
    (-33.8688, 151.2093, "Australia", "AU", "Sydney"),
    (-37.8136, 144.9631, "Australia", "AU", "Melbourne"),
    (-23.5505, -46.6333, "Brazil", "BR", "São Paulo"),
    (-22.9068, -43.1729, "Brazil", "BR", "Rio de Janeiro"),
    (19.4326, -99.1332, "Mexico", "MX", "Mexico City"),
    (-34.6037, -58.3816, "Argentina", "AR", "Buenos Aires"),
    (30.0444, 31.2357, "Egypt", "EG", "Cairo"),
    (-1.2921, 36.8219, "Kenya", "KE", "Nairobi"),
    (6.5244, 3.3792, "Nigeria", "NG", "Lagos"),
    (-26.2041, 28.0473, "South Africa", "ZA", "Johannesburg"),
    (25.2048, 55.2708, "United Arab Emirates", "AE", "Dubai"),
    (41.0082, 28.9784, "Turkey", "TR", "Istanbul"),
    (43.6532, -79.3832, "Canada", "CA", "Toronto"),
    (49.2827, -123.1207, "Canada", "CA", "Vancouver"),
    (59.3293, 18.0686, "Sweden", "SE", "Stockholm"),
    (52.3676, 4.9041, "Netherlands", "NL", "Amsterdam"),
    (50.0755, 14.4378, "Czechia", "CZ", "Prague"),
    (47.4979, 19.0402, "Hungary", "HU", "Budapest"),
    (37.9838, 23.7275, "Greece", "GR", "Athens"),
    (24.7136, 46.6753, "Saudi Arabia", "SA", "Riyadh"),
    (13.7563, 100.5018, "Thailand", "TH", "Bangkok"),
    (14.5995, 120.9842, "Philippines", "PH", "Manila"),
    (-6.2088, 106.8456, "Indonesia", "ID", "Jakarta"),
    (3.1390, 101.6869, "Malaysia", "MY", "Kuala Lumpur"),
    (33.6844, 73.0479, "Pakistan", "PK", "Islamabad"),
]

FIRST_NAMES = ["Alex", "Maria", "Li", "Aisha", "João", "Priya", "Yuki", "Omar", "Sofia", "Chen",
               "Emma", "Raj", "Fatima", "Lucas", "Nina", "Carlos", "Hana", "Tom", "Zara", "Diego",
               "Anya", "Kwame", "Ines", "Sven", "Lena", "Kai", "Amir", "Nora", "Theo", "Isla"]
LAST_NAMES = ["Smith", "García", "Wang", "Khan", "Silva", "Patel", "Tanaka", "Hassan", "Rossi", "Liu",
              "Müller", "Dubois", "Ivanov", "Kim", "Okafor", "Nguyen", "Rahman", "Kowalski", "Costa", "Singh"]


def jitter(lat, lng, km=60):
    # ~1 degree lat = 111 km
    dlat = random.uniform(-km, km) / 111.0
    dlng = random.uniform(-km, km) / (111.0 * max(0.2, math.cos(math.radians(lat))))
    return lat + dlat, lng + dlng


def random_ip():
    return f"{random.randint(11,223)}.{random.randint(0,255)}.{random.randint(0,255)}.{random.randint(1,254)}"


async def seed_if_empty():
    courses_count = await db.courses.count_documents({})
    if courses_count == 0:
        courses = []
        for i, c in enumerate(COURSES_SEED, 1):
            courses.append({
                "id": f"c{i}",
                "code": c["code"],
                "name": c["name"],
                "category": c["category"],
            })
        await db.courses.insert_many([c.copy() for c in courses])
        logger.info(f"Seeded {len(courses)} courses")

    students_count = await db.students.count_documents({})
    if students_count == 0:
        random.seed(42)
        students = []
        courses_list = await db.courses.find({}, {"_id": 0}).to_list(100)
        for i in range(1, 451):
            anchor = random.choice(CITY_ANCHORS)
            lat, lng = jitter(anchor[0], anchor[1])
            fn = random.choice(FIRST_NAMES)
            ln = random.choice(LAST_NAMES)
            enrolled = random.sample([c["id"] for c in courses_list], k=random.randint(2, 5))
            # Give each country a slight performance bias so thematic maps look meaningful
            country_bias = (hash(anchor[3]) % 20) - 10  # -10..+9
            grades = []
            for cid in enrolled:
                base = random.gauss(72 + country_bias, 12)
                g = max(0, min(100, round(base, 1)))
                grades.append({"course_id": cid, "grade": g})
            overall = round(sum(g["grade"] for g in grades) / len(grades), 2)
            students.append({
                "id": f"s{i:04d}",
                "first_name": fn,
                "last_name": ln,
                "email": f"{fn.lower()}.{ln.lower()}{i}@university.edu",
                "ip": random_ip(),
                "country": anchor[2],
                "country_code": anchor[3],
                "city": anchor[4],
                "lat": round(lat, 5),
                "lng": round(lng, 5),
                "grades": grades,
                "overall_grade": overall,
                "enrolled_courses": enrolled,
                "last_access": datetime.now(timezone.utc).isoformat(),
            })
        await db.students.insert_many(students)
        logger.info(f"Seeded {len(students)} students")


@app.on_event("startup")
async def on_startup():
    await seed_if_empty()


# ---------------- Endpoints ---------------- #
@api_router.get("/")
async def root():
    return {"service": "GOLDEN", "description": "Geospatial Model for Distance Education"}


@api_router.get("/courses", response_model=List[Course])
async def get_courses():
    courses = await db.courses.find({}, {"_id": 0}).to_list(500)
    return courses


@api_router.get("/students")
async def get_students(
    course_id: Optional[str] = Query(None),
    min_grade: float = Query(0),
    max_grade: float = Query(100),
):
    query = {}
    if course_id:
        query["enrolled_courses"] = course_id
    docs = await db.students.find(query, {"_id": 0}).to_list(2000)

    results = []
    for s in docs:
        if course_id:
            grade = next((g["grade"] for g in s["grades"] if g["course_id"] == course_id), None)
            if grade is None:
                continue
            effective_grade = grade
        else:
            effective_grade = s["overall_grade"]
        if effective_grade < min_grade or effective_grade > max_grade:
            continue
        results.append({
            "id": s["id"],
            "first_name": s["first_name"],
            "last_name": s["last_name"],
            "email": s["email"],
            "ip": s["ip"],
            "country": s["country"],
            "country_code": s["country_code"],
            "city": s["city"],
            "lat": s["lat"],
            "lng": s["lng"],
            "effective_grade": round(effective_grade, 2),
            "overall_grade": s["overall_grade"],
            "enrolled_courses": s["enrolled_courses"],
        })
    return results


@api_router.get("/stats", response_model=StatsResponse)
async def get_stats(course_id: Optional[str] = Query(None)):
    query = {}
    if course_id:
        query["enrolled_courses"] = course_id
    docs = await db.students.find(query, {"_id": 0}).to_list(2000)
    courses = await db.courses.find({}, {"_id": 0}).to_list(500)

    if not docs:
        return StatsResponse(
            total_students=0, countries_covered=0, avg_grade=0,
            active_courses=len(courses), top_countries=[], grade_distribution=[]
        )

    # per-country
    country_data = {}
    grades_for_avg = []
    for s in docs:
        if course_id:
            grade = next((g["grade"] for g in s["grades"] if g["course_id"] == course_id), None)
            if grade is None:
                continue
        else:
            grade = s["overall_grade"]
        grades_for_avg.append(grade)
        c = s["country"]
        country_data.setdefault(c, {"count": 0, "grade_sum": 0})
        country_data[c]["count"] += 1
        country_data[c]["grade_sum"] += grade

    top_countries = sorted(
        [{"country": k, "students": v["count"], "avg_grade": round(v["grade_sum"]/v["count"], 1)} for k, v in country_data.items()],
        key=lambda x: -x["students"]
    )[:10]

    # grade distribution 0-100 in 10 buckets
    buckets = [0] * 10
    for g in grades_for_avg:
        idx = min(9, int(g // 10))
        buckets[idx] += 1
    grade_distribution = [{"range": f"{i*10}-{i*10+9}", "count": buckets[i]} for i in range(10)]

    return StatsResponse(
        total_students=len(grades_for_avg),
        countries_covered=len(country_data),
        avg_grade=round(sum(grades_for_avg) / len(grades_for_avg), 2) if grades_for_avg else 0,
        active_courses=len(courses),
        top_countries=top_countries,
        grade_distribution=grade_distribution,
    )


@api_router.get("/hotspots")
async def get_hotspots(course_id: Optional[str] = Query(None), grade_threshold: float = Query(0)):
    """Return weighted points for Leaflet.heat; weight = inverse of grade for struggling-student hotspots."""
    query = {}
    if course_id:
        query["enrolled_courses"] = course_id
    docs = await db.students.find(query, {"_id": 0}).to_list(2000)
    points = []
    for s in docs:
        if course_id:
            grade = next((g["grade"] for g in s["grades"] if g["course_id"] == course_id), None)
            if grade is None:
                continue
        else:
            grade = s["overall_grade"]
        if grade < grade_threshold:
            continue
        # Higher weight for struggling students (low grade) to surface support-needs hotspots
        weight = max(0.1, (100 - grade) / 100.0)
        points.append([s["lat"], s["lng"], round(weight, 3)])
    return {"points": points, "count": len(points)}


@api_router.get("/plugin-info")
async def plugin_info():
    return {
        "name": "local_golden",
        "display_name": "GOLDEN – Geospatial Model for Distance Education",
        "version": "2026020100",
        "moodle_min": "4.3",
        "download": "/local_golden.zip",
        "description": "Admin-only WebGIS plugin that geolocates Moodle users by IP and renders cluster, choropleth and hotspot maps.",
    }


app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()
