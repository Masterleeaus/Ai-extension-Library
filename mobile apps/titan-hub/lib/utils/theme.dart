import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';
import 'package:google_fonts/google_fonts.dart';

import 'custom_color.dart';

class Themes {
  final _box = GetStorage();
  final _key = 'isDarkMode';

  _saveThemeToBox(bool isDarkMode) => _box.write(_key, isDarkMode);

  bool _loadThemeFromBox() => _box.read(_key) ?? false;

  ThemeMode get theme => _loadThemeFromBox() ? ThemeMode.dark : ThemeMode.light;

  void switchTheme() {
    Get.changeThemeMode(_loadThemeFromBox() ? ThemeMode.light : ThemeMode.dark);
    _saveThemeToBox(!_loadThemeFromBox());
  }

  static ThemeData light = ThemeData.light().copyWith(
    primaryColor: CustomColor.primaryLightColor,
    scaffoldBackgroundColor: CustomColor.primaryLightScaffoldBackgroundColor,
    brightness: Brightness.light,
    textTheme: ThemeData.dark().textTheme.apply(
        fontFamily: GoogleFonts.josefinSans().fontFamily,
        bodyColor: Colors.black),
  );
  static ThemeData dark = ThemeData.dark().copyWith(
    primaryColor: CustomColor.primaryDarkColor,
    scaffoldBackgroundColor: CustomColor.primaryDarkScaffoldBackgroundColor,
    brightness: Brightness.dark,
    textTheme: ThemeData.dark().textTheme.apply(
        fontFamily: GoogleFonts.josefinSans().fontFamily,
        bodyColor: Colors.black),
  );

  static void init({required ColorMode primary, }) {
    dark = ThemeData.dark().copyWith(
      primaryColor: primary.dark,
    );
    light = ThemeData.light().copyWith(
      primaryColor: primary.light,
    );
  }
}

class ColorMode {
  final Color light, dark;
  ColorMode({required this.light, required this.dark});
}
