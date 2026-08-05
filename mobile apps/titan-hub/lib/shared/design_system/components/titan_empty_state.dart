import 'package:flutter/material.dart';

import '../foundations/titan_spacing.dart';
import 'titan_button.dart';

class TitanEmptyState extends StatelessWidget {
  const TitanEmptyState({
    required this.title,
    required this.message,
    required this.icon,
    this.actionLabel,
    this.onAction,
    super.key,
  });

  final String title;
  final String message;
  final IconData icon;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Semantics(
      label: '$title. $message',
      container: true,
      child: ExcludeSemantics(
        child: Padding(
          padding: const EdgeInsets.all(TitanSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                icon,
                size: 48,
                color: theme.colorScheme.primary,
              ),
              const SizedBox(height: TitanSpacing.lg),
              Text(
                title,
                textAlign: TextAlign.center,
                style: theme.textTheme.titleLarge,
              ),
              const SizedBox(height: TitanSpacing.sm),
              Text(
                message,
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              if (actionLabel != null && onAction != null) ...[
                const SizedBox(height: TitanSpacing.lg),
                TitanButton(label: actionLabel!, onPressed: onAction),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
