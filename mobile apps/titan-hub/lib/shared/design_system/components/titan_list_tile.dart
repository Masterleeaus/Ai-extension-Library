import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_spacing.dart';
import 'titan_badge.dart';

class TitanListTile extends StatelessWidget {
  const TitanListTile({
    required this.title,
    this.subtitle,
    this.leading,
    this.trailing,
    this.metadata,
    this.badge,
    this.onTap,
    this.isDestructive = false,
    this.enabled = true,
    super.key,
  });

  final String title;
  final String? subtitle;
  final Widget? leading;
  final Widget? trailing;
  final String? metadata;
  final Widget? badge;
  final VoidCallback? onTap;
  final bool isDestructive;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final effectiveTap = enabled ? onTap : null;
    final label = <String>[
      isDestructive ? '$title, destructive action' : title,
      if (subtitle != null) subtitle!,
      if (metadata != null) metadata!,
      if (badge is TitanBadge) (badge! as TitanBadge).label,
    ].join('\n');
    final titleColor = isDestructive
        ? theme.extension<TitanSemanticColors>()!.danger
        : theme.colorScheme.onSurface;

    final content = ConstrainedBox(
      constraints: const BoxConstraints(minHeight: 48),
      child: Padding(
        padding: const EdgeInsets.symmetric(
          horizontal: TitanSpacing.lg,
          vertical: TitanSpacing.md,
        ),
        child: LayoutBuilder(
          builder: (context, constraints) {
            final copy = Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: theme.textTheme.bodyLarge?.copyWith(
                    color: titleColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: TitanSpacing.xs),
                  Text(
                    subtitle!,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ],
            );
            final trailingWidget = trailing ??
                (effectiveTap == null ? null : const Icon(Icons.chevron_right));
            final compact = constraints.maxWidth < 360 &&
                (metadata != null || badge != null);

            if (compact) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      if (leading != null) ...[
                        IconTheme.merge(
                          data: IconThemeData(color: titleColor),
                          child: leading!,
                        ),
                        const SizedBox(width: TitanSpacing.md),
                      ],
                      Expanded(child: copy),
                      if (trailingWidget != null) ...[
                        const SizedBox(width: TitanSpacing.sm),
                        trailingWidget,
                      ],
                    ],
                  ),
                  const SizedBox(height: TitanSpacing.md),
                  Wrap(
                    spacing: TitanSpacing.md,
                    runSpacing: TitanSpacing.sm,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      if (metadata != null)
                        Text(
                          metadata!,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      if (badge != null) badge!,
                    ],
                  ),
                ],
              );
            }

            return Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                if (leading != null) ...[
                  IconTheme.merge(
                    data: IconThemeData(color: titleColor),
                    child: leading!,
                  ),
                  const SizedBox(width: TitanSpacing.md),
                ],
                Expanded(child: copy),
                if (metadata != null) ...[
                  const SizedBox(width: TitanSpacing.md),
                  Flexible(
                    child: Text(
                      metadata!,
                      textAlign: TextAlign.end,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                ],
                if (badge != null) ...[
                  const SizedBox(width: TitanSpacing.md),
                  badge!,
                ],
                if (trailingWidget != null) ...[
                  const SizedBox(width: TitanSpacing.sm),
                  trailingWidget,
                ],
              ],
            );
          },
        ),
      ),
    );

    return Semantics(
      label: label,
      button: effectiveTap != null,
      enabled: effectiveTap != null ? true : null,
      container: true,
      child: ExcludeSemantics(
        child: Opacity(
          opacity: enabled ? 1 : 0.55,
          child: effectiveTap == null
              ? content
              : Material(
                  color: Colors.transparent,
                  child: InkWell(onTap: effectiveTap, child: content),
                ),
        ),
      ),
    );
  }
}
