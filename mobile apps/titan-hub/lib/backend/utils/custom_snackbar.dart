import 'package:qrpay/utils/custom_color.dart';
import 'package:qrpay/utils/dimensions.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

class CustomSnackBar {
  static success(String message) {
    return Get.snackbar(
      'Success',
      message,
      icon: Icon(
        Icons.check_circle,
        color: CustomColor.greenColor,
        size: Dimensions.heightSize * 2.6,
      ),
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: Colors.black,
      colorText: Colors.white,
      duration: const Duration(seconds: 2),
      dismissDirection: DismissDirection.horizontal,
      forwardAnimationCurve: Curves.easeOutBack,
      margin: EdgeInsets.all(Dimensions.marginSizeVertical * 0.5),
    );
  }

  static error(String message) {
    return Get.snackbar(
      'Alert',
      message,
      icon: Icon(
        Icons.error,
        color: CustomColor.redColor,
        size: Dimensions.heightSize * 2.6,
      ),
      margin: EdgeInsets.all(Dimensions.marginSizeVertical * 0.5),
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: Colors.black,
      colorText: Colors.white,
      duration: const Duration(seconds: 2),
      dismissDirection: DismissDirection.horizontal,
      forwardAnimationCurve: Curves.easeOutBack,
    );
  }
}
