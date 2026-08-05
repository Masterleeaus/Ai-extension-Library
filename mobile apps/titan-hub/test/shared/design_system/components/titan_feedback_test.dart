import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/shared/design_system/components/titan_empty_state.dart';
import 'package:qrpay/shared/design_system/components/titan_error_state.dart';
import 'package:qrpay/shared/design_system/components/titan_loading.dart';
import 'package:qrpay/shared/design_system/components/titan_status_banner.dart';
import 'package:qrpay/shared/design_system/foundations/titan_theme.dart';

Widget _app(Widget child, {double textScale = 1}) {
  return MaterialApp(
    theme: TitanTheme.light(),
    home: MediaQuery(
      data: MediaQueryData(textScaler: TextScaler.linear(textScale)),
      child: Scaffold(body: SingleChildScrollView(child: child)),
    ),
  );
}

void main() {
  testWidgets('status banner announces tone, title and message', (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        TitanStatusBanner(
          tone: TitanStatusTone.warning,
          title: 'Connection interrupted',
          message: 'Changes will sync when the device is online.',
          actionLabel: 'Retry',
          onAction: () => presses++,
        ),
      ),
    );

    expect(
      tester.getSemantics(find.byType(TitanStatusBanner)),
      matchesSemantics(
        label:
            'Warning. Connection interrupted. Changes will sync when the device is online.',
      ),
    );
    await tester.tap(find.text('Retry'));
    expect(presses, 1);
  });

  testWidgets('offline factory provides consistent copy', (tester) async {
    await tester.pumpWidget(_app(const TitanStatusBanner.offline()));

    expect(find.text('You are offline'), findsOneWidget);
    expect(find.text('Saved changes will sync when your connection returns.'),
        findsOneWidget);
  });

  testWidgets('empty state supports an accessible action', (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        TitanEmptyState(
          title: 'No upcoming bookings',
          message: 'Create a booking or share your booking link.',
          icon: Icons.event_available_outlined,
          actionLabel: 'Create booking',
          onAction: () => presses++,
        ),
      ),
    );

    expect(find.text('No upcoming bookings'), findsOneWidget);
    expect(find.text('Create a booking or share your booking link.'),
        findsOneWidget);
    await tester.tap(find.text('Create booking'));
    expect(presses, 1);
  });

  testWidgets('error state invokes retry and announces error', (tester) async {
    var retries = 0;

    await tester.pumpWidget(
      _app(
        TitanErrorState(
          title: 'Could not load invoices',
          message: 'Check your connection and try again.',
          onRetry: () => retries++,
        ),
      ),
    );

    expect(
      tester.getSemantics(find.byType(TitanErrorState)),
      matchesSemantics(
        label:
            'Error. Could not load invoices. Check your connection and try again.',
      ),
    );
    await tester.tap(find.text('Try again'));
    expect(retries, 1);
  });

  testWidgets('loading indicator exposes progress semantics', (tester) async {
    await tester.pumpWidget(
      _app(const TitanLoading(label: 'Loading customer activity')),
    );

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(
      tester.getSemantics(find.byType(TitanLoading)),
      matchesSemantics(
        label: 'Loading customer activity',
        isInMutuallyExclusiveGroup: false,
      ),
    );
  });

  testWidgets('feedback states support 200 percent text without overflow',
      (tester) async {
    await tester.pumpWidget(
      _app(
        const SizedBox(
          width: 320,
          child: TitanStatusBanner(
            tone: TitanStatusTone.info,
            title: 'A long operational status title for the current customer',
            message:
                'This message explains what happened and the next safe action without truncating important details.',
          ),
        ),
        textScale: 2,
      ),
    );

    expect(tester.takeException(), isNull);
  });
}
