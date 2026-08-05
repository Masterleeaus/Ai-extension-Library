import '../manifest/business_app_manifest.dart';

class TitanFeatureFlags {
  const TitanFeatureFlags({
    required this.qr,
    required this.wallet,
    required this.payments,
    required this.kyc,
    required this.biometrics,
    required this.receipts,
    required this.transactionHistory,
    required this.notifications,
    required this.localisation,
    required this.hostedCheckout,
  });

  factory TitanFeatureFlags.fromManifest(BusinessAppManifest manifest) {
    bool enabled(String key) => manifest.featureFlags[key] ?? true;

    return TitanFeatureFlags(
      qr: enabled('qr'),
      wallet: enabled('wallet'),
      payments: enabled('payments'),
      kyc: enabled('kyc'),
      biometrics: enabled('biometrics'),
      receipts: enabled('receipts'),
      transactionHistory: enabled('transaction_history'),
      notifications: enabled('notifications'),
      localisation: enabled('localisation'),
      hostedCheckout: enabled('hosted_checkout'),
    );
  }

  final bool qr;
  final bool wallet;
  final bool payments;
  final bool kyc;
  final bool biometrics;
  final bool receipts;
  final bool transactionHistory;
  final bool notifications;
  final bool localisation;
  final bool hostedCheckout;

  bool isEnabled(String? flag) {
    if (flag == null || flag.isEmpty) return true;

    return switch (flag) {
      'qr' => qr,
      'wallet' => wallet,
      'payments' => payments,
      'kyc' => kyc,
      'biometrics' => biometrics,
      'receipts' => receipts,
      'transaction_history' => transactionHistory,
      'notifications' => notifications,
      'localisation' => localisation,
      'hosted_checkout' => hostedCheckout,
      _ => false,
    };
  }
}
