import 'package:flutter/services.dart';

class DecimalTextInputFormatter extends TextInputFormatter {
  final int decimalRange;

  DecimalTextInputFormatter({this.decimalRange = 2});

  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    if (newValue.text == '') {
      return newValue;
    }

    final TextEditingValue newText = newValue.copyWith(
      text: newValue.text.replaceAll(RegExp(r'[^0-9.]'), ''),
    );

    if (newText.text.contains('.') &&
        newText.text.indexOf('.') != newText.text.lastIndexOf('.')) {
      return oldValue;
    }

    if (newText.text.contains('.')) {
      final List<String> parts = newText.text.split('.');
      if (parts.length > 1 && parts[1].length > decimalRange) {
        return oldValue;
      }
    }

    return newText;
  }
}