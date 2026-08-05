import 'dart:async';

import 'package:qrpay/routes/routes.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'kyc_form_controller.dart';

class EmailOtpController extends GetxController {
  final basicDataController = Get.put(BasicDataController());
  final emailOtpInputController = TextEditingController();

  bool hasError = false;
  RxString currentText = "".obs;

  changeCurrentText(value) {
    currentText.value = value;
  }

  @override
  void dispose() {
    timer?.cancel();
    emailOtpInputController.dispose();
    super.dispose();
  }

  @override
  void onInit() {
    // connectivityController.getConnectivity();

    timerInit();
    super.onInit();
  }

  final _isVerifyCode = false.obs;

  bool get isVerifyCode => _isVerifyCode.value;

  // void verifyOTP({
  //   required BuildContext context,
  //   required String verificationId,
  //   required String userOTP,
  // }) async {
  //   _isVerifyCode.value = true;
  //   try {
  //     PhoneAuthCredential credential = PhoneAuthProvider.credential(
  //       verificationId: verificationId,
  //       smsCode: userOTP,
  //     );
  //     await FirebaseAuth.instance.signInWithCredential(credential);
  //     basicDataController.getBasicData();
  //     Get.toNamed(Routes.kycFromScreen);
  //     _isVerifyCode.value = false;
  //   } on FirebaseAuthException {
  //     _isVerifyCode.value = false;
  //     CustomSnackBar.error(Strings.theCodeisNotValid.tr);
  //   }
  // }

//timer widget
  timerInit() {
    timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (secondsRemaining.value != 0) {
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
    // onInit();
  }

  void onPressedSigninOtpSubmit() {
    Get.toNamed(Routes.kycFromScreen);
  }
}
