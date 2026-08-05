import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/core/manifest/business_app_manifest.dart';
import 'package:qrpay/features/shell/titan_hub_shell.dart';

void main() {
  final manifest = BusinessAppManifest.fromJson({
    'business_profile_id': 'clean-smart',
    'primary_vertical': 'field-home-services',
    'primary_app_mode': 'service',
    'supporting_app_modes': ['booking', 'quote_first'],
    'branding': {'app_name': 'CleanSmart'},
  });

  Future<void> pumpShell(
    WidgetTester tester, {
    required Size size,
    VoidCallback? onHubQr,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    addTearDown(tester.view.resetDevicePixelRatio);
    addTearDown(tester.view.resetPhysicalSize);

    await tester.pumpWidget(
      MaterialApp(
        home: TitanHubShell(
          manifest: manifest,
          onHubQr: onHubQr,
        ),
      ),
    );
    await tester.pumpAndSettle();
  }

  testWidgets('uses bottom navigation on compact screens', (tester) async {
    await pumpShell(tester, size: const Size(390, 844));

    expect(find.byType(NavigationBar), findsOneWidget);
    expect(find.byType(NavigationRail), findsNothing);
    expect(find.text('Home'), findsWidgets);
    expect(find.text('Explore'), findsWidgets);
    expect(find.text('Hub QR'), findsWidgets);
    expect(find.text('Activity'), findsWidgets);
    expect(find.text('Account'), findsWidgets);
    expect(find.text('CleanSmart'), findsOneWidget);
  });

  testWidgets('uses a navigation rail on expanded screens', (tester) async {
    await pumpShell(tester, size: const Size(1200, 800));

    expect(find.byType(NavigationRail), findsOneWidget);
    expect(find.byType(NavigationBar), findsNothing);
    expect(find.text('CleanSmart'), findsOneWidget);
  });

  testWidgets('Hub QR invokes the retained QR action', (tester) async {
    var qrInvocations = 0;
    await pumpShell(
      tester,
      size: const Size(390, 844),
      onHubQr: () => qrInvocations++,
    );

    await tester.tap(find.text('Hub QR').last);
    await tester.pump();

    expect(qrInvocations, 1);
    expect(find.text('Home'), findsWidgets);
  });

  testWidgets('Explore exposes resolved vertical and mode capabilities',
      (tester) async {
    await pumpShell(tester, size: const Size(390, 844));

    await tester.tap(find.text('Explore').last);
    await tester.pumpAndSettle();

    expect(find.text('Services'), findsOneWidget);
    expect(find.text('Appointments'), findsOneWidget);
    expect(find.text('Request a quote'), findsOneWidget);
  });
}
