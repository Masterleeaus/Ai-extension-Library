import 'package:flutter/material.dart';

import '../foundations/titan_colors.dart';
import '../foundations/titan_radius.dart';
import '../foundations/titan_spacing.dart';
import 'titan_list_tile.dart';

enum TitanActionSheetTone { neutral, primary, destructive }

class TitanActionSheetAction<T> {
  const TitanActionSheetAction({
    required this.value,
    required this.label,
    this.icon,
    this.tone = TitanActionSheetTone.neutral,
    this.enabled = true,
  });

  final T value;
  final String label;
  final IconData? icon;
  final TitanActionSheetTone tone;
  final bool enabled;
}

Future<T?> showTitanActionSheet<T>(
  BuildContext context, {
  required String title,
  String? message,
  required List<TitanActionSheetAction<T>> actions,
}) {
  return showModalBottomSheet<T>(
    context: context,
    showDragHandle: true,
    useSafeArea: true,
    isScrollControlled: true,
    builder: (sheetContext) {
      final theme = Theme.of(sheetContext);
      final maxHeight = MediaQuery.sizeOf(sheetContext).height * 0.8;

      return Align(
        alignment: Alignment.bottomCenter,
        child: ConstrainedBox(
          constraints: BoxConstraints(maxWidth: 640, maxHeight: maxHeight),
          child: Material(
            color: theme.colorScheme.surface,
            borderRadius: const BorderRadius.vertical(
              top: Radius.circular(TitanRadius.prominent),
            ),
            clipBehavior: Clip.antiAlias,
            child: SingleChildScrollView(
              padding: const EdgeInsets.only(bottom: TitanSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(
                      TitanSpacing.lg,
                      TitanSpacing.sm,
                      TitanSpacing.lg,
                      TitanSpacing.lg,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(title, style: theme.textTheme.titleLarge),
                        if (message != null) ...[
                          const SizedBox(height: TitanSpacing.sm),
                          Text(
                            message,
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  for (final action in actions)
                    TitanListTile(
                      title: action.label,
                      leading: action.icon == null
                          ? null
                          : Icon(
                              action.icon,
                              color: _toneColor(theme, action.tone),
                            ),
                      trailing: const SizedBox.shrink(),
                      enabled: action.enabled,
                      isDestructive:
                          action.tone == TitanActionSheetTone.destructive,
                      onTap: action.enabled
                          ? () => Navigator.pop<T>(sheetContext, action.value)
                          : null,
                    ),
                ],
              ),
            ),
          ),
        ),
      );
    },
  );
}

Color _toneColor(ThemeData theme, TitanActionSheetTone tone) {
  return switch (tone) {
    TitanActionSheetTone.neutral => theme.colorScheme.onSurfaceVariant,
    TitanActionSheetTone.primary => theme.colorScheme.primary,
    TitanActionSheetTone.destructive =>
      theme.extension<TitanSemanticColors>()!.danger,
  };
}
