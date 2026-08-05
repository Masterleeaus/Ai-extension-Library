import 'app_mode.dart';
import 'vertical_slug.dart';

class BusinessBranding {
  const BusinessBranding({
    required this.appName,
    required this.accentHex,
    this.logoUrl,
  });

  factory BusinessBranding.fromJson(Map<String, dynamic>? json) {
    final appName = json?['app_name']?.toString().trim();
    final accentHex = json?['accent_hex']?.toString().trim();
    final logoUrl = json?['logo_url']?.toString().trim();

    return BusinessBranding(
      appName: appName == null || appName.isEmpty ? 'Titan Hub' : appName,
      accentHex:
          accentHex == null || accentHex.isEmpty ? '#1F6BFF' : accentHex,
      logoUrl: logoUrl == null || logoUrl.isEmpty ? null : logoUrl,
    );
  }

  final String appName;
  final String accentHex;
  final String? logoUrl;
}

class BusinessAppManifest {
  const BusinessAppManifest({
    required this.businessProfileId,
    required this.primaryVertical,
    required this.secondaryVerticals,
    required this.primaryAppMode,
    required this.supportingAppModes,
    required this.branding,
    required this.featureFlags,
    required this.navigation,
  });

  factory BusinessAppManifest.fromJson(Map<String, dynamic> json) {
    final primaryVertical =
        VerticalSlug.normalize(json['primary_vertical']?.toString()) ??
            VerticalSlug.fieldHomeServices;
    final primaryAppMode =
        AppMode.normalize(json['primary_app_mode']?.toString()) ??
            AppMode.service;

    final secondaryVerticals = _uniqueNormalized(
      json['secondary_verticals'],
      VerticalSlug.normalize,
    )..remove(primaryVertical);
    final supportingAppModes = _uniqueNormalized(
      json['supporting_app_modes'],
      AppMode.normalize,
    )..remove(primaryAppMode);

    return BusinessAppManifest(
      businessProfileId:
          _nonEmptyString(json['business_profile_id']) ?? 'default-business',
      primaryVertical: primaryVertical,
      secondaryVerticals: List.unmodifiable(secondaryVerticals),
      primaryAppMode: primaryAppMode,
      supportingAppModes: List.unmodifiable(supportingAppModes),
      branding: BusinessBranding.fromJson(_stringMap(json['branding'])),
      featureFlags: Map.unmodifiable(_boolMap(json['feature_flags'])),
      navigation: List.unmodifiable(_mapList(json['navigation'])),
    );
  }

  final String businessProfileId;
  final String primaryVertical;
  final List<String> secondaryVerticals;
  final String primaryAppMode;
  final List<String> supportingAppModes;
  final BusinessBranding branding;
  final Map<String, bool> featureFlags;
  final List<Map<String, dynamic>> navigation;

  List<String> get verticals => [primaryVertical, ...secondaryVerticals];

  List<String> get appModes => [primaryAppMode, ...supportingAppModes];

  static List<String> _uniqueNormalized(
    Object? source,
    String? Function(String?) normalize,
  ) {
    final values = source is Iterable ? source : const [];
    final result = <String>[];

    for (final value in values) {
      final normalized = normalize(value?.toString());
      if (normalized != null && !result.contains(normalized)) {
        result.add(normalized);
      }
    }

    return result;
  }

  static String? _nonEmptyString(Object? value) {
    final string = value?.toString().trim();
    return string == null || string.isEmpty ? null : string;
  }

  static Map<String, dynamic>? _stringMap(Object? value) {
    if (value is! Map) return null;
    return value.map((key, item) => MapEntry(key.toString(), item));
  }

  static Map<String, bool> _boolMap(Object? value) {
    final map = _stringMap(value);
    if (map == null) return const {};

    return {
      for (final entry in map.entries)
        if (entry.value is bool) entry.key: entry.value as bool,
    };
  }

  static List<Map<String, dynamic>> _mapList(Object? value) {
    if (value is! Iterable) return const [];

    return value
        .whereType<Map>()
        .map(
          (item) => item.map(
            (key, itemValue) => MapEntry(key.toString(), itemValue),
          ),
        )
        .toList(growable: false);
  }
}
