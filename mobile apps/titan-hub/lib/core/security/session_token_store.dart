import 'dart:convert';

import 'secure_key_value_store.dart';

class SessionTokens {
  const SessionTokens({
    required this.accessToken,
    required this.refreshToken,
    required this.expiresAt,
  });

  final String accessToken;
  final String refreshToken;
  final DateTime expiresAt;

  bool get isExpired => !expiresAt.isAfter(DateTime.now().toUtc());
}

class SessionStorageException implements Exception {
  const SessionStorageException(this.operation, this.cause);

  final String operation;
  final Object cause;

  @override
  String toString() => 'SessionStorageException($operation)';
}

class SessionTokenStore {
  SessionTokenStore(this._storage);

  static const String _sessionKey = 'titan.session.bundle';
  static const List<String> _legacyKeys = [
    'titan.session.access_token',
    'titan.session.refresh_token',
    'titan.session.expires_at',
  ];

  final SecureKeyValueStore _storage;

  Future<void> writeSession({
    required String accessToken,
    required String refreshToken,
    required DateTime expiresAt,
  }) async {
    final payload = jsonEncode({
      'access_token': accessToken,
      'refresh_token': refreshToken,
      'expires_at': expiresAt.toUtc().toIso8601String(),
    });

    try {
      await _storage.write(_sessionKey, payload);
      await _deleteLegacyKeys();
    } on Object catch (error) {
      throw SessionStorageException('write', error);
    }
  }

  Future<void> rotateSession({
    required String accessToken,
    required String refreshToken,
    required DateTime expiresAt,
  }) {
    return writeSession(
      accessToken: accessToken,
      refreshToken: refreshToken,
      expiresAt: expiresAt,
    );
  }

  Future<SessionTokens?> readSession() async {
    try {
      final payload = await _storage.read(_sessionKey);
      if (payload == null || payload.isEmpty) return null;

      final decoded = jsonDecode(payload);
      if (decoded is! Map) return null;

      final accessToken = decoded['access_token']?.toString();
      final refreshToken = decoded['refresh_token']?.toString();
      final expiresAt = DateTime.tryParse(decoded['expires_at']?.toString() ?? '');
      if (accessToken == null || accessToken.isEmpty) return null;
      if (refreshToken == null || refreshToken.isEmpty) return null;
      if (expiresAt == null) return null;

      return SessionTokens(
        accessToken: accessToken,
        refreshToken: refreshToken,
        expiresAt: expiresAt.toUtc(),
      );
    } on FormatException {
      return null;
    } on Object catch (error) {
      throw SessionStorageException('read', error);
    }
  }

  Future<void> clear() async {
    try {
      await _storage.delete(_sessionKey);
      await _deleteLegacyKeys();
    } on Object catch (error) {
      throw SessionStorageException('clear', error);
    }
  }

  Future<void> _deleteLegacyKeys() async {
    for (final key in _legacyKeys) {
      await _storage.delete(key);
    }
  }
}
