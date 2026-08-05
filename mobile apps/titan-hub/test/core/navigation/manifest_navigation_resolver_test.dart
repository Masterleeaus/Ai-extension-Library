import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/manifest/business_app_manifest.dart';
import 'package:qrpay/core/navigation/manifest_navigation_resolver.dart';

void main() {
  const resolver = ManifestNavigationResolver();

  BusinessAppManifest manifest({
    required String vertical,
    required String primaryMode,
    List<String> supportingModes = const [],
    List<Map<String, dynamic>> navigation = const [],
  }) {
    return BusinessAppManifest.fromJson({
      'primary_vertical': vertical,
      'primary_app_mode': primaryMode,
      'supporting_app_modes': supportingModes,
      'navigation': navigation,
    });
  }

  test('always places the five Titan Hub destinations first', () {
    final destinations = resolver.resolve(
      manifest(vertical: 'field-home-services', primaryMode: 'service'),
    );

    expect(
      destinations.take(5).map((destination) => destination.id),
      ['home', 'explore', 'hub-qr', 'activity', 'account'],
    );
    expect(destinations.take(5).every((destination) => destination.isPrimary),
        isTrue);
  });

  test('different vertical profiles contribute different capabilities', () {
    final fieldServices = resolver.resolve(
      manifest(vertical: 'field-home-services', primaryMode: 'service'),
    );
    final ecommerce = resolver.resolve(
      manifest(vertical: 'ecommerce-retail', primaryMode: 'sales'),
    );

    final fieldIds = fieldServices.map((destination) => destination.id).toSet();
    final ecommerceIds = ecommerce.map((destination) => destination.id).toSet();

    expect(fieldIds, containsAll(['services', 'request-quote']));
    expect(fieldIds, isNot(contains('shop')));
    expect(ecommerceIds, containsAll(['shop', 'orders']));
    expect(ecommerceIds, isNot(contains('request-quote')));
  });

  test('merges multiple App Modes without duplicate destinations', () {
    final destinations = resolver.resolve(
      manifest(
        vertical: 'field-home-services',
        primaryMode: 'service',
        supportingModes: ['booking', 'quote_first', 'booking'],
      ),
    );
    final ids = destinations.map((destination) => destination.id).toList();

    expect(ids, containsAll(['services', 'appointments', 'request-quote']));
    expect(ids.toSet().length, ids.length);
  });

  test('explicit manifest navigation wins collisions deterministically', () {
    final destinations = resolver.resolve(
      manifest(
        vertical: 'salons-personal-care',
        primaryMode: 'booking',
        navigation: [
          {
            'id': 'appointments',
            'label': 'My schedule',
            'route': '/custom-schedule',
            'priority': 950,
          },
        ],
      ),
    );

    final appointment = destinations.singleWhere(
      (destination) => destination.id == 'appointments',
    );

    expect(appointment.label, 'My schedule');
    expect(appointment.route, '/custom-schedule');
    expect(appointment.source, 'manifest');
  });

  test('supporting mode order does not change resolved destination order', () {
    final first = resolver.resolve(
      manifest(
        vertical: 'booking-capacity',
        primaryMode: 'booking',
        supportingModes: ['subscription', 'sales'],
      ),
    );
    final second = resolver.resolve(
      manifest(
        vertical: 'booking-capacity',
        primaryMode: 'booking',
        supportingModes: ['sales', 'subscription'],
      ),
    );

    expect(
      first.map((destination) => destination.id),
      second.map((destination) => destination.id),
    );
  });
}
