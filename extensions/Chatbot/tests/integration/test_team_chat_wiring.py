from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]

def read(path):
    return (ROOT / path).read_text()


def test_staff_inbox_exposes_navigation_and_creation_modals():
    index = read('resources/views/staff-inbox/index.blade.php')
    panel = read('resources/views/staff-inbox/team-chat/panel.blade.php')
    assert 'data-chatbot-workspace-tab="customer"' in index
    assert 'data-chatbot-workspace-tab="team"' in index
    assert 'data-chatbot-workspace-tab="channels"' in index
    assert 'data-team-group-modal' in panel
    assert 'data-team-channel-modal' in panel


def test_message_payload_and_realtime_contract_are_aligned():
    local = read('resources/pwa/chatbot-pwa/team-chat-local-first.js')
    event = read('System/Events/TeamChat/TeamMessageCreated.php')
    assert "body: payload.body || payload.message || payload.content || ''" in local
    assert "item.body || item.message || item.content" in local
    assert "chatbot.team.' . $conversation->tenant_id . '.conversation.' . $conversation->uuid" in event
    assert "return 'team.message.created';" in event


def test_channel_client_has_csrf_and_ui_renderer():
    client = read('resources/assets/js/team-chat/business-channels.js')
    ui = read('resources/pwa/chatbot-pwa/team-chat-local-ui.js')
    assert "X-CSRF-TOKEN" in client
    assert "team-chat:channels" in ui
    assert "data-team-channel-list" in ui
    assert "data-team-chat-new" in ui
    assert "data-team-channel-new" in ui


def test_no_temporary_runtime_file_remains():
    assert not (ROOT / 'resources/pwa/chatbot-pwa/team-chat-local-first.js.tmp').exists()
