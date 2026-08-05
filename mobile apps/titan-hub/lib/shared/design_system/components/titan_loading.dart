import 'package:flutter/material.dart';

import '../foundations/titan_spacing.dart';

class TitanLoading extends StatelessWidget {
  const TitanLoading({
    required this.label,
    this.compact = false,
    super.key,
  });

  final String label;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final indicator = const SizedBox.square(
      dimension: 28,
      child: CircularProgressIndicator(strokeWidth: 3),
    );

    return Semantics(
      label: label,
      container: true,
      child: ExcludeSemantics(
        child: compact
            ? indicator
            : Padding(
                padding: const EdgeInsets.all(TitanSpacing.xl),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    indicator,
                    const SizedBox(height: TitanSpacing.md),
                    Text(label, textAlign: TextAlign.center),
                  ],
                ),
              ),
      ),
    );
  }
}
