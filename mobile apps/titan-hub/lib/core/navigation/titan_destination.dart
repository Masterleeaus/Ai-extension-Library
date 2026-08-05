class TitanDestination {
  const TitanDestination({
    required this.id,
    required this.label,
    required this.route,
    required this.iconName,
    required this.priority,
    required this.sourceRank,
    required this.source,
    this.isPrimary = false,
    this.featureFlag,
  });

  final String id;
  final String label;
  final String route;
  final String iconName;
  final int priority;
  final int sourceRank;
  final String source;
  final bool isPrimary;
  final String? featureFlag;
}
