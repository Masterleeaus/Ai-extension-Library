import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/shared/design_system/components/titan_action_sheet.dart';
import 'package:qrpay/shared/design_system/components/titan_list_tile.dart';
import 'package:qrpay/shared/design_system/foundations/titan_theme.dart';

class _Harness extends StatefulWidget {
  const _Harness({this.textScale = 1});

  final double textScale;

  @override
  State<_Harness> createState() => _HarnessState();
}

class _HarnessState extends State<_Harness> {
  int? selected;

  @override
  Widget build(BuildContext context) {
    return MediaQuery(
      data: MediaQueryData(textScaler: TextScaler.linear(widget.textScale)),
      child: Scaffold(
        body: Column(
          children: [
            FilledButton(
              onPressed: () async {
                final result = await showTitanActionSheet<int>(
                  context,
                  title: 'Booking actions',
                  message: 'Choose what should happen next.',
                  actions: const [
                    TitanActionSheetAction(
                      value: 1,
                      label: 'Open booking',
                      icon: Icons.open_in_new,
                      tone: TitanActionSheetTone.primary,
                    ),
                    TitanActionSheetAction(
                      value: 2,
                      label: 'Unavailable action',
                      enabled: false,
                    ),
                    TitanActionSheetAction(
                      value: 3,
                      label: 'Delete booking',
                      icon: Icons.delete_outline,
                      tone: TitanActionSheetTone.destructive,
                    ),
                  ],
                );
                if (mounted) setState(() => selected = result);
              },
              child: const Text('Show actions'),
            ),
            Text('Selected: ${selected ?? 'none'}'),
          ],
        ),
      ),
    );
  }
}

void main() {
  testWidgets('action sheet returns a strongly typed selection', (tester) async {
    await tester.pumpWidget(
      MaterialApp(theme: TitanTheme.light(), home: const _Harness()),
    );

    await tester.tap(find.text('Show actions'));
    await tester.pumpAndSettle();
    expect(find.text('Booking actions'), findsOneWidget);
    expect(find.text('Choose what should happen next.'), findsOneWidget);

    await tester.tap(find.text('Open booking'));
    await tester.pumpAndSettle();
    expect(find.text('Selected: 1'), findsOneWidget);
  });

  testWidgets('disabled action remains open and cannot return a value',
      (tester) async {
    await tester.pumpWidget(
      MaterialApp(theme: TitanTheme.light(), home: const _Harness()),
    );

    await tester.tap(find.text('Show actions'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Unavailable action'));
    await tester.pumpAndSettle();

    expect(find.text('Booking actions'), findsOneWidget);
    expect(find.text('Selected: none'), findsOneWidget);
  });

  testWidgets('destructive action is announced explicitly', (tester) async {
    await tester.pumpWidget(
      MaterialApp(theme: TitanTheme.light(), home: const _Harness()),
    );

    await tester.tap(find.text('Show actions'));
    await tester.pumpAndSettle();

    final destructive = find
        .ancestor(
          of: find.text('Delete booking'),
          matching: find.byType(TitanListTile),
        )
        .first;
    expect(
      tester.getSemantics(destructive),
      matchesSemantics(
        label: 'Delete booking, destructive action',
        isButton: true,
        hasEnabledState: true,
        isEnabled: true,
      ),
    );
  });

  testWidgets('action sheet supports 200 percent text without overflow',
      (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: TitanTheme.light(),
        home: const _Harness(textScale: 2),
      ),
    );

    await tester.tap(find.text('Show actions'));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    expect(find.text('Delete booking'), findsOneWidget);
  });
}
