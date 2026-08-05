import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/manifest/app_mode.dart';
import 'package:qrpay/core/manifest/business_app_manifest.dart';
import 'package:qrpay/core/manifest/vertical_slug.dart';

void main() {
  group('VerticalSlug', () {
    test('normalises canonical and legacy values', () {
      expect(
        VerticalSlug.normalize('field_home_services'),
        VerticalSlug.fieldHomeServices,
      );
      expect(
        VerticalSlug.normalize('hotel_bnb'),
        VerticalSlug.accommodationRooming,
      );
      expect(
        VerticalSlug.normalize('automotive'),
        VerticalSlug.automotiveServices,
      );
      expect(VerticalSlug.normalize('unknown_vertical'), isNull);
    });
  });

  group('AppMode', () {
    test('normalises aliases and rejects unknown values', () {
      expect(AppMode.normalize('quote-first'), AppMode.quoteFirst);
      expect(AppMode.normalize('capacity-booking'), AppMode.capacityBooking);
      expect(AppMode.normalize('made_up_mode'), isNull);
    });
  });

  group('BusinessAppManifest', () {
    test('parses one primary mode and de-duplicates supporting modes', () {
      final manifest = BusinessAppManifest.fromJson({
        'business_profile_id': 'business-42',
        'primary_vertical': 'field_service',
        'secondary_verticals': ['ecommerce_retail', 'field-home-services'],
        'primary_app_mode': 'service',
        'supporting_app_modes': [
          'booking',
          'quote-first',
          'booking',
          'unknown',
        ],
        'branding': {'app_name': 'Jason Services'},
        'feature_flags': {'wallet': false, 'qr': true},
      });

      expect(manifest.businessProfileId, 'business-42');
      expect(manifest.primaryVertical, VerticalSlug.fieldHomeServices);
      expect(manifest.secondaryVerticals, [VerticalSlug.ecommerceRetail]);
      expect(manifest.primaryAppMode, AppMode.service);
      expect(
        manifest.supportingAppModes,
        [AppMode.booking, AppMode.quoteFirst],
      );
      expect(manifest.branding.appName, 'Jason Services');
      expect(manifest.featureFlags['wallet'], isFalse);
      expect(manifest.featureFlags['qr'], isTrue);
    });

    test('uses Titan Hub defaults when optional fields are missing', () {
      final manifest = BusinessAppManifest.fromJson({
        'primary_vertical': 'fitness_membership',
        'primary_app_mode': 'membership',
      });

      expect(manifest.businessProfileId, 'default-business');
      expect(manifest.branding.appName, 'Titan Hub');
      expect(manifest.branding.accentHex, '#1F6BFF');
      expect(manifest.supportingAppModes, isEmpty);
      expect(manifest.secondaryVerticals, isEmpty);
    });
  });
}
