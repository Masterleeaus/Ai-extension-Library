from __future__ import annotations

import unittest

from pydantic import ValidationError

from app.core.config import Settings


class OfflineConfigurationTest(unittest.TestCase):
    def test_network_ai_cannot_be_enabled(self) -> None:
        with self.assertRaises(ValidationError):
            Settings(_env_file=None, NETWORK_AI_ENABLED=True)


if __name__ == "__main__":
    unittest.main()
