# Offline LocalBrain Deployment Guide

## Requirements

- PHP 8.2 or later.
- Python 3.10 or later.
- Node.js 20 or later only when rebuilding/testing the TypeScript offline companion.
- PHP Sodium extension.
- No AI API keys.
- No internet connection required after local language/runtime dependencies are installed.

## 1. Extract and verify

```bash
unzip Titan-Zero-Offline-LocalBrain-2026-08-03.zip
cd Titan-Zero-Complete-Master-Final-2026-08-03
php -v
python3 --version
```

## 2. Verify the interaction package

```bash
cd interaction-engine
php tests/run.php
php tests/localbrain_v2_run.php
php tests/offline_only_run.php
npm install
npm test
cd ..
```

`offline_only_run.php` fails if the cloud implementation or cloud configuration is restored.

## 3. Verify the LocalBrain CLI

```bash
printf '%s' '{"message":"Show my mathematics marks","context":{"tenant_id":"school-a","user_id":"user-a","subject_id":"STU101"}}' \
  | php interaction-engine/bin/localbrain.php
```

The response must contain:

```json
{"ok":true,"result":{"mode":"offline","model_version":"local-brain-v2"}}
```

Its audit object must report `cloud_used: false`.

## 4. Prepare the Python service

```bash
cd ai-erp-system
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python3 mock_data/init_db.py
python3 -m unittest discover -s tests -v
```

For API tests, install the development dependencies:

```bash
pip install -r requirements-dev.txt
```

## 5. Run locally

```bash
uvicorn app.main:app --host 127.0.0.1 --port 8000
```

Use loopback unless a trusted local-network deployment is deliberately configured.

## 6. Configuration

Optional `.env` settings:

```dotenv
LOCALBRAIN_PHP_BINARY=php
LOCALBRAIN_SCRIPT=/absolute/path/to/interaction-engine/bin/localbrain.php
LOCALBRAIN_TIMEOUT_SECONDS=3.0
LOCALBRAIN_MIN_CONFIDENCE=0.65
DATABASE_URL=/absolute/path/to/school_erp.db
MEMORY_DATABASE=/absolute/path/to/conversation_memory.sqlite3
MEMORY_HISTORY_LIMIT=50
NETWORK_AI_ENABLED=false
```

Setting `NETWORK_AI_ENABLED=true` is rejected during configuration loading. Legacy cloud-provider settings are not supported.

## 7. Airplane-mode acceptance test

1. Disconnect networking or block external sockets.
2. Run the PHP and Python test suites.
3. Start the API.
4. Call `GET /health`; verify `cloud_enabled=false` and `network_ai_calls=0`.
5. Call `POST /chat` for all eight supported intents.
6. Verify low-confidence text returns clarification.
7. Verify one tenant/user cannot read another scope's history.

## 8. Production cautions

This is not a complete production host. Before exposure beyond a trusted local device/network, add authenticated scope derivation, encrypted Python memory, rate limiting, host-level authorization and end-to-end WorkCore integration tests.
