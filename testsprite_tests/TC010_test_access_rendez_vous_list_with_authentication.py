import requests
import re

BASE_URL = "http://localhost:80/cabinetsavwa/public"
LOGIN_URL = f"{BASE_URL}/login"
RENDEZ_VOUS_URL = f"{BASE_URL}/rendez-vous"
TIMEOUT = 30

VALID_CREDENTIALS = {
    "email": "admin@example.com",
    "password": "password123"
}

def test_access_rendez_vous_list_with_authentication():
    session = requests.Session()
    try:
        # Step 1: GET /login to retrieve login page (usually to get cookies, tokens, CSRF etc.)
        login_get_resp = session.get(LOGIN_URL, timeout=TIMEOUT)
        assert login_get_resp.status_code == 200, f"Unexpected status code on GET /login: {login_get_resp.status_code}"

        # Parse CSRF token from login page HTML using regex
        match = re.search(r'name=["\']_token["\'] value=["\']([^"\']+)["\']', login_get_resp.text)
        assert match is not None, "CSRF token input not found in login page"
        csrf_token = match.group(1)
        assert csrf_token, "CSRF token value is empty"

        # Step 2: POST /login with valid credentials and CSRF token to authenticate
        login_post_resp = session.post(
            LOGIN_URL,
            data={**VALID_CREDENTIALS, '_token': csrf_token},
            allow_redirects=False,
            timeout=TIMEOUT
        )
        assert login_post_resp.status_code == 302, f"Expected 302 redirect on POST /login, got {login_post_resp.status_code}"
        assert "/accueil-patient" in login_post_resp.headers.get("Location", ""), "Redirect location after login is not /accueil-patient"

        # Step 3: With auth session cookie, GET /rendez-vous
        rendez_vous_resp = session.get(RENDEZ_VOUS_URL, timeout=TIMEOUT)
        assert rendez_vous_resp.status_code == 200, f"Expected 200 status on GET /rendez-vous with auth, got {rendez_vous_resp.status_code}"
        content_type = rendez_vous_resp.headers.get("Content-Type", "")
        assert "html" in content_type.lower(), f"Expected HTML content-type, got {content_type}"
        # Further lightweight check: page likely contains 'Livewire' keyword for Livewire page
        assert "livewire" in rendez_vous_resp.text.lower(), "The rendez-vous Livewire page content expected but not found"
    finally:
        # Logout to clear session if possible
        session.post(f"{BASE_URL}/logout", timeout=TIMEOUT)

test_access_rendez_vous_list_with_authentication()
