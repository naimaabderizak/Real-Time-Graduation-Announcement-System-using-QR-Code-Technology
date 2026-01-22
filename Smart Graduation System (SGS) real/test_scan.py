import urllib.request
import urllib.parse
import json

base_url = "http://localhost:5001"
student_id = "HS0109208"

print(f"Testing scan for VALID ID: {student_id}")
try:
    req = urllib.request.Request(f"{base_url}/scan/{student_id}", method="POST")
    with urllib.request.urlopen(req) as response:
        print(f"Scan status: {response.getcode()}")
        print(f"Response: {response.read().decode()}")
except urllib.error.HTTPError as e:
    print(f"HTTP Error: {e.code} - {e.read().decode()}")
except Exception as e:
    print(f"Failed to scan: {e}")
