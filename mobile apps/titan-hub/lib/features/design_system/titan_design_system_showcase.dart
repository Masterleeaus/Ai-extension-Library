import 'package:flutter/material.dart';

import '../../shared/design_system/components/titan_action_card.dart';
import '../../shared/design_system/components/titan_action_sheet.dart';
import '../../shared/design_system/components/titan_badge.dart';
import '../../shared/design_system/components/titan_button.dart';
import '../../shared/design_system/components/titan_card.dart';
import '../../shared/design_system/components/titan_empty_state.dart';
import '../../shared/design_system/components/titan_error_state.dart';
import '../../shared/design_system/components/titan_list_tile.dart';
import '../../shared/design_system/components/titan_loading.dart';
import '../../shared/design_system/components/titan_section_header.dart';
import '../../shared/design_system/components/titan_status_banner.dart';
import '../../shared/design_system/components/titan_text_field.dart';
import '../../shared/design_system/foundations/titan_colors.dart';
import '../../shared/design_system/foundations/titan_radius.dart';
import '../../shared/design_system/foundations/titan_spacing.dart';

/// Internal component catalogue; it is deliberately absent from customer nav.
class TitanDesignSystemShowcase extends StatefulWidget {
  const TitanDesignSystemShowcase({super.key});

  @override
  State<TitanDesignSystemShowcase> createState() =>
      _TitanDesignSystemShowcaseState();
}

class _TitanDesignSystemShowcaseState extends State<TitanDesignSystemShowcase> {
  final _emailController = TextEditingController(text: 'team@titanzero.io');
  final _passwordController = TextEditingController(text: 'showcase-only');
  final _notesController = TextEditingController();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final sections = <Widget>[
      const _ShowcaseSection(
        title: 'Foundations',
        child: Wrap(
          spacing: TitanSpacing.sm,
          runSpacing: TitanSpacing.sm,
          children: const [
            _ColourSwatch(label: 'Primary', color: TitanColors.primary),
            _ColourSwatch(label: 'Success', color: TitanColors.success),
            _ColourSwatch(label: 'Warning', color: TitanColors.warning),
            _ColourSwatch(label: 'Danger', color: TitanColors.danger),
            _ColourSwatch(label: 'Info', color: TitanColors.info),
          ],
        ),
      ),
      _ShowcaseSection(
        title: 'Actions and badges',
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Wrap(
              spacing: TitanSpacing.sm,
              runSpacing: TitanSpacing.sm,
              children: [
                TitanBadge(label: 'Primary', tone: TitanTone.primary),
                TitanBadge(label: 'Ready', tone: TitanTone.success),
                TitanBadge(label: 'Review', tone: TitanTone.warning),
                TitanBadge(label: 'Blocked', tone: TitanTone.danger),
              ],
            ),
            const SizedBox(height: TitanSpacing.lg),
            Wrap(
              spacing: TitanSpacing.sm,
              runSpacing: TitanSpacing.sm,
              children: [
                TitanButton(label: 'Continue', onPressed: () {}),
                TitanButton(
                  label: 'Secondary',
                  variant: TitanButtonVariant.outlined,
                  onPressed: () {},
                ),
                const TitanButton(
                  label: 'Saving',
                  isLoading: true,
                  onPressed: null,
                ),
              ],
            ),
          ],
        ),
      ),
      _ShowcaseSection(
        title: 'Cards and lists',
        child: Column(
          children: [
            TitanActionCard(
              title: 'Schedule a service',
              description:
                  'Choose availability, assign resources and confirm reminders.',
              icon: Icons.calendar_month_outlined,
              badge: const TitanBadge(label: 'Recommended'),
              onTap: () {},
            ),
            const SizedBox(height: TitanSpacing.md),
            const TitanCard(
              header: Text('Invoice ready'),
              footer: Text('Due Friday'),
              child: Text(r'$240.00'),
            ),
            const SizedBox(height: TitanSpacing.md),
            TitanListTile(
              title: 'Property inspection',
              subtitle: '12 Collins Street',
              leading: const Icon(Icons.home_outlined),
              metadata: '9:30 AM',
              badge: const TitanBadge(label: '3 tasks'),
              onTap: () {},
            ),
          ],
        ),
      ),
      _ShowcaseSection(
        title: 'Forms',
        child: Column(
          children: [
            TitanTextField(
              controller: _emailController,
              label: 'Email',
              helperText: 'Used for receipts',
              showClear: true,
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: TitanSpacing.md),
            TitanTextField(
              controller: _passwordController,
              label: 'Password',
              obscureText: true,
            ),
            const SizedBox(height: TitanSpacing.md),
            TitanTextField(
              controller: _notesController,
              label: 'Access instructions',
              variant: TitanTextFieldVariant.basic,
              maxLines: 3,
              prefix: const Icon(Icons.key_outlined),
            ),
          ],
        ),
      ),
      const _ShowcaseSection(
        title: 'Feedback states',
        child: Column(
          children: [
            TitanStatusBanner.offline(),
            SizedBox(height: TitanSpacing.md),
            TitanStatusBanner(
              tone: TitanStatusTone.success,
              title: 'Booking confirmed',
              message: 'The customer and assigned worker were notified.',
            ),
            SizedBox(height: TitanSpacing.md),
            TitanEmptyState(
              title: 'No upcoming bookings',
              message: 'Create a booking or share your booking link.',
              icon: Icons.event_available_outlined,
            ),
            TitanErrorState(
              title: 'Could not load invoices',
              message: 'Check your connection and try again.',
            ),
            TitanLoading(label: 'Loading customer activity'),
          ],
        ),
      ),
      _ShowcaseSection(
        title: 'Native overlays',
        child: TitanButton(
          label: 'Show action sheet',
          icon: Icons.more_horiz,
          onPressed: () => _showActions(context),
        ),
      ),
    ];

    return Scaffold(
      appBar: AppBar(title: const Text('Titan Hub Design System')),
      body: LayoutBuilder(
        builder: (context, constraints) {
          final wide = constraints.maxWidth >= 900;
          final content = wide
              ? Wrap(
                  key: const ValueKey('showcase-wide-layout'),
                  spacing: TitanSpacing.lg,
                  runSpacing: TitanSpacing.lg,
                  children: [
                    for (final section in sections)
                      SizedBox(
                        width: (constraints.maxWidth -
                                (TitanSpacing.xl * 2) -
                                TitanSpacing.lg) /
                            2,
                        child: section,
                      ),
                  ],
                )
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    for (var index = 0; index < sections.length; index++) ...[
                      sections[index],
                      if (index != sections.length - 1)
                        const SizedBox(height: TitanSpacing.lg),
                    ],
                  ],
                );

          return SingleChildScrollView(
            padding: const EdgeInsets.all(TitanSpacing.xl),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 1200),
                child: content,
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _showActions(BuildContext context) async {
    await showTitanActionSheet<String>(
      context,
      title: 'Showcase actions',
      message: 'These rows are native Flutter controls.',
      actions: const [
        TitanActionSheetAction(
          value: 'open',
          label: 'Open sample',
          icon: Icons.open_in_new,
          tone: TitanActionSheetTone.primary,
        ),
        TitanActionSheetAction(
          value: 'archive',
          label: 'Archive sample',
          icon: Icons.archive_outlined,
        ),
        TitanActionSheetAction(
          value: 'delete',
          label: 'Delete sample',
          icon: Icons.delete_outline,
          tone: TitanActionSheetTone.destructive,
        ),
      ],
    );
  }
}

class _ShowcaseSection extends StatelessWidget {
  const _ShowcaseSection({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return TitanCard(
      variant: TitanCardVariant.tonal,
      header: TitanSectionHeader(title: title),
      child: child,
    );
  }
}

class _ColourSwatch extends StatelessWidget {
  const _ColourSwatch({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: '$label colour token',
      child: Container(
        width: 96,
        padding: const EdgeInsets.all(TitanSpacing.sm),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(TitanRadius.standard),
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.labelMedium?.copyWith(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
        ),
      ),
    );
  }
}
