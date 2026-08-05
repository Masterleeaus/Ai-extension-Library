import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/shared/design_system/components/titan_badge.dart';
import 'package:qrpay/shared/design_system/components/titan_list_tile.dart';
import 'package:qrpay/shared/design_system/components/titan_text_field.dart';
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
  testWidgets('list tile exposes a 48px labelled row with metadata and badge',
      (tester) async {
    var presses = 0;

    await tester.pumpWidget(
      _app(
        TitanListTile(
          title: 'Property inspection',
          subtitle: '12 Collins Street',
          leading: const Icon(Icons.home_outlined),
          metadata: '9:30 AM',
          badge: const TitanBadge(label: '3 tasks'),
          onTap: () => presses++,
        ),
      ),
    );

    expect(tester.getSize(find.byType(TitanListTile)).height,
        greaterThanOrEqualTo(48));
    expect(find.text('Property inspection'), findsOneWidget);
    expect(find.text('12 Collins Street'), findsOneWidget);
    expect(find.text('9:30 AM'), findsOneWidget);
    expect(find.text('3 tasks'), findsOneWidget);
    expect(
      tester.getSemantics(find.byType(TitanListTile)),
      matchesSemantics(
        label: 'Property inspection\n12 Collins Street\n9:30 AM\n3 tasks',
        isButton: true,
        hasEnabledState: true,
        isEnabled: true,
      ),
    );
    await tester.tap(find.byType(TitanListTile));
    expect(presses, 1);
  });

  testWidgets('destructive list tile announces destructive action',
      (tester) async {
    await tester.pumpWidget(
      _app(
        const TitanListTile(
          title: 'Delete saved card',
          isDestructive: true,
        ),
      ),
    );

    expect(
      tester.getSemantics(find.byType(TitanListTile)),
      matchesSemantics(label: 'Delete saved card, destructive action'),
    );
  });

  testWidgets('text field renders helper and error text', (tester) async {
    final controller = TextEditingController();
    addTearDown(controller.dispose);

    await tester.pumpWidget(
      _app(
        TitanTextField(
          controller: controller,
          label: 'Email',
          hint: 'name@example.com',
          helperText: 'Used for receipts',
          errorText: 'Enter a valid email',
        ),
      ),
    );

    expect(find.text('Email'), findsOneWidget);
    expect(find.text('name@example.com'), findsOneWidget);
    expect(find.text('Used for receipts'), findsOneWidget);
    expect(find.text('Enter a valid email'), findsOneWidget);
  });

  testWidgets('clear action removes text without retaining a copy',
      (tester) async {
    final controller = TextEditingController(text: 'Temporary note');
    addTearDown(controller.dispose);

    await tester.pumpWidget(
      _app(
        TitanTextField(
          controller: controller,
          label: 'Note',
          showClear: true,
        ),
      ),
    );

    await tester.tap(find.byTooltip('Clear Note'));
    await tester.pump();
    expect(controller.text, isEmpty);
  });

  testWidgets('password reveal toggles obscured entry', (tester) async {
    final controller = TextEditingController(text: 'not-logged');
    addTearDown(controller.dispose);

    await tester.pumpWidget(
      _app(
        TitanTextField(
          controller: controller,
          label: 'Password',
          obscureText: true,
        ),
      ),
    );

    expect(
        tester.widget<TextField>(find.byType(TextField)).obscureText, isTrue);
    await tester.tap(find.byTooltip('Show Password'));
    await tester.pump();
    expect(
        tester.widget<TextField>(find.byType(TextField)).obscureText, isFalse);
    expect(find.byTooltip('Hide Password'), findsOneWidget);
  });

  testWidgets('basic multiline field supports 200 percent text',
      (tester) async {
    final controller = TextEditingController();
    addTearDown(controller.dispose);

    await tester.pumpWidget(
      _app(
        SizedBox(
          width: 320,
          child: TitanTextField(
            controller: controller,
            label: 'Access instructions',
            hint: 'Add gate codes and safe entry details',
            variant: TitanTextFieldVariant.basic,
            maxLines: 4,
            prefix: const Icon(Icons.key_outlined),
          ),
        ),
        textScale: 2,
      ),
    );

    expect(tester.takeException(), isNull);
    expect(find.text('Access instructions'), findsOneWidget);
  });
}
