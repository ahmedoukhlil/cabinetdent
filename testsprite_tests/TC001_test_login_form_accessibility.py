import requests

def test_login_form_accessibility():
    base_url = "http://localhost:80/cabinetsavwa/public"
    url = f"{base_url}/login"
    headers = {
        "Accept": "text/html",
        "User-Agent": "test-agent"
    }
    try:
        response = requests.get(url, headers=headers, timeout=30)
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"
        content_type = response.headers.get("Content-Type", "")
        assert "text/html" in content_type, f"Expected 'text/html' in Content-Type but got {content_type}"
        assert "<form" in response.text, "Login form HTML not found in response body"
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"

test_login_form_accessibility()