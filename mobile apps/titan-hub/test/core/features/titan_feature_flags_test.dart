import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/features/titan_feature_flags.dart';
import 'package:qrpay/core/manifest/business_app_manifest.dart';

void main() {
  test('retains proven QRPay flows by default', () {
    final flags = TitanFeatureFlags.fromManifest(
      BusinessAppManifest.fromJson({
        'primary_vertical': 'field-home-services',
        'primary_app_mode': 'service',
      }),
    );

    expect(flags.qr, isTrue);
    expect(flags.wallet, isTrue);
    expect(flags.payments, isTrue);
    expect(flags.kyc, isTrue);
    expect(flags.biometrics, isTrue);
    expect(flags.receipts, isTrue);
    expect(flags.transactionHistory, isTrue);
    expect(flags.notifications, isTrue);
    expect(flags.localisation, isTrue);
    expect(flags.hostedCheckout, isTrue);
  });

  test('applies explicit backend manifest overrides', () {
    final flags = TitanFeatureFlags.fromManifest(
      BusinessAppManifest.fromJson({
        'primary_vertical': 'ecommerce-retail',
        'primary_app_mode': 'sales',
        'feature_flags': {
          'wallet': false,
          'kyc': false,
          'hosted_checkout': false,
          'transaction_history': false,
          'qr': true,
        },
      }),
    );

    expect(flags.qr, isTrue);
    expect(flags.wallet, isFalse);
    expect(flags.kyc, isFalse);
    expect(flags.hostedCheckout, isFalse);
    expect(flags.transactionHistory, isFalse);
    expect(flags.payments, isTrue);
  });

  test('answers flag names used by destinations and routes', () {
    final flags = TitanFeatureFlags.fromManifest(
      BusinessAppManifest.fromJson({
        'primary_vertical': 'fitness-membership',
        'primary_app_mode': 'membership',
        'feature_flags': {'qr': false},
      }),
    );

    expect(flags.isEnabled('qr'), isFalse);
    expect(flags.isEnabled('transaction_history'), isTrue);
    expect(flags.isEnabled('unknown_flag'), isFalse);
    expect(flags.isEnabled(null), isTrue);
  });
}
