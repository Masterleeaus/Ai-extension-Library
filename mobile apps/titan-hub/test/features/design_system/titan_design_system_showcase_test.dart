import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/manifest/business_app_manifest.dart';
import 'package:qrpay/core/navigation/manifest_navigation_resolver.dart';
import 'package:qrpay/features/design_system/titan_design_system_showcase.dart';
import 'package:qrpay/routes/routes.dart';
import 'package:qrpay/shared/design_system/foundations/titan_theme.dart';

// The showcase is internal tooling and must never become primary navigation.
// Its route exists for developers without adding a sixth customer destination.
// This contract protects that boundary as new component families are added.
// Exactly one route also prevents accidental duplicate developer registration.
Future<void> _pumpShowcase(
  WidgetTester tester, {
  required Size size,
  double textScale = 1,
}) async {
  tester.view.physicalSize = size;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);

  await tester.pumpWidget(
    MaterialApp(
      theme: TitanTheme.light(),
      home: MediaQuery(
        data: MediaQueryData(textScaler: TextScaler.linear(textScale)),
        child: const TitanDesignSystemShowcase(),
      ),
    ),
  );
  await tester.pump();
}

void main() {
  test('showcase route is internal and primary navigation remains five items',
      () {
    final showcaseRoutes = Routes.list
        .where((page) => page.name == Routes.designSystemShowcase)
        .toList(growable: false);
    expect(showcaseRoutes, hasLength(1));

    final manifest = BusinessAppManifest.fromJson(const {
      'business_profile_id': 'showcase-business',
      'primary_vertical': 'field_home_services',
      'primary_app_mode': 'service',
      'supporting_app_modes': ['booking', 'quote_first'],
    });
    final primary = const ManifestNavigationResolver()
        .resolve(manifest)
        .where((destination) => destination.isPrimary)
        .toList(growable: false);
    expect(primary, hasLength(5));
  });

  testWidgets('showcase renders public component families on a compact screen',
      (tester) async {
    await _pumpShowcase(
      tester,
      size: const Size(390, 1200),
      textScale: 2,
    );

    expect(find.text('Titan Hub Design System'), findsOneWidget);
    expect(find.text('Foundations'), findsOneWidget);
    expect(find.text('Actions and badges'), findsOneWidget);
    expect(find.text('Cards and lists'), findsOneWidget);
    expect(find.text('Forms'), findsOneWidget);
    expect(find.text('Feedback states'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('showcase uses a wider responsive composition', (tester) async {
    await _pumpShowcase(tester, size: const Size(1100, 900));

    expect(find.byKey(const ValueKey('showcase-wide-layout')), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('showcase opens the native typed action sheet', (tester) async {
    await _pumpShowcase(tester, size: const Size(390, 1200));

    final trigger = find.text('Show action sheet');
    await tester.ensureVisible(trigger);
    await tester.tap(trigger);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 500));

    expect(find.text('Showcase actions'), findsOneWidget);
    expect(find.text('Archive sample'), findsOneWidget);
  });
}
