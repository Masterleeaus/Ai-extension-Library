import 'dart:convert';

class TitanLogRedactor {
  const TitanLogRedactor();

  static const Set<String> _sensitiveKeys = {
    'authorization',
    'password',
    'passcode',
    'pin',
    'token',
    'access_token',
    'refresh_token',
    'id_token',
    'api_key',
    'client_secret',
    'secret',
    'cookie',
    'set-cookie',
    'card',
    'card_number',
    'cvv',
    'cvc',
    'email',
  };

  static final RegExp _bearerPattern = RegExp(
    r'(?i)\bBearer\s+[A-Za-z0-9._~+\-/]+=*',
  );
  static final RegExp _emailPattern = RegExp(
    r'\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b',
    caseSensitive: false,
  );
  static final RegExp _cardPattern = RegExp(
    r'\b(?:\d[ -]*?){13,19}\b',
  );

  String redact(Object? value) {
    final sanitized = _sanitize(value);
    final rendered = sanitized is String ? sanitized : jsonEncode(sanitized);

    return rendered
        .replaceAll(_bearerPattern, 'Bearer [REDACTED]')
        .replaceAll(_emailPattern, '[REDACTED_EMAIL]')
        .replaceAll(_cardPattern, '[REDACTED_CARD]');
  }

  Object? _sanitize(Object? value) {
    if (value is Map) {
      return {
        for (final entry in value.entries)
          entry.key.toString(): _isSensitiveKey(entry.key.toString())
              ? '[REDACTED]'
              : _sanitize(entry.value),
      };
    }

    if (value is Iterable) {
      return value.map(_sanitize).toList(growable: false);
    }

    return value?.toString();
  }

  bool _isSensitiveKey(String key) {
    final normalized = key.trim().toLowerCase().replaceAll('-', '_');
    return _sensitiveKeys.contains(normalized) ||
        normalized.endsWith('_token') ||
        normalized.endsWith('_secret') ||
        normalized.endsWith('_password');
  }
}
