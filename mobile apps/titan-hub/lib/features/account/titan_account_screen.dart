import 'package:flutter/material.dart';

import '../../core/manifest/business_app_manifest.dart';

class TitanAccountScreen extends StatelessWidget {
  const TitanAccountScreen({required this.manifest, super.key});

  final BusinessAppManifest manifest;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        ListTile(
          leading: const Icon(Icons.business),
          title: const Text('Business profile'),
          subtitle: Text(manifest.businessProfileId),
        ),
        const ListTile(
          leading: Icon(Icons.security),
          title: Text('Security and privacy'),
        ),
        const ListTile(
          leading: Icon(Icons.settings),
          title: Text('Settings'),
        ),
      ],
    );
  }
}
