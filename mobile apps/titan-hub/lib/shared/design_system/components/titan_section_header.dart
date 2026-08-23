import 'package:flutter/material.dart';

import '../foundations/titan_spacing.dart';

class TitanSectionHeader extends StatelessWidget {
  const TitanSectionHeader({
    required this.title,
    this.subtitle,
    this.action,
    super.key,
  });

  final String title;
  final String? subtitle;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final copy = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(title, style: theme.textTheme.titleLarge),
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

    if (action == null) return copy;

    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth >= 480) {
          return Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(child: copy),
              const SizedBox(width: TitanSpacing.lg),
              action!,
            ],
          );
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            copy,
            const SizedBox(height: TitanSpacing.sm),
            action!,
          ],
        );
      },
    );
  }
}
