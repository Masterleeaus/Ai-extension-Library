import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/shared/design_system/components/titan_action_card.dart';
import 'package:qrpay/shared/design_system/components/titan_badge.dart';
import 'package:qrpay/shared/design_system/components/titan_button.dart';
import 'package:qrpay/shared/design_system/components/titan_card.dart';
import 'package:qrpay/shared/design_system/components/titan_section_header.dart';
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
  testWidgets('buttons expose 48px targets and suppress unavailable callbacks',
      (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        Column(
          children: [
            TitanButton(
              label: 'Continue',
              icon: Icons.arrow_forward,
              onPressed: () => presses++,
            ),
            TitanButton(
              label: 'Saving',
              isLoading: true,
              onPressed: () => presses++,
            ),
            const TitanButton(label: 'Unavailable', onPressed: null),
          ],
        ),
      ),
    );

    expect(tester.getSize(find.widgetWithText(TitanButton, 'Continue')).height,
        greaterThanOrEqualTo(48));
    await tester.tap(find.text('Continue'));
    await tester.tap(find.text('Saving'));
    await tester.tap(find.text('Unavailable'));
    expect(presses, 1);
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(
      tester.getSemantics(find.text('Saving')),
      matchesSemantics(
        label: 'Saving',
        isButton: true,
        hasEnabledState: true,
        isEnabled: false,
      ),
    );
  });

  testWidgets('badge and section header preserve semantic text',
      (tester) async {
    await tester.pumpWidget(
      _app(
        const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TitanSectionHeader(
              title: 'Upcoming work',
              subtitle: 'Two jobs need attention',
              action: Text('View all'),
            ),
            TitanBadge(label: 'Warning', tone: TitanTone.warning),
          ],
        ),
      ),
    );

    expect(find.text('Upcoming work'), findsOneWidget);
    expect(find.text('Two jobs need attention'), findsOneWidget);
    expect(find.text('View all'), findsOneWidget);
    expect(
      tester.getSemantics(find.text('Warning')),
      matchesSemantics(label: 'Warning'),
    );
  });

  testWidgets('whole card is a labelled accessible press target',
      (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        TitanCard(
          header: const Text('Invoice ready'),
          footer: const Text('Due Friday'),
          onTap: () => presses++,
          child: const Text(r'$240.00'),
        ),
      ),
    );

    expect(
      tester.getSemantics(find.byType(TitanCard)),
      matchesSemantics(
        label: 'Invoice ready\n\$240.00\nDue Friday',
        isButton: true,
        hasEnabledState: true,
        isEnabled: true,
      ),
    );
    await tester.tap(find.byType(TitanCard));
    expect(presses, 1);
  });

  testWidgets('action card supports 200 percent text without overflow',
      (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        SizedBox(
          width: 320,
          child: TitanActionCard(
            title: 'Schedule a recurring maintenance appointment',
            description:
                'Choose availability, assign a technician and confirm reminders.',
            icon: Icons.calendar_month,
            badge: const TitanBadge(label: 'Recommended'),
            onTap: () => presses++,
          ),
        ),
        textScale: 2,
      ),
    );

    expect(tester.takeException(), isNull);
    expect(find.text('Schedule a recurring maintenance appointment'),
        findsOneWidget);
    expect(find.text('Recommended'), findsOneWidget);
    await tester.tap(find.byType(TitanActionCard));
    expect(presses, 1);
  });

  testWidgets('disabled action card does not invoke callback', (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        TitanActionCard(
          title: 'Locked capability',
          description: 'This capability is not included in the current plan.',
          icon: Icons.lock,
          enabled: false,
          onTap: () => presses++,
        ),
      ),
    );

    await tester.tap(find.byType(TitanActionCard));
    expect(presses, 0);
    expect(
      tester.getSemantics(find.byType(TitanActionCard)),
      matchesSemantics(
        label:
            'Locked capability\nThis capability is not included in the current plan.',
        isButton: true,
        hasEnabledState: true,
        isEnabled: false,
      ),
    );
  });
}
