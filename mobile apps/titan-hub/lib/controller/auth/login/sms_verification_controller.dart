import 'dart:async';
import 'dart:math';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../routes/routes.dart';

class SMSVerificationController extends GetxController {
  final otpController = TextEditingController();


  bool hasError = false;
  RxString currentText = "".obs;

  changeCurrentText(value) {
    currentText.value = value;
  }

  @override
  void dispose() {
    timer?.cancel();
    otpController.dispose();
    super.dispose();
  }

  @override
  void onInit() {
    // connectivityController.getConnectivity();
    timerInit();
    super.onInit();
  }

  timerInit() {
    timer = Timer.periodic(const Duration(seconds: 1), (_) {

      if ( secondsRemaining.value != 0) {
        secondsRemaining.value--;
      } else {
        enableResend.value = true;
      }

    });
  }



  RxInt secondsRemaining = 59.obs;
  RxInt minuteRemaining = 00.obs;
  RxBool enableResend = false.obs;
  Timer? timer;

  resendCode() {
    secondsRemaining.value = 59;
    enableResend.value = false;
  }

  void onPressedSigninOtpSubmit() {
    Get.toNamed(Routes.resetPasswordScreen);              
  }


  final emailMaskRegExp = RegExp('^(.)(.*?)([^@]?)(?=@[^@]+\$)');

  String maskEmail(String input, [int minFill = 4, String fillChar = '*']) {
    minFill == 4;
    fillChar == '*';
    return input.replaceFirstMapped(emailMaskRegExp, (m) {
      var start = m.group(1);
      var middle = fillChar * max(minFill, m.group(2)!.length);
      var end = m.groupCount >= 3 ? m.group(3) : start;
      return start! + middle + end!;
    });
  }
}
