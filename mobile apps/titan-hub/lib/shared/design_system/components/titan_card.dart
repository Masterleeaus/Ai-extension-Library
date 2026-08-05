import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_radius.dart';
import '../foundations/titan_spacing.dart';
import 'titan_badge.dart';

enum TitanCardVariant { outlined, elevated, tonal }

class TitanCard extends StatelessWidget {
  const TitanCard({
    required this.child,
    this.header,
    this.footer,
    this.onTap,
    this.variant = TitanCardVariant.outlined,
    this.statusTone,
    this.semanticLabel,
    this.padding = const EdgeInsets.all(TitanSpacing.lg),
    super.key,
  });

  final Widget child;
  final Widget? header;
  final Widget? footer;
  final VoidCallback? onTap;
  final TitanCardVariant variant;
  final TitanTone? statusTone;
  final String? semanticLabel;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final radius = BorderRadius.circular(TitanRadius.standard);
    final borderColor = statusTone == null
        ? theme.colorScheme.outline
        : _toneColor(theme, statusTone!);
    final background = switch (variant) {
      TitanCardVariant.tonal => theme.colorScheme.surfaceContainerLow,
      _ => theme.colorScheme.surface,
    };
    final label = semanticLabel ??
        [header, child, footer]
            .map(_widgetText)
            .where((value) => value != null && value.isNotEmpty)
            .join('\n');

    final content = Padding(
      padding: padding,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          if (header != null) ...[
            DefaultTextStyle.merge(
              style: theme.textTheme.titleMedium,
              child: header!,
            ),
            const SizedBox(height: TitanSpacing.md),
          ],
          child,
          if (footer != null) ...[
            const SizedBox(height: TitanSpacing.md),
            DefaultTextStyle.merge(
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
              child: footer!,
            ),
          ],
        ],
      ),
    );

    return Semantics(
      label: label.isEmpty ? null : label,
      button: onTap != null,
      enabled: onTap != null,
      container: true,
      child: ExcludeSemantics(
        child: Card(
          elevation: variant == TitanCardVariant.elevated ? 2 : 0,
          color: background,
          shape: RoundedRectangleBorder(
            borderRadius: radius,
            side: BorderSide(
              color: borderColor,
              width: statusTone == null ? 1 : 2,
            ),
          ),
          clipBehavior: Clip.antiAlias,
          child: onTap == null
              ? content
              : InkWell(
                  onTap: onTap,
                  borderRadius: radius,
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(minHeight: 48),
                    child: content,
                  ),
                ),
        ),
      ),
    );
  }
}

String? _widgetText(Widget? widget) {
  if (widget is Text) return widget.data;
  return null;
}

Color _toneColor(ThemeData theme, TitanTone tone) {
  final semantic = theme.extension<TitanSemanticColors>()!;
  return switch (tone) {
    TitanTone.neutral => theme.colorScheme.outline,
    TitanTone.primary => theme.colorScheme.primary,
    TitanTone.success => semantic.success,
    TitanTone.warning => semantic.warning,
    TitanTone.danger => semantic.danger,
    TitanTone.info => semantic.info,
  };
}
