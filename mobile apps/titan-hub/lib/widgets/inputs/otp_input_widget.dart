import 'package:flutter/material.dart';
import 'package:pin_code_fields/pin_code_fields.dart';

import '../../utils/custom_color.dart';
import '../../utils/dimensions.dart';

class OtpInputTextFieldWidget extends StatelessWidget {
  const OtpInputTextFieldWidget({super.key, required this.controller});

  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return PinCodeTextField(
      controller: controller,
      appContext: context,
      cursorColor: CustomColor.primaryLightColor,
      length: 6,
      obscureText: false,
      keyboardType: TextInputType.number,
      textStyle: TextStyle(color: CustomColor.primaryLightColor),
      animationType: AnimationType.fade,
      pinTheme: PinTheme(
        shape: PinCodeFieldShape.box,
        borderRadius: BorderRadius.circular(
          Dimensions.radius * 0.5,
        ),
        selectedColor: CustomColor.primaryTextColor,
        activeColor: CustomColor.primaryLightColor,
        inactiveColor: CustomColor.primaryTextColor,
        fieldHeight: 50,
        fieldWidth: 48,
        activeFillColor: CustomColor.transparent,
        borderWidth: 2,
      ),
      onChanged: (String value) {},
    );
  }
}
