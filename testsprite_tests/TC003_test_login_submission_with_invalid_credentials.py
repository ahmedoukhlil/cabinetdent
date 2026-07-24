import re
import requests

BASE_URL = "http://localhost:80/cabinetsavwa/public"
LOGIN_PATH = "/login"
TIMEOUT = 30

def test_login_submission_with_invalid_credentials():
    session = requests.Session()
    # First, get the login page to obtain CSRF token
    try:
        login_page_response = session.get(BASE_URL + LOGIN_PATH, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request to {BASE_URL + LOGIN_PATH} failed: {e}"

    assert login_page_response.status_code == 200, f"GET {LOGIN_PATH} expected 200 OK, got {login_page_response.status_code}"

    # Parse CSRF token from login page HTML form using regex
    match = re.search(r'name=["\']_token["\'] value=["\']([^"\']+)["\']', login_page_response.text)
    assert match, "CSRF token not found in login form"
    csrf_token = match.group(1)

    url = BASE_URL + LOGIN_PATH
    headers = {
        "Content-Type": "application/x-www-form-urlencoded"
    }
    # Using clearly invalid credentials
    data = {
        "email": "invaliduser@example.com",
        "password": "wrongpassword",
        "_token": csrf_token
    }

    try:
        response = session.post(url, headers=headers, data=data, allow_redirects=False, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request to {url} failed: {e}"

    # Allowed responses:
    # 1) 302 redirect back to /login with error flash message
    # 2) 422 validation errors
    assert response.status_code in (302, 422), f"Unexpected status code: {response.status_code}"

    if response.status_code == 302:
        location = response.headers.get("Location", "")
        # redirect location expected to be /login (relative) or full URL with /login path
        assert location.endswith("/login") or location == "/login", f"Expected redirect to /login, got: {location}"
    elif response.status_code == 422:
        # Commonly validation errors return JSON or HTML form with errors
        content_type = response.headers.get("Content-Type", "")
        # If JSON, expect error messages in body
        if "application/json" in content_type:
            try:
                json_body = response.json()
            except ValueError:
                assert False, "Response is not valid JSON for 422 status"
            # Expect keys indicating errors (commonly "errors" or similar)
            assert ("errors" in json_body and json_body["errors"]) or ("message" in json_body), "Validation errors expected in JSON body"
        else:
            # If not JSON, possibly HTML with validation messages; at least check content non-empty
            assert response.text, "Expected error details in response body for 422"

test_login_submission_with_invalid_credentials()
