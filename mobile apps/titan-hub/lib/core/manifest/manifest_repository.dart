import 'business_app_manifest.dart';

class ManifestRepository {
  ManifestRepository({Future<Map<String, dynamic>> Function()? loader})
      : _loader = loader;

  final Future<Map<String, dynamic>> Function()? _loader;

  Future<BusinessAppManifest> load() async {
    final loader = _loader;
    if (loader == null) return fallbackManifest;

    try {
      return BusinessAppManifest.fromJson(await loader());
    } on Object {
      return fallbackManifest;
    }
  }

  static final BusinessAppManifest fallbackManifest =
      BusinessAppManifest.fromJson({
    'business_profile_id': 'default-business',
    'primary_vertical': 'field-home-services',
    'primary_app_mode': 'service',
    'supporting_app_modes': ['booking', 'quote_first'],
    'branding': {
      'app_name': 'Titan Hub',
      'accent_hex': '#1F6BFF',
    },
    'feature_flags': {
      'qr': true,
      'wallet': true,
      'payments': true,
      'kyc': true,
      'biometrics': true,
      'receipts': true,
      'transaction_history': true,
      'notifications': true,
      'localisation': true,
      'hosted_checkout': true,
    },
  });
}
