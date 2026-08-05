import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/security/secure_key_value_store.dart';
import 'package:qrpay/core/security/session_token_store.dart';

class MemorySecureStore implements SecureKeyValueStore {
  final Map<String, String> values = {};

  @override
  Future<void> delete(String key) async {
    values.remove(key);
  }

  @override
  Future<String?> read(String key) async => values[key];

  @override
  Future<void> write(String key, String value) async {
    values[key] = value;
  }
}

void main() {
  late MemorySecureStore storage;
  late SessionTokenStore sessions;

  setUp(() {
    storage = MemorySecureStore();
    sessions = SessionTokenStore(storage);
  });

  test('persists and reads a complete session', () async {
    final expiry = DateTime.utc(2026, 8, 5, 23, 30);

    await sessions.writeSession(
      accessToken: 'access-one',
      refreshToken: 'refresh-one',
      expiresAt: expiry,
    );

    final session = await sessions.readSession();

    expect(session, isNotNull);
    expect(session!.accessToken, 'access-one');
    expect(session.refreshToken, 'refresh-one');
    expect(session.expiresAt, expiry);
  });

  test('rotates all session values atomically', () async {
    await sessions.writeSession(
      accessToken: 'access-one',
      refreshToken: 'refresh-one',
      expiresAt: DateTime.utc(2026, 8, 5, 23),
    );

    await sessions.rotateSession(
      accessToken: 'access-two',
      refreshToken: 'refresh-two',
      expiresAt: DateTime.utc(2026, 8, 6),
    );

    final session = await sessions.readSession();
    expect(session!.accessToken, 'access-two');
    expect(session.refreshToken, 'refresh-two');
    expect(session.expiresAt, DateTime.utc(2026, 8, 6));
  });

  test('returns null for incomplete session state', () async {
    storage.values['titan.session.access_token'] = 'orphaned-token';

    expect(await sessions.readSession(), isNull);
  });

  test('clears every session value', () async {
    await sessions.writeSession(
      accessToken: 'access-one',
      refreshToken: 'refresh-one',
      expiresAt: DateTime.utc(2026, 8, 6),
    );

    await sessions.clear();

    expect(storage.values, isEmpty);
    expect(await sessions.readSession(), isNull);
  });
}
