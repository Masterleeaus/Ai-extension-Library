from __future__ import annotations

import unittest

from fastapi.testclient import TestClient

from app.main import app


class ApiTest(unittest.TestCase):
    def test_health_declares_offline_localbrain(self) -> None:
        client = TestClient(app)
        response = client.get("/health")
        self.assertEqual(200, response.status_code)
        payload = response.json()
        self.assertEqual("offline", payload["mode"])
        self.assertEqual("local-brain-v2", payload["brain"])
        self.assertFalse(payload["cloud_enabled"])


if __name__ == "__main__":
    unittest.main()
