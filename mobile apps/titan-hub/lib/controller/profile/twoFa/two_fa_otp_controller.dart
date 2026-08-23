import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/services/api_services.dart';

class TwoFaOtpController extends GetxController {
  final emailOtpInputController = TextEditingController();

  bool hasError = false;
  RxString currentText = "".obs;

  @override
  void dispose() {
    emailOtpInputController.dispose();
    super.dispose();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late CommonSuccessModel _updateTwoFAModelData;
  CommonSuccessModel get updateTwoFAModelData => _updateTwoFAModelData;

  // --------------------------- Api function ----------------------------------
  // Profile update process without image
  Future<CommonSuccessModel> twoFAEnabledProcess() async {
    _isLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'otp': emailOtpInputController.text,
    };

    await ApiServices.twoFAVerifyAPi(body: inputBody).then((value) {
      _updateTwoFAModelData = value!;

      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
      _isLoading.value = false;
      update();
    });

    return _updateTwoFAModelData;
  }
}
