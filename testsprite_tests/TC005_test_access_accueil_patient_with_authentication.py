import requests
import re

BASE_URL = "http://localhost:80/cabinetsavwa/public"
TIMEOUT = 30

# Use valid credentials for authentication
VALID_USERNAME = "admin@example.com"
VALID_PASSWORD = "correctpassword"

def test_access_accueil_patient_with_authentication():
    session = requests.Session()
    try:
        # Step 1: GET /login (to get cookies, csrf token)
        login_page_response = session.get(f"{BASE_URL}/login", timeout=TIMEOUT)
        assert login_page_response.status_code == 200, "Login page should return 200 OK"

        # Extract CSRF token from login page
        match = re.search(r'name="_token" value="([^"]+)"', login_page_response.text)
        assert match, "CSRF token not found in login page"
        csrf_token = match.group(1)

        # Step 2: POST /login with valid credentials and CSRF token
        login_payload = {
            "_token": csrf_token,
            "email": VALID_USERNAME,
            "password": VALID_PASSWORD,
        }
        login_headers = {
            "Content-Type": "application/x-www-form-urlencoded",
            "Accept": "text/html"
        }
        login_response = session.post(f"{BASE_URL}/login", data=login_payload, headers=login_headers,
                                      allow_redirects=False, timeout=TIMEOUT)
        assert login_response.status_code == 302, "Login should return a redirect status 302"
        assert "/accueil-patient" in login_response.headers.get("Location", ""), "Login should redirect to /accueil-patient"

        # Step 3: GET /accueil-patient with authenticated session
        accueil_response = session.get(f"{BASE_URL}/accueil-patient", timeout=TIMEOUT)
        assert accueil_response.status_code == 200, "Authenticated access to /accueil-patient should return 200 OK"
        text_lower = accueil_response.text.lower()
        # Check for Livewire page indicator in HTML content roughly
        assert "livewire" in text_lower or "accueilpatient" in text_lower, \
            "Response should contain 'livewire' or 'AccueilPatient' indicating the Livewire page"

    finally:
        # Step 4: POST /logout with authenticated session to clear session
        # Need to get fresh CSRF token for logout (usually from a page or embed token)
        # Here, attempt GET /accueil-patient to get fresh token
        page_response = session.get(f"{BASE_URL}/accueil-patient", timeout=TIMEOUT)
        if page_response.status_code == 200:
            match = re.search(r'name="_token" value="([^"]+)"', page_response.text)
            if match:
                logout_csrf_token = match.group(1)
            else:
                logout_csrf_token = csrf_token  # fallback to previous token
        else:
            logout_csrf_token = csrf_token

        logout_payload = {
            "_token": logout_csrf_token
        }
        logout_headers = {
            "Content-Type": "application/x-www-form-urlencoded",
            "Accept": "text/html"
        }
        logout_response = session.post(f"{BASE_URL}/logout", data=logout_payload, headers=logout_headers, allow_redirects=False, timeout=TIMEOUT)
        assert logout_response.status_code == 302, "Logout should return a redirect status 302"
        assert "/login" in logout_response.headers.get("Location", ""), "Logout should redirect to /login"


test_access_accueil_patient_with_authentication()
