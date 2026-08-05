import 'package:flutter/material.dart';

abstract final class TitanColors {
  static const Color primary = Color(0xFF1E74FD);
  static const Color secondary = Color(0xFF6C7C94);
  static const Color success = Color(0xFF34C759);
  static const Color danger = Color(0xFFEC4433);
  static const Color warning = Color(0xFFFE9500);
  static const Color info = Color(0xFF592BCA);

  static const Color lightBackground = Color(0xFFF9F9F9);
  static const Color lightSurface = Color(0xFFFFFFFF);
  static const Color lightOutline = Color(0xFFE1E1E1);
  static const Color lightHeading = Color(0xFF141515);
  static const Color lightText = Color(0xFF4F5050);
  static const Color lightMuted = Color(0xFFA1A1A2);

  static const Color darkBackground = Color(0xFF0C1624);
  static const Color darkSurface = Color(0xFF0F1C2F);
  static const Color darkOutline = Color(0xFF1B283B);
  static const Color darkHeading = Color(0xFFFFFFFF);
  static const Color darkText = Color(0xFFB8C7D4);
  static const Color darkMuted = Color(0xFF8195A6);
}

@immutable
class TitanSemanticColors extends ThemeExtension<TitanSemanticColors> {
  const TitanSemanticColors({
    required this.success,
    required this.onSuccess,
    required this.warning,
    required this.onWarning,
    required this.danger,
    required this.onDanger,
    required this.info,
    required this.onInfo,
    required this.muted,
  });

  final Color success;
  final Color onSuccess;
  final Color warning;
  final Color onWarning;
  final Color danger;
  final Color onDanger;
  final Color info;
  final Color onInfo;
  final Color muted;

  @override
  TitanSemanticColors copyWith({
    Color? success,
    Color? onSuccess,
    Color? warning,
    Color? onWarning,
    Color? danger,
    Color? onDanger,
    Color? info,
    Color? onInfo,
    Color? muted,
  }) {
    return TitanSemanticColors(
      success: success ?? this.success,
      onSuccess: onSuccess ?? this.onSuccess,
      warning: warning ?? this.warning,
      onWarning: onWarning ?? this.onWarning,
      danger: danger ?? this.danger,
      onDanger: onDanger ?? this.onDanger,
      info: info ?? this.info,
      onInfo: onInfo ?? this.onInfo,
      muted: muted ?? this.muted,
    );
  }

  @override
  TitanSemanticColors lerp(
    covariant ThemeExtension<TitanSemanticColors>? other,
    double t,
  ) {
    if (other is! TitanSemanticColors) return this;

    return TitanSemanticColors(
      success: Color.lerp(success, other.success, t)!,
      onSuccess: Color.lerp(onSuccess, other.onSuccess, t)!,
      warning: Color.lerp(warning, other.warning, t)!,
      onWarning: Color.lerp(onWarning, other.onWarning, t)!,
      danger: Color.lerp(danger, other.danger, t)!,
      onDanger: Color.lerp(onDanger, other.onDanger, t)!,
      info: Color.lerp(info, other.info, t)!,
      onInfo: Color.lerp(onInfo, other.onInfo, t)!,
      muted: Color.lerp(muted, other.muted, t)!,
    );
  }
}
