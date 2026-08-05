import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';

import '../shared/design_system/foundations/titan_theme.dart';

class Themes {
  final _box = GetStorage();
  final _key = 'isDarkMode';

  Future<void> _saveThemeToBox(bool isDarkMode) {
    return _box.write(_key, isDarkMode);
  }

  bool _loadThemeFromBox() => _box.read(_key) ?? false;

  ThemeMode get theme => _loadThemeFromBox() ? ThemeMode.dark : ThemeMode.light;

  Future<void> switchTheme() async {
    final enableDark = !_loadThemeFromBox();
    Get.changeThemeMode(enableDark ? ThemeMode.dark : ThemeMode.light);
    await _saveThemeToBox(enableDark);
  }

  static ThemeData light = TitanTheme.light();
  static ThemeData dark = TitanTheme.dark();

  static void init({required ColorMode primary}) {
    light = TitanTheme.light(seedColor: primary.light);
    dark = TitanTheme.dark(seedColor: primary.dark);
  }
}

class ColorMode {
  const ColorMode({required this.light, required this.dark});

  final Color light;
  final Color dark;
}
