import requests
import re

BASE_URL = "http://localhost:80/cabinetsavwa/public"
LOGIN_URL = f"{BASE_URL}/login"
LOGOUT_URL = f"{BASE_URL}/logout"

VALID_USERNAME = "admin"  # Replace with a valid email or username
VALID_PASSWORD = "password"  # Replace with the valid password

def test_logout_functionality():
    session = requests.Session()
    try:
        # Step 1: Get login form to obtain cookies and CSRF token
        login_get_resp = session.get(LOGIN_URL, timeout=30)
        assert login_get_resp.status_code == 200, f"Expected 200 for GET /login but got {login_get_resp.status_code}"

        # Extract CSRF token from login form
        match = re.search(r'name="_token" value="([^"]+)"', login_get_resp.text)
        assert match is not None, "CSRF token not found in login form"
        csrf_token = match.group(1)

        # Step 2: Post valid credentials to login with CSRF token
        login_payload = {
            "username": VALID_USERNAME,
            "password": VALID_PASSWORD,
            "_token": csrf_token
        }
        login_post_resp = session.post(LOGIN_URL, data=login_payload, timeout=30, allow_redirects=False)
        assert login_post_resp.status_code == 302, f"Expected 302 redirect after login POST but got {login_post_resp.status_code}"
        location = login_post_resp.headers.get("Location", "")
        assert "/accueil-patient" in location, f"Expected redirect to /accueil-patient, got {location}"

        # Step 3: Perform logout POST with valid session cookie
        # Need to fetch CSRF token again if required for logout
        # Typically, logout POST requires CSRF token too
        # Fetch the CSRF token from any authenticated GET page (e.g., /accueil-patient)
        accueil_resp = session.get(f"{BASE_URL}/accueil-patient", timeout=30)
        assert accueil_resp.status_code == 200, f"Expected 200 for GET /accueil-patient after login but got {accueil_resp.status_code}"
        match_logout = re.search(r'name="_token" value="([^"]+)"', accueil_resp.text)
        assert match_logout is not None, "CSRF token not found on accueil-patient page for logout"
        csrf_token_logout = match_logout.group(1)

        logout_resp = session.post(LOGOUT_URL, data={"_token": csrf_token_logout}, timeout=30, allow_redirects=False)
        assert logout_resp.status_code == 302, f"Expected 302 redirect after logout POST but got {logout_resp.status_code}"
        location_header = logout_resp.headers.get("Location", "")
        assert location_header.endswith("/login"), f"Expected redirect to /login after logout, got {location_header}"

        # Step 4: Verify session cookie is cleared by accessing a protected page redirects to /login
        accueil_patient_resp = session.get(f"{BASE_URL}/accueil-patient", timeout=30, allow_redirects=False)
        assert accueil_patient_resp.status_code == 302, f"Expected 302 redirect accessing protected resource after logout but got {accueil_patient_resp.status_code}"
        location_after_logout = accueil_patient_resp.headers.get("Location", "")
        assert location_after_logout.endswith("/login"), f"Expected redirect to /login when accessing protected resource after logout, got {location_after_logout}"

    finally:
        session.close()


test_logout_functionality()
