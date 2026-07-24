import requests
import re

BASE_URL = "http://localhost:80/cabinetsavwa/public"
LOGIN_PATH = "/login"
ACCUEIL_PATIENT_PATH = "/accueil-patient"
TIMEOUT = 30

def test_login_submission_with_valid_credentials():
    session = requests.Session()
    login_url = BASE_URL + LOGIN_PATH
    accueil_url = BASE_URL + ACCUEIL_PATIENT_PATH

    # NOTE: Replace these valid credentials with actual test credentials known to be valid in the test environment
    valid_credentials = {
        'email': 'validuser@example.com',
        'password': 'validpassword'
    }

    try:
        # First GET /login to get CSRF token
        get_response = session.get(login_url, timeout=TIMEOUT)
        assert get_response.status_code == 200, f"Expected 200 OK on GET /login but got {get_response.status_code}"
        # Extract CSRF token from HTML input
        match = re.search(r'name=["\']_token["\'] value=["\']([^"\']+)["\']', get_response.text)
        assert match is not None, "CSRF token not found in login page"
        csrf_token = match.group(1)

        # Add _token to the login data
        login_data = valid_credentials.copy()
        login_data['_token'] = csrf_token

        # POST /login with valid credentials and CSRF token
        response = session.post(login_url, data=login_data, allow_redirects=False, timeout=TIMEOUT)
        # Assert 302 redirect status code
        assert response.status_code == 302, f"Expected 302 redirect but got {response.status_code}"
        # Assert redirect location header points to /accueil-patient
        location = response.headers.get('Location')
        assert location is not None, "Redirect missing Location header"
        assert location.endswith(ACCUEIL_PATIENT_PATH), f"Expected redirect to {ACCUEIL_PATIENT_PATH} but got {location}"
        # Assert that session cookie is set
        cookies = session.cookies
        # Common Laravel session cookie name: 'laravel_session'
        session_cookie = None
        for cookie in cookies:
            if 'laravel_session' in cookie.name:
                session_cookie = cookie.value
                break
        assert session_cookie is not None and len(session_cookie) > 0, "Authentication session cookie not set on login"

        # Optionally, follow the redirect and GET /accueil-patient with session cookie to confirm access
        accueil_response = session.get(accueil_url, timeout=TIMEOUT)
        assert accueil_response.status_code == 200, f"Expected 200 OK on {ACCUEIL_PATIENT_PATH} but got {accueil_response.status_code}"
        assert 'html' in accueil_response.headers.get('Content-Type', ''), "Accueil-patient response does not contain HTML content"
    finally:
        session.close()

test_login_submission_with_valid_credentials()