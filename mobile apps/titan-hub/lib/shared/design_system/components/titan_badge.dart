import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_radius.dart';
import '../foundations/titan_spacing.dart';

enum TitanTone { neutral, primary, success, warning, danger, info }

class TitanBadge extends StatelessWidget {
  const TitanBadge({
    required this.label,
    this.tone = TitanTone.neutral,
    super.key,
  });

  final String label;
  final TitanTone tone;

  @override
  Widget build(BuildContext context) {
    final colors = _toneColors(context, tone);

    return Semantics(
      label: label,
      container: true,
      child: ExcludeSemantics(
        child: Container(
          padding: const EdgeInsets.symmetric(
            horizontal: TitanSpacing.sm,
            vertical: TitanSpacing.xs,
          ),
          decoration: BoxDecoration(
            color: colors.background,
            borderRadius: BorderRadius.circular(TitanRadius.prominent),
          ),
          child: Text(
            label,
            style: Theme.of(context).textTheme.labelMedium?.copyWith(
                  color: colors.foreground,
                  fontWeight: FontWeight.w700,
                ),
          ),
        ),
      ),
    );
  }
}

_TitanToneColors _toneColors(BuildContext context, TitanTone tone) {
  final theme = Theme.of(context);
  final scheme = theme.colorScheme;
  final semantic = theme.extension<TitanSemanticColors>()!;

  return switch (tone) {
    TitanTone.neutral => _TitanToneColors(
        scheme.surfaceContainerHighest,
        scheme.onSurfaceVariant,
      ),
    TitanTone.primary => _TitanToneColors(
        scheme.primaryContainer,
        scheme.onPrimaryContainer,
      ),
    TitanTone.success => _TitanToneColors(
        semantic.success.withOpacity(0.16),
        semantic.success,
      ),
    TitanTone.warning => _TitanToneColors(
        semantic.warning.withOpacity(0.18),
        semantic.onWarning == Colors.black
            ? const Color(0xFF6B3D00)
            : semantic.warning,
      ),
    TitanTone.danger => _TitanToneColors(
        semantic.danger.withOpacity(0.16),
        semantic.danger,
      ),
    TitanTone.info => _TitanToneColors(
        semantic.info.withOpacity(0.16),
        semantic.info,
      ),
  };
}

class _TitanToneColors {
  const _TitanToneColors(this.background, this.foreground);

  final Color background;
  final Color foreground;
}
