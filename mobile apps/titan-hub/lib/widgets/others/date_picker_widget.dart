import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';

import '../inputs/primary_input_filed.dart';

class DatePickerInputWidget extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String hint;

  final bool isValidator;

  const DatePickerInputWidget({
    super.key,
    required this.controller,
    required this.label,
    required this.hint,
    this.isValidator = true,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () async {
        DateTime? pickedDate = await showDatePicker(
          context: Get.context!,
          initialDate: DateTime.now(),
          firstDate: DateTime(2000),
          lastDate: DateTime(2100),
        );
        if (pickedDate != null) {
          final DateFormat formatter = DateFormat('dd/MM/yyyy');
          final String formattedDate = formatter.format(pickedDate);
          controller.text = formattedDate;
        }
      },
      child: PrimaryInputWidget(
        controller: controller,
        label: label,
        hint: hint,
        readOnly: true,
        isValidator: isValidator,
      ),
    );
  }
}
