import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qrpay/shared/design_system/foundations/titan_colors.dart';
import 'package:qrpay/shared/design_system/foundations/titan_radius.dart';
import 'package:qrpay/shared/design_system/foundations/titan_spacing.dart';
import 'package:qrpay/shared/design_system/foundations/titan_theme.dart';

void main() {
  test('preserves approved MobileKit-derived semantic colours', () {
    expect(TitanColors.primary, const Color(0xFF1E74FD));
    expect(TitanColors.secondary, const Color(0xFF6C7C94));
    expect(TitanColors.success, const Color(0xFF34C759));
    expect(TitanColors.danger, const Color(0xFFEC4433));
    expect(TitanColors.warning, const Color(0xFFFE9500));
    expect(TitanColors.info, const Color(0xFF592BCA));
    expect(TitanColors.darkBackground, const Color(0xFF0C1624));
    expect(TitanColors.darkSurface, const Color(0xFF0F1C2F));
  });

  test('uses a predictable spacing and radius scale', () {
    expect(TitanSpacing.xs, 4);
    expect(TitanSpacing.sm, 8);
    expect(TitanSpacing.md, 12);
    expect(TitanSpacing.lg, 16);
    expect(TitanSpacing.xl, 24);
    expect(TitanSpacing.xxl, 32);
    expect(TitanRadius.compact, 8);
    expect(TitanRadius.standard, 12);
    expect(TitanRadius.prominent, 20);
  });

  test('builds Material 3 light theme with semantic extensions', () {
    final theme = TitanTheme.light();
    final semantics = theme.extension<TitanSemanticColors>();

    expect(theme.useMaterial3, isTrue);
    expect(theme.brightness, Brightness.light);
    expect(theme.textTheme.bodyMedium?.fontFamily, contains('Inter'));
    expect(semantics, isNotNull);
    expect(semantics!.success, TitanColors.success);
    expect(semantics.warning, TitanColors.warning);
    expect(semantics.danger, TitanColors.danger);
    expect(semantics.info, TitanColors.info);

    final minimumSize = theme.filledButtonTheme.style?.minimumSize?.resolve({});
    expect(minimumSize?.height, greaterThanOrEqualTo(48));
  });

  test('builds distinct readable dark surfaces', () {
    final theme = TitanTheme.dark();
    final semantics = theme.extension<TitanSemanticColors>();

    expect(theme.useMaterial3, isTrue);
    expect(theme.brightness, Brightness.dark);
    expect(theme.scaffoldBackgroundColor, TitanColors.darkBackground);
    expect(theme.colorScheme.surface, TitanColors.darkSurface);
    expect(theme.colorScheme.surface, isNot(theme.scaffoldBackgroundColor));
    expect(theme.colorScheme.onSurface, isNot(TitanColors.darkSurface));
    expect(semantics?.success, TitanColors.success);
  });

  test('tenant seed changes primary without changing status semantics', () {
    const tenantAccent = Color(0xFF8B5CF6);
    final theme = TitanTheme.light(seedColor: tenantAccent);
    final semantics = theme.extension<TitanSemanticColors>()!;

    expect(theme.colorScheme.primary, isNot(TitanColors.primary));
    expect(semantics.success, TitanColors.success);
    expect(semantics.warning, TitanColors.warning);
    expect(semantics.danger, TitanColors.danger);
  });
}
