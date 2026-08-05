import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/routes/routes.dart';

import '../../../backend/local_storage/local_storage.dart';
import '../../../backend/model/common/common_success_model.dart';
import '../../../backend/model/two_fa/two_fa_info_model.dart';
import '../../../backend/services/api_services.dart';

class TwoFaController extends GetxController {
  RxString numberCode = LocalStorages.getCountryCode()!.obs;

  final numberController = TextEditingController();
  final qrSecretController = TextEditingController();

  RxString qrCode = ''.obs;
  RxString alert = ''.obs;
  RxInt status = 0.obs;

  @override
  void onInit() {
    getBasicData();
    super.onInit();
  }

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late TwoFaInfoModel _twoFaInfoModelData;

  TwoFaInfoModel get twoFaInfoModelData => _twoFaInfoModelData;

  Future<TwoFaInfoModel> getBasicData() async {
    _isLoading.value = true;

    update();

    // calling  from api service
    await ApiServices.getTwoFAInfoAPi().then((value) {
      _twoFaInfoModelData = value!;
      _setData(_twoFaInfoModelData);
      _isLoading.value = false;
      update();
    }).catchError(
      (onError) {
        log.e(onError);
      },
    );
    update();
    return _twoFaInfoModelData;
  }

  void gotoOtp() {
    Get.toNamed(Routes.otp2FaScreen);
  }

  /// >> set loading process & Two Fa Submit Model
  final _isSubmitLoading = false.obs;
  late CommonSuccessModel _twoFaSubmitModel;

  /// >> get loading process & Two Fa Submit Model
  bool get isSubmitLoading => _isSubmitLoading.value;
  CommonSuccessModel get twoFaSubmitModel => _twoFaSubmitModel;

  ///* Two fa submit api process
  Future<CommonSuccessModel> twoFaSubmitApiProcess() async {
    _isSubmitLoading.value = true;
    update();

    var inputBody = <String, String>{
      'status': status.value == 0 ? '1' : '0',
    };

    await ApiServices.twoFaSubmitApiProcess(
      body: inputBody,
    ).then((value) {
      _twoFaSubmitModel = value!;
      Get.offAllNamed(Routes.bottomNavBarScreen);
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isSubmitLoading.value = false;
    update();
    return _twoFaSubmitModel;
  }

  _setData(TwoFaInfoModel twoFaInfoModel) {
    qrSecretController.text = twoFaInfoModel.data.qrSecrete;
    qrCode.value = twoFaInfoModel.data.qrCode;
    alert.value = twoFaInfoModel.data.alert;
    status.value = twoFaInfoModel.data.qrStatus;
  }
}
