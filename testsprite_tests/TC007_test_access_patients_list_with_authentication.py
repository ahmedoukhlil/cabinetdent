import requests
from bs4 import BeautifulSoup

BASE_URL = "http://localhost:80/cabinetsavwa/public"
LOGIN_URL = f"{BASE_URL}/login"
PATIENTS_URL = f"{BASE_URL}/patients"
TIMEOUT = 30

# Replace these with valid test user credentials existing in the system
VALID_USER_CREDENTIALS = {
    "login": "testuser@example.com",
    "password": "CorrectPassword123"
}

def test_access_patients_list_with_authentication():
    session = requests.Session()
    try:
        # Step 1: GET /login to get login page, cookies and CSRF token
        resp_get_login = session.get(LOGIN_URL, timeout=TIMEOUT)
        assert resp_get_login.status_code == 200, f"Expected 200 from GET /login but got {resp_get_login.status_code}"
        
        # Parse CSRF token from the login page HTML
        soup = BeautifulSoup(resp_get_login.text, 'html.parser')
        csrf_input = soup.find('input', {'name': '_token'})
        assert csrf_input is not None, "CSRF token input not found on login page"
        csrf_token = csrf_input.get('value')
        assert csrf_token, "CSRF token value is empty"

        # Step 2: POST /login with valid credentials and CSRF token
        login_payload = VALID_USER_CREDENTIALS.copy()
        login_payload['_token'] = csrf_token

        resp_post_login = session.post(
            LOGIN_URL,
            data=login_payload,
            allow_redirects=False,
            timeout=TIMEOUT
        )

        # Expect 302 redirect to /accueil-patient on success
        assert resp_post_login.status_code == 302, f"Expected 302 redirect on login but got {resp_post_login.status_code}"
        location = resp_post_login.headers.get("Location", "")
        assert location.endswith("/accueil-patient"), f"Expected redirect to /accueil-patient but got {location}"

        # Step 3: GET /patients with authenticated session to fetch patients list
        resp_patients = session.get(PATIENTS_URL, timeout=TIMEOUT)
        assert resp_patients.status_code == 200, f"Expected 200 from GET /patients but got {resp_patients.status_code}"
        content_type = resp_patients.headers.get("Content-Type", "")
        assert "text/html" in content_type, f"Expected Content-Type text/html but got {content_type}"
        assert len(resp_patients.text) > 0, "Patients list HTML response is empty"

    finally:
        # Logout to clear session (best effort)
        logout_url = f"{BASE_URL}/logout"
        try:
            session.post(logout_url, timeout=TIMEOUT)
        except requests.RequestException:
            pass

test_access_patients_list_with_authentication()
