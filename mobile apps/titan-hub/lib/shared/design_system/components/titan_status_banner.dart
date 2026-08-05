import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_radius.dart';
import '../foundations/titan_spacing.dart';
import 'titan_button.dart';

enum TitanStatusTone { success, warning, danger, info }

/// Accessible operational banner for success, warning, error and info states.
class TitanStatusBanner extends StatelessWidget {
  const TitanStatusBanner({
    required this.tone,
    required this.title,
    required this.message,
    this.actionLabel,
    this.onAction,
    super.key,
  });

  const TitanStatusBanner.offline({
    this.tone = TitanStatusTone.warning,
    this.title = 'You are offline',
    this.message = 'Saved changes will sync when your connection returns.',
    this.actionLabel,
    this.onAction,
    super.key,
  });

  final TitanStatusTone tone;
  final String title;
  final String message;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final presentation = _presentation(theme, tone);
    final semanticLabel = '${presentation.name}. $title. $message';

    return Semantics(
      label: semanticLabel,
      container: true,
      child: ExcludeSemantics(
        child: Container(
          padding: const EdgeInsets.all(TitanSpacing.lg),
          decoration: BoxDecoration(
            color: presentation.color.withOpacity(0.14),
            border: Border.all(color: presentation.color),
            borderRadius: BorderRadius.circular(TitanRadius.standard),
          ),
          child: LayoutBuilder(
            builder: (context, constraints) {
              final copy = Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(presentation.icon, color: presentation.color),
                  const SizedBox(width: TitanSpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(title, style: theme.textTheme.titleMedium),
                        const SizedBox(height: TitanSpacing.xs),
                        Text(message, style: theme.textTheme.bodyMedium),
                      ],
                    ),
                  ),
                ],
              );

              if (actionLabel == null || onAction == null) return copy;
              if (constraints.maxWidth < 480) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    copy,
                    const SizedBox(height: TitanSpacing.md),
                    TitanButton(
                      label: actionLabel!,
                      variant: TitanButtonVariant.outlined,
                      onPressed: onAction,
                    ),
                  ],
                );
              }

              return Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(child: copy),
                  const SizedBox(width: TitanSpacing.lg),
                  TitanButton(
                    label: actionLabel!,
                    variant: TitanButtonVariant.outlined,
                    onPressed: onAction,
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

_StatusPresentation _presentation(ThemeData theme, TitanStatusTone tone) {
  final semantic = theme.extension<TitanSemanticColors>()!;
  return switch (tone) {
    TitanStatusTone.success => _StatusPresentation(
        'Success', semantic.success, Icons.check_circle_outline),
    TitanStatusTone.warning =>
      _StatusPresentation('Warning', semantic.warning, Icons.warning_amber),
    TitanStatusTone.danger =>
      _StatusPresentation('Error', semantic.danger, Icons.error_outline),
    TitanStatusTone.info =>
      _StatusPresentation('Information', semantic.info, Icons.info_outline),
  };
}

class _StatusPresentation {
  const _StatusPresentation(this.name, this.color, this.icon);

  final String name;
  final Color color;
  final IconData icon;
}
