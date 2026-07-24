import requests

BASE_URL = "http://localhost:80/cabinetsavwa/public"
TIMEOUT = 30

def test_access_patients_routes_without_authentication():
    session = requests.Session()
    try:
        # Test GET /patients without auth
        response_patients = session.get(f"{BASE_URL}/patients", allow_redirects=False, timeout=TIMEOUT)
        assert response_patients.status_code == 302, f"Expected 302 redirect, got {response_patients.status_code}"
        location_patients = response_patients.headers.get("Location", "")
        assert location_patients.endswith("/login") or location_patients == "/login", f"Expected redirect location to /login, got {location_patients}"

        # Test GET /patients/create without auth
        response_create = session.get(f"{BASE_URL}/patients/create", allow_redirects=False, timeout=TIMEOUT)
        assert response_create.status_code == 302, f"Expected 302 redirect, got {response_create.status_code}"
        location_create = response_create.headers.get("Location", "")
        assert location_create.endswith("/login") or location_create == "/login", f"Expected redirect location to /login, got {location_create}"

    finally:
        session.close()

test_access_patients_routes_without_authentication()