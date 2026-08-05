import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_spacing.dart';

enum TitanButtonVariant { filled, outlined, text, danger }

class TitanButton extends StatelessWidget {
  const TitanButton({
    required this.label,
    required this.onPressed,
    this.variant = TitanButtonVariant.filled,
    this.icon,
    this.isLoading = false,
    this.expand = false,
    super.key,
  });

  final String label;
  final VoidCallback? onPressed;
  final TitanButtonVariant variant;
  final IconData? icon;
  final bool isLoading;
  final bool expand;

  @override
  Widget build(BuildContext context) {
    final effectiveCallback = isLoading ? null : onPressed;
    final child = _TitanButtonContent(
      label: label,
      icon: icon,
      isLoading: isLoading,
    );

    final button = switch (variant) {
      TitanButtonVariant.filled => FilledButton(
          onPressed: effectiveCallback,
          child: child,
        ),
      TitanButtonVariant.outlined => OutlinedButton(
          onPressed: effectiveCallback,
          child: child,
        ),
      TitanButtonVariant.text => TextButton(
          onPressed: effectiveCallback,
          child: child,
        ),
      TitanButtonVariant.danger => FilledButton(
          style: FilledButton.styleFrom(
            backgroundColor: TitanColors.danger,
            foregroundColor: Colors.white,
          ),
          onPressed: effectiveCallback,
          child: child,
        ),
    };

    return Semantics(
      label: label,
      button: true,
      enabled: effectiveCallback != null,
      container: true,
      child: ExcludeSemantics(
        child: ConstrainedBox(
          constraints: const BoxConstraints(minHeight: 48, minWidth: 48),
          child: SizedBox(width: expand ? double.infinity : null, child: button),
        ),
      ),
    );
  }
}

class _TitanButtonContent extends StatelessWidget {
  const _TitanButtonContent({
    required this.label,
    required this.icon,
    required this.isLoading,
  });

  final String label;
  final IconData? icon;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    final foreground = IconTheme.of(context).color;
    final children = <Widget>[];

    if (isLoading) {
      children.add(
        SizedBox.square(
          dimension: 18,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: foreground,
          ),
        ),
      );
    } else if (icon != null) {
      children.add(Icon(icon, size: 20));
    }

    if (children.isNotEmpty) {
      children.add(const SizedBox(width: TitanSpacing.sm));
    }
    children.add(Flexible(child: Text(label, textAlign: TextAlign.center)));

    return Row(
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: children,
    );
  }
}
