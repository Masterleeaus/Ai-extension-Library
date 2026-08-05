import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_spacing.dart';
import 'titan_button.dart';

class TitanErrorState extends StatelessWidget {
  const TitanErrorState({
    required this.title,
    required this.message,
    this.onRetry,
    this.retryLabel = 'Try again',
    super.key,
  });

  final String title;
  final String message;
  final VoidCallback? onRetry;
  final String retryLabel;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final danger = theme.extension<TitanSemanticColors>()!.danger;

    return Semantics(
      label: 'Error. $title. $message',
      container: true,
      child: ExcludeSemantics(
        child: Padding(
          padding: const EdgeInsets.all(TitanSpacing.xl),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 48, color: danger),
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
              if (onRetry != null) ...[
                const SizedBox(height: TitanSpacing.lg),
                TitanButton(
                  label: retryLabel,
                  variant: TitanButtonVariant.outlined,
                  onPressed: onRetry,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
