import 'package:flutter/material.dart';

import '../../core/navigation/titan_destination.dart';

class TitanExploreScreen extends StatelessWidget {
  const TitanExploreScreen({required this.destinations, super.key});

  final List<TitanDestination> destinations;

  @override
  Widget build(BuildContext context) {
    if (destinations.isEmpty) {
      return const Center(child: Text('No additional capabilities enabled'));
    }

    return GridView.builder(
      padding: const EdgeInsets.all(24),
      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
        maxCrossAxisExtent: 280,
        mainAxisExtent: 112,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
      ),
      itemCount: destinations.length,
      itemBuilder: (context, index) {
        final destination = destinations[index];
        return Card(
          child: ListTile(
            title: Text(destination.label),
            subtitle: Text(destination.source),
            trailing: const Icon(Icons.chevron_right),
          ),
        );
      },
    );
  }
}
