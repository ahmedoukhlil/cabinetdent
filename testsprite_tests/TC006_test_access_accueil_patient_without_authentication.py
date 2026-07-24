import requests

BASE_URL = "http://localhost:80/cabinetsavwa/public"
TIMEOUT = 30

def test_access_accueil_patient_without_authentication():
    url = f"{BASE_URL}/accueil-patient"
    try:
        response = requests.get(url, allow_redirects=False, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request to {url} failed: {e}"
    
    # Assert that the response status code is 302 (redirect)
    assert response.status_code == 302, f"Expected 302 redirect, got {response.status_code}"

    # Assert that the Location header redirects to /login
    location = response.headers.get("Location", "")
    assert location.endswith("/login"), f"Expected redirect to /login, got {location}"

test_access_accueil_patient_without_authentication()