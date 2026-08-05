import 'package:flutter/material.dart';

import '../../core/manifest/business_app_manifest.dart';

class TitanHomeScreen extends StatelessWidget {
  const TitanHomeScreen({required this.manifest, super.key});

  final BusinessAppManifest manifest;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        Text(
          'Your business, in one place',
          style: Theme.of(context).textTheme.headlineMedium,
        ),
        const SizedBox(height: 12),
        Text(
          'Primary profile: ${manifest.primaryVertical}',
          style: Theme.of(context).textTheme.bodyLarge,
        ),
        const SizedBox(height: 8),
        Text(
          'Active modes: ${manifest.appModes.join(', ')}',
          style: Theme.of(context).textTheme.bodyMedium,
        ),
      ],
    );
  }
}
