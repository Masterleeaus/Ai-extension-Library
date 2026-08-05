import 'package:flutter/material.dart';

import '../foundations/titan_spacing.dart';
import 'titan_card.dart';

class TitanActionCard extends StatelessWidget {
  const TitanActionCard({
    required this.title,
    required this.description,
    required this.icon,
    required this.onTap,
    this.badge,
    this.isLoading = false,
    this.enabled = true,
    super.key,
  });

  final String title;
  final String description;
  final IconData icon;
  final VoidCallback onTap;
  final Widget? badge;
  final bool isLoading;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    final effectiveCallback = enabled && !isLoading ? onTap : null;
    final theme = Theme.of(context);

    return Semantics(
      label: '$title\n$description',
      button: true,
      enabled: effectiveCallback != null,
      container: true,
      child: ExcludeSemantics(
        child: Opacity(
          opacity: enabled ? 1 : 0.58,
          child: TitanCard(
            onTap: effectiveCallback,
            semanticLabel: '',
            child: LayoutBuilder(
              builder: (context, constraints) {
                final iconWidget = Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: theme.colorScheme.primaryContainer,
                    shape: BoxShape.circle,
                  ),
                  alignment: Alignment.center,
                  child: isLoading
                      ? SizedBox.square(
                          dimension: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: theme.colorScheme.onPrimaryContainer,
                          ),
                        )
                      : Icon(
                          icon,
                          color: theme.colorScheme.onPrimaryContainer,
                        ),
                );
                final copy = Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(title, style: theme.textTheme.titleMedium),
                    const SizedBox(height: TitanSpacing.xs),
                    Text(
                      description,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                );

                if (constraints.maxWidth >= 520) {
                  return Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      iconWidget,
                      const SizedBox(width: TitanSpacing.lg),
                      Expanded(child: copy),
                      if (badge != null) ...[
                        const SizedBox(width: TitanSpacing.lg),
                        badge!,
                      ],
                    ],
                  );
                }

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Wrap(
                      spacing: TitanSpacing.md,
                      runSpacing: TitanSpacing.sm,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [iconWidget, if (badge != null) badge!],
                    ),
                    const SizedBox(height: TitanSpacing.md),
                    copy,
                  ],
                );
              },
            ),
          ),
        ),
      ),
    );
  }
}
