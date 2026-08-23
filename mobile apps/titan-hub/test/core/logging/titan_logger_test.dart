import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/logging/titan_logger.dart';

void main() {
  const redactor = TitanLogRedactor();

  test('redacts authorization headers and bearer tokens', () {
    final result = redactor.redact({
      'Authorization': 'Bearer secret-access-token',
      'status': 200,
    });

    expect(result, isNot(contains('secret-access-token')));
    expect(result, contains('[REDACTED]'));
    expect(result, contains('200'));
  });

  test('redacts sensitive map keys recursively', () {
    final result = redactor.redact({
      'password': 'correct horse battery staple',
      'profile': {
        'email': 'person@example.com',
        'refresh_token': 'refresh-secret',
      },
    });

    expect(result, isNot(contains('correct horse battery staple')));
    expect(result, isNot(contains('person@example.com')));
    expect(result, isNot(contains('refresh-secret')));
    expect(result, contains('[REDACTED]'));
  });

  test('redacts email addresses and payment-card patterns in free text', () {
    final result = redactor.redact(
      'Customer person@example.com used card 4242 4242 4242 4242',
    );

    expect(result, isNot(contains('person@example.com')));
    expect(result, isNot(contains('4242 4242 4242 4242')));
    expect(result, contains('[REDACTED_EMAIL]'));
    expect(result, contains('[REDACTED_CARD]'));
  });

  test('preserves ordinary operational diagnostics', () {
    expect(
      redactor.redact({'operation': 'load_manifest', 'status': 'complete'}),
      contains('load_manifest'),
    );
  });
}
