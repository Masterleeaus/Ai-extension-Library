import 'package:flutter/material.dart';

import 'titan_colors.dart';
import 'titan_radius.dart';

abstract final class TitanTheme {
  static ThemeData light({Color? seedColor}) {
    final scheme = ColorScheme.fromSeed(
      seedColor: seedColor ?? TitanColors.primary,
      brightness: Brightness.light,
    ).copyWith(
      surface: TitanColors.lightSurface,
      onSurface: TitanColors.lightHeading,
      outline: TitanColors.lightOutline,
      error: TitanColors.danger,
      onError: Colors.white,
    );

    return _build(
      scheme: scheme,
      scaffoldBackground: TitanColors.lightBackground,
      muted: TitanColors.lightMuted,
    );
  }

  static ThemeData dark({Color? seedColor}) {
    final scheme = ColorScheme.fromSeed(
      seedColor: seedColor ?? TitanColors.primary,
      brightness: Brightness.dark,
    ).copyWith(
      surface: TitanColors.darkSurface,
      onSurface: TitanColors.darkHeading,
      outline: TitanColors.darkOutline,
      error: TitanColors.danger,
      onError: Colors.white,
    );

    return _build(
      scheme: scheme,
      scaffoldBackground: TitanColors.darkBackground,
      muted: TitanColors.darkMuted,
    );
  }

  static ThemeData _build({
    required ColorScheme scheme,
    required Color scaffoldBackground,
    required Color muted,
  }) {
    final base = ThemeData(
      useMaterial3: true,
      brightness: scheme.brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: scaffoldBackground,
      fontFamily: 'Inter',
    );
    final minimumButtonStyle = ButtonStyle(
      minimumSize: const MaterialStatePropertyAll(Size(48, 48)),
      padding: const MaterialStatePropertyAll(
        EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      ),
      shape: MaterialStatePropertyAll(
        RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(TitanRadius.standard),
        ),
      ),
    );

    return base.copyWith(
      textTheme: base.textTheme.apply(
        fontFamily: 'Inter',
        bodyColor: scheme.onSurface,
        displayColor: scheme.onSurface,
      ),
      filledButtonTheme: FilledButtonThemeData(style: minimumButtonStyle),
      elevatedButtonTheme: ElevatedButtonThemeData(style: minimumButtonStyle),
      outlinedButtonTheme: OutlinedButtonThemeData(style: minimumButtonStyle),
      textButtonTheme: TextButtonThemeData(style: minimumButtonStyle),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: scheme.surface,
        constraints: const BoxConstraints(minHeight: 48),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(TitanRadius.standard),
          borderSide: BorderSide(color: scheme.outline),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(TitanRadius.standard),
          borderSide: BorderSide(color: scheme.outline),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(TitanRadius.standard),
          borderSide: BorderSide(color: scheme.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(TitanRadius.standard),
          borderSide: const BorderSide(color: TitanColors.danger),
        ),
      ),
      cardTheme: CardTheme(
        color: scheme.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(TitanRadius.standard),
          side: BorderSide(color: scheme.outline),
        ),
      ),
      dividerTheme: DividerThemeData(color: scheme.outline, space: 1),
      navigationBarTheme: NavigationBarThemeData(
        height: 72,
        backgroundColor: scheme.surface,
        indicatorColor: scheme.primaryContainer,
      ),
      navigationRailTheme: NavigationRailThemeData(
        backgroundColor: scheme.surface,
        indicatorColor: scheme.primaryContainer,
        minWidth: 72,
      ),
      extensions: [
        TitanSemanticColors(
          success: TitanColors.success,
          onSuccess: Colors.white,
          warning: TitanColors.warning,
          onWarning: Colors.black,
          danger: TitanColors.danger,
          onDanger: Colors.white,
          info: TitanColors.info,
          onInfo: Colors.white,
          muted: muted,
        ),
      ],
    );
  }
}
