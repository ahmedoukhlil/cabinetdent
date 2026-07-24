import requests

BASE_URL = "http://localhost:80/cabinetsavwa/public"
LOGIN_URL = f"{BASE_URL}/login"
PATIENTS_CREATE_URL = f"{BASE_URL}/patients/create"
LOGOUT_URL = f"{BASE_URL}/logout"
TIMEOUT = 30

VALID_CREDENTIALS = {
    "email": "admin@admin.com",
    "password": "admin"
}

def test_access_patients_create_form_with_authentication():
    session = requests.Session()
    login_successful = False
    try:
        # Get the login page (to get any cookies, tokens if needed)
        resp_get_login = session.get(LOGIN_URL, timeout=TIMEOUT)
        assert resp_get_login.status_code == 200

        # Post valid credentials to login
        resp_post_login = session.post(LOGIN_URL, data=VALID_CREDENTIALS, allow_redirects=False, timeout=TIMEOUT)
        assert resp_post_login.status_code == 302
        # Verify redirect to /accueil-patient or similar (auth success redirect)
        location = resp_post_login.headers.get('Location') or resp_post_login.headers.get('location')
        assert location is not None and "/accueil-patient" in location

        login_successful = True

        # Now access /patients/create page with authenticated session
        resp_pat_create = session.get(PATIENTS_CREATE_URL, timeout=TIMEOUT)
        assert resp_pat_create.status_code == 200

        html = resp_pat_create.text
        # Check that Livewire component mounts by looking for characteristic Livewire markers
        assert "wire:id" in html or "livewire:" in html or "livewire" in html.lower()
        # Also check presence of form or patient creation characteristic text
        assert "patient" in html.lower() and ("create" in html.lower() or "form" in html.lower())

    finally:
        if login_successful:
            # Perform logout to clear session
            resp_logout = session.post(LOGOUT_URL, timeout=TIMEOUT)
            assert resp_logout.status_code in [302, 200]

test_access_patients_create_form_with_authentication()
