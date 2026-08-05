import '../manifest/app_mode.dart';
import '../manifest/business_app_manifest.dart';
import '../manifest/vertical_slug.dart';
import 'titan_destination.dart';

class ManifestNavigationResolver {
  const ManifestNavigationResolver();

  static const Map<String, int> _primaryOrder = {
    'home': 0,
    'explore': 1,
    'hub-qr': 2,
    'activity': 3,
    'account': 4,
  };

  List<TitanDestination> resolve(BusinessAppManifest manifest) {
    final candidates = <TitanDestination>[
      ..._coreDestinations,
      ..._verticalDestinations(manifest.primaryVertical, 10),
    ];

    final secondaryVerticals = [...manifest.secondaryVerticals]..sort();
    for (var index = 0; index < secondaryVerticals.length; index++) {
      candidates.addAll(
        _verticalDestinations(secondaryVerticals[index], 20 + index),
      );
    }

    candidates.addAll(_modeDestinations(manifest.primaryAppMode, 30));
    final supportingModes = [...manifest.supportingAppModes]..sort();
    for (var index = 0; index < supportingModes.length; index++) {
      candidates.addAll(_modeDestinations(supportingModes[index], 40 + index));
    }

    candidates.addAll(_manifestDestinations(manifest.navigation));

    final resolvedById = <String, TitanDestination>{};
    for (final candidate in candidates) {
      final existing = resolvedById[candidate.id];
      if (existing == null || _isPreferred(candidate, existing)) {
        resolvedById[candidate.id] = candidate;
      }
    }

    final resolved = resolvedById.values.toList()..sort(_compare);
    return List.unmodifiable(resolved);
  }

  static const List<TitanDestination> _coreDestinations = [
    TitanDestination(
      id: 'home',
      label: 'Home',
      route: '/titan/home',
      iconName: 'home',
      priority: 10000,
      sourceRank: 0,
      source: 'core',
      isPrimary: true,
    ),
    TitanDestination(
      id: 'explore',
      label: 'Explore',
      route: '/titan/explore',
      iconName: 'explore',
      priority: 9990,
      sourceRank: 0,
      source: 'core',
      isPrimary: true,
    ),
    TitanDestination(
      id: 'hub-qr',
      label: 'Hub QR',
      route: '/qr-code',
      iconName: 'qr_code_scanner',
      priority: 9980,
      sourceRank: 0,
      source: 'core',
      isPrimary: true,
      featureFlag: 'qr',
    ),
    TitanDestination(
      id: 'activity',
      label: 'Activity',
      route: '/titan/activity',
      iconName: 'history',
      priority: 9970,
      sourceRank: 0,
      source: 'core',
      isPrimary: true,
    ),
    TitanDestination(
      id: 'account',
      label: 'Account',
      route: '/titan/account',
      iconName: 'person',
      priority: 9960,
      sourceRank: 0,
      source: 'core',
      isPrimary: true,
    ),
  ];

  Iterable<TitanDestination> _verticalDestinations(
    String vertical,
    int sourceRank,
  ) {
    final source = 'vertical:$vertical';
    return switch (vertical) {
      VerticalSlug.fieldHomeServices => [
          _destination(
            id: 'services',
            label: 'Services',
            route: '/services',
            iconName: 'handyman',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'request-quote',
            label: 'Request a quote',
            route: '/quotes/new',
            iconName: 'request_quote',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'jobs',
            label: 'My jobs',
            route: '/jobs',
            iconName: 'work',
            priority: 580,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.accommodationRooming => [
          _destination(
            id: 'stays',
            label: 'Stays',
            route: '/stays',
            iconName: 'hotel',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'reservations',
            label: 'Reservations',
            route: '/reservations',
            iconName: 'event_available',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.realEstate => [
          _destination(
            id: 'properties',
            label: 'Properties',
            route: '/properties',
            iconName: 'apartment',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'inspections',
            label: 'Inspections',
            route: '/inspections',
            iconName: 'fact_check',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.salonsPersonalCare => [
          _destination(
            id: 'treatments',
            label: 'Treatments',
            route: '/treatments',
            iconName: 'spa',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'appointments',
            label: 'Appointments',
            route: '/appointments',
            iconName: 'calendar_month',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.fitnessMembership => [
          _destination(
            id: 'classes',
            label: 'Classes',
            route: '/classes',
            iconName: 'fitness_center',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'memberships',
            label: 'Memberships',
            route: '/memberships',
            iconName: 'card_membership',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.automotiveServices => [
          _destination(
            id: 'vehicle-services',
            label: 'Vehicle services',
            route: '/vehicle-services',
            iconName: 'car_repair',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'appointments',
            label: 'Appointments',
            route: '/appointments',
            iconName: 'calendar_month',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.ecommerceRetail => [
          _destination(
            id: 'shop',
            label: 'Shop',
            route: '/shop',
            iconName: 'storefront',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'orders',
            label: 'Orders',
            route: '/orders',
            iconName: 'receipt_long',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.hireRental => [
          _destination(
            id: 'rentals',
            label: 'Hire and rental',
            route: '/rentals',
            iconName: 'inventory_2',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'returns',
            label: 'Returns',
            route: '/returns',
            iconName: 'assignment_return',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      VerticalSlug.bookingCapacity => [
          _destination(
            id: 'availability',
            label: 'Availability',
            route: '/availability',
            iconName: 'date_range',
            priority: 600,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'reservations',
            label: 'Reservations',
            route: '/reservations',
            iconName: 'event_available',
            priority: 590,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      _ => const <TitanDestination>[],
    };
  }

  Iterable<TitanDestination> _modeDestinations(String mode, int sourceRank) {
    final source = 'mode:$mode';
    return switch (mode) {
      AppMode.service => [
          _destination(
            id: 'services',
            label: 'Services',
            route: '/services',
            iconName: 'handyman',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.quoteFirst => [
          _destination(
            id: 'request-quote',
            label: 'Request a quote',
            route: '/quotes/new',
            iconName: 'request_quote',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.booking || AppMode.capacityBooking => [
          _destination(
            id: 'appointments',
            label: 'Appointments',
            route: '/appointments',
            iconName: 'calendar_month',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.reservation || AppMode.accommodation => [
          _destination(
            id: 'reservations',
            label: 'Reservations',
            route: '/reservations',
            iconName: 'event_available',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.classMode => [
          _destination(
            id: 'classes',
            label: 'Classes',
            route: '/classes',
            iconName: 'groups',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.eventTicketing => [
          _destination(
            id: 'tickets',
            label: 'Tickets',
            route: '/tickets',
            iconName: 'confirmation_number',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.transportBooking => [
          _destination(
            id: 'trips',
            label: 'Trips',
            route: '/trips',
            iconName: 'directions_car',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.sales || AppMode.orderAhead => [
          _destination(
            id: 'shop',
            label: 'Shop',
            route: '/shop',
            iconName: 'storefront',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
          _destination(
            id: 'orders',
            label: 'Orders',
            route: '/orders',
            iconName: 'receipt_long',
            priority: 490,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.hire || AppMode.rental => [
          _destination(
            id: 'rentals',
            label: 'Hire and rental',
            route: '/rentals',
            iconName: 'inventory_2',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.membership => [
          _destination(
            id: 'memberships',
            label: 'Memberships',
            route: '/memberships',
            iconName: 'card_membership',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.subscription => [
          _destination(
            id: 'subscriptions',
            label: 'Subscriptions',
            route: '/subscriptions',
            iconName: 'autorenew',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.marketplace => [
          _destination(
            id: 'marketplace',
            label: 'Marketplace',
            route: '/marketplace',
            iconName: 'store_mall_directory',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      AppMode.application => [
          _destination(
            id: 'applications',
            label: 'Applications',
            route: '/applications',
            iconName: 'description',
            priority: 500,
            sourceRank: sourceRank,
            source: source,
          ),
        ],
      _ => const <TitanDestination>[],
    };
  }

  Iterable<TitanDestination> _manifestDestinations(
    List<Map<String, dynamic>> navigation,
  ) sync* {
    for (final item in navigation) {
      final id = item['id']?.toString().trim();
      final label = item['label']?.toString().trim();
      final route = item['route']?.toString().trim();
      if (id == null || id.isEmpty || label == null || label.isEmpty) continue;
      if (route == null || route.isEmpty) continue;

      yield TitanDestination(
        id: id,
        label: label,
        route: route,
        iconName: item['icon']?.toString().trim().isNotEmpty == true
            ? item['icon'].toString().trim()
            : 'apps',
        priority: _intValue(item['priority']) ?? 900,
        sourceRank: 1,
        source: 'manifest',
        isPrimary: item['is_primary'] == true,
        featureFlag: item['feature_flag']?.toString().trim(),
      );
    }
  }

  static TitanDestination _destination({
    required String id,
    required String label,
    required String route,
    required String iconName,
    required int priority,
    required int sourceRank,
    required String source,
  }) {
    return TitanDestination(
      id: id,
      label: label,
      route: route,
      iconName: iconName,
      priority: priority,
      sourceRank: sourceRank,
      source: source,
    );
  }

  static bool _isPreferred(
    TitanDestination candidate,
    TitanDestination existing,
  ) {
    if (candidate.priority != existing.priority) {
      return candidate.priority > existing.priority;
    }
    if (candidate.sourceRank != existing.sourceRank) {
      return candidate.sourceRank < existing.sourceRank;
    }
    final sourceComparison = candidate.source.compareTo(existing.source);
    if (sourceComparison != 0) return sourceComparison < 0;
    return candidate.route.compareTo(existing.route) < 0;
  }

  static int _compare(TitanDestination left, TitanDestination right) {
    if (left.isPrimary || right.isPrimary) {
      if (left.isPrimary && !right.isPrimary) return -1;
      if (!left.isPrimary && right.isPrimary) return 1;
      return (_primaryOrder[left.id] ?? 999)
          .compareTo(_primaryOrder[right.id] ?? 999);
    }

    final priorityComparison = right.priority.compareTo(left.priority);
    if (priorityComparison != 0) return priorityComparison;

    final sourceComparison = left.sourceRank.compareTo(right.sourceRank);
    if (sourceComparison != 0) return sourceComparison;

    return left.id.compareTo(right.id);
  }

  static int? _intValue(Object? value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '');
  }
}
