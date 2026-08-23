import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../core/manifest/business_app_manifest.dart';
import '../../core/navigation/titan_destination.dart';
import '../../routes/routes.dart';
import '../../shared/layout/titan_breakpoints.dart';
import '../account/titan_account_screen.dart';
import '../activity/titan_activity_screen.dart';
import '../explore/titan_explore_screen.dart';
import '../home/titan_home_screen.dart';
import 'titan_hub_shell_controller.dart';

class TitanHubShell extends StatefulWidget {
  const TitanHubShell({
    required this.manifest,
    this.onHubQr,
    super.key,
  });

  final BusinessAppManifest manifest;
  final VoidCallback? onHubQr;

  @override
  State<TitanHubShell> createState() => _TitanHubShellState();
}

class _TitanHubShellState extends State<TitanHubShell> {
  late final TitanHubShellController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TitanHubShellController(manifest: widget.manifest)
      ..addListener(_refresh);
  }

  @override
  void dispose() {
    _controller
      ..removeListener(_refresh)
      ..dispose();
    super.dispose();
  }

  void _refresh() => setState(() {});

  void _select(int index) {
    final destination = _controller.primaryDestinations[index];
    if (destination.id == 'hub-qr') {
      final onHubQr = widget.onHubQr;
      if (onHubQr != null) {
        onHubQr();
      } else {
        Get.toNamed(Routes.qRCodeScreen);
      }
      return;
    }

    _controller.select(index);
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final destinations = _controller.primaryDestinations;
    final content = _buildContent(_controller.selectedDestination.id);

    if (TitanBreakpoints.isCompact(width)) {
      return Scaffold(
        appBar: AppBar(title: Text(widget.manifest.branding.appName)),
        body: content,
        bottomNavigationBar: NavigationBar(
          selectedIndex: _controller.selectedIndex,
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
          onDestinationSelected: _select,
          destinations: [
            for (final destination in destinations)
              NavigationDestination(
                icon: Icon(_iconFor(destination)),
                label: destination.label,
              ),
          ],
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(title: Text(widget.manifest.branding.appName)),
      body: Row(
        children: [
          NavigationRail(
            selectedIndex: _controller.selectedIndex,
            extended: TitanBreakpoints.isExpanded(width),
            labelType: TitanBreakpoints.isExpanded(width)
                ? NavigationRailLabelType.none
                : NavigationRailLabelType.all,
            onDestinationSelected: _select,
            destinations: [
              for (final destination in destinations)
                NavigationRailDestination(
                  icon: Icon(_iconFor(destination)),
                  label: Text(destination.label),
                ),
            ],
          ),
          const VerticalDivider(width: 1),
          Expanded(child: content),
        ],
      ),
    );
  }

  Widget _buildContent(String destinationId) {
    return switch (destinationId) {
      'explore' => TitanExploreScreen(
          destinations: _controller.capabilityDestinations,
        ),
      'activity' => const TitanActivityScreen(),
      'account' => TitanAccountScreen(manifest: widget.manifest),
      _ => TitanHomeScreen(manifest: widget.manifest),
    };
  }

  IconData _iconFor(TitanDestination destination) {
    return switch (destination.iconName) {
      'explore' => Icons.explore,
      'qr_code_scanner' => Icons.qr_code_scanner,
      'history' => Icons.history,
      'person' => Icons.person,
      _ => Icons.home,
    };
  }
}
