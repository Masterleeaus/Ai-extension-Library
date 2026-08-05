abstract final class TitanBreakpoints {
  static const double medium = 600;
  static const double expanded = 1024;

  static bool isCompact(double width) => width < medium;

  static bool isExpanded(double width) => width >= expanded;
}
