import 'package:flutter/widgets.dart';

abstract final class TitanMotion {
  static const Duration fast = Duration(milliseconds: 120);
  static const Duration standard = Duration(milliseconds: 220);
  static const Duration deliberate = Duration(milliseconds: 360);

  static Duration resolve(BuildContext context, Duration duration) {
    return MediaQuery.maybeOf(context)?.disableAnimations == true
        ? Duration.zero
        : duration;
  }
}
