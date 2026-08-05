import 'package:flutter/material.dart';

import '../../core/manifest/manifest_repository.dart';
import '../../features/shell/titan_hub_shell.dart';

class BottomNavBarScreen extends StatelessWidget {
  const BottomNavBarScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return TitanHubShell(manifest: ManifestRepository.fallbackManifest);
  }
}
