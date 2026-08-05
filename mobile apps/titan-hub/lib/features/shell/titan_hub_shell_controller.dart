import 'package:flutter/foundation.dart';

import '../../core/features/titan_feature_flags.dart';
import '../../core/manifest/business_app_manifest.dart';
import '../../core/navigation/manifest_navigation_resolver.dart';
import '../../core/navigation/titan_destination.dart';

class TitanHubShellController extends ChangeNotifier {
  TitanHubShellController({
    required this.manifest,
    ManifestNavigationResolver resolver = const ManifestNavigationResolver(),
  })  : featureFlags = TitanFeatureFlags.fromManifest(manifest),
        destinations = resolver.resolve(manifest) {
    primaryDestinations = destinations
        .where((destination) => destination.isPrimary)
        .take(5)
        .toList(growable: false);
    capabilityDestinations = destinations
        .where(
          (destination) =>
              !destination.isPrimary &&
              featureFlags.isEnabled(destination.featureFlag),
        )
        .toList(growable: false);
  }

  final BusinessAppManifest manifest;
  final TitanFeatureFlags featureFlags;
  final List<TitanDestination> destinations;
  late final List<TitanDestination> primaryDestinations;
  late final List<TitanDestination> capabilityDestinations;

  int _selectedIndex = 0;

  int get selectedIndex => _selectedIndex;

  TitanDestination get selectedDestination =>
      primaryDestinations[_selectedIndex];

  void select(int index) {
    if (index < 0 || index >= primaryDestinations.length) return;
    if (_selectedIndex == index) return;

    _selectedIndex = index;
    notifyListeners();
  }
}
